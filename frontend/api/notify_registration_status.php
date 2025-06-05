<?php
// filepath: /Applications/MAMP/htdocs/school-management/frontend/api/notify_registration_status.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/db_pdo.php';

// Function to log activity for debugging
function debugLog($message) {
    error_log("[NOTIFICATION DEBUG] " . print_r($message, true));
}

debugLog("Starting notification process");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Invalid request method");
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $registrationId = $input['registration_id'] ?? 0;
    $status = $input['status'] ?? '';
    $message = $input['message'] ?? '';
    
    if (empty($registrationId) || empty($status)) {
        throw new Exception('Missing required parameters');
    }
    
    // Get registration details
    $stmt = $conn->prepare("
        SELECT r.*, 
               CASE 
                   WHEN r.registration_type = 'new' THEN (SELECT CONCAT(n.fname, ' ', n.lname) FROM new_student_registration n WHERE n.registration_id = r.id)
                   ELSE (SELECT CONCAT(s.fname, ' ', s.lname) FROM student s WHERE s.id = r.student_id)
               END as student_name,
               r.parent_email,
               r.parent_phone
        FROM registration r
        WHERE r.id = ?
    ");
    
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        throw new Exception('Registration not found');
    }
    
    debugLog("Registration found: " . print_r($registration, true));
    
    // Prepare notification content
    $studentName = $registration['student_name'] ?? 'Unknown';
    $parentName = $registration['parent_name'] ?? '';
    $parentEmail = $registration['parent_email'] ?? '';
    $parentPhone = $registration['parent_phone'] ?? '';
    
    // Create notification record
    $stmt = $conn->prepare("
        INSERT INTO registration_actions 
        (registration_id, action, message, user_id) 
        VALUES (?, ?, ?, ?)
    ");
    
    $action = "notification_" . $status;
    $userId = $input['user_id'] ?? 1; // Use a default admin ID if not provided
    
    $stmt->execute([$registrationId, $action, $message, $userId]);
    
    // In a real production system, we would send an actual email or SMS here
    // For this example, we'll just log it
    
    $response = [
        'success' => true,
        'message' => 'Notification sent successfully',
        'details' => [
            'registration_id' => $registrationId,
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'status' => $status,
            'notification_sent_to' => $parentEmail ?: $parentPhone
        ]
    ];
    
    debugLog("Notification processed: " . print_r($response, true));
    echo json_encode($response);
    
} catch (Exception $e) {
    debugLog("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
