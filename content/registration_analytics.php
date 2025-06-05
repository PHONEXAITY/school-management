<?php
// Fetch registration statistics
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

// Fetch monthly registration data for the current year
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

// Fetch registration type distribution
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

// Fetch recent activities
$activities_query = "
    SELECT 
        ra.action,
        ra.registration_id,
        ra.message,
        ra.created_at,
        u.username as admin_name,
        CONCAT(s.fname, ' ', s.lname) as student_name
    FROM registration_actions ra
    JOIN user u ON ra.user_id = u.id
    JOIN registration r ON ra.registration_id = r.id
    LEFT JOIN student s ON r.student_id = s.id
    ORDER BY ra.created_at DESC
    LIMIT 10
";
$activities_result = $conn->query($activities_query);
$activities = [];
while ($row = $activities_result->fetch_assoc()) {
    $activities[] = $row;
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Registration Analytics</h1>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" onclick="exportAnalytics('pdf')">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button type="button" class="btn btn-success" onclick="exportAnalytics('excel')">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Registrations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_registrations']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Approved
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['approved_count']); ?>
                            </div>
                            <div class="text-xs text-gray-600">
                                <?php echo $stats['total_registrations'] > 0 ? round(($stats['approved_count'] / $stats['total_registrations']) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['pending_count']); ?>
                            </div>
                            <div class="text-xs text-gray-600">
                                <?php echo $stats['total_registrations'] > 0 ? round(($stats['pending_count'] / $stats['total_registrations']) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Rejected
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['rejected_count']); ?>
                            </div>
                            <div class="text-xs text-gray-600">
                                <?php echo $stats['total_registrations'] > 0 ? round(($stats['rejected_count'] / $stats['total_registrations']) * 100, 1) : 0; ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Monthly Registrations Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Registration Trends</h6>
                    <div class="dropdown no-arrow">
                        <select id="yearSelect" class="form-control form-control-sm" onchange="updateMonthlyChart()">
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                            <option value="2022">2022</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Distribution Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Type Distribution -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Registration Type Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Admin Activities</h6>
                </div>
                <div class="card-body">
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="d-flex mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <?php
                                        $icon = 'fas fa-edit';
                                        $color = 'text-info';
                                        switch ($activity['action']) {
                                            case 'status_update':
                                                $icon = 'fas fa-exchange-alt';
                                                $color = 'text-warning';
                                                break;
                                            case 'created':
                                                $icon = 'fas fa-plus';
                                                $color = 'text-success';
                                                break;
                                            case 'deleted':
                                                $icon = 'fas fa-trash';
                                                $color = 'text-danger';
                                                break;
                                        }
                                        ?>
                                        <i class="<?php echo $icon . ' ' . $color; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="small text-gray-500 mb-1">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'] ?? date('Y-m-d H:i:s'))); ?>
                                        </div>
                                        <div class="font-weight-bold">
                                            <?php echo htmlspecialchars($activity['admin_name'] ?? 'Unknown User'); ?>
                                        </div>
                                        <div class="text-sm">
                                            <?php if ($activity['action'] === 'status_change'): ?>
                                                <strong><?php echo htmlspecialchars($activity['student_name'] ?? 'Unknown Student'); ?></strong>
                                                <br><span class="text-muted"><?php echo htmlspecialchars($activity['message'] ?? ''); ?></span>
                                            <?php else: ?>
                                                <?php echo ucfirst($activity['action']); ?> registration for 
                                                <strong><?php echo htmlspecialchars($activity['student_name'] ?? 'Unknown Student'); ?></strong>
                                                <?php if (!empty($activity['message'])): ?>
                                                    <br><span class="text-muted"><?php echo htmlspecialchars($activity['message'] ?? ''); ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($activity['notes'])): ?>
                                            <div class="text-xs text-muted mt-1">
                                                Note: <?php echo htmlspecialchars($activity['notes'] ?? ''); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>No recent activities found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden data for JavaScript -->
<script>
window.analyticsData = {
    monthly: <?php echo json_encode($monthly_data); ?>,
    status: {
        approved: <?php echo $stats['approved_count']; ?>,
        pending: <?php echo $stats['pending_count']; ?>,
        rejected: <?php echo $stats['rejected_count']; ?>
    },
    types: <?php echo json_encode($type_data); ?>
};
</script>