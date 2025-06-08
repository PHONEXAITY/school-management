<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $stmt = $conn->query("SELECT id, name as year_name FROM year ORDER BY name DESC");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'years' => $years
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพาดในการดึงข้อมูลปีการศึกษา: ' . $e->getMessage()
    ]);
}
?>
