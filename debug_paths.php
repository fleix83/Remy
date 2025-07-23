<?php
require_once 'config/config.php';

echo "<h2>Debug Path Information</h2>";
echo "<p><strong>BASE_URL:</strong> " . BASE_URL . "</p>";
echo "<p><strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";

// Test if kantone directory exists
$kantonePath = __DIR__ . '/assets/kantone/';
echo "<p><strong>Assets kantone path:</strong> " . $kantonePath . "</p>";
echo "<p><strong>Assets directory exists:</strong> " . (is_dir($kantonePath) ? 'YES' : 'NO') . "</p>";

if (is_dir($kantonePath)) {
    $files = scandir($kantonePath);
    echo "<p><strong>Files in kantone directory:</strong> " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })) . "</p>";
}

// Test some sample paths
$testCantons = ['VD', 'SO', 'FR', 'TG', 'BS'];
echo "<h3>Testing Canton File Paths:</h3>";
foreach ($testCantons as $canton) {
    $fullPath = __DIR__ . '/assets/kantone/' . $canton . '.png';
    $webPath = BASE_URL . 'assets/kantone/' . $canton . '.png';
    echo "<p><strong>$canton:</strong><br>";
    echo "File path: $fullPath<br>";
    echo "Web path: $webPath<br>";
    echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "</p>";
}

// Check if uploads/kantone exists (old path)
$oldPath = __DIR__ . '/uploads/kantone/';
echo "<p><strong>Old uploads path exists:</strong> " . (is_dir($oldPath) ? 'YES' : 'NO') . "</p>";
?>