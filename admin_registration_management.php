<?php
// Start the session
session_start();

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', './debug.log');

// Check if the user is logged in and is Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

include("./config/db.php");

// Handle AJAX requests
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json');
    
    // Debug logging
    error_log("AJAX Request received: " . print_r($_POST, true));
    
    try {
        // Handle single status update
        if (isset($_POST['update_status'])) {
            error_log("Processing single status update");
            
            $registrationId = (int)$_POST['registration_id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            $userId = $_SESSION['user_id'] ?? 1; // Default to 1 if session user_id not set
            
            error_log("Registration ID: $registrationId, Status: $status, User ID: $userId");
            
            if (empty($registrationId) || empty($status)) {
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }
            
            // Update registration status
            $stmt = $conn->prepare("UPDATE registration SET registration_status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $registrationId);
            $result = $stmt->execute();
            
            error_log("Update result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                // Log the action
                $logStmt = $conn->prepare("INSERT INTO registration_actions (registration_id, action, message, user_id) VALUES (?, ?, ?, ?)");
                $logMessage = "Status changed to: $status. Notes: $notes";
                $logStmt->bind_param("issi", $registrationId, 'status_change', $logMessage, $userId);
                $logStmt->execute();
                
                // If approved, create student record
                if ($status == 'approved') {
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
                    
                    if ($registration && $registration['registration_type'] == 'new') {
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
                            $insertStmt->execute();
                            error_log("Student record created for registration ID: $registrationId");
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'อัพเดทสถานะสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพเดทสถานะ: ' . $conn->error]);
            }
            exit;
        }
        
        // Handle bulk status update
        if (isset($_POST['bulk_update'])) {
            $registrationIds = $_POST['registration_ids'] ?? [];
            $status = $_POST['bulk_status'];
            $notes = $_POST['bulk_notes'] ?? '';
            $userId = $_SESSION['user_id'];
            
            if (empty($registrationIds)) {
                echo json_encode(['success' => false, 'message' => 'ไม่ได้เลือกรายการ']);
                exit;
            }
            
            $successCount = 0;
            $totalCount = count($registrationIds);
            
            foreach ($registrationIds as $registrationId) {
                $registrationId = (int)$registrationId;
                
                // Update registration status
                $stmt = $conn->prepare("UPDATE registration SET registration_status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $registrationId);
                $result = $stmt->execute();
                
                if ($result) {
                    $successCount++;
                    
                    // Log the action
                    $logStmt = $conn->prepare("INSERT INTO registration_actions (registration_id, action, message, user_id) VALUES (?, ?, ?, ?)");
                    $logMessage = "Bulk status changed to: $status. Notes: $notes";
                    $logStmt->bind_param("issi", $registrationId, 'bulk_status_change', $logMessage, $userId);
                    $logStmt->execute();
                    
                    // If approved, create student record
                    if ($status == 'approved') {
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
                        
                        if ($registration && $registration['registration_type'] == 'new') {
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
                                $insertStmt->execute();
                            }
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => "อัพเดทสำเร็จ $successCount จาก $totalCount รายการ"]);
            exit;
        }
        
        // Handle registration deletion
        if (isset($_POST['delete_registration'])) {
            $registrationId = (int)$_POST['registration_id'];
            $userId = $_SESSION['user_id'];
            
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
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'ลบการลงทะเบียนสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ']);
            }
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Set page-specific variables
$pageTitle = "Admin Registration Management - School Management System";
$activePage = "admin_registrations";
$contentPath = "content/admin_registration_management.php";

// Page specific CSS
$pageSpecificCSS = '
<link href="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css" rel="stylesheet">
<link href="assets/css/registrations.css" rel="stylesheet">
<style>
.registration-status-badge {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}
.action-buttons .btn {
    margin: 0.1rem;
}
.admin-actions {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
    margin-bottom: 1rem;
}
.quick-stats {
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
}
.status-filters .btn {
    margin: 0.2rem;
}
</style>';

// Page specific scripts
$pageSpecificScripts = '
<script src="sb-admin-2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/admin-registration-management.js"></script>';

include("includes/layout.php");
?>
