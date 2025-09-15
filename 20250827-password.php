<?php

echo "1. password_hash vs password_verify<br>";
// 建立密碼雜湊（註冊時用）
echo password_hash('12345678', PASSWORD_DEFAULT);

// 手動存一個 hash（模擬資料庫裡的密碼）
$hash = '$2y$10$1hHn8l.eSQD/mmCYhhmOpOij164Oag3KXL9/TR3ByDjLNrwXAuDdq';

echo "<br>";

// 驗證密碼是否正確（登入時用）
if (password_verify('12345678', $hash)) {
    echo "驗證成功!";
} else {
    echo "驗證失敗!";
}
echo "<br>";
echo "<br>";
// 顯示章節標題
echo "2. 時間相關 <br>";
// 設定時區（避免時間不正確）
date_default_timezone_set('UTC');
// 顯示時間戳記（從 1970-01-01 起算的秒數）
echo time(); 
echo "<br>";
// 顯示格式化時間
echo date("Y/m/d h:i:s");


echo "<br>";
echo "<br>";
//3. 亂數相關
echo "3. 亂數相關 <br>";
// 產生唯一識別碼
echo 'uniqid: ' . uniqid();
echo "<br>";

// 使用 hash() 加上 time() 產生亂數字串
echo 'uniqid(time()): ' . uniqid(time());
echo "<br>";
echo 'hash("sha256", time()): ' . hash("sha256", time());
echo "<br>";
echo 'hash("sha512", time()): ' . hash("sha512", time());
echo "<br>";
echo 'hash("md5", time()): ' . hash("md5", time());
echo "<br>";

echo "<br>";
echo "<br>";

//自訂意亂數產生
echo "4.自訂意亂數產生<br>";
echo substr(hash("sha256" , uniqid(time())), 0 ,10 ) . substr(hash("sha256" , uniqid(time())), 20 ,10 );

echo "<br>";
echo "<br>";

//自己測試自訂二
echo "4.自訂意亂數產生<br>";
echo substr(hash("sha512" , uniqid(time())), 0 ,6 ) . substr(hash("sha256" , uniqid(time())), 12 ,20 );