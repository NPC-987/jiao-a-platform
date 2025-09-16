<?php
$conn = new mysqli(
    "btonfkflezevlaynxohd-mysql.services.clever-cloud.com",
    "uhpsngd8tma9dx2f",
    "SnBYocGCFJy7debiAiAB",
    "btonfkflezevlaynxohd",
    3306
);

if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
echo "資料庫連線成功！";
?>
