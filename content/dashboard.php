<?php
// Include database connection
include 'config/db.php'; // Ensure this file establishes $conn and handles errors

// Initialize variables
$class_names = [];
$student_counts = [];
$total_students = 0;
$total_teachers = 0;
$total_classes = 0;
$total_users = 0;
$error_message = '';
$months_initial = []; // Renamed to avoid conflict with JS variable
$registration_counts_initial = []; // Renamed to avoid conflict with JS variable

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

    // Query for area chart: registrations per month for the initially selected year
    $selected_year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); // Default to current year for consistency
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(registration_date, '%Y-%m') as month,
               COUNT(*) as registration_count
        FROM registration
        WHERE YEAR(registration_date) = ?
        GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
        ORDER BY month
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();

    $data_from_db_initial = [];
    while ($row = $result->fetch_assoc()) {
        $data_from_db_initial[$row['month']] = (int) $row['registration_count'];
    }
    $stmt->close();

    // Populate all 12 months for initial chart load, filling in 0 for months with no data
    for ($i = 1; $i <= 12; $i++) {
        $month_key = $selected_year . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $month_name = date('F', strtotime($month_key . '-01'));
        $months_initial[] = $month_name;
        $registration_counts_initial[] = $data_from_db_initial[$month_key] ?? 0;
    }


    // Convert arrays to JSON for JavaScript
    $class_names_json = json_encode($class_names);
    $student_counts_json = json_encode($student_counts);
    $months_initial_json = json_encode($months_initial);
    $registration_counts_initial_json = json_encode($registration_counts_initial);

} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $class_names_json = json_encode([]);
    $student_counts_json = json_encode([]);
    $months_initial_json = json_encode([]); // Ensure these are still valid JSON even on error
    $registration_counts_initial_json = json_encode([]);
    $total_students = 0;
    $total_teachers = 0;
    $total_classes = 0;
    $total_users = 0;
} finally {
    // It's good practice to close the connection when all operations are done
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .chart-container {
            position: relative;
            height: 330px;
        }

        .chart-pie {
            position: relative;
            height: 220px;
        }



        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">ໜ້າຫຼັກ</h1>
    </div>

    <?php if ($error_message): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                ນັກຮຽນທັງໝົດ
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

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                ນາຍຄູທັງໝົດ
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

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                ຫ້ອງຮຽນທັງໝົດ
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

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                ຜູ້ໃຊ້ລະບົບທັງໝົດ
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

    <div class="row">
        <div class="col-xl-8 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Trends ການລົງທະບຽນແຕ່ລະເດືອນ</h6>
                    <div class="dropdown no-arrow">
                        <select id="yearSelect" class="form-control form-control-sm" onchange="updateMonthlyChart()">
                            <option value="2024" <?php echo $selected_year == 2024 ? 'selected' : ''; ?>>2024</option>
                            <option value="2023" <?php echo $selected_year == 2023 ? 'selected' : ''; ?>>2023</option>
                            <option value="2022" <?php echo $selected_year == 2022 ? 'selected' : ''; ?>>2022</option>
                            <?php
                            // Optionally, dynamically generate years
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                if (!in_array($y, [2024, 2023, 2022])) { // Avoid duplicates if hardcoded years are present
                                    echo '<option value="' . $y . '"' . ($selected_year == $y ? ' selected' : '') . '>' . $y . '</option>';
                                }
                            }
                            ?>
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

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">ຈຳນວນນັກຮຽນຕາມຫ້ອງຮຽນ</h6>
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

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        // --- Pie Chart Initialization ---
        const colors = [
            '#007bff', '#28a745', '#17a2b8', '#ffc107', '#dc3545',
            '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#e83e8c'
        ];

        const classNames = <?php echo $class_names_json; ?>;
        const studentCounts = <?php echo $student_counts_json; ?>;

        // Only render pie chart if data exists
        if (classNames.length > 0) {
            const backgroundColors = classNames.map((_, index) => colors[index % colors.length]);
            const borderColors = backgroundColors.map(color => {
                // Darken border color slightly
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

        // --- Area Chart Initialization and Update ---
        let monthlyChart; // Declare chart variable globally so updateMonthlyChart can access it

        function initializeMonthlyChart(initialMonths, initialCounts) {
            const ctxArea = document.getElementById('monthlyChart').getContext('2d');
            monthlyChart = new Chart(ctxArea, { // Assign to the global variable
                type: 'line',
                data: {
                    labels: initialMonths.length ? initialMonths : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    datasets: [{
                        label: 'Registrations',
                        data: initialCounts.length ? initialCounts : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: '#007bff',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Registrations'
                            }
                        }
                    }
                }
            });
        }

        // Call initializeMonthlyChart with initial PHP data on page load
        initializeMonthlyChart(<?php echo $months_initial_json; ?>, <?php echo $registration_counts_initial_json; ?>);

        // Function to update chart based on year selection via AJAX
        function updateMonthlyChart() {
            const year = document.getElementById('yearSelect').value;
            fetch(`get_monthly_data.php?year=${year}`) // Call the new PHP endpoint
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json(); // Expect JSON response
                })
                .then(data => {
                    if (data.error) {
                        console.error('Server error:', data.error);
                        // Optionally display this error to the user
                    }
                    const newMonths = data.months;
                    const newCounts = data.registrationCounts;

                    // Update chart data
                    monthlyChart.data.labels = newMonths.length ? newMonths : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    monthlyChart.data.datasets[0].data = newCounts.length ? newCounts : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                    monthlyChart.update();
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    // Handle network or parsing errors, e.g., display a message
                    monthlyChart.data.labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    monthlyChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                    monthlyChart.update();
                });
        }
    </script>
</body>

</html>