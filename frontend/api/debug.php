<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$debug_info = array();

try {
    // Test database connection
    $debug_info['database_connection'] = 'Success';
    
    // Check if required tables exist
    $tables_to_check = ['student', 'registration', 'years', 'terms'];
    $existing_tables = [];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->fetch()) {
                $existing_tables[$table] = 'exists';
                
                // Get table structure
                $structStmt = $conn->prepare("DESCRIBE $table");
                $structStmt->execute();
                $existing_tables[$table . '_structure'] = $structStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $existing_tables[$table] = 'missing';
            }
        } catch (Exception $e) {
            $existing_tables[$table] = 'error: ' . $e->getMessage();
        }
    }
    
    $debug_info['tables'] = $existing_tables;
    
    // Check sample data
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM student LIMIT 1");
        $stmt->execute();
        $debug_info['student_count'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $debug_info['student_count'] = 'Error: ' . $e->getMessage();
    }
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registration LIMIT 1");
        $stmt->execute();
        $debug_info['registration_count'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $debug_info['registration_count'] = 'Error: ' . $e->getMessage();
    }
    
    // Get sample student data
    try {
        $stmt = $conn->prepare("SELECT id, fname, lname FROM student LIMIT 3");
        $stmt->execute();
        $debug_info['sample_students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug_info['sample_students'] = 'Error: ' . $e->getMessage();
    }
    
    // Get sample registration data
    try {
        $stmt = $conn->prepare("SELECT r.id, r.student_id, r.status, r.payment_status, 
                                       y.name as year_name, t.name as term_name 
                                FROM registration r 
                                LEFT JOIN years y ON r.year_id = y.id 
                                LEFT JOIN terms t ON r.term_id = t.id 
                                LIMIT 3");
        $stmt->execute();
        $debug_info['sample_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug_info['sample_registrations'] = 'Error: ' . $e->getMessage();
    }
    
} catch (Exception $e) {
    $debug_info['database_connection'] = 'Failed: ' . $e->getMessage();
}

echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>