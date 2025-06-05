<?php
// API endpoint for admin registration actions
session_start();

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../debug.log');

// Start output buffering to catch any unwanted output
ob_start();

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if the user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include("../config/db.php");

// Debug: Log all POST data
error_log("AJAX Request received. POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Handle single status update
    if (isset($_POST['update_status'])) {
        error_log("Processing single status update");
        
        $registrationId = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        error_log("Registration ID: $registrationId, Status: '$status', Notes: '$notes', User ID: $userId");
        
        if (empty($registrationId) || empty($status)) {
            error_log("Missing required data: registrationId=$registrationId, status='$status'");
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }
        
        // Validate status
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            error_log("Invalid status: '$status'");
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'สถานะไม่ถูกต้อง']);
            exit;
        }
        
        // Check if registration exists
        $checkStmt = $conn->prepare("SELECT id, registration_status FROM registration WHERE id = ?");
        $checkStmt->bind_param("i", $registrationId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingReg = $checkResult->fetch_assoc();
        
        if (!$existingReg) {
            error_log("Registration not found: ID=$registrationId");
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการลงทะเบียน']);
            exit;
        }
        
        error_log("Current status: " . $existingReg['registration_status'] . " -> New status: $status");
        
        // Update registration status
        $stmt = $conn->prepare("UPDATE registration SET registration_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $registrationId);
        $result = $stmt->execute();
        
        error_log("Update result: " . ($result ? 'success' : 'failed') . ", affected rows: " . $conn->affected_rows);
        
        if ($result && $conn->affected_rows > 0) {
            // Log the action
            $logStmt = $conn->prepare("INSERT INTO registration_actions (registration_id, action, message, user_id) VALUES (?, ?, ?, ?)");
            $logMessage = "Status changed from '" . $existingReg['registration_status'] . "' to '$status'. Notes: $notes";
            $logStmt->bind_param("issi", $registrationId, 'status_change', $logMessage, $userId);
            $logStmt->execute();
            
            error_log("Action logged successfully");
            
            // If approved, create student record
            if ($status == 'approved') {
                error_log("Processing approved status - creating student record if needed");
                
                // Get registration details
                $regStmt = $conn->prepare("
                    SELECT r.*, n.fname, n.lname, n.gender, n.birth_date, n.village, n.district, n.province, n.parent_name, n.phone, n.class_id
                    FROM registration r
                    LEFT JOIN new_student_registration n ON r.id = n.registration_id
                    WHERE r.id = ?
                ");
                $regStmt->bind_param("i", $registrationId);
                $regStmt->execute();
                $regResult = $regStmt->get_result();
                $registration = $regResult->fetch_assoc();
                
                if ($registration && $registration['registration_type'] == 'new' && !empty($registration['fname'])) {
                    error_log("Found new student registration data");
                    
                    // Check if student already exists
                    $checkStmt = $conn->prepare("SELECT id FROM student WHERE fname = ? AND lname = ? AND birth_date = ?");
                    $checkStmt->bind_param("sss", $registration['fname'], $registration['lname'], $registration['birth_date']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $existingStudent = $checkResult->fetch_assoc();
                    
                    if (!$existingStudent) {
                        // Create new student record
                        $insertStmt = $conn->prepare("
                            INSERT INTO student (fname, lname, gender, birth_date, village, district, province, parent_name, phone, class_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insertStmt->bind_param("ssssssssss", 
                            $registration['fname'],
                            $registration['lname'],
                            $registration['gender'],
                            $registration['birth_date'],
                            $registration['village'],
                            $registration['district'],
                            $registration['province'],
                            $registration['parent_name'],
                            $registration['phone'],
                            $registration['class_id']
                        );
                        $insertResult = $insertStmt->execute();
                        
                        if ($insertResult) {
                            error_log("Student record created successfully for registration ID: $registrationId");
                        } else {
                            error_log("Failed to create student record: " . $conn->error);
                        }
                    } else {
                        error_log("Student record already exists");
                    }
                } else {
                    error_log("No new student data found or registration type is not 'new'");
                }
            }
            
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'อัพเดทสถานะสำเร็จ']);
        } else {
            error_log("Update failed or no rows affected. Error: " . $conn->error);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพเดทสถานะ']);
        }
        exit;
    }
    
    // Handle bulk status update
    if (isset($_POST['bulk_update'])) {
        error_log("Processing bulk status update");
        
        $registrationIds = $_POST['registration_ids'] ?? [];
        $status = isset($_POST['bulk_status']) ? trim($_POST['bulk_status']) : '';
        $notes = isset($_POST['bulk_notes']) ? trim($_POST['bulk_notes']) : '';
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        if (empty($registrationIds) || empty($status)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }
        
        $successCount = 0;
        $totalCount = count($registrationIds);
        
        foreach ($registrationIds as $registrationId) {
            $registrationId = (int)$registrationId;
            
            // Update registration status
            $stmt = $conn->prepare("UPDATE registration SET registration_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $registrationId);
            $result = $stmt->execute();
            
            if ($result && $conn->affected_rows > 0) {
                $successCount++;
                
                // Log the action
                $logStmt = $conn->prepare("INSERT INTO registration_actions (registration_id, action, message, user_id) VALUES (?, ?, ?, ?)");
                $logMessage = "Bulk status changed to: $status. Notes: $notes";
                $logStmt->bind_param("issi", $registrationId, 'bulk_status_change', $logMessage, $userId);
                $logStmt->execute();
            }
        }
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => "อัพเดทสำเร็จ $successCount จาก $totalCount รายการ"]);
        exit;
    }
    
    // Handle registration deletion
    if (isset($_POST['delete_registration'])) {
        error_log("Processing registration deletion");
        
        $registrationId = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        if (empty($registrationId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }
        
        // Delete related records first
        $stmt1 = $conn->prepare("DELETE FROM registration_actions WHERE registration_id = ?");
        $stmt1->bind_param("i", $registrationId);
        $stmt1->execute();
        
        $stmt2 = $conn->prepare("DELETE FROM new_student_registration WHERE registration_id = ?");
        $stmt2->bind_param("i", $registrationId);
        $stmt2->execute();
        
        // Delete main registration record
        $stmt3 = $conn->prepare("DELETE FROM registration WHERE id = ?");
        $stmt3->bind_param("i", $registrationId);
        $result = $stmt3->execute();
        
        if ($result && $conn->affected_rows > 0) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'ลบการลงทะเบียนสำเร็จ']);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ']);
        }
        exit;
    }
    
} catch (Exception $e) {
    error_log("Exception occurred: " . $e->getMessage());
    ob_clean();
    
    // Use more user-friendly message and hide technical details
    $userMessage = 'เกิดข้อผิดพลาดระหว่างดำเนินการ กรุณาลองอีกครั้ง';
    
    // If it's a gender-related error, provide specific message
    if (stripos($e->getMessage(), 'gender') !== false || stripos($e->getMessage(), 'truncated') !== false) {
        $userMessage = 'ไม่สามารถบันทึกข้อมูลได้ กรุณาตรวจสอบข้อมูลนักเรียนและลองใหม่อีกครั้ง';
    }
    
    echo json_encode(['success' => false, 'message' => $userMessage]);
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'ไม่พบคำสั่งที่ถูกต้อง']);
?>
