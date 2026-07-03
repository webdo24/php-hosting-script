<?php
// বড় ফাইল আপলোডের জন্য টাইম এবং মেমোরি লিমিট সর্বোচ্চ করা
@set_time_limit(0);
@ini_set('memory_limit', '1024M');

// --- আপনার তথ্যগুলো এখানে পরিবর্তন করুন ---
$hfToken   = "hf_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"; // আপনার Hugging Face WRITE Token
$username  = "your-hf-username";                               // আপনার Hugging Face ইউজারনেম
$repoName  = "your-hf-repo";               // আপনার ডেটাসেট রিপোজিটরির নাম
$filePath  = "./filename.zip";            // হোস্টিংয়ে থাকা যে ফাইলটি আপলোড করবেন
// ----------------------------------------

if (!file_exists($filePath)) {
    die("Error: হোস্টিংয়ে আপলোড করার মতো কোনো ফাইল খুঁজে পাওয়া যায়নি।");
}

$fileName = basename($filePath);
// হাগিং ফেসের API আপলোড ইউআরএল স্ট্রাকচার
$uploadUrl = "https://huggingface.co/api/datasets/{$username}/{$repoName}/upload/main/{$fileName}";

echo "হাগিং ফেস প্রাইভেট রেপোতে ফাইল আপলোড শুরু হচ্ছে... (১ জিবি ফাইলের জন্য কিছুটা সময় লাগতে পারে)<br>";

// cURL এর মাধ্যমে ফাইল আপলোডের প্রসেস
$ch = curl_init();
$fileData = fopen($filePath, 'r');

curl_setopt($ch, CURLOPT_URL, $uploadUrl);
curl_setopt($ch, CURLOPT_PUT, true); // ফাইল পুশ করার জন্য PUT মেথড
curl_setopt($ch, CURLOPT_INFILE, $fileData);
curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// অথেন্টিকেশন হেডার পাঠানো
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: Bearer " . $hfToken
));

// এক্সিকিউট করা
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

fclose($fileData);
curl_close($ch);

// ফলাফল চেক করা
if ($statusCode == 200 || $statusCode == 201) {
    echo "<h3>সফলভাবে ফাইলটি হাগিং ফেস-এ আপলোড হয়েছে!</h3>";
    
    // ঐচ্ছিক: আপলোড সফল হলে হোস্টিংয়ের স্পেস বাঁচাতে ফাইলটি ডিলিট করে দিতে পারেন
    // unlink($filePath); 
    // echo "হোস্টিং থেকে স্থানীয় জিপ ফাইলটি মুছে ফেলা হয়েছে।";
} else {
    echo "আপলোড ব্যর্থ হয়েছে। HTTP Status Code: " . $statusCode . "<br>";
    echo "সার্ভার রেসপন্স: " . $response;
}
?>
