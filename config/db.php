<?php
ini_set('max_execution_time', 120); // ขยาย timeout เผื่อเชื่อมช้า

$host = "ballast.proxy.rlwy.net"; // host จาก Railway
$port = 19297; // port จาก Railway
$username = "root";
$password = "StYwAYQxytyjIrNNvbyohSgKSFsdeWwS";
$database = "railway";

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to Railway DB successfully!";
?>
