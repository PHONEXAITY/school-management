<?php
ob_start(); // Start output buffering

// Ensure database connection
include 'config/db.php';

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
            
            // Clean output buffer before redirect
            if (ob_get_length()) {
                ob_end_clean();
            }
            
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
                        
                        // Map gender value (M/F to Male/Female)
                        $gender = $new_student['gender'] === 'M' ? 'Male' : 'Female';
                        
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
                        
                        // Create notification message
                        $notification_message = "ການລົງທະບຽນຂອງ {$new_student['fname']} {$new_student['lname']} ໄດ້ຮັບການອະນຸມັດແລ້ວ. ລະຫັດນັກຮຽນຂອງທ່ານຄື {$new_student_id}";
                        
                        // Insert registration action log (if table exists)
                        $log_message = "New student registration approved: Student ID {$new_student_id} created";
                        $log_stmt = $conn->prepare("INSERT INTO registration_actions (registration_id, action, message, user_id) VALUES (?, ?, ?, ?)");
                        if ($log_stmt) {
                            $action_type = "approve_new_student";
                            $log_stmt->bind_param("issi", $reg_id, $action_type, $log_message, $admin_id);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }
                    }
                } else {
                    // Create notification message for existing student
                    $notification_message = $action === 'approve' 
                        ? "ການລົງທະບຽນຂອງນັກຮຽນຂອງທ່ານໄດ້ຮັບການອະນຸມັດແລ້ວ." 
                        : "ການລົງທະບຽນຂອງນັກຮຽນຂອງທ່ານໄດ້ຖືກປະຕິເສດ. ເຫດຜົນ: {$notes}";
                    
                    // Log for existing student registration (if table exists)
                    $log_message = $action === 'approve' 
                        ? "Existing student registration approved: Student ID {$registration['student_id']}"
                        : "Registration rejected: {$notes}";
                        
                    $log_stmt = $conn->prepare("INSERT INTO registration_actions (registration_id, action, message, user_id) VALUES (?, ?, ?, ?)");
                    if ($log_stmt) {
                        $action_type = $action === 'approve' ? "approve_existing" : "reject";
                        $log_stmt->bind_param("issi", $reg_id, $action_type, $log_message, $admin_id);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }
                }
                
                // Try to send notification using the API (optional - won't break if fails)
                try {
                    $notification_data = json_encode([
                        'registration_id' => $reg_id,
                        'status' => $status,
                        'message' => $notification_message,
                        'user_id' => $admin_id
                    ]);
                    
                    $ch = curl_init('http://localhost/school-management/frontend/api/notify_registration_status.php');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $notification_data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($notification_data)
                    ]);
                    
                    $response = curl_exec($ch);
                    curl_close($ch);
                } catch (Exception $e) {
                    // Ignore notification errors - don't fail the whole process
                }
                
                // Commit transaction
                $conn->commit();
                
                // Set success message
                $alertClass = $action === 'approve' ? 'success' : 'warning';
                $alertMessage = $action === 'approve' 
                    ? 'ການລົງທະບຽນຖືກອະນຸມັດສຳເລັດແລ້ວ' 
                    : 'ການລົງທະບຽນຖືກປະຕິເສດແລ້ວ';
                
                $_SESSION['alert'] = [
                    'class' => $alertClass,
                    'message' => $alertMessage
                ];
                
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
    
    // Clean output buffer before redirect
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    header("Location: registrations.php");
    exit;
}

// Show alert if set
if (isset($_SESSION['alert'])) {
    $alertClass = $_SESSION['alert']['class'];
    $alertMessage = $_SESSION['alert']['message'];
    echo "<div class='alert alert-$alertClass alert-dismissible fade show' role='alert'>
            $alertMessage
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
          </div>";
    unset($_SESSION['alert']);
}

// Define filter variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$status_options = ['pending', 'approved', 'rejected', 'all'];

if (!in_array($filter_status, $status_options)) {
    $filter_status = 'pending';
}

// Build SQL query based on filter
$sql = "
    SELECT r.*, 
           CASE 
               WHEN r.registration_type = 'new' THEN CONCAT(COALESCE(n.fname, ''), ' ', COALESCE(n.lname, ''), ' (ນັກຮຽນໃໝ່)')
               ELSE CONCAT(COALESCE(s.fname, ''), ' ', COALESCE(s.lname, ''), ' (ID: ', COALESCE(r.student_id, ''), ')')
           END as student_name,
           u.username as approved_by_name,
           CASE
               WHEN r.registration_type = 'new' THEN c.name
               ELSE existing_class.name
           END as class_name,
           y.name as academic_year_name
    FROM registration r
    LEFT JOIN new_student_registration n ON r.id = n.registration_id
    LEFT JOIN student s ON r.student_id = s.id AND r.registration_type = 'existing'
    LEFT JOIN class c ON n.class_id = c.id
    LEFT JOIN class existing_class ON s.class_id = existing_class.id
    LEFT JOIN user u ON r.approved_by = u.id
    LEFT JOIN year y ON r.academic_year_id = y.id
";

if ($filter_status !== 'all') {
    $sql .= " WHERE r.registration_status = '$filter_status'";
}

$sql .= " ORDER BY r.registration_date DESC";

$registrations_result = $conn->query($sql);

// Set filter buttons class based on selected filter
$btn_class = [
    'pending' => 'btn-secondary',
    'approved' => 'btn-secondary',
    'rejected' => 'btn-secondary',
    'all' => 'btn-secondary'
];
$btn_class[$filter_status] = 'btn-primary';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">ຈັດການການລົງທະບຽນຂອງນັກຮຽນ</h1>
</div>

<!-- Filter Options -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ຕົວກອງຂໍ້ມູນ</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="font-weight-bold mb-2">ສະຖານະການລົງທະບຽນ:</label>
                    <div class="btn-group d-block">
                        <a href="registrations.php?status=pending" class="btn <?php echo $btn_class['pending']; ?>">
                            <i class="fas fa-clock"></i> ລໍຖ້າການອະນຸມັດ 
                            <?php
                            $pending_count_sql = "SELECT COUNT(*) as count FROM registration WHERE registration_status = 'pending'";
                            $pending_count_result = $conn->query($pending_count_sql);
                            if ($pending_count_result && $pending_count_row = $pending_count_result->fetch_assoc()) {
                                $pending_count = (int)$pending_count_row['count'];
                                if ($pending_count > 0) {
                                    echo "<span class='badge badge-light'>{$pending_count}</span>";
                                }
                            }
                            ?>
                        </a>
                        <a href="registrations.php?status=approved" class="btn <?php echo $btn_class['approved']; ?>">
                            <i class="fas fa-check-circle"></i> ອະນຸມັດແລ້ວ
                        </a>
                        <a href="registrations.php?status=rejected" class="btn <?php echo $btn_class['rejected']; ?>">
                            <i class="fas fa-times-circle"></i> ປະຕິເສດແລ້ວ
                        </a>
                        <a href="registrations.php?status=all" class="btn <?php echo $btn_class['all']; ?>">
                            <i class="fas fa-list"></i> ທັງໝົດ
                        </a>
                    </div>
                    <input type="hidden" id="currentStatus" value="<?php echo $filter_status; ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="font-weight-bold mb-2">ກອງຕາມວັນທີລົງທະບຽນ:</label>
                    <div class="form-row">
                        <div class="col">
                            <input type="date" id="startDate" class="form-control" 
                                value="<?php echo isset($_GET['start']) ? $_GET['start'] : ''; ?>" 
                                placeholder="ວັນທີເລີ່ມຕົ້ນ">
                        </div>
                        <div class="col">
                            <input type="date" id="endDate" class="form-control" 
                                value="<?php echo isset($_GET['end']) ? $_GET['end'] : ''; ?>" 
                                placeholder="ວັນທີສິ້ນສຸດ">
                        </div>
                        <div class="col-auto">
                            <button id="filterDates" class="btn btn-primary">
                                <i class="fas fa-filter"></i> ກອງ
                            </button>
                            <button id="resetFilter" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> ລ້າງ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Show statistics
        $stats_sql = "SELECT 
            SUM(CASE WHEN registration_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN registration_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN registration_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN registration_type = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN registration_type = 'existing' THEN 1 ELSE 0 END) as existing_count,
            COUNT(*) as total_count
          FROM registration";
          
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();
        ?>
        
        <div class="row mt-3">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    ລໍຖ້າການອະນຸມັດ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_count']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    ອະນຸມັດແລ້ວ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['approved_count']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    ປະຕິເສດແລ້ວ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['rejected_count']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    ທັງໝົດ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_count']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Registration List Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">ລາຍການລົງທະບຽນ <?php echo $filter_status === 'all' ? 'ທັງໝົດ' : ($filter_status === 'pending' ? 'ທີ່ລໍຖ້າການອະນຸມັດ' : ($filter_status === 'approved' ? 'ທີ່ອະນຸມັດແລ້ວ' : 'ທີ່ປະຕິເສດແລ້ວ')); ?></h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                <div class="dropdown-header">ຕົວເລືອກ:</div>
                <a class="dropdown-item" href="#" onclick="window.print()">
                    <i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i>
                    ພິມລາຍການ
                </a>
                <a class="dropdown-item" href="#" id="exportCsv">
                    <i class="fas fa-file-csv fa-sm fa-fw mr-2 text-gray-400"></i>
                    ສົ່ງອອກເປັນ CSV
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="registrations.php">
                    <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                    ໂຫລດຂໍ້ມູນຄືນໃໝ່
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="registrationsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">ລະຫັດ</th>
                        <th width="15%">ນັກຮຽນ</th>
                        <th width="7%">ປະເພດ</th>
                        <th width="9%">ວັນທີລົງທະບຽນ</th>
                        <th width="11%">ຊື່ຜູ້ປົກຄອງ</th>
                        <th width="9%">ຫ້ອງຮຽນ</th>
                        <th width="9%">ປີການຮຽນ</th>
                        <th width="7%">ຈຳນວນເງີນ</th>
                        <th width="7%">ສະຖານະ</th>
                        <th width="9%">ຜູ້ອະນຸມັດ</th>
                        <th width="12%">ຈັດການ</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($registrations_result && $registrations_result->num_rows > 0) {
                    while($row = $registrations_result->fetch_assoc()) {
                        // Format status for display with different icons and colors
                        switch ($row['registration_status']) {
                            case 'pending':
                                $status = '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> ລໍຖ້າການອະນຸມັດ</span>';
                                $row_class = 'table-warning';
                                break;
                            case 'approved':
                                $status = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> ອະນຸມັດແລ້ວ</span>';
                                $row_class = '';
                                break;
                            case 'rejected':
                                $status = '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> ປະຕິເສດ</span>';
                                $row_class = 'table-danger';
                                break;
                            default:
                                $status = '<span class="badge badge-secondary"><i class="fas fa-question-circle mr-1"></i> ບໍ່ຮູ້ຈັກ</span>';
                                $row_class = '';
                        }
                        
                        // Format registration type for display
                        $type = $row['registration_type'] === 'new' 
                            ? '<span class="badge badge-info"><i class="fas fa-user-plus mr-1"></i> ນັກຮຽນໃໝ່</span>' 
                            : '<span class="badge badge-primary"><i class="fas fa-user mr-1"></i> ນັກຮຽນເກົ່າ</span>';
                        
                        // Format date
                        $reg_date = new DateTime($row['registration_date']);
                        $formatted_date = $reg_date->format('d/m/Y H:i');
                        
                        // Prepare student details for modal - Fix null parameter issue
                        $student_name = $row['student_name'] ?? '';
                        $student_parts = $student_name ? explode(' (', $student_name) : ['ບໍ່ມີຂໍ້ມູນ'];
                        $student_base_name = $student_parts[0] ?? 'ບໍ່ມີຂໍ້ມູນ';
                        
                        // Get additional student data for modal
                        $student_data = [];
                        if ($row['registration_type'] === 'new') {
                            // Get new student registration details
                            $stmt = $conn->prepare("
                                SELECT * FROM new_student_registration 
                                WHERE registration_id = ?
                            ");
                            $stmt->bind_param("i", $row['id']);
                            $stmt->execute();
                            $student_data = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                        } else {
                            // Get existing student details
                            $stmt = $conn->prepare("
                                SELECT * FROM student 
                                WHERE id = ?
                            ");
                            $stmt->bind_param("s", $row['student_id']);
                            $stmt->execute();
                            $student_data = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                        }
                        
                        $student_json = json_encode($student_data);
                        
                        echo "<tr class='{$row_class}'>
                            <td>{$row['id']}</td>
                            <td>
                                <a href='#' class='view-student font-weight-bold' 
                                   data-student='{$student_json}' 
                                   data-name='{$student_base_name}'
                                   data-type='{$row['registration_type']}'
                                   data-class='{$row['class_name']}'>
                                    {$student_base_name}
                                </a>
                            </td>
                            <td>{$type}</td>
                            <td>{$formatted_date}</td>
                            <td>
                                {$row['parent_name']}
                                <div class='small'>
                                    <i class='fas fa-phone'></i> {$row['parent_phone']}
                                </div>
                            </td>
                            <td>{$row['class_name']}</td>
                            <td>
                                <span class='badge badge-secondary'>
                                    <i class='fas fa-calendar-alt mr-1'></i>
                                    {$row['academic_year_name']}
                                </span>
                            </td>
                            <td class='text-right'>" . number_format($row['transfer_amount'], 0, '.', ',') . " ກີບ</td>
                            <td class='text-center'>{$status}</td>
                            <td>";
                            
                        if (!empty($row['approved_by_name'])) {
                            echo $row['approved_by_name'] . "<div class='small'>";
                            
                            if (!empty($row['approved_date'])) {
                                $approved_date = new DateTime($row['approved_date']);
                                echo $approved_date->format('d/m/Y H:i');
                            }
                            
                            echo "</div>";
                        } else {
                            echo "<span class='text-muted'>--</span>";
                        }
                        
                        echo "</td>
                            <td>";
                            
                        // Action buttons based on status
                        if ($row['registration_status'] === 'pending') {
                            echo "<div class='btn-group'>
                                <button class='btn btn-primary btn-sm view-payment' data-slip='{$row['payment_slip_path']}'>
                                    <i class='fas fa-receipt'></i> ໃບຮັບເງີນ
                                </button>
                                <button class='btn btn-success btn-sm approve-btn' 
                                    data-id='{$row['id']}' 
                                    data-type='{$row['registration_type']}'
                                    data-name='{$student_base_name}'>
                                    <i class='fas fa-check'></i> ອະນຸມັດ
                                </button>
                                <button class='btn btn-danger btn-sm reject-btn' 
                                    data-id='{$row['id']}'
                                    data-name='{$student_base_name}'>
                                    <i class='fas fa-times'></i> ປະຕິເສດ
                                </button>
                            </div>";
                        } else {
                            echo "<div class='btn-group'>
                                <button class='btn btn-primary btn-sm view-payment' data-slip='{$row['payment_slip_path']}'>
                                    <i class='fas fa-receipt'></i> ໃບຮັບເງີນ
                                </button>";
                            
                            // Show notes for rejected registrations
                            if ($row['registration_status'] === 'rejected' && !empty($row['notes'])) {
                                echo "<button class='btn btn-info btn-sm' data-toggle='tooltip' title='{$row['notes']}'>
                                    <i class='fas fa-comment'></i> ເຫດຜົນ
                                </button>";
                            }
                            
                            echo "</div>";
                        }
                            
                        echo "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='10' class='text-center py-5'>
                        <div class='text-gray-500 mb-2'><i class='fas fa-folder-open fa-3x'></i></div>
                        <h5>ບໍ່ມີຂໍ້ມູນການລົງທະບຽນ</h5>
                        <p class='text-muted'>ບໍ່ພົບຂໍ້ມູນການລົງທະບຽນທີ່ກົງກັບເງື່ອນໄຂທີ່ເລືອກ</p>
                    </td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hidden form for actions -->
<form id="actionForm" action="registrations.php" method="post" style="display: none;">
    <input type="hidden" name="action_type" id="action_type" value="">
    <input type="hidden" name="registration_id" id="registration_id" value="">
    <input type="hidden" name="notes" id="notes" value="">
</form>

<!-- Payment Slip Modal -->
<div class="modal fade" id="paymentSlipModal" tabindex="-1" role="dialog" aria-labelledby="paymentSlipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentSlipModalLabel">ຫຼັກຖານການໂອນເງີນ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3 btn-group">
                    <button id="zoomIn" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search-plus"></i> ຂະຫຍາຍ
                    </button>
                    <button id="resetZoom" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-redo"></i> ຄືນເດີມ
                    </button>
                    <button id="zoomOut" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search-minus"></i> ຫຍໍ້
                    </button>
                </div>
                <div class="slip-container" style="overflow: auto; max-height: 70vh;">
                    <img id="paymentSlipImg" src="" class="img-fluid" style="transition: transform 0.2s ease; transform-origin: center center;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ປິດ</button>
                <a id="downloadSlip" href="#" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> ດາວໂຫລດ
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentDetailsModalLabel">ລາຍລະອຽດນັກຮຽນ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="studentDetailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
echo $content;
?>
