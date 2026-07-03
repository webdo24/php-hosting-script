<?php

set_time_limit(0);
ini_set('memory_limit', '512M');

$root = __DIR__;

// Get domain name
$domain = $_SERVER['HTTP_HOST'] ?? 'website';
$domain = preg_replace('/^www\./', '', $domain);

// Zip filename
$zipName = $domain . '-' . date('d-m-Y') . '.zip';
$zipPath = $root . DIRECTORY_SEPARATOR . $zipName;

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die('Cannot create ZIP file.');
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$self = realpath(__FILE__);

foreach ($files as $file) {

    $filePath = $file->getRealPath();

    // Skip this script
    if ($filePath === $self) {
        continue;
    }

    // Skip generated zip files
    if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip') {
        continue;
    }

    $relativePath = substr($filePath, strlen($root) + 1);

    if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
    } else {
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

echo "ZIP created successfully.<br>";
echo "<a href=\"" . htmlspecialchars($zipName) . "\">Download {$zipName}</a>";
