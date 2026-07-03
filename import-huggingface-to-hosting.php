<?php
// বড় ফাইল ডাউনলোডের জন্য টাইম এবং মেমোরি লিমিট বাড়ানো
@set_time_limit(0);
@ini_set('memory_limit', '1024M');

// --- আপনার তথ্যগুলো এখানে পরিবর্তন করুন ---
$hfToken   = "hf_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"; // আপনার Hugging Face Read Token
$username  = "your-hf-username";                      // আপনার Hugging Face ইউজারনেম
$repoName  = "your-repo-name";                        // আপনার ডেটাসেট রিপোজিটরির নাম
$fileName  = "mydomain.com-03-06-2026.zip";            // হাগিং ফেসে থাকা ফাইলটির নাম
$saveTo    = "imported_backup.zip";                    // আপনার হোস্টিংয়ে যে নামে সেভ হবে
// ----------------------------------------

// Hugging Face Private File-এর ডাউনলোডের আসল লিংক স্ট্রাকচার
$sourceUrl = "https://huggingface.co/datasets/{$username}/{$repoName}/resolve/main/{$fileName}";

echo "হাগিং ফেস থেকে ফাইল ইমপোর্ট করার প্রক্রিয়া শুরু হচ্ছে...<br>";

// cURL এর মাধ্যমে প্রাইভেট ফাইল ডাউনলোডের প্রসেস
$ch = curl_init();
$fp = fopen($saveTo, 'w+');

if ($fp === false) {
    die("Error: হোস্টিংয়ে ফাইলটি তৈরি বা রাইট করার অনুমতি নেই (Permission Denied)।");
}

curl_setopt($ch, CURLOPT_URL, $sourceUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // রিডাইরেক্ট ফলো করার জন্য
curl_setopt($ch, CURLOPT_FILE, $fp);

// প্রাইভেট রিপোজিটরির জন্য Bearer Token হেডার পাঠানো
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: Bearer " . $hfToken
));

// ফাইলটি ডাউনলোড করা
curl_exec($ch);

// কোনো এরর আছে কিনা চেক করা
if (curl_errno($ch)) {
    echo "ডাউনলোড এরর: " . curl_error($ch);
} else {
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($statusCode == 200) {
        echo "<h3>১ জিবি ব্যাকআপ ফাইলটি সফলভাবে আপনার হোস্টিংয়ে ইমপোর্ট হয়েছে!</h3>";
        echo "ফাইল পাথ: <b>" . realpath($saveTo) . "</b>";
    } else {
        echo "ডাউনলোড ব্যর্থ হয়েছে। HTTP Status Code: " . $statusCode . "<br>";
        echo "অনুগ্রহ করে চেক করুন আপনার Token, Username বা Filename ঠিক আছে কিনা।";
        // ব্যর্থ হলে খালি ফাইলটি মুছে ফেলা
        fclose($fp);
        unlink($saveTo);
        exit;
    }
}

fclose($fp);
curl_close($ch);
?>
