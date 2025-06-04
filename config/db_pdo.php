<?php
ini_set('max_execution_time', 120);

$host = "ballast.proxy.rlwy.net";
$port = 19297;
$username = "root";
$password = "StYwAYQxytyjIrNNvbyohSgKSFsdeWwS";
$database = "railway";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $conn = new PDO($dsn, $username, $password, $options);
    
} catch (PDOException $e) {
    throw new Exception("Database connection failed: " . $e->getMessage());
}
?>