<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Add more sample data for students 14, 15, 16
    $sampleScores = [
        // Student 14 - เดือน 1, เทอม 1 (คะแนนต่ำกว่า)
        [14, 'ภาษาลาว', 70.0, 100, 1, 1, 2024],
        [14, 'คณิตศาสตร์', 65.5, 100, 1, 1, 2024],
        [14, 'วิทยาศาสตร์', 68.0, 100, 1, 1, 2024],
        [14, 'ภาษาอังกฤษ', 72.5, 100, 1, 1, 2024],
        [14, 'สังคมศึกษา', 69.0, 100, 1, 1, 2024],
        
        // Student 15 - เดือน 1, เทอม 1 (คะแนนปานกลาง)
        [15, 'ภาษาลาว', 75.0, 100, 1, 1, 2024],
        [15, 'คณิตศาสตร์', 78.5, 100, 1, 1, 2024],
        [15, 'วิทยาศาสตร์', 74.0, 100, 1, 1, 2024],
        [15, 'ภาษาอังกฤษ', 76.5, 100, 1, 1, 2024],
        [15, 'สังคมศึกษา', 73.0, 100, 1, 1, 2024],
        
        // Student 16 - เดือน 1, เทอม 1 (คะแนนสูง)
        [16, 'ภาษาลาว', 95.0, 100, 1, 1, 2024],
        [16, 'คณิตศาสตร์', 98.5, 100, 1, 1, 2024],
        [16, 'วิทยาศาสตร์', 92.0, 100, 1, 1, 2024],
        [16, 'ภาษาอังกฤษ', 96.5, 100, 1, 1, 2024],
        [16, 'สังคมศึกษา', 94.0, 100, 1, 1, 2024],
        
        // เดือน 2, เทอม 1 สำหรับนักเรียนเพิ่มเติม
        [14, 'ภาษาลาว', 72.0, 100, 2, 1, 2024],
        [14, 'คณิตศาสตร์', 67.5, 100, 2, 1, 2024],
        [14, 'วิทยาศาสตร์', 70.0, 100, 2, 1, 2024],
        [14, 'ภาษาอังกฤษ', 74.5, 100, 2, 1, 2024],
        [14, 'สังคมศึกษา', 71.0, 100, 2, 1, 2024],
        
        [15, 'ภาษาลาว', 77.0, 100, 2, 1, 2024],
        [15, 'คณิตศาสตร์', 80.5, 100, 2, 1, 2024],
        [15, 'วิทยาศาสตร์', 76.0, 100, 2, 1, 2024],
        [15, 'ภาษาอังกฤษ', 78.5, 100, 2, 1, 2024],
        [15, 'สังคมศึกษา', 75.0, 100, 2, 1, 2024],
        
        [16, 'ภาษาลาว', 97.0, 100, 2, 1, 2024],
        [16, 'คณิตศาสตร์', 99.5, 100, 2, 1, 2024],
        [16, 'วิทยาศาสตร์', 94.0, 100, 2, 1, 2024],
        [16, 'ภาษาอังกฤษ', 98.5, 100, 2, 1, 2024],
        [16, 'สังคมศึกษา', 96.0, 100, 2, 1, 2024],
    ];
    
    // Check if data already exists for student 14
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM scores WHERE student_id = 14");
    $checkStmt->execute();
    $result = $checkStmt->fetch();
    
    if ($result['count'] == 0) {
        $insertStmt = $conn->prepare("
            INSERT INTO scores (student_id, subject, score, max_score, month, term, year) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleScores as $score) {
            $insertStmt->execute($score);
        }
        
        echo "Additional sample scores data inserted successfully.\n";
        echo "Total records inserted: " . count($sampleScores) . "\n";
    } else {
        echo "Additional sample data already exists. Found " . $result['count'] . " records for student 14.\n";
    }
    
    // Show summary of all data
    $summaryStmt = $conn->prepare("
        SELECT student_id, COUNT(*) as count, AVG(score) as avg_score,
               MIN(score) as min_score, MAX(score) as max_score
        FROM scores 
        GROUP BY student_id 
        ORDER BY avg_score DESC
    ");
    $summaryStmt->execute();
    $summary = $summaryStmt->fetchAll();
    
    echo "\nComplete scores summary by student (sorted by average):\n";
    foreach ($summary as $index => $row) {
        $rank = $index + 1;
        echo "Rank #{$rank} - Student ID " . $row['student_id'] . ": " . 
             $row['count'] . " scores, average: " . round($row['avg_score'], 2) . 
             " (range: " . $row['min_score'] . "-" . $row['max_score'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
