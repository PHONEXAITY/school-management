<?php
// Include database connection
include 'config/db.php';



// Initialize variables
$class_names = [];
$student_counts = [];
$total_students = 0;
$total_teachers = 0;
$total_classes = 0;
$total_users = 0;
$error_message = '';

try {
    // Query for pie chart: students per class
    $stmt = $conn->prepare("
        SELECT c.name, COUNT(s.id) as student_count
        FROM class c
        LEFT JOIN student s ON c.id = s.class_id
        GROUP BY c.id, c.name
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $class_names[] = $row['name'];
        $student_counts[] = (int) $row['student_count'];
    }
    $stmt->close();

    // Queries for dashboard cards
    $result = $conn->query("SELECT COUNT(*) as count FROM student");
    if ($result) {
        $total_students = $result->fetch_assoc()['count'];
        $result->free();
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM teacher");
    if ($result) {
        $total_teachers = $result->fetch_assoc()['count'];
        $result->free();
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM class");
    if ($result) {
        $total_classes = $result->fetch_assoc()['count'];
        $result->free();
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM user");
    if ($result) {
        $total_users = $result->fetch_assoc()['count'];
        $result->free();
    }

    // Convert arrays to JSON for JavaScript
    $class_names_json = json_encode($class_names);
    $student_counts_json = json_encode($student_counts);

} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $class_names_json = json_encode([]);
    $student_counts_json = json_encode([]);
    $total_students = 0;
    $total_teachers = 0;
    $total_classes = 0;
    $total_users = 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        .card {
            transition: all 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .border-left-primary {
            border-left: 0.25rem solid #007bff !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #28a745 !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #17a2b8 !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #ffc107 !important;
        }

        .text-primary {
            color: #007bff !important;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-info {
            color: #17a2b8 !important;
        }

        .text-gray-800 {
            color: #3a3b45 !important;
        }

        .text-gray-300 {
            color: #dddfeb !important;
        }

        .shadow {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        .chart-area,
        .chart-pie {
            position: relative;
            height: 250px;
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <?php if ($error_message): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Content Row -->
    <div class="row">
        <!-- Total Students Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Students
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo htmlspecialchars($total_students); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Teachers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Teachers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo htmlspecialchars($total_teachers); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Classes Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Classes
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php echo htmlspecialchars($total_classes); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total User Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total User
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo htmlspecialchars($total_users); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Area Chart (Placeholder) -->
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

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Student Distribution by Class</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="myPieChart"></canvas>


                    </div>
                    <div class="mt-4 text-center small" id="chartLegend">
                        <?php if (empty($class_names)): ?>
                            <span class="text-gray-800">No class data available</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Initialize Pie Chart -->
    <script>
        const colors = [
            '#007bff', '#28a745', '#17a2b8', '#ffc107', '#dc3545',
            '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#e83e8c'
        ];

        const classNames = <?php echo $class_names_json; ?>;
        const studentCounts = <?php echo $student_counts_json; ?>;

        // Only render chart if data exists
        if (classNames.length > 0) {
            const backgroundColors = classNames.map((_, index) => colors[index % colors.length]);
            const borderColors = backgroundColors.map(color => {
                return color.replace(/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i, (m, r, g, b) => {
                    r = Math.max(0, parseInt(r, 16) - 20).toString(16).padStart(2, '0');
                    g = Math.max(0, parseInt(g, 16) - 20).toString(16).padStart(2, '0');
                    b = Math.max(0, parseInt(b, 16) - 20).toString(16).padStart(2, '0');
                    return `#${r}${g}${b}`;
                });
            });

            const ctxPie = document.getElementById('myPieChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: classNames,
                    datasets: [{
                        data: studentCounts,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            const legendContainer = document.getElementById('chartLegend');
            legendContainer.innerHTML = classNames.map((name, index) => `
                <span class="mr-2">
                    <i class="fas fa-circle" style="color: ${backgroundColors[index]};"></i> ${name}
                </span>
            `).join('');
        }
    </script>
</body>

</html>