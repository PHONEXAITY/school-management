<?php
ob_start();

include 'config/db.php';

// Handle registration status updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Update registration status
        if (isset($_POST['update_status'])) {
            $registration_id = intval($_POST['registration_id']);
            $new_status = $_POST['new_status'];
            $admin_notes = $_POST['admin_notes'] ?? '';
            $admin_id = $_SESSION['user_id'];
            
            // Begin transaction
            $conn->begin_transaction();
            
            // Update registration
            $stmt = $conn->prepare("
                UPDATE registration 
                SET registration_status = ?, 
                    approved_by = ?, 
                    approved_date = NOW(), 
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("sisi", $new_status, $admin_id, $admin_notes, $registration_id);
            $stmt->execute();
            
            // Log the action
            $action_message = "Status updated to: $new_status" . ($admin_notes ? " - Notes: $admin_notes" : "");
            $log_stmt = $conn->prepare("
                INSERT INTO registration_actions (registration_id, action, message, user_id, created_at) 
                VALUES (?, 'status_update', ?, ?, NOW())
            ");
            $log_stmt->bind_param("isi", $registration_id, $action_message, $admin_id);
            $log_stmt->execute();
            
            // If approved and it's a new student, create student record
            if ($new_status === 'approved') {
                $reg_stmt = $conn->prepare("SELECT * FROM registration WHERE id = ?");
                $reg_stmt->bind_param("i", $registration_id);
                $reg_stmt->execute();
                $registration = $reg_stmt->get_result()->fetch_assoc();
                
                if ($registration && $registration['registration_type'] === 'new') {
                    $new_student_stmt = $conn->prepare("
                        SELECT * FROM new_student_registration WHERE registration_id = ? AND is_processed = 0
                    ");
                    $new_student_stmt->bind_param("i", $registration_id);
                    $new_student_stmt->execute();
                    $new_student = $new_student_stmt->get_result()->fetch_assoc();
                    
                    if ($new_student) {
                        // Create student record
                        $create_student_stmt = $conn->prepare("
                            INSERT INTO student (fname, lname, gender, birth_date, village, district, province, parent_name, phone, class_id, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $gender = $new_student['gender'] === 'M' ? 'Male' : 'Female';
                        $create_student_stmt->bind_param(
                            "sssssssssi",
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
                        $create_student_stmt->execute();
                        $new_student_id = $conn->insert_id;
                        
                        // Mark as processed
                        $processed_stmt = $conn->prepare("
                            UPDATE new_student_registration SET is_processed = 1 WHERE id = ?
                        ");
                        $processed_stmt->bind_param("i", $new_student['id']);
                        $processed_stmt->execute();
                        
                        $response['student_id'] = $new_student_id;
                    }
                }
            }
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'อัพเดทสถานะสำเร็จแล้ว';
            
        }
        
        // Bulk status update
        if (isset($_POST['bulk_update'])) {
            $registration_ids = $_POST['registration_ids'];
            $bulk_status = $_POST['bulk_status'];
            $bulk_notes = $_POST['bulk_notes'] ?? '';
            $admin_id = $_SESSION['user_id'];
            
            if (!empty($registration_ids) && is_array($registration_ids)) {
                $conn->begin_transaction();
                
                $placeholders = str_repeat('?,', count($registration_ids) - 1) . '?';
                $stmt = $conn->prepare("
                    UPDATE registration 
                    SET registration_status = ?, 
                        approved_by = ?, 
                        approved_date = NOW(), 
                        notes = ?,
                        updated_at = NOW()
                    WHERE id IN ($placeholders)
                ");
                
                $types = 'sis' . str_repeat('i', count($registration_ids));
                $params = array_merge([$bulk_status, $admin_id, $bulk_notes], $registration_ids);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
                // Log bulk action
                foreach ($registration_ids as $reg_id) {
                    $log_message = "Bulk status update to: $bulk_status" . ($bulk_notes ? " - Notes: $bulk_notes" : "");
                    $log_stmt = $conn->prepare("
                        INSERT INTO registration_actions (registration_id, action, message, user_id, created_at) 
                        VALUES (?, 'bulk_update', ?, ?, NOW())
                    ");
                    $log_stmt->bind_param("isi", $reg_id, $log_message, $admin_id);
                    $log_stmt->execute();
                }
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'อัพเดทแบบเป็นกลุ่มสำเร็จแล้ว (' . count($registration_ids) . ' รายการ)';
            }
        }
        
        // Delete registration
        if (isset($_POST['delete_registration'])) {
            $registration_id = intval($_POST['registration_id']);
            
            $conn->begin_transaction();
            
            // Delete related records first
            $conn->query("DELETE FROM registration_actions WHERE registration_id = $registration_id");
            $conn->query("DELETE FROM new_student_registration WHERE registration_id = $registration_id");
            $conn->query("DELETE FROM registration WHERE id = $registration_id");
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'ลบการลงทะเบียนสำเร็จแล้ว';
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'เกิดข้อผิดพาด: ' . $e->getMessage();
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        $_SESSION['alert'] = [
            'class' => $response['success'] ? 'success' : 'danger',
            'message' => $response['message']
        ];
        header("Location: admin_registration_management.php");
        exit;
    }
}

// Handle AJAX GET requests for analytics
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action'])) {
    $response = ['success' => false, 'data' => []];
    
    try {
        switch ($_GET['action']) {
            case 'get_monthly_data':
                $year = intval($_GET['year'] ?? date('Y'));
                $monthly_query = "
                    SELECT 
                        MONTH(registration_date) as month,
                        COUNT(*) as count,
                        SUM(CASE WHEN registration_status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN registration_status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN registration_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM registration 
                    WHERE YEAR(registration_date) = ?
                    GROUP BY MONTH(registration_date)
                    ORDER BY MONTH(registration_date)
                ";
                $stmt = $conn->prepare($monthly_query);
                $stmt->bind_param("i", $year);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $monthly_data = [];
                while ($row = $result->fetch_assoc()) {
                    $monthly_data[] = $row;
                }
                
                $response['success'] = true;
                $response['monthly'] = $monthly_data;
                break;
                
            case 'get_analytics_data':
                // Get current statistics
                $stats_query = "
                    SELECT 
                        COUNT(*) as total_registrations,
                        SUM(CASE WHEN registration_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                        SUM(CASE WHEN registration_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                        SUM(CASE WHEN registration_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                    FROM registration
                ";
                $stats_result = $conn->query($stats_query);
                $stats = $stats_result->fetch_assoc();
                
                // Get monthly data
                $monthly_query = "
                    SELECT 
                        MONTH(registration_date) as month,
                        COUNT(*) as count,
                        SUM(CASE WHEN registration_status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN registration_status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN registration_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM registration 
                    WHERE YEAR(registration_date) = YEAR(CURDATE())
                    GROUP BY MONTH(registration_date)
                    ORDER BY MONTH(registration_date)
                ";
                $monthly_result = $conn->query($monthly_query);
                $monthly_data = [];
                while ($row = $monthly_result->fetch_assoc()) {
                    $monthly_data[] = $row;
                }
                
                // Get type data
                $type_query = "
                    SELECT 
                        registration_type,
                        COUNT(*) as count
                    FROM registration
                    GROUP BY registration_type
                    ORDER BY count DESC
                ";
                $type_result = $conn->query($type_query);
                $type_data = [];
                while ($row = $type_result->fetch_assoc()) {
                    $type_data[] = $row;
                }
                
                $response['success'] = true;
                $response['analytics'] = [
                    'monthly' => $monthly_data,
                    'status' => [
                        'approved' => $stats['approved_count'],
                        'pending' => $stats['pending_count'],
                        'rejected' => $stats['rejected_count']
                    ],
                    'types' => $type_data
                ];
                break;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Show alert if set
if (isset($_SESSION['alert'])) {
    echo "<div class='alert alert-{$_SESSION['alert']['class']} alert-dismissible fade show' role='alert'>
            {$_SESSION['alert']['message']}
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
          </div>";
    unset($_SESSION['alert']);
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_term = $_GET['search'] ?? '';

// Build where conditions
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "r.registration_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($type_filter !== 'all') {
    $where_conditions[] = "r.registration_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if ($date_from) {
    $where_conditions[] = "DATE(r.registration_date) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(r.registration_date) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if ($search_term) {
    $where_conditions[] = "(
        CONCAT(IFNULL(n.fname, ''), ' ', IFNULL(n.lname, '')) LIKE ? OR 
        CONCAT(IFNULL(s.fname, ''), ' ', IFNULL(s.lname, '')) LIKE ? OR
        r.parent_name LIKE ? OR
        r.parent_phone LIKE ?
    )";
    $search_like = "%$search_term%";
    $params = array_merge($params, [$search_like, $search_like, $search_like, $search_like]);
    $types .= 'ssss';
}

$where_clause = $where_conditions ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

// Get registrations data
$sql = "
    SELECT r.*, 
           CASE 
               WHEN r.registration_type = 'new' THEN CONCAT(n.fname, ' ', n.lname)
               ELSE CONCAT(s.fname, ' ', s.lname)
           END as student_name,
           CASE 
               WHEN r.registration_type = 'new' THEN n.fname
               ELSE s.fname
           END as fname,
           CASE 
               WHEN r.registration_type = 'new' THEN n.lname
               ELSE s.lname
           END as lname,
           CASE
               WHEN r.registration_type = 'new' THEN c.name
               ELSE existing_class.name
           END as class_name,
           u.username as approved_by_name,
           y.name as academic_year_name
    FROM registration r
    LEFT JOIN new_student_registration n ON r.id = n.registration_id
    LEFT JOIN student s ON r.student_id = s.id AND r.registration_type = 'existing'
    LEFT JOIN class c ON n.class_id = c.id
    LEFT JOIN class existing_class ON s.class_id = existing_class.id
    LEFT JOIN user u ON r.approved_by = u.id
    LEFT JOIN year y ON r.academic_year_id = y.id
    $where_clause
    ORDER BY r.registration_date DESC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$registrations = $stmt->get_result();

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN registration_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN registration_status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN registration_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN registration_type = 'new' THEN 1 ELSE 0 END) as new_students,
        SUM(CASE WHEN registration_type = 'existing' THEN 1 ELSE 0 END) as existing_students
    FROM registration r
    $where_clause
";

$stats_stmt = $conn->prepare($stats_sql);
if ($params) {
    $stats_stmt->bind_param($types, ...$params);
}
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-user-graduate"></i> จัดการการลงทะเบียนนักเรียน (Admin)
    </h1>
    <div class="btn-group">
        <button class="btn btn-primary" data-toggle="modal" data-target="#bulkActionModal">
            <i class="fas fa-tasks"></i> การดำเนินการแบบกลุ่ม
        </button>
        <button class="btn btn-info" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> รีเฟรช
        </button>
    </div>
</div>

<!-- Quick Statistics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 quick-stats">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-light text-uppercase mb-1">ทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold text-light"><?= number_format($stats['total']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-light opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">รอดำเนินการ</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['pending'] ?? 0) ?></div>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">อนุมัติแล้ว</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['approved'] ?? 0) ?></div>
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
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">ปฏิเสธแล้ว</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['rejected'] ?? 0) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ตัวกรองและค้นหา</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="admin-actions">
            <div class="row">
                <div class="col-md-2">
                    <label class="small font-weight-bold">สถานะ:</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>ปฏิเสธแล้ว</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">ประเภท:</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        <option value="new" <?= $type_filter === 'new' ? 'selected' : '' ?>>นักเรียนใหม่</option>
                        <option value="existing" <?= $type_filter === 'existing' ? 'selected' : '' ?>>นักเรียนเก่า</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">วันที่เริ่ม:</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from ?? '') ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">วันที่สิ้นสุด:</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to ?? '') ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">ค้นหา:</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search_term ?? '') ?>" 
                           placeholder="ชื่อ, ผู้ปกครอง, เบอร์โทร..." class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="small font-weight-bold">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="admin_registration_management.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Registrations Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            รายการลงทะเบียน (<?= number_format($registrations->num_rows) ?> รายการ)
        </h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow">
                <div class="dropdown-header">ตัวเลือก:</div>
                <a class="dropdown-item" href="#" onclick="exportTable()">
                    <i class="fas fa-file-excel fa-sm fa-fw mr-2"></i> ส่งออก Excel
                </a>
                <a class="dropdown-item" href="#" onclick="printTable()">
                    <i class="fas fa-print fa-sm fa-fw mr-2"></i> พิมพ์รายการ
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="registrationsTable" width="100%">
                <thead class="thead-light">
                    <tr>
                        <th width="3%">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th width="4%">ID</th>
                        <th width="14%">ชื่อนักเรียน</th>
                        <th width="7%">ประเภท</th>
                        <th width="9%">ห้องเรียน</th>
                        <th width="9%">ปีการศึกษา</th>
                        <th width="11%">ผู้ปกครอง</th>
                        <th width="9%">วันที่ลงทะเบียน</th>
                        <th width="7%">จำนวนเงิน</th>
                        <th width="9%">สถานะ</th>
                        <th width="18%">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($registrations->num_rows > 0): ?>
                        <?php while ($row = $registrations->fetch_assoc()): ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td>
                                    <input type="checkbox" class="registration-checkbox form-check-input" 
                                           value="<?= $row['id'] ?>">
                                </td>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['student_name'] ?? 'N/A') ?></strong>
                                    <?php if ($row['registration_type'] === 'existing'): ?>
                                        <br><small class="text-muted">ID: <?= htmlspecialchars($row['student_id'] ?? 'N/A') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['registration_type'] === 'new'): ?>
                                        <span class="badge badge-info">นักเรียนใหม่</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">นักเรียนเก่า</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['class_name'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($row['academic_year_name'])): ?>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($row['academic_year_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['parent_name'] ?? 'N/A') ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['parent_phone'] ?? 'N/A') ?></small>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['registration_date'])) ?></td>
                                <td class="text-right"><?= number_format($row['transfer_amount'] ?? 0) ?> ₭</td>
                                <td class="text-center">
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    $status_icon = '';
                                    
                                    switch ($row['registration_status']) {
                                        case 'pending':
                                            $status_class = 'warning';
                                            $status_text = 'รอดำเนินการ';
                                            $status_icon = 'clock';
                                            break;
                                        case 'approved':
                                            $status_class = 'success';
                                            $status_text = 'อนุมัติแล้ว';
                                            $status_icon = 'check-circle';
                                            break;
                                        case 'rejected':
                                            $status_class = 'danger';
                                            $status_text = 'ปฏิเสธแล้ว';
                                            $status_icon = 'times-circle';
                                            break;
                                    }
                                    ?>
                                    <span class="badge badge-<?= $status_class ?> registration-status-badge">
                                        <i class="fas fa-<?= $status_icon ?>"></i> <?= $status_text ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Quick status change -->
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-toggle="dropdown">
                                                <i class="fas fa-edit"></i> เปลี่ยนสถานะ
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item status-change" 
                                                   data-id="<?= $row['id'] ?>" 
                                                   data-status="pending">
                                                    <i class="fas fa-clock text-warning"></i> รอดำเนินการ
                                                </a>
                                                <a class="dropdown-item status-change" 
                                                   data-id="<?= $row['id'] ?>" 
                                                   data-status="approved">
                                                    <i class="fas fa-check-circle text-success"></i> อนุมัติ
                                                </a>
                                                <a class="dropdown-item status-change" 
                                                   data-id="<?= $row['id'] ?>" 
                                                   data-status="rejected">
                                                    <i class="fas fa-times-circle text-danger"></i> ปฏิเสธ
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Other actions -->
                                        <button class="btn btn-sm btn-info view-details" 
                                                data-id="<?= $row['id'] ?>"
                                                data-toggle="tooltip" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if (!empty($row['payment_slip_path'])): ?>
                                        <button class="btn btn-sm btn-primary view-payment" 
                                                data-slip="<?= htmlspecialchars($row['payment_slip_path'] ?? '') ?>"
                                                data-toggle="tooltip" title="ดูใบโอนเงิน">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-sm btn-danger delete-registration" 
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['student_name'] ?? 'N/A') ?>"
                                                data-toggle="tooltip" title="ลบการลงทะเบียน">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <h5>ไม่พบข้อมูลการลงทะเบียน</h5>
                                    <p>ไม่มีข้อมูลที่ตรงกับเงื่อนไขการค้นหา</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">การดำเนินการแบบกลุ่ม</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="bulkActionForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        เลือกรายการที่ต้องการดำเนินการก่อนกดปุ่มนี้
                    </div>
                    
                    <div class="form-group">
                        <label>การดำเนินการ:</label>
                        <select name="bulk_status" class="form-control" required>
                            <option value="">-- เลือกการดำเนินการ --</option>
                            <option value="pending">เปลี่ยนเป็น รอดำเนินการ</option>
                            <option value="approved">เปลี่ยนเป็น อนุมัติ</option>
                            <option value="rejected">เปลี่ยนเป็น ปฏิเสธ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>หมายเหตุ (ตัวเลือก):</label>
                        <textarea name="bulk_notes" class="form-control" rows="3" 
                                  placeholder="หมายเหตุสำหรับการดำเนินการนี้..."></textarea>
                    </div>
                    
                    <div id="selectedCount" class="alert alert-secondary">
                        จำนวนรายการที่เลือก: <span id="countNumber">0</span> รายการ
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">ดำเนินการ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เปลี่ยนสถานะการลงทะเบียน</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="statusChangeForm">
                <div class="modal-body">
                    <input type="hidden" id="changeStatusId" name="registration_id">
                    <input type="hidden" id="changeStatusValue" name="status">
                    
                    <div class="alert alert-info" id="statusChangeInfo">
                        <!-- Info will be populated by JS -->
                    </div>
                    
                    <div class="form-group">
                        <label>หมายเหตุ:</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="หมายเหตุสำหรับการเปลี่ยนสถานะ (ตัวเลือก)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">ยืนยันการเปลี่ยนสถานะ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Slip Modal -->
<div class="modal fade" id="paymentSlipModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">หลักฐานการโอนเงิน</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="paymentSlipImg" src="" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
                <a id="downloadSlip" href="#" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> ดาวน์โหลด
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
echo $content;
?>
