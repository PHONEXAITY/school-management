<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function debugLog($message) {
    error_log("[REGISTRATION DEBUG] " . print_r($message, true));
}

debugLog("Starting registration process");
debugLog("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
debugLog("POST data: " . print_r($_POST, true));
debugLog("FILES data: " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Invalid request method");
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    debugLog("Loading database connection");
    
    $configFiles = [
        '../../config/db_pdo.php',
        '../../config/db_pdo.php',
        '../config/db.php',
        '../config/db_pdo.php'
    ];
    
    $dbLoaded = false;
    foreach ($configFiles as $configFile) {
        if (file_exists($configFile)) {
            debugLog("Loading config file: " . $configFile);
            require_once $configFile;
            $dbLoaded = true;
            break;
        }
    }
    
    if (!$dbLoaded) {
        throw new Exception('Database configuration file not found');
    }
    
    debugLog("Database connection established");
    
    $uploadDir = '../../uploads/payment_slips/';
    if (!is_dir($uploadDir)) {
        debugLog("Creating upload directory: " . $uploadDir);
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Cannot create upload directory');
        }
    }
    
    $registrationType = $_POST['registration_type'] ?? 'existing';
    $parentName = $_POST['parent_name'] ?? '';
    $parentPhone = $_POST['parent_phone'] ?? '';
    $parentEmail = $_POST['parent_email'] ?? '';
    $address = $_POST['address'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $transferDate = $_POST['transfer_date'] ?? '';
    $transferTime = $_POST['transfer_time'] ?? '';
    $transferAmount = $_POST['transfer_amount'] ?? 0;
    
    debugLog("Registration type: " . $registrationType);
    debugLog("Parent name: " . $parentName);
    
    if (empty($parentName) || empty($parentPhone) || empty($relationship) || 
        empty($transferDate) || empty($transferTime) || empty($transferAmount)) {
        throw new Exception('ກະລຸນາປ້ອນຂໍ້ມູນຜູ້ປົກຄອງແລະການຊຳລະເງີນໃຫ້ຄົບຖ້ວນ');
    }
    
    debugLog("Parent/payment validation passed");
    
    if (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] !== UPLOAD_ERR_OK) {
        debugLog("Payment slip error: " . ($_FILES['payment_slip']['error'] ?? 'not set'));
        throw new Exception('ກະລຸນາອັບໂຫລດຫຼັກຖານການໂອນເງີນ');
    }
    
    $file = $_FILES['payment_slip'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    
    debugLog("File type: " . $file['type']);
    debugLog("File size: " . $file['size']);
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('ໄຟລ໌ຕ້ອງເປັນຮູບພາບ (JPG, PNG) ເທົ່ານັ້ນ');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('ຂະໜາດໄຟລ໌ຕ້ອງບໍ່ເກີນ 5MB');
    }
    
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('payment_') . '_' . date('Y-m-d_H-i-s') . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    debugLog("Uploading file to: " . $filePath);
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('ບໍ່ສາມາດອັບໂຫລດໄຟລ໌ໄດ້');
    }
    
    debugLog("File uploaded successfully");

    $studentId = null;
    
    if ($registrationType === 'existing') {
        debugLog("Processing existing student registration");
        
        $studentId = $_POST['student_id'] ?? '';
        if (empty($studentId)) {
            throw new Exception('ກະລຸນາເລືອກນັກຮຽນເກົ່າ');
        }
        
        if (isset($conn) && $conn instanceof PDO) {
            debugLog("Using PDO connection");
            $stmt = $conn->prepare("SELECT id FROM student WHERE id = ?");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception('ບໍ່ພົບຂໍ້ມູນນັກຮຽນ');
            }
        } else {
            debugLog("Using mysqli connection");
            $checkStmt = $conn->prepare("SELECT id FROM student WHERE id = ?");
            $checkStmt->bind_param("s", $studentId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('ບໍ່ພົບຂໍ້ມູນນັກຮຽນ');
            }
        }
        
    } else {
        debugLog("Processing new student registration");
        
        $fname = $_POST['student_fname'] ?? '';
        $lname = $_POST['student_lname'] ?? '';
        $gender = $_POST['student_gender'] ?? '';
        $birthDate = $_POST['student_birth_date'] ?? '';
        $village = $_POST['student_village'] ?? '';
        $district = $_POST['student_district'] ?? '';
        $province = $_POST['student_province'] ?? '';
        $classId = $_POST['class_id'] ?? '';
        
        debugLog("New student data - fname: $fname, lname: $lname, gender: $gender, birthDate: $birthDate");
        debugLog("Address - village: $village, district: $district, province: $province");
        debugLog("Class ID: $classId");
        
        if (empty($fname) || empty($lname) || empty($gender) || empty($birthDate) || 
            empty($village) || empty($district) || empty($province) || empty($classId)) {
            throw new Exception('ກະລຸນາປ້ອນຂໍ້ມູນນັກຮຽນໃໝ່ໃຫ້ຄົບຖ້ວນ');
        }
        
        $studentId = 'NEW_' . uniqid();
        debugLog("Generated student ID: " . $studentId);
    }
    
    debugLog("Student ID: " . $studentId);
    
    if (isset($conn) && $conn instanceof PDO) {
        debugLog("Checking pending registrations with PDO");
        $stmt = $conn->prepare("SELECT id FROM registration WHERE student_id = ? AND registration_status = 'pending'");
        $stmt->execute([$studentId]);
        $regResult = $stmt->fetch();
        
        if ($regResult) {
            throw new Exception('ນັກຮຽນຄົນນີ້ມີການລົງທະບຽນທີ່ລໍການອະນຸມັດຢູ່ແລ້ວ');
        }
    } else {
        debugLog("Checking pending registrations with mysqli");
        $checkRegStmt = $conn->prepare("SELECT id FROM registration WHERE student_id = ? AND registration_status = 'pending'");
        $checkRegStmt->bind_param("s", $studentId);
        $checkRegStmt->execute();
        $regResult = $checkRegStmt->get_result();
        
        if ($regResult->num_rows > 0) {
            throw new Exception('ນັກຮຽນຄົນນີ້ມີການລົງທະບຽນທີ່ລໍການອະນຸມັດຢູ່ແລ້ວ');
        }
    }
    
    debugLog("No pending registrations found");

    $slipPath = 'uploads/payment_slips/' . $fileName;
    
    if (isset($conn) && $conn instanceof PDO) {
        debugLog("Inserting registration with PDO");
        
        $stmt = $conn->prepare("
            INSERT INTO registration (
                student_id, parent_name, parent_phone, parent_email, address, 
                relationship, transfer_date, transfer_time, transfer_amount, 
                payment_slip_path, registration_status, registration_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        
        $result = $stmt->execute([
            $studentId, $parentName, $parentPhone, $parentEmail, $address,
            $relationship, $transferDate, $transferTime, $transferAmount, $slipPath, $registrationType
        ]);
        
        if (!$result) {
            throw new Exception('ບໍ່ສາມາດບັນທຶກຂໍ້ມູນການລົງທະບຽນໄດ້');
        }
        
        $registrationId = $conn->lastInsertId();
        
    } else {
        debugLog("Inserting registration with mysqli");
        
        $insertStmt = $conn->prepare("
            INSERT INTO registration (
                student_id, parent_name, parent_phone, parent_email, address, 
                relationship, transfer_date, transfer_time, transfer_amount, 
                payment_slip_path, registration_status, registration_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        
        $insertStmt->bind_param("ssssssssdss", 
            $studentId, $parentName, $parentPhone, $parentEmail, $address,
            $relationship, $transferDate, $transferTime, $transferAmount, $slipPath, $registrationType
        );
        
        if (!$insertStmt->execute()) {
            throw new Exception('ບໍ່ສາມາດບັນທຶກຂໍ້ມູນການລົງທະບຽນໄດ້');
        }
        
        $registrationId = $conn->insert_id;
    }
    
    debugLog("Registration inserted with ID: " . $registrationId);

    if ($registrationType === 'new') {
        debugLog("Inserting new student data");
        
        if (isset($conn) && $conn instanceof PDO) {
            $stmt = $conn->prepare("
                INSERT INTO new_student_registration (
                    registration_id, fname, lname, gender, birth_date, 
                    village, district, province, parent_name, phone, class_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $registrationId, $fname, $lname, $gender, $birthDate,
                $village, $district, $province, $parentName, $parentPhone, $classId
            ]);
            
            if (!$result) {
                throw new Exception('ບໍ່ສາມາດບັນທຶກຂໍ້ມູນນັກຮຽນໃໝ່ໄດ້');
            }
            
        } else {
            $newStudentStmt = $conn->prepare("
                INSERT INTO new_student_registration (
                    registration_id, fname, lname, gender, birth_date, 
                    village, district, province, parent_name, phone, class_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $newStudentStmt->bind_param("issssssssss",
                $registrationId, $fname, $lname, $gender, $birthDate,
                $village, $district, $province, $parentName, $parentPhone, $classId
            );
            
            if (!$newStudentStmt->execute()) {
                throw new Exception('ບໍ່ສາມາດບັນທຶກຂໍ້ມູນນັກຮຽນໃໝ່ໄດ້');
            }
        }
        
        debugLog("New student data inserted successfully");
    }
    
    $message = $registrationType === 'existing' 
        ? 'ສົ່ງໃບສະໝັກສຳເລັດແລ້ວ! ທາງໂຮງຮຽນຈະກວດສອບການຊຳລະເງີນແລະຕິດຕໍ່ກັບພາຍໃນ 24 ຊົ່ວໂມງ'
        : 'ສົ່ງໃບສະໝັກນັກຮຽນໃໝ່ສຳເລັດແລ້ວ! ທາງໂຮງຮຽນຈະກວດສອບຂໍ້ມູນແລະການຊຳລະເງີນ ແລະຕິດຕໍ່ກັບພາຍໃນ 24 ຊົ່ວໂມງ';
    
    debugLog("Registration completed successfully");
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'registration_id' => $registrationId,
        'registration_type' => $registrationType
    ]);
    
} catch (Exception $e) {
    debugLog("Error occurred: " . $e->getMessage());
    debugLog("Stack trace: " . $e->getTraceAsString());
    
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
        debugLog("Cleaned up uploaded file");
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    if ($conn instanceof PDO) {
        $conn = null;
    } else {
        $conn->close();
    }
    debugLog("Database connection closed");
}

debugLog("Registration process ended");
?>