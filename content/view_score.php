<?php
include 'config/db.php';

// Fetch classes for the dropdown
$class_sql = "SELECT id, name FROM class";
$class_result = $conn->query($class_sql);
if ($class_result === false) {
    die("Error fetching classes: " . $conn->error);
}

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

// Fetch scores with joins to get related data
$selected_class = isset($_POST['class_id']) ? $_POST['class_id'] : '';
$where_clause = $selected_class ? "WHERE st.class_id = ?" : "";
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

if ($selected_class) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_class);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result === false) {
    die("Error fetching scores: " . $conn->error);
}

// Group scores by student, class, term, year, and month
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
            'scores' => []
        ];
    }
    $grouped_scores[$key]['scores'][$row['subject_id']] = $row['score'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Scores</title>
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
        }

        .table {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            color: white;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 12px;
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
    </style>
</head>

<body>
    <div class="container-fluid py-2">
        <!-- Page Heading -->
        <h1 class="h3 mb-3 text-gray-800">ເບິ່ງຄະແນນຂອງນັກຮຽນ</h1>
        <p class="mb-4 text-muted">ເບິ່ງ ແລະ ສະແດງຄະແນນຂອງນັກຮຽນຕາມຫ້ອງຮຽນ.</p>

        <!-- Card for Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">ຕາຕະລາງຄະແນນ</h6>
                <form method="post" class="d-flex align-items-center">
                    <label class="form-label me-2">ສະແດງດ້ວຍຫ້ອງຮຽນ:</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="">ຫ້ອງທັງໝົດ</option>
                        <?php
                        $class_result->data_seek(0); // Reset pointer
                        while ($class = $class_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($class['id']); ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ຊື່ນັກຮຽນ</th>
                                <th>ຫ້ອງ</th>
                                <th>ພາກຮຽນ</th>
                                <th>ສົກຮຽນ</th>
                                <th>ເດືອນ</th>
                                <?php foreach ($subjects as $subject_id => $subject_name): ?>
                                    <th><?php echo htmlspecialchars($subject_name); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $months = [
                                1 => 'ມັງກອນ',
                                2 => 'ກຸມພາ',
                                3 => 'ມີນາ',
                                4 => 'ເມສາ',
                                5 => 'ພຶດສະພາ',
                                6 => 'ມິຖຸນາ',
                                7 => 'ກໍລະກົດ',
                                8 => 'ສິງຫາ',
                                9 => 'ກັນຍາ',
                                10 => 'ຕຸລາ',
                                11 => 'ພະຈິກ',
                                12 => 'ທັນວາ'
                            ];
                            foreach ($grouped_scores as $group): ?>
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
            $('#dataTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "order": [[0, "asc"]] // Sort by Student Name by default
            });
        });
    </script>
</body>

</html>

<?php
if ($selected_class) {
    $stmt->close();
}
$conn->close();
?>