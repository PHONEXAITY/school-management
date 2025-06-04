<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!file_exists('../../config/db_pdo.php')) {
    echo json_encode(['success' => false, 'error' => 'Database config file not found']);
    exit;
}

try {
    require_once '../../config/db_pdo.php';
    if (!isset($conn) && !isset($pdo)) {
        throw new Exception('Database connection not established');
    }
    
    $db = isset($pdo) ? $pdo : $conn;
    
    $testStmt = $db->prepare("SELECT 1 as test");
    $testStmt->execute();
    $testResult = $testStmt->fetch();
    
    if (!$testResult) {
        throw new Exception('Database connection test failed');
    }
    
    $stmt = $db->prepare("
        SELECT l.id as level_id, l.name as level_name,
               c.id as class_id, c.name as class_name
        FROM levels l
        LEFT JOIN class c ON l.id = c.level_id
        ORDER BY l.name ASC, c.name ASC
    ");
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $levels = [];
    
    foreach ($results as $row) {
        $levelId = $row['level_id'];
        
        if (!isset($levels[$levelId])) {
            $levels[$levelId] = [
                'id' => $levelId,
                'name' => $row['level_name'],
                'classes' => []
            ];
        }
        
        if ($row['class_id']) {
            $levels[$levelId]['classes'][] = [
                'id' => $row['class_id'],
                'name' => $row['class_name']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'levels' => array_values($levels),
        'total_levels' => count($levels)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>