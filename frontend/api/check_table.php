<?php
require_once '../../config/db_pdo.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Show registration table structure
    echo "Registration table structure:\n";
    $sql = "DESCRIBE registration";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . $column['Null'] . " - " . $column['Default'] . "\n";
    }
    
    echo "\n\nSample registration data:\n";
    $dataSql = "SELECT * FROM registration LIMIT 3";
    $dataStmt = $conn->prepare($dataSql);
    $dataStmt->execute();
    
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $row) {
        print_r($row);
        echo "\n---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
