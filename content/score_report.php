<?php
ob_start(); // Start output buffering

include 'config/db.php';

// Check if PDF generation is requested
if (isset($_POST['save_pdf']) && $_POST['save_pdf'] == '1') {
    header("Location: generate_score_report_pdf.php?" . http_build_query($_POST));
    ob_end_clean();
    exit;
}

// Fetch classes, students, terms, and years for dropdowns
$class_sql = "SELECT id, name FROM class";
$class_result = $conn->query($class_sql);

$student_sql = "SELECT id, CONCAT(fname, ' ', lname) AS name FROM student";
$student_result = $conn->query($student_sql);

$term_sql = "SELECT id, name FROM term";
$term_result = $conn->query($term_sql);

$year_sql = "SELECT id, name FROM year";
$year_result = $conn->query($year_sql);

// Fetch all subjects for column headers
$subject_sql = "SELECT id, name FROM subject";
$subject_result = $conn->query($subject_sql);
if ($subject_result === false) {
    die("Error fetching subjects: " . $conn->error);
}
$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[$row['id']] = $row['name'];
}

// Handle filters
$selected_class = isset($_POST['class_id']) ? $_POST['class_id'] : '';
$selected_student = isset($_POST['student_id']) ? $_POST['student_id'] : '';
$selected_term = isset($_POST['term_id']) ? $_POST['term_id'] : '';
$selected_year = isset($_POST['sch_year_id']) ? $_POST['sch_year_id'] : '';
$selected_month = isset($_POST['month']) ? $_POST['month'] : '';

$conditions = [];
$params = [];
$types = '';
if ($selected_class) {
    $conditions[] = "st.class_id = ?";
    $params[] = $selected_class;
    $types .= 's';
}
if ($selected_student) {
    $conditions[] = "sc.student_id = ?";
    $params[] = $selected_student;
    $types .= 'i';
}
if ($selected_term) {
    $conditions[] = "sc.term_id = ?";
    $params[] = $selected_term;
    $types .= 's';
}
if ($selected_year) {
    $conditions[] = "sc.sch_year_id = ?";
    $params[] = $selected_year;
    $types .= 's';
}
if ($selected_month) {
    $conditions[] = "sc.month = ?";
    $params[] = $selected_month;
    $types .= 'i';
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$sql = "SELECT 
            CONCAT(st.fname, ' ', st.lname) AS student_name, 
            c.name AS class_name, 
            sub.id AS subject_id, 
            sub.name AS subject_name, 
            sc.score, 
            sc.month, 
            t.name AS term_name, 
            y.name AS year_name 
        FROM score sc
        JOIN student st ON sc.student_id = st.id
        JOIN class c ON st.class_id = c.id
        JOIN subject sub ON sc.sub_id = sub.id
        JOIN term t ON sc.term_id = t.id
        JOIN year y ON sc.sch_year_id = y.id
        $where_clause
        ORDER BY student_name, c.name, t.name, y.name, sc.month";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result === false) {
    die("Error fetching scores: " . $conn->error);
}

// Group scores and calculate sum, average, and status
$grouped_scores = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['student_name'] . '|' . $row['class_name'] . '|' . $row['term_name'] . '|' . $row['year_name'] . '|' . $row['month'];
    if (!isset($grouped_scores[$key])) {
        $grouped_scores[$key] = [
            'student_name' => $row['student_name'],
            'class_name' => $row['class_name'],
            'term_name' => $row['term_name'],
            'year_name' => $row['year_name'],
            'month' => $row['month'],
            'scores' => [],
            'sum' => 0,
            'count' => 0,
            'average' => 0,
            'status' => ''
        ];
    }
    if ($row['score'] !== null) {
        $grouped_scores[$key]['scores'][$row['subject_id']] = $row['score'];
        $grouped_scores[$key]['sum'] += $row['score'];
        $grouped_scores[$key]['count']++;
    }
}

// Calculate average and status
foreach ($grouped_scores as $key => &$group) {
    if ($group['count'] > 0) {
        $group['average'] = $group['sum'] / $group['count'];
        $group['status'] = $group['average'] >= 5 ? 'ຜ່ານ' : 'ບໍ່ຜ່ານ';
    } else {
        $group['average'] = 0;
        $group['status'] = 'Fail';
    }
}

// Calculate arrange (rank) based on sum
$sums = array_column($grouped_scores, 'sum');
$unique_sums = array_unique($sums);
rsort($unique_sums); // Sort in descending order
$rank_map = [];
$rank = 1;
foreach ($unique_sums as $sum) {
    $rank_map[$sum] = $rank++;
}
foreach ($grouped_scores as $key => &$group) {
    $group['arrange'] = $rank_map[$group['sum']] ?? $rank;
}
unset($group); // Unset reference
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .form-select {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
            width: auto;
            display: inline-block;
        }

        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
            background: #ffffff;
        }

        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            margin-right: 10px;
        }

        .table {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            table-layout: auto;
            /* Allow cells to expand based on content */
        }

        .table thead {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            color: white;
        }

        .table th,
        .table td {
            white-space: nowrap;
            /* Prevent text wrapping */
            vertical-align: middle;
            padding: 12px;
            overflow: visible;
            /* Ensure content is visible without truncation */
        }

        .table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 8px;
            margin-bottom: 10px;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 5px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 5px;
            margin: 2px;
            padding: 5px 10px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(90deg, #28a745, #218838);
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #218838, #1e7e34);
            transform: translateY(-2px);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .arrange-column {
            cursor: pointer;
        }

        .arrange-hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-2">
        <!-- Page Heading -->
        <h1 class="h3 mb-3 text-gray-800">Score Report</h1>
        <p class="mb-4 text-muted">Generate a report of student scores by class, student, term, school year, and month.
        </p>

        <!-- Card for Filters and Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Score Report</h6>
                <div>
                    <button class="btn btn-primary me-2" onclick="window.print()"><i
                            class="fas fa-print me-2"></i>Print</button>

                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="post" id="filter_form" class="filter-form mb-4">
                    <div class="d-flex align-items-center">
                        <label class="form-label">Class:</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Classes</option>
                            <?php
                            $class_result->data_seek(0);
                            while ($class = $class_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($class['id']); ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="form-label">Student:</label>
                        <select name="student_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Students</option>
                            <?php
                            $student_result->data_seek(0);
                            while ($student = $student_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo $selected_student == $student['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="form-label">Term:</label>
                        <select name="term_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Terms</option>
                            <?php
                            $term_result->data_seek(0);
                            while ($term = $term_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($term['id']); ?>" <?php echo $selected_term == $term['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($term['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="form-label">School Year:</label>
                        <select name="sch_year_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            <?php
                            $year_result->data_seek(0);
                            while ($year = $year_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($year['id']); ?>" <?php echo $selected_year == $year['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="form-label">Month:</label>
                        <select name="month" class="form-select" onchange="this.form.submit()">
                            <option value="">All Months</option>
                            <?php
                            $months = [
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December'
                            ];
                            foreach ($months as $month_id => $month_name): ?>
                                <option value="<?php echo $month_id; ?>" <?php echo $selected_month == $month_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($month_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="save_pdf" id="save_pdf" value="0">
                </form>

                <!-- Report Table -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Term</th>
                                <th>School Year</th>
                                <th>Month</th>
                                <?php foreach ($subjects as $subject_id => $subject_name): ?>
                                    <th><?php echo htmlspecialchars($subject_name); ?></th>
                                <?php endforeach; ?>
                                <th>Sum</th>
                                <th>Average</th>
                                <th>Status</th>
                                <th class="arrange-column">Arrange</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_scores as $group): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($group['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($group['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($group['term_name']); ?></td>
                                    <td><?php echo htmlspecialchars($group['year_name']); ?></td>
                                    <td><?php echo htmlspecialchars($months[$group['month']]); ?></td>
                                    <?php foreach ($subjects as $subject_id => $subject_name): ?>
                                        <td>
                                            <?php
                                            echo isset($group['scores'][$subject_id])
                                                ? htmlspecialchars($group['scores'][$subject_id])
                                                : '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td><?php echo $group['sum']; ?></td>
                                    <td><?php echo number_format($group['average'], 2); ?></td>
                                    <td><?php echo $group['status']; ?></td>
                                    <td class="arrange-column"><?php echo $group['arrange']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize DataTables
            $('#dataTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "order": [[0, "asc"]]
            });

            // Toggle Arrange column visibility
            let arrangeVisible = true;
            $('.arrange-column').on('click', function () {
                arrangeVisible = !arrangeVisible;
                if (arrangeVisible) {
                    $('.arrange-column').removeClass('arrange-hidden');
                } else {
                    $('.arrange-column').addClass('arrange-hidden');
                }
            });
        });
    </script>
</body>

</html>

<?php
ob_end_flush();
if (!empty($params)) {
    $stmt->close();
}
$conn->close();
?>