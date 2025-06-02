<?php
ini_set('max_execution_time', 120);

$host = "ballast.proxy.rlwy.net";
$port = 19297;
$username = "root";
$password = "StYwAYQxytyjIrNNvbyohSgKSFsdeWwS";
$database = "railway";

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

//echo "✅ Connected to Railway DB successfully!";
?>