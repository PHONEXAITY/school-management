<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Test connection
    echo "Database connection successful!\n";
    
    // Check if student table exists and get some sample data
    $sql = "SELECT s.id, s.fname, s.lname FROM student s LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($students) . " students:\n";
    foreach($students as $student) {
        echo "ID: " . $student['id'] . " - Name: " . $student['fname'] . " " . $student['lname'] . "\n";
    }
    
    // Check registration table
    $regSql = "SELECT COUNT(*) as count FROM registration";
    $regStmt = $conn->prepare($regSql);
    $regStmt->execute();
    $regCount = $regStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nRegistrations count: " . $regCount['count'] . "\n";
    
    // Check a few registrations
    $regDetailsSql = "SELECT r.student_id, r.status, r.payment_status, s.fname, s.lname 
                     FROM registration r 
                     JOIN student s ON r.student_id = s.id 
                     LIMIT 3";
    $regDetailsStmt = $conn->prepare($regDetailsSql);
    $regDetailsStmt->execute();
    $registrations = $regDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample registrations:\n";
    foreach($registrations as $reg) {
        echo "Student: " . $reg['fname'] . " " . $reg['lname'] . " (ID: " . $reg['student_id'] . ") - Status: " . $reg['status'] . " - Payment: " . $reg['payment_status'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
