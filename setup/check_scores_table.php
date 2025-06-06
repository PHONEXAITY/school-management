<?php
require_once '../config/db_pdo.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "=== DESCRIBE scores table ===\n";
    $stmt = $conn->prepare("DESCRIBE scores");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "Column: {$column['Field']} | Type: {$column['Type']} | Null: {$column['Null']} | Default: {$column['Default']}\n";
    }
    
    echo "\n=== Sample scores data ===\n";
    $stmt = $conn->prepare("SELECT * FROM scores LIMIT 3");
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $sample) {
        print_r($sample);
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
