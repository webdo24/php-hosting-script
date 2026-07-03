<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

$root = realpath(__DIR__ . '/..'); // Usually /home/username

if (!$root) {
    die("Cannot determine root directory.");
}

$items = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $root,
        FilesystemIterator::SKIP_DOTS
    ),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    try {
        if ($file->isFile()) {
            $items[] = [
                'type' => 'File',
                'path' => $file->getPathname(),
                'size' => $file->getSize()
            ];
        }
    } catch (Exception $e) {
        // Ignore inaccessible files
    }
}

usort($items, function ($a, $b) {
    return $b['size'] <=> $a['size'];
});

echo "<h2>Largest Files</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Size (MB)</th><th>Path</th></tr>";

foreach (array_slice($items, 0, 100) as $item) {
    echo "<tr>";
    echo "<td>" . number_format($item['size'] / 1024 / 1024, 2) . "</td>";
    echo "<td>" . htmlspecialchars($item['path']) . "</td>";
    echo "</tr>";
}

echo "</table>";
