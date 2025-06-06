<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Check if scores table exists
    $checkTableStmt = $conn->prepare("SHOW TABLES LIKE 'scores'");
    $checkTableStmt->execute();
    
    if (!$checkTableStmt->fetch()) {
        // Create scores table if it doesn't exist
        $createTableSQL = "
        CREATE TABLE scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject VARCHAR(100) NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            max_score DECIMAL(5,2) NOT NULL DEFAULT 100,
            month INT NOT NULL,
            term INT NOT NULL,
            year INT NOT NULL DEFAULT 2024,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE CASCADE
        )";
        
        $conn->exec($createTableSQL);
        echo "Scores table created successfully.\n";
    }
    
    // Check if sample data already exists
    $checkDataStmt = $conn->prepare("SELECT COUNT(*) as count FROM scores WHERE student_id = 12");
    $checkDataStmt->execute();
    $result = $checkDataStmt->fetch();
    
    if ($result['count'] == 0) {
        // Insert sample scores data
        $sampleScores = [
            // เดือน 1, เทอม 1
            [12, 'ภาษาลาว', 85.5, 100, 1, 1, 2024],
            [12, 'คณิตศาสตร์', 92.0, 100, 1, 1, 2024],
            [12, 'วิทยาศาสตร์', 78.0, 100, 1, 1, 2024],
            [12, 'ภาษาอังกฤษ', 88.5, 100, 1, 1, 2024],
            [12, 'สังคมศึกษา', 76.0, 100, 1, 1, 2024],
            
            // เดือน 2, เทอม 1
            [12, 'ภาษาลาว', 89.0, 100, 2, 1, 2024],
            [12, 'คณิตศาสตร์', 94.5, 100, 2, 1, 2024],
            [12, 'วิทยาศาสตร์', 82.5, 100, 2, 1, 2024],
            [12, 'ภาษาอังกฤษ', 91.0, 100, 2, 1, 2024],
            [12, 'สังคมศึกษา', 80.0, 100, 2, 1, 2024],
            
            // เดือน 1, เทอม 2
            [12, 'ภาษาลาว', 87.5, 100, 1, 2, 2024],
            [12, 'คณิตศาสตร์', 90.0, 100, 1, 2, 2024],
            [12, 'วิทยาศาสตร์', 85.0, 100, 1, 2, 2024],
            [12, 'ภาษาอังกฤษ', 86.5, 100, 1, 2, 2024],
            [12, 'สังคมศึกษา', 83.0, 100, 1, 2, 2024],
            
            // Student 13 data
            [13, 'ภาษาลาว', 79.5, 100, 1, 1, 2024],
            [13, 'คณิตศาสตร์', 88.0, 100, 1, 1, 2024],
            [13, 'วิทยาศาสตร์', 72.0, 100, 1, 1, 2024],
            [13, 'ภาษาอังกฤษ', 84.5, 100, 1, 1, 2024],
            [13, 'สังคมศึกษา', 81.0, 100, 1, 1, 2024],
        ];
        
        $insertStmt = $conn->prepare("
            INSERT INTO scores (student_id, subject, score, max_score, month, term, year) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleScores as $score) {
            $insertStmt->execute($score);
        }
        
        echo "Sample scores data inserted successfully.\n";
        echo "Total records inserted: " . count($sampleScores) . "\n";
    } else {
        echo "Sample data already exists. Found " . $result['count'] . " records for student 12.\n";
    }
    
    // Show summary of data
    $summaryStmt = $conn->prepare("
        SELECT student_id, COUNT(*) as count, AVG(score) as avg_score 
        FROM scores 
        GROUP BY student_id 
        ORDER BY student_id
    ");
    $summaryStmt->execute();
    $summary = $summaryStmt->fetchAll();
    
    echo "\nScores summary by student:\n";
    foreach ($summary as $row) {
        echo "Student ID " . $row['student_id'] . ": " . $row['count'] . " scores, average: " . round($row['avg_score'], 2) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
