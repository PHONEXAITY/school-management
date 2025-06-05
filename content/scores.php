<?php
include 'config/db.php';

// Fetch students
$student_sql = "SELECT id, CONCAT(fname, ' ', lname) AS name FROM student";
$student_result = $conn->query($student_sql);

// Fetch terms
$term_sql = "SELECT id, name FROM term";
$term_result = $conn->query($term_sql);

// Fetch school years
$year_sql = "SELECT id, name FROM year";
$year_result = $conn->query($year_sql);

// Fetch all subjects
$subject_sql = "SELECT id, name FROM subject";
$subject_result = $conn->query($subject_sql);

if ($subject_result === false) {
    die("Error fetching subjects: " . $conn->error);
}

$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[$row['id']] = $row['name'];
}

if (empty($subjects)) {
    $error_message = "Error: No subjects found in the database. Current subjects in the table are: " .
        (function () use ($conn) {
            $subject_list = $conn->query("SELECT id, name FROM subject");
            $list = [];
            while ($row = $subject_list->fetch_assoc()) {
                $list[] = $row['id'] . " (" . $row['name'] . ")";
            }
            return empty($list) ? "None" : implode(", ", $list);
        })();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Student Scores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css"
        rel="stylesheet">
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

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
        }

        .form-control:focus,
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

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(90deg, #6b7280, #4b5563);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: linear-gradient(90deg, #4b5563, #374151);
            transform: translateY(-2px);
        }

        .form-group {
            position: relative;
        }

        .form-group .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 1px;
        }

        .form-control {
            border-radius: 4px;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .btn-cancel {
            background-color: #7F7E7E;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #6b7280;
        }

        .btn-save {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background-color: #2563eb;
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: 38px;
            padding: 6px 12px;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: 10px;
            border: 1px solid #d1d5db;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-2">
        <!-- Page Heading -->
        <h1 class="h3 mb-3 text-gray-800">ປ້ອນຂໍ້ມູນຄະແນນ</h1>
        <p class="mb-4 text-muted">ປ້ອນຄະແນນປະຈໍາເດືອນຂ້ອງນັກຮຽນໃນທຸກວິຊາໃຫ້ຄົບຖ້ວນເພື່ອທັນທຶກໄວ້ໃນລະບົບ.</p>

        <!-- Card for Form -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ຟອມປ້ອນຄະແນນ</h6>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <p class="text-danger"><?php echo $error_message; ?></p>
                <?php else: ?>
                    <form method="post" action="process_score.php">
                        <div class="row g-2">
                            <!-- Student Selection -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label required">ນັກຮຽນ</label>
                                    <select name="student_id" class="form-select select2-student" required>
                                        <option value="">ເລືອກນັກຮຽນ</option>
                                        <?php
                                        $student_result->data_seek(0); // Reset pointer
                                        while ($student = $student_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($student['id']); ?>">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Month Selection -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label required">ເດືອນ</label>
                                    <select name="month" class="form-select" required>
                                        <option value="">ກະລຸນາເລືອກເດືອນ</option>
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
                                            12 => 'ທັນວາ'////////////////////////////////////////
                                        ];
                                        foreach ($months as $num => $name): ?>
                                            <option value="<?php echo $num; ?>" <?php echo $num == 6 ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Term Selection -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label required">ພາກຮຽນ</label>
                                    <select name="term_id" class="form-select" required>
                                        <option value="">ກະລຸນາເລືອກພາກຮຽນ</option>
                                        <?php
                                        $term_result->data_seek(0); // Reset pointer
                                        while ($term = $term_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($term['id']); ?>">
                                                <?php echo htmlspecialchars($term['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- School Year Selection -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label required">ສົກຮຽນ</label>
                                    <select name="sch_year_id" class="form-select" required>
                                        <option value="">ກະລຸນາເລືອກສົກຮຽນ</option>
                                        <?php
                                        $year_result->data_seek(0); // Reset pointer
                                        while ($year = $year_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($year['id']); ?>">
                                                <?php echo htmlspecialchars($year['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Scores for Each Subject -->
                            <?php foreach ($subjects as $id => $name): ?>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label required"><?php echo htmlspecialchars($name); ?> </label>
                                        <input type="hidden" name="sub_id[<?php echo $name; ?>]"
                                            value="<?php echo htmlspecialchars($id); ?>">
                                        <input type="number" name="score[<?php echo $name; ?>]" class="form-control"
                                            placeholder="ປ້ອນຄະແນນ (0-10)" min="0" max="10" required>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions">
                            <button type="reset" class="btn-cancel">ຍົກເລີກ</button>
                            <button type="submit" name="submit_scores" class="btn-save"><i
                                    class="fas fa-save me-2"></i>ບັນທືນ</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts in correct order -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Check if jQuery and Select2 are loaded
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }
            if (typeof $.fn.select2 === 'undefined') {
                console.error('Select2 is not loaded');
                return;
            }

            // Initialize Select2 for student selection
            console.log('Initializing Select2 for student dropdown');
            $('.select2-student').select2({
                theme: 'bootstrap-5',
                placeholder: 'ເລືອກນັກຮຽນ',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 1 // Show search for 1 or more options
            });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>