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

$searchType = $input['searchType'] ?? '';
$searchValue = $input['searchValue'] ?? '';

if (empty($searchValue)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search value is required']);
    exit;
}

try {
    $sql = "SELECT s.id, s.fname, s.lname, s.gender, s.birth_date, s.village, 
                   s.district, s.province, s.parent_name, s.phone, s.class_id,
                   c.name as class_name, l.name as level_name
            FROM student s
            LEFT JOIN class c ON s.class_id = c.id
            LEFT JOIN levels l ON c.level_id = l.id
            WHERE ";
    
    $params = [];
    
    if ($searchType === 'id') {
        $sql .= "s.id = ?";
        $params[] = $searchValue;
    } elseif ($searchType === 'name') {
        $sql .= "(s.fname LIKE ? OR s.lname LIKE ? OR CONCAT(s.fname, ' ', s.lname) LIKE ?)";
        $searchPattern = "%{$searchValue}%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    } else {
        $sql .= "(s.id = ? OR s.fname LIKE ? OR s.lname LIKE ? OR CONCAT(s.fname, ' ', s.lname) LIKE ?)";
        $params[] = $searchValue;
        $searchPattern = "%{$searchValue}%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $sql .= " LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo json_encode([
            'success' => false,
            'message' => 'ບໍ່ພົບຂໍ້ມູນນັກຮຽນ'
        ]);
    } else {
        foreach ($students as &$student) {
            $student['full_name'] = $student['fname'] . ' ' . $student['lname'];
            $student['age'] = date_diff(date_create($student['birth_date']), date_create('today'))->y;
            $student['full_address'] = $student['village'] . ', ' . $student['district'] . ', ' . $student['province'];
        }
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>