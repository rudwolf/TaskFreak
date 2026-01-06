<?php
/**
 * Database Connection Test Script
 * Place in /var/www/freak/public/ and access via browser
 * DELETE after testing for security!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TaskFreak Database Connection Test</h1>";

// Test 1: Check if config.php exists and can be loaded
echo "<h2>1. Config File Check</h2>";
$configPath = __DIR__ . '/include/config.php';
echo "<p>Looking for config at: <strong>" . htmlspecialchars($configPath) . "</strong></p>";

if (!file_exists($configPath)) {
    die("<p style='color:red;'>ERROR: config.php not found!</p>");
}

echo "<p style='color:green;'>✓ config.php found</p>";

// Load config
require_once $configPath;

// Test 2: Display loaded constants
echo "<h2>2. Database Configuration Constants</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Constant</th><th>Value</th><th>Status</th></tr>";

$constants = [
    'TZN_DB_HOST' => defined('TZN_DB_HOST') ? TZN_DB_HOST : null,
    'TZN_DB_USER' => defined('TZN_DB_USER') ? TZN_DB_USER : null,
    'TZN_DB_PASS' => defined('TZN_DB_PASS') ? '***HIDDEN***' : null,
    'TZN_DB_BASE' => defined('TZN_DB_BASE') ? TZN_DB_BASE : null,
    'TZN_DB_PREFIX' => defined('TZN_DB_PREFIX') ? TZN_DB_PREFIX : null,
    'TZN_DB_PERMANENT' => defined('TZN_DB_PERMANENT') ? TZN_DB_PERMANENT : null,
];

foreach ($constants as $name => $value) {
    $status = is_null($value) ? "<span style='color:red;'>NOT DEFINED</span>" : "<span style='color:green;'>✓</span>";
    $displayValue = is_null($value) ? 'NULL' : htmlspecialchars($value);
    echo "<tr><td><strong>$name</strong></td><td>$displayValue</td><td>$status</td></tr>";
}
echo "</table>";

// Test 3: Direct mysqli connection test
echo "<h2>3. Direct MySQLi Connection Test</h2>";

$host = defined('TZN_DB_HOST') ? TZN_DB_HOST : null;
$user = defined('TZN_DB_USER') ? TZN_DB_USER : null;
$pass = defined('TZN_DB_PASS') ? TZN_DB_PASS : null;
$base = defined('TZN_DB_BASE') ? TZN_DB_BASE : null;

if (!$host || !$user || !$base) {
    echo "<p style='color:red;'>ERROR: Missing required database constants!</p>";
} else {
    echo "<p>Attempting connection with:</p>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . htmlspecialchars($host) . "</li>";
    echo "<li><strong>User:</strong> " . htmlspecialchars($user) . "</li>";
    echo "<li><strong>Database:</strong> " . htmlspecialchars($base) . "</li>";
    echo "</ul>";

    // Try connection
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $link = new mysqli($host, $user, $pass, $base);
        echo "<p style='color:green; font-weight:bold;'>✓ CONNECTION SUCCESSFUL!</p>";
        echo "<p>Server Info: " . htmlspecialchars($link->server_info) . "</p>";
        echo "<p>Server Version: " . $link->server_version . "</p>";
        echo "<p>Host Info: " . htmlspecialchars($link->host_info) . "</p>";

        // Test query
        $result = $link->query("SHOW TABLES");
        if ($result) {
            echo "<p>Tables in database: " . $result->num_rows . "</p>";
            $result->free();
        }

        $link->close();

    } catch (mysqli_sql_exception $e) {
        echo "<p style='color:red; font-weight:bold;'>✗ CONNECTION FAILED!</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";

        echo "<h3>Common Fixes:</h3>";
        echo "<ul>";
        echo "<li><strong>Host '$host':</strong> If this is a Docker container name, ensure it's reachable from PHP container</li>";
        echo "<li><strong>Try '127.0.0.1' or 'localhost':</strong> Change TZN_DB_HOST in config.php</li>";
        echo "<li><strong>Check MariaDB is running:</strong> Run: <code>docker ps | grep mariadb</code></li>";
        echo "<li><strong>Test from workspace:</strong> Run: <code>mysql -h$host -u$user -p$pass $base</code></li>";
        echo "<li><strong>Grant privileges:</strong> <code>GRANT ALL ON $base.* TO '$user'@'%' IDENTIFIED BY '$pass';</code></li>";
        echo "</ul>";
    }
}

// Test 4: Test using TaskFreak's TznDbConnection class
echo "<h2>4. TznDbConnection Class Test</h2>";

$classPath = __DIR__ . '/include/classes/tzn_mysql.php';
if (!file_exists($classPath)) {
    echo "<p style='color:red;'>ERROR: tzn_mysql.php not found at: " . htmlspecialchars($classPath) . "</p>";
} else {
    require_once __DIR__ . '/include/classes/tzn_generic.php';
    require_once $classPath;

    try {
        echo "<p>Creating TznDbConnection with explicit parameters...</p>";
        $objDb = new TznDbConnection($host, $user, $pass, $base);

        echo "<p>Connection object properties:</p>";
        echo "<ul>";
        echo "<li>_dbHost: " . htmlspecialchars($objDb->_dbHost ?? 'NULL') . "</li>";
        echo "<li>_dbUser: " . htmlspecialchars($objDb->_dbUser ?? 'NULL') . "</li>";
        echo "<li>_dbBase: " . htmlspecialchars($objDb->_dbBase ?? 'NULL') . "</li>";
        echo "</ul>";

        $connected = $objDb->connect();

        if ($connected) {
            echo "<p style='color:green; font-weight:bold;'>✓ TznDbConnection SUCCESSFUL!</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>✗ TznDbConnection FAILED!</p>";
            if (isset($objDb->_error['db'])) {
                echo "<p>Error: " . htmlspecialchars($objDb->_error['db']) . "</p>";
            }
        }

    } catch (Exception $e) {
        echo "<p style='color:red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
    }
}

// Test 5: Network connectivity test
echo "<h2>5. Network Connectivity Test</h2>";
echo "<p>Testing if host '$host' is reachable...</p>";

if ($host === 'localhost' || $host === '127.0.0.1') {
    echo "<p>Host is localhost - checking local MySQL socket...</p>";
    $socketPaths = ['/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock', '/var/lib/mysql/mysql.sock'];
    foreach ($socketPaths as $socket) {
        if (file_exists($socket)) {
            echo "<p style='color:green;'>✓ Found socket: $socket</p>";
        }
    }
} else {
    // Try to resolve hostname
    $ip = gethostbyname($host);
    if ($ip === $host) {
        echo "<p style='color:orange;'>⚠ Warning: Could not resolve hostname '$host'</p>";
        echo "<p>This might be a Docker container name. Ensure PHP container can reach it.</p>";
    } else {
        echo "<p style='color:green;'>✓ Hostname resolved to: $ip</p>";
    }

    // Try to connect to port 3306
    $fp = @fsockopen($host, 3306, $errno, $errstr, 5);
    if ($fp) {
        echo "<p style='color:green;'>✓ Port 3306 is open on $host</p>";
        fclose($fp);
    } else {
        echo "<p style='color:red;'>✗ Cannot connect to port 3306 on $host</p>";
        echo "<p>Error: $errstr ($errno)</p>";
    }
}

echo "<hr>";
echo "<p><strong>⚠ SECURITY WARNING:</strong> Delete this file after testing!</p>";
?>
