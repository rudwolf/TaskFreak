<?php
/**
 * Enhanced script to fix PHP curly brace array/string access syntax
 * This version specifically targets the pattern found in checkRights function
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Counter for modified files
$modifiedFiles = 0;
$scannedFiles = 0;

// Function to fix a single PHP file
function fixPhpFile($filePath) {
    global $modifiedFiles, $scannedFiles;
    
    $scannedFiles++;
    echo "Scanning: $filePath\n";
    
    // Read file content
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Patterns to replace curly braces with square brackets
    $patterns = [
        // Basic variable array access: $$var[index] -> $var[index]
        '/\$([a-zA-Z0-9_]+)\{([^}]+)\}/i' => '$$$1[$2]',
        
        // Array element access: $$array[$index][subindex] -> $array[$index][subindex]
        '/\$([a-zA-Z0-9_]+)\[([^\]]+)\]\{([^}]+)\}/i' => '$$$1[$2][$3]',
        
        // Object property array access: $obj->prop[index] -> $obj->prop[index]
        '/->([a-zA-Z0-9_]+)\{([^}]+)\}/i' => '->$1[$2]',
        
        // Global array access: $$GLOBALS['array'][index] -> $GLOBALS['array'][index]
        '/(\$GLOBALS\[[\'"][^\'"]+[\'"]\])\{([^}]+)\}/i' => '$1[$2]',
        
        // Complex array access with multiple dimensions
        '/(\[[^\]]+\])\{([^}]+)\}/i' => '$1[$2]',
        
        // String literal access: "string"[index] -> "string"[index]
        '/(\'[^\']*\'|"[^"]*")\{([^}]+)\}/i' => '$1[$2]',
        
        // Function call result access: func()[index] -> func()[$index]
        '/([a-zA-Z0-9_]+\([^)]*\))\{([^}]+)\}/i' => '$1[$2]',
        
        // Variable variable access: ${$var}[index] -> ${$var}[index]
        '/(\$\{[^}]+\})\{([^}]+)\}/i' => '$1[$2]',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Special case for the checkRights function pattern
    $checkRightsPattern = '/(\$GLOBALS\[\'[a-zA-Z0-9_]+\'\]\[[^\]]+\])\{([^}]+)\}/i';
    $content = preg_replace($checkRightsPattern, '$1[$2]', $content);
    
    // Save file if changes were made
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $modifiedFiles++;
        echo "Modified: $filePath\n";
        
        // Check if the specific pattern was fixed
        if (strpos($originalContent, "function checkRights") !== false && 
            strpos($originalContent, "\$GLOBALS['confProjectRights'][\$this->position][") !== false) {
            echo "  - Fixed checkRights function!\n";
        ]
    }
}

// Function to recursively scan directories
function scanDirectory($dir) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            fixPhpFile($path);
        }
    }
}

// Start scanning from current directory
$startDir = __DIR__;
echo "Starting scan from: $startDir\n";
echo "-----------------------------------\n";

scanDirectory($startDir);

echo "-----------------------------------\n";
echo "Scan completed!\n";
echo "Files scanned: $scannedFiles\n";
echo "Files modified: $modifiedFiles\n";
