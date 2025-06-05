<?php
ob_start(); // Start output buffering

include 'config/db.php';

// Check if report view is requested
$show_report = isset($_GET['report']) && $_GET['report'] == '1';

// Check if PDF generation is requested for the report
if ($show_report && isset($_POST['save_pdf']) && $_POST['save_pdf'] == '1') {
    header("Location: generate_teacher_report_pdf.php?" . http_build_query($_POST));
    ob_end_clean();
    exit;
}

// Fetch classes for the form (used in both views)
$class_sql = "SELECT id, name FROM class";
$class_result = $conn->query($class_sql);

// Report View: Fetch teachers with class filter
if ($show_report) {
    $selected_class = isset($_POST['class_id']) ? $_POST['class_id'] : '';
    $where_clause = $selected_class ? "WHERE t.class_id = ?" : "";
    $sql = "SELECT t.*, c.name AS class_name 
            FROM teacher t 
            LEFT JOIN class c ON t.class_id = c.id
            $where_clause
            ORDER BY t.id";

    if ($selected_class) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selected_class);
        $stmt->execute();
        $teachers_result = $stmt->get_result();
    } else {
        $teachers_result = $conn->query($sql);
    }
} else {
    // Management View: Fetch teachers
    $sql = "SELECT t.*, c.name AS class_name 
            FROM teacher t 
            LEFT JOIN class c ON t.class_id = c.id";
    $teachers_result = $conn->query($sql);

    // Fetch teacher data for edit modal
    $edit_teacher = null;
    $edit_error = null;
    if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
        $edit_id = (int) $_GET['edit_id'];
        $sql = "SELECT * FROM teacher WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $edit_error = "Failed to prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $edit_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $edit_teacher = $result->fetch_assoc();
                } else {
                    $edit_error = "No teacher found with ID: $edit_id";
                }
            } else {
                $edit_error = "Query execution failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_GET['edit_id'])) {
        $edit_error = "Invalid teacher ID provided";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_report ? 'Teacher Report' : 'Teachers Management'; ?></title>
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

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
            width: auto;
            display: inline-block;
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
            margin-right: 10px;
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

        .btn-info,
        .btn-warning,
        .btn-danger {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .btn-info:hover,
        .btn-warning:hover,
        .btn-danger:hover {
            transform: scale(1.1);
        }

        .btn-success {
            background: linear-gradient(90deg, #28a745, #218838);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #218838, #1e7e34);
            transform: translateY(-2px);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(90deg, #FFFFFFFF, #FFFFFFFF);
            color: white;
            padding: 20px;
        }

        .modal-title {
            font-weight: 700;
            color: #000000;
        }

        .modal-body {
            background: #f9fafb;
            padding: 24px;
        }

        .modal-footer {
            border-top: none;
            padding: 10px;
            background: #f9fafb;
        }

        .form-select {
            border-radius: 4px;
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

        .btn-delete {
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #dc2626;
        }

        .btn-cancel {
            background-color: #7F7E7EFF;
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

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-2">
        <?php if ($show_report): ?>
            <!-- Report View -->
            <h1 class="h3 mb-3 text-gray-800">ລາຍງານຂໍ້ມູນ ນາຍຄູ</h1>
            <p class="mb-4 text-muted">ສ້າງ​​ລາຍ​ງານ​ລາຍ​ລະ​ອຽດ​ຂອງ​ນາຍຄູ​​ດ້ວຍຫ້ອງ​ຮຽນ​.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">ລາຍງານຂໍ້ມູນ</h6>
                    <div>
                        <button class="btn btn-primary me-2" onclick="window.print()"><i
                                class="fas fa-print me-2"></i>Print</button>

                        <a href="teachers.php" class="btn btn-secondary"><i
                                class="fas fa-arrow-left me-2"></i>ກັບໄປໜ້າຈັດການ</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Class Filter -->
                    <form method="post" id="filter_form" class="filter-form mb-4">
                        <div class="d-flex align-items-center">
                            <label class="form-label">ຫ້ອງ:</label>
                            <select name="class_id" class="form-select" onchange="this.form.submit()">
                                <option value="">ທຸກຫ້ອງ</option>
                                <?php
                                $class_result->data_seek(0);
                                while ($class = $class_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($class['id']); ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <input type="hidden" name="save_pdf" id="save_pdf" value="0">
                    </form>

                    <!-- Report Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ລະຫັດ</th>
                                    <th>ຊື່ ແລະ ນາມສະກຸນ</th>
                                    <th>ເພດ</th>
                                    <th>ວັນເດືອນປີເກີດ</th>
                                    <th>ບ້ານ</th>
                                    <th>ເມືອງ</th>
                                    <th>ແຂວງ</th>
                                    <th>ລະດັບການສຶກສາ</th>
                                    <th>ເບີໂທ</th>
                                    <th>ສອນຫ້ອງ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $teachers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($row['birth_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['village']); ?></td>
                                        <td><?php echo htmlspecialchars($row['district']); ?></td>
                                        <td><?php echo htmlspecialchars($row['province']); ?></td>
                                        <td><?php echo htmlspecialchars($row['degree']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] ?: 'N/A'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Management View -->
            <h1 class="h3 mb-3 text-gray-800">ຈັດການຂໍ້ມູນ ນາຍຄູ</h1>
            <p class="mb-4 text-muted">ຈັດການຂໍ້ມູນ ນາຍຄູ ບັນທຶກ, ແກ້ໄຂ ຫຼື ລຶບ
                ລາຍລະອຽດ.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">ນາຍຄູ</h6>
                    <div>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="fas fa-plus me-2"></i>ເພີ່ມນາຍຄູ
                        </button>
                        <a href="teachers.php?report=1" class="btn btn-success">
                            <i class="fas fa-file-alt me-2"></i>ລາຍງານຂໍ້ມູນນາຍຄູ
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ລະຫັດ</th>
                                    <th>ຊື່ ແລະ ນາມສະກຸນ</th>
                                    <th>ເພດ</th>
                                    <th>ວັນເດືອນປີເກີດ</th>
                                    <th>ບ້ານຢູ່ປັດຈຸບັນ</th>
                                    <th>ເມືອງ</th>
                                    <th>ແຂວງ</th>
                                    <th>ລະດັບການສຶກສາ</th>
                                    <th>ເບີໂທ</th>
                                    <th>ສອນຫ້ອງ</th>
                                    <th>ຈັດການຂໍ້ມູນ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $teachers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($row['birth_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['village']); ?></td>
                                        <td><?php echo htmlspecialchars($row['district']); ?></td>
                                        <td><?php echo htmlspecialchars($row['province']); ?></td>
                                        <td><?php echo htmlspecialchars($row['degree']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <a href="teachers.php?edit_id=<?php echo htmlspecialchars($row['id']); ?>"
                                                class="btn btn-warning btn-circle btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete"
                                                data-bs-toggle="modal" data-bs-target="#deleteTeacherModal"
                                                data-teacher-id="<?php echo htmlspecialchars($row['id']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Teacher Modal -->
            <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title" id="addTeacherModalLabel">ເພີ່ມນາຍຄູ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body">
                            <form method="post" action="process_teacher.php">
                                <div class="row g-2">
                                    <!-- First Name -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ຊື່ບາຍຄູ</label>
                                        <input type="text" name="fname" class="form-control" placeholder="Enter first name"
                                            required>
                                    </div>

                                    <!-- Last Name -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ນາມສະກຸນ</label>
                                        <input type="text" name="lname" class="form-control" placeholder="Enter last name"
                                            required>
                                    </div>

                                    <!-- Gender -->
                                    <div class="col-md-3">
                                        <label class="form-label required"> ເລຶອກເພດ -</label>
                                        <select name="gender" class="form-select" required>
                                            <option value="">ເລຶອກເພດ</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <!-- Date of Birth -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ວັນເດືອນປີເກີດ</label>
                                        <input type="date" name="birth_date" class="form-control" required>
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ເບີໂທ</label>
                                        <input type="tel" name="phone" class="form-control" placeholder="Enter phone number"
                                            required>
                                    </div>

                                    <!-- Village -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ບ້ານ</label>
                                        <input type="text" name="village" class="form-control" placeholder="Enter village"
                                            required>
                                    </div>

                                    <!-- District -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ເມືອງ</label>
                                        <input type="text" name="district" class="form-control" placeholder="Enter district"
                                            required>
                                    </div>

                                    <!-- Province -->
                                    <div class="col-md-4">
                                        <label class="form-label required">ແຂວງ</label>
                                        <input type="text" name="province" class="form-control" placeholder="Enter province"
                                            required>
                                    </div>

                                    <!-- Degree -->
                                    <div class="col-md-3">
                                        <label class="form-label required">ລະດັບການສຶກສາ</label>
                                        <select name="degree" class="form-select" required>
                                            <option value="">ເລຶອກລະດັບການສຶກສາ</option>
                                            <option value="Master">Master</option>
                                            <option value="Bachelor">Bachelor</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <!-- Class (Optional) -->
                                    <div class="col-md-4">
                                        <label class="form-label">ສອນຫ້ອງ</label>
                                        <select name="class_id" class="form-select">
                                            <option value="">ເລຶອກຫ້ອງ (Optional)</option>
                                            <?php
                                            $class_result->data_seek(0); // Reset class result pointer
                                            while ($class = $class_result->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($class['id']); ?>">
                                                    <?php echo htmlspecialchars($class['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer mt-3">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                                    <button type="submit" name="submit_add" class="btn btn-primary">ບັນທຶກ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Edit Teacher Modal -->
            <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTeacherModalLabel">ແກ້ໄຂຂໍ້ມູນ ນາຍຄູ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($edit_teacher): ?>
                                <form method="post" action="process_teacher.php">
                                    <input type="hidden" name="teacher_id"
                                        value="<?php echo htmlspecialchars($edit_teacher['id']); ?>">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ຊື່ນາຍຄູ</label>
                                                <input type="text" name="fname" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['fname']); ?>"
                                                    placeholder="Enter first name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ນາມສະກຸນ</label>
                                                <input type="text" name="lname" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['lname']); ?>"
                                                    placeholder="Enter last name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="form-label required">ເພດ</label>
                                                <select name="gender" class="form-select" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male" <?php echo $edit_teacher['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo $edit_teacher['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Other" <?php echo $edit_teacher['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ວັນເດືອນປີເກີດ</label>
                                                <input type="date" name="birth_date" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['birth_date']); ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ເບີໂທ</label>
                                                <input type="tel" name="phone" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['phone']); ?>"
                                                    placeholder="Enter phone number" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ບ້ານ</label>
                                                <input type="text" name="village" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['village']); ?>"
                                                    placeholder="Enter village" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ເມືອງ</label>
                                                <input type="text" name="district" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['district']); ?>"
                                                    placeholder="Enter district" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">ແຂວງ</label>
                                                <input type="text" name="province" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_teacher['province']); ?>"
                                                    placeholder="Enter province" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="form-label required">ລະດັບການສຶກສາ</label>
                                                <select name="degree" class="form-select" required>
                                                    <option value="">ເລຶອກລະດັບການສຶກສາ</option>
                                                    <option value="Master" <?php echo $edit_teacher['degree'] == 'Master' ? 'selected' : ''; ?>>Master</option>
                                                    <option value="Bachelor" <?php echo $edit_teacher['degree'] == 'Bachelor' ? 'selected' : ''; ?>>Bachelor</option>
                                                    <option value="Other" <?php echo $edit_teacher['degree'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">ສອນຫ້ອງ</label>
                                                <select name="class_id" class="form-select">
                                                    <option value="">ເລຶອກຫ້ອງ (Optional)</option>
                                                    <?php
                                                    $class_result->data_seek(0); // Reset class result pointer
                                                    while ($class = $class_result->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($class['id']); ?>" <?php echo $edit_teacher['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($class['name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">
                                            ຍົກເລີກ
                                        </button>
                                        <button type="submit" name="submit_edit" class="btn-save">
                                            </i>ແກ້ໄຂ
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="text-danger">ບໍ່ພົບຂໍ້ມູນນາຍຄູໃນການ ແກ້ໄຂ.
                                    <?php echo isset($edit_error) ? htmlspecialchars($edit_error) : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Teacher Modal -->
            <div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteTeacherModalLabel">ຢືນຢັນການລຶບ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບຂໍ້ມູນນາຍຄູນີ້.</p>
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="process_teacher.php">
                                <input type="hidden" name="teacher_id" id="delete_teacher_id">
                                <button type="button" class="btn-cancel" data-bs-dismiss="modal"> ຍົກເລີກ</button>
                                <button type="submit" name="submit_delete" class="btn-delete">
                                    </i>ລຶບ
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
                "order": [[0, "asc"]] // Sort by ID by default
            });

            // Populate teacher_id in delete modal (management view only)
            $('#deleteTeacherModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var teacherId = button.data('teacher-id');
                $('#delete_teacher_id').val(teacherId);
            });

            // Auto-show edit modal if edit_id is in URL and teacher data exists (management view only)
            <?php if (!$show_report && isset($_GET['edit_id']) && $edit_teacher): ?>
                $('#editTeacherModal').modal('show');
            <?php endif; ?>
        });
    </script>
</body>

</html>

<?php
/* ob_end_flush();
if (isset($stmt)) {
    $stmt->close();
} */
$conn->close();
?>