<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$query = $input['query'] ?? '';
$limit = intval($input['limit'] ?? 5);

if (empty($query) || strlen(trim($query)) < 2) {
    echo json_encode([
        'status' => 'success',
        'students' => []
    ]);
    exit;
}

try {
    $searchPattern = "%{$query}%";
    
    $sql = "SELECT DISTINCT s.id, s.fname, s.lname 
            FROM student s
            WHERE (s.fname LIKE ? OR s.lname LIKE ? OR CONCAT(s.fname, ' ', s.lname) LIKE ?)
            ORDER BY s.fname, s.lname
            LIMIT ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $searchPattern, PDO::PARAM_STR);
    $stmt->bindParam(2, $searchPattern, PDO::PARAM_STR);
    $stmt->bindParam(3, $searchPattern, PDO::PARAM_STR);
    $stmt->bindParam(4, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'students' => $students
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
}
?>
