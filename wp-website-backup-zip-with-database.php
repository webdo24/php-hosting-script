
<?php

// ফাইল ডাউনলোডের রিকোয়েস্ট হ্যান্ডেল করা
if (isset($_GET['action']) && $_GET['action'] == 'download' && isset($_GET['file'])) {
    $file = basename($_GET['file']); // সুরক্ষার জন্য শুধু ফাইলনেম নেওয়া
    
    if (file_exists($file) && strpos($file, '.zip') !== false) {
        // ব্রাউজারকে ডাউনলোডের জন্য হেডার পাঠানো
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        
        // ফাইলটি রিড করে ব্রাউজারে পাঠানো
        readfile($file);
        
        // ডাউনলোড শেষ হওয়ার পর সার্ভারের সুরক্ষার জন্য জিপ ফাইলটি ডিলিট করে দেওয়া
        unlink($file);
        exit;
    } else {
        die("ফাইলটি খুঁজে পাওয়া যায়নি অথবা ডাউনলোড করার অনুমতি নেই।");
    }
}

// ১. মেমোরি ও টাইম লিমিট সর্বোচ্চ করা
@set_time_limit(0); 
@ini_set('memory_limit', '1024M');

if (file_exists('wp-config.php')) {
    require_once('wp-config.php');
} else {
    die("wp-config.php পাওয়া যায়নি।");
}

$domain = $_SERVER['HTTP_HOST'];
$date = date('d-m-Y');
$sql_filename = "{$domain}-database-{$date}.sql";
$zip_filename = "{$domain}-{$date}.zip";

echo "ব্যাকআপ প্রসেস শুরু হয়েছে...<br>";

// ২. ডাটাবেজ ব্যাকআপ (PDO মেথড)
try {
    $host = explode(':', DB_HOST)[0];
    $pdo = new PDO("mysql:host={$host};dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "-- WordPress Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $create_table = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $sql .= "\n\n" . $create_table['Create Table'] . ";\n\n";
        
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map(function($val) use ($pdo) {
                return is_null($val) ? 'NULL' : $pdo->quote($val);
            }, $row);
            $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
        }
    }
    file_put_contents($sql_filename, $sql);
    echo "১. ডাটাবেজ সফলভাবে এক্সপোর্ট হয়েছে।<br>";
} catch (Exception $e) {
    die("ডাটাবেজ এক্সপোর্টে সমস্যা: " . $e->getMessage());
}

// ৩. জিপ ফাইল তৈরি
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        
        $rootPath = realpath(__DIR__);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // তৈরি হওয়া জিপ ফাইলটি যেন আবার জিপ না হয়
            if (basename($filePath) == $zip_filename) {
                continue;
            }

            // এই কোডটি নিজেকে (self file) জিপ করা থেকে বিরত রাখবে
            if (basename($filePath) == basename(__FILE__)) {
                continue;
            }

            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
        echo "২. সম্পূর্ণ ফাইলের ZIP তৈরি সম্পন্ন হয়েছে: <b>{$zip_filename}</b><br>";
        
        // অস্থায়ী SQL ফাইল মুছে ফেলা
        if (file_exists($sql_filename)) {
            unlink($sql_filename);
        }
        
        // ডাউনলোড বোতাম এবং ডিলিট করার অ্যাকশন দেখানো
        echo "<h3>ব্যাকআপ সফল!</h3>";
        echo "<div style='margin: 20px 0; padding: 15px; background: #e1f5fe; border: 1px solid #b3e5fc; display: inline-block; border-radius: 5px;'>";
        echo "  <p style='margin-top:0;'>আপনার ব্যাকআপ ফাইলটি প্রস্তুত। নিচের বোতামে ক্লিক করে ডাউনলোড করুন:</p>";
        echo "  <a href='?action=download&file=" . urlencode($zip_filename) . "' style='background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>Download ZIP File</a>";
        echo "</div>";
    } else {
        echo "ZIP তৈরি করতে ব্যর্থ হয়েছে।";
    }
} else {
    echo "আপনার সার্ভারে ZipArchive এক্সটেনশনটি ইনেবল করা নেই।";
}
?>
