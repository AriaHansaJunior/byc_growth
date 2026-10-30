<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h2>1. PHP is working: PHP " . PHP_VERSION . "</h2>";

$baseDir = dirname(__DIR__);
echo "<p>Base Dir: " . htmlspecialchars($baseDir) . "</p>";

// Check bootstrap
$bootstrap = $baseDir . '/bootstrap/app.php';
if (!file_exists($bootstrap)) {
    echo "<p style='color:red;'>FATAL: bootstrap/app.php NOT FOUND at " . htmlspecialchars($bootstrap) . "</p>";
} else {
    echo "<p style='color:green;'>FOUND: bootstrap/app.php</p>";
}

// Check vendor
$autoload = $baseDir . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    echo "<p style='color:red;'>FATAL: vendor/autoload.php NOT FOUND at " . htmlspecialchars($autoload) . "</p>";
} else {
    echo "<p style='color:green;'>FOUND: vendor/autoload.php</p>";
}

// Check storage writability
$storage = $baseDir . '/storage';
if (!is_writable($storage)) {
    echo "<p style='color:red;'>PERMISSION ERROR: storage is NOT writable (" . substr(sprintf('%o', fileperms($storage)), -4) . ")</p>";
} else {
    echo "<p style='color:green;'>WRITABLE: storage (" . substr(sprintf('%o', fileperms($storage)), -4) . ")</p>";
}

// Try bootstrap Laravel
try {
    require $autoload;
    $app = require_once $bootstrap;
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "<p style='color:green;'>SUCCESS: Laravel Framework bootstrapped properly!</p>";
} catch (Throwable $e) {
    echo "<div style='background:#fee; border:1px solid red; padding:10px;'>";
    echo "<h3>Laravel Bootstrap Exception:</h3>";
    echo "<b>" . get_class($e) . "</b>: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
