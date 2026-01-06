<?php
/**
 * Targeted script to fix common method signature issues in TaskFreak
 * This script focuses on specific known issues rather than trying to be generic
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$startDir = __DIR__;
$modifiedFiles = 0;
$scannedFiles = 0;
$fixedMethods = [];

echo "Starting targeted method signature fixer\n";
echo "---------------------------------------\n";

// Known problematic methods and their correct signatures
$knownIssues = [
    // TznUser class issues
    ['class' => 'TznUser', 'method' => 'add', 'signature' => 'function add($ignore = false)'],
    ['class' => 'TznUser', 'method' => 'update', 'signature' => 'function update($fields = null, $filter = null)'],
    
    // Project class issues
    ['class' => 'Project', 'method' => 'add', 'signature' => 'function add($ignore = false, $status = null, $userId = null)'],
    
    // ProjectStats class issues
    ['class' => 'ProjectStats', 'method' => 'load', 'signature' => 'function load($userId = null, $strict = true)'],
    
    // Add more known issues here as you encounter them
];

// Scan all PHP files
scanDirectory($startDir);

// Report results
echo "\n---------------------------------------\n";
echo "Scan completed!\n";
echo "Files scanned: $scannedFiles\n";
echo "Files modified: $modifiedFiles\n";

if (count($fixedMethods) > 0) {
    echo "\nFixed methods:\n";
    foreach ($fixedMethods as $file => $methods) {
        echo "- $file\n";
        foreach ($methods as $method) {
            echo "  - $method\n";
        }
    }
}

// Function to recursively scan directories
function scanDirectory($dir) {
    global $scannedFiles;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'fixsig_targeted.php') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $scannedFiles++;
            fixPhpFile($path);
        }
    }
}

// Function to fix a PHP file
function fixPhpFile($filePath) {
    global $knownIssues, $modifiedFiles, $fixedMethods;
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $modified = false;
    
    foreach ($knownIssues as $issue) {
        $className = $issue['class'];
        $methodName = $issue['method'];
        $newSignature = $issue['signature'];
        
        // Look for class definition
        $classPattern = '/class\s+' . preg_quote($className, '/') . '\s+(?:extends\s+\w+\s+)?{/';
        if (preg_match($classPattern, $content)) {
            // Found the class, now look for the method
            $methodPattern = '/function\s+' . preg_quote($methodName, '/') . '\s*\([^)]*\)/';
            if (preg_match($methodPattern, $content, $matches)) {
                $oldSignature = $matches[0];
                
                // Skip if signatures are identical
                if ($oldSignature === $newSignature) {
                    continue;
                }
                
                // Replace the method signature
                $content = str_replace($oldSignature, $newSignature, $content);
                $modified = true;
                
                if (!isset($fixedMethods[$filePath])) {
                    $fixedMethods[$filePath] = [];
                }
                $fixedMethods[$filePath][] = "$className::$methodName";
                
                echo "Fixed method signature: $className::$methodName in $filePath\n";
            }
        }
    }
    
    if ($modified) {
        file_put_contents($filePath, $content);
        $modifiedFiles++;
    }
}
