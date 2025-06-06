<?php
// Fetch system logs from registration_actions table
$logs_query = "
    SELECT 
        ra.id,
        ra.action,
        ra.registration_id,
        ra.message,
        ra.created_at,
        u.username as admin_name,
        CASE 
            WHEN r.registration_type = 'existing' AND s.fname IS NOT NULL 
            THEN CONCAT(s.fname, ' ', s.lname)
            WHEN r.registration_type = 'new' AND r.student_id IS NOT NULL
            THEN CONCAT('Student ID: ', r.student_id)
            ELSE 'Unknown Student'
        END as student_name,
        r.registration_type,
        r.parent_name,
        r.parent_phone
    FROM registration_actions ra
    LEFT JOIN user u ON ra.user_id = u.id
    LEFT JOIN registration r ON ra.registration_id = r.id
    LEFT JOIN student s ON r.student_id = s.id AND r.registration_type = 'existing'
    ORDER BY ra.created_at DESC
    LIMIT 500
";
$logs_result = $conn->query($logs_query);
$logs = [];
while ($row = $logs_result->fetch_assoc()) {
    $logs[] = $row;
}

// Get summary statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_actions,
        COUNT(DISTINCT user_id) as active_admins,
        COUNT(DISTINCT DATE(created_at)) as active_days,
        COUNT(CASE WHEN action = 'status_update' THEN 1 END) as status_updates,
        COUNT(CASE WHEN action = 'bulk_update' THEN 1 END) as bulk_updates,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days
    FROM registration_actions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">ບັນທຶກິດຈະກຳຂອງລະບົບ</h1>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-secondary" onclick="refreshLogs()">
                <i class="fas fa-sync-alt"></i> ຣິເຟຣ
            </button>
            <button type="button" class="btn btn-primary" onclick="exportLogs()">
                <i class="fas fa-download"></i> ສົ່ງອອກ
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                ການກະທໍາທັງຫມົດ (30 ມື້)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_actions']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
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
                                ຜູ້ເບິ່ງແຍງລະບົບທີ່ເຮັດວຽກ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['active_admins']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
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
                                ອັບເດດສະຖານະ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['status_updates']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                7 ມື້ທີ່ຜ່ານມາ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['last_7_days']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ກິດຈະກໍາລະບົບທີ່ຜ່ານມາ</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="systemLogsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ເວລາ</th>
                            <th>ແອັບມີນ</th>
                            <th>ຈັດການ</th>
                            <th>ນັກຮຽນ</th>
                            <th>ລາຍລະອຽບ</th>
                            <th>ປ່ຽນສະຖານະ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="log-timestamp">
                                    <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="font-weight-bold">
                                        <?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="log-action">
                                        <?php
                                        $action_display = ucfirst(str_replace('_', ' ', $log['action']));
                                        $badge_class = 'secondary';
                                        switch ($log['action']) {
                                            case 'status_update':
                                                $badge_class = 'warning';
                                                $action_display = 'Status Update';
                                                break;
                                            case 'bulk_update':
                                                $badge_class = 'info';
                                                $action_display = 'Bulk Update';
                                                break;
                                            case 'created':
                                                $badge_class = 'success';
                                                $action_display = 'Created';
                                                break;
                                            case 'deleted':
                                                $badge_class = 'danger';
                                                $action_display = 'Deleted';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                            <?php echo $action_display; ?>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <div class="font-weight-bold">
                                        <?php echo htmlspecialchars($log['student_name'] ?: 'N/A'); ?>
                                    </div>
                                    <?php if ($log['registration_type']): ?>
                                        <small class="text-muted">
                                            Type: <?php echo ucfirst($log['registration_type']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($log['parent_name']): ?>
                                        <br><small class="text-muted">
                                            Parent: <?php echo htmlspecialchars($log['parent_name']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="log-entry">
                                        Registration ID: #<?php echo $log['registration_id']; ?>
                                        <?php if (!empty($log['message'])): ?>
                                            <div class="log-details">
                                                <strong>Details:</strong> <?php echo htmlspecialchars($log['message']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($log['message']) && $log['action'] === 'status_update'): ?>
                                        <div class="text-center">
                                            <span class="badge badge-info">
                                                Status Updated
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($log['message']); ?>
                                            </small>
                                        </div>
                                    <?php elseif (!empty($log['message'])): ?>
                                        <span class="badge badge-primary">
                                            <?php echo ucfirst($log['action']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($log['message']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function refreshLogs() {
        location.reload();
    }

    function exportLogs() {
        // Create form to export logs
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'system_logs.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'export_logs';
        input.value = '1';

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
</script>