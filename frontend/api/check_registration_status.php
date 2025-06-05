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
    // First, check if the student exists
    $sql = "SELECT s.id, s.fname, s.lname 
            FROM student s
            WHERE ";
    
    $params = [];
    
    if ($searchType === 'student_id') {
        $sql .= "s.id = ?";
        $params[] = $searchValue;
    } elseif ($searchType === 'name') {
        $sql .= "(s.fname LIKE ? OR s.lname LIKE ? OR CONCAT(s.fname, ' ', s.lname) LIKE ?)";
        $searchPattern = "%{$searchValue}%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    } else {
        throw new Exception('Invalid search type');
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'ບໍ່ພົບຂໍ້ມູນນັກຮຽນ'
        ]);
        exit;
    }
    
    // Check registration status for the current term/year
    $regSql = "SELECT r.id, r.registration_status as status, r.transfer_amount, r.student_id, 
                      r.registration_type, r.approved_by, r.approved_date, r.academic_year_id,
                      DATE_FORMAT(r.registration_date, '%d/%m/%Y') as registration_date,
                      DATE_FORMAT(r.transfer_date, '%d/%m/%Y') as transfer_date,
                      TIME_FORMAT(r.transfer_time, '%H:%i') as transfer_time,
                      r.parent_name, r.parent_phone, r.relationship,
                      y.name as academic_year_name
               FROM registration r
               LEFT JOIN year y ON r.academic_year_id = y.id
               WHERE r.student_id = ?
               ORDER BY r.id DESC
               LIMIT 1";
               
    $regStmt = $conn->prepare($regSql);
    $regStmt->execute([$student['id']]);
    
    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        echo json_encode([
            'status' => 'not_registered',
            'student' => $student,
            'message' => 'ນັກຮຽນຍັງບໍ່ໄດ້ລົງທະບຽນ'
        ]);
        exit;
    }
    
    // Return status with details
    $paymentStatus = 'pending'; // Default payment status
    if ($registration['transfer_amount'] > 0) {
        $paymentStatus = 'paid';
    }
    
    echo json_encode([
        'status' => 'success',
        'student' => $student,
        'registration' => array_merge($registration, ['payment_status' => $paymentStatus]),
        'message' => 'ພົບຂໍ້ມູນການລົງທະບຽນແລ້ວ',
        'registration_status' => $registration['status'],
        'payment_status' => $paymentStatus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
}
