<?php
session_start();

// Ensure database connection
include 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Process approval/rejection action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type'])) {
    $action = $_POST['action_type'];
    $reg_id = $_POST['registration_id'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    $admin_id = $_SESSION['user_id'] ?? 0;
    
    if ($reg_id > 0 && $admin_id > 0) {
        // Verify admin user exists
        $admin_check = $conn->prepare("SELECT id FROM user WHERE id = ? AND role = 'admin'");
        $admin_check->bind_param("i", $admin_id);
        $admin_check->execute();
        $admin_exists = $admin_check->get_result()->num_rows > 0;
        $admin_check->close();
        
        if (!$admin_exists) {
            $_SESSION['alert'] = [
                'class' => 'danger',
                'message' => 'ຂໍ້ຜິດພາດ: ບໍ່ພົບຜູ້ໃຊ້ admin ຫຼື ບໍ່ມີສິດອະນຸຍາດ'
            ];
            header("Location: registrations.php");
            exit;
        }
        
        // Get registration data first
        $stmt = $conn->prepare("SELECT * FROM registration WHERE id = ?");
        $stmt->bind_param("i", $reg_id);
        $stmt->execute();
        $registration = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($registration) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Update registration status
                $stmt = $conn->prepare("
                    UPDATE registration 
                    SET registration_status = ?, approved_by = ?, approved_date = NOW(), notes = ?
                    WHERE id = ?
                ");
                $status = $action === 'approve' ? 'approved' : 'rejected';
                $stmt->bind_param("sisi", $status, $admin_id, $notes, $reg_id);
                $stmt->execute();
                $stmt->close();
                
                // For approval of new student registrations, create student record
                if ($action === 'approve' && $registration['registration_type'] === 'new') {
                    // Get new student data
                    $stmt = $conn->prepare("SELECT * FROM new_student_registration WHERE registration_id = ?");
                    $stmt->bind_param("i", $reg_id);
                    $stmt->execute();
                    $new_student = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($new_student) {
                        // Insert into student table
                        $stmt = $conn->prepare("
                            INSERT INTO student (fname, lname, gender, birth_date, village, district, province, parent_name, phone, class_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        // Gender is already in the correct format, no need for conversion
                        $gender = $new_student['gender'];
                        
                        $stmt->bind_param(
                            "ssssssssss",
                            $new_student['fname'],
                            $new_student['lname'],
                            $gender,
                            $new_student['birth_date'],
                            $new_student['village'],
                            $new_student['district'],
                            $new_student['province'],
                            $new_student['parent_name'],
                            $new_student['phone'],
                            $new_student['class_id']
                        );
                        $stmt->execute();
                        $new_student_id = $conn->insert_id;
                        $stmt->close();
                        
                        // Update new_student_registration as processed
                        $stmt = $conn->prepare("UPDATE new_student_registration SET is_processed = 1 WHERE id = ?");
                        $stmt->bind_param("i", $new_student['id']);
                        $stmt->execute();
                        $stmt->close();
                        
                        $_SESSION['alert'] = [
                            'class' => 'success',
                            'message' => 'ອະນຸມັດການລົງທະບຽນສຳເລັດ! ນັກຮຽນໃໝ່ໄດ້ຖືກເພີ່ມໃສ່ລະບົບແລ້ວ (ID: ' . $new_student_id . ')'
                        ];
                    } else {
                        $_SESSION['alert'] = [
                            'class' => 'warning',
                            'message' => 'ອະນຸມັດການລົງທະບຽນສຳເລັດ! ແຕ່ບໍ່ພົບຂໍ້ມູນນັກຮຽນ'
                        ];
                    }
                } else {
                    $alertMessage = $action === 'approve' ? 'ອະນຸມັດການລົງທະບຽນສຳເລັດ!' : 'ປະຕິເສດການລົງທະບຽນສຳເລັດ!';
                    $_SESSION['alert'] = [
                        'class' => 'success',
                        'message' => $alertMessage
                    ];
                }
                
                // Log the action
                $log_stmt = $conn->prepare("
                    INSERT INTO registration_actions (registration_id, admin_id, action_type, notes, action_date)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                if ($log_stmt) {
                    $log_stmt->bind_param("iiss", $reg_id, $admin_id, $action, $notes);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
                
                // Commit transaction
                $conn->commit();
                
                // Try to send notification (optional - won't break if it fails)
                try {
                    // Get student contact info for notification
                    $notification_data = [
                        'registration_id' => $reg_id,
                        'status' => $status,
                        'notes' => $notes
                    ];
                    
                    // Call notification API with timeout
                    $ch = curl_init('http://localhost/school-management/frontend/api/notify_registration_status.php');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
                    curl_exec($ch);
                    curl_close($ch);
                } catch (Exception $e) {
                    // Ignore notification errors - they shouldn't break the main process
                }
                
            } catch (Exception $e) {
                // If error occurs, rollback
                $conn->rollback();
                
                $_SESSION['alert'] = [
                    'class' => 'danger',
                    'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()
                ];
            }
        } else {
            $_SESSION['alert'] = [
                'class' => 'danger', 
                'message' => 'ຂໍ້ຜິດພາດ: ບໍ່ພົບຂໍ້ມູນການລົງທະບຽນ'
            ];
        }
    } else {
        $_SESSION['alert'] = [
            'class' => 'danger', 
            'message' => 'ຂໍ້ຜິດພາດ: ຂໍ້ມູນບໍ່ຖືກຕ້ອງ'
        ];
    }
    
    header("Location: registrations.php");
    exit;
}

// If not POST request, redirect back
header("Location: registrations.php");
exit;
?>
