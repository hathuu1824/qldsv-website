<?php
$host = "localhost";
$dbname = "qldsv";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_time = date('Y-m-d H:i:s');
//echo("Kết nối thành công");
$conn->set_charset("utf8mb4");
?>