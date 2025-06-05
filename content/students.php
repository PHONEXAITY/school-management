<?php
ob_start(); // Start output buffering

include 'config/db.php';

// Check if report view is requested
$show_report = isset($_GET['report']) && $_GET['report'] == '1';

// Check if PDF generation is requested for the report
if ($show_report && isset($_POST['save_pdf']) && $_POST['save_pdf'] == '1') {
    header("Location: generate_student_report_pdf.php?" . http_build_query($_POST));
    ob_end_clean();
    exit;
}

// Fetch classes for the form (used in both views)
$class_sql = "SELECT id, name FROM class";
$class_result = $conn->query($class_sql);

// Report View: Fetch students with class filter
if ($show_report) {
    $selected_class = isset($_POST['class_id']) ? $_POST['class_id'] : '';
    $where_clause = $selected_class ? "WHERE s.class_id = ?" : "";
    $sql = "SELECT s.*, c.name AS class_name 
            FROM student s 
            LEFT JOIN class c ON s.class_id = c.id
            $where_clause
            ORDER BY s.id";

    if ($selected_class) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selected_class);
        $stmt->execute();
        $students_result = $stmt->get_result();
    } else {
        $students_result = $conn->query($sql);
    }
} else {
    // Management View: Fetch students
    $sql = "SELECT s.*, c.name AS class_name 
            FROM student s 
            LEFT JOIN class c ON s.class_id = c.id";
    $students_result = $conn->query($sql);

    // Fetch student data for edit modal
    $edit_student = null;
    $edit_error = null;
    if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
        $edit_id = (int) $_GET['edit_id'];
        $sql = "SELECT * FROM student WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $edit_error = "Failed to prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $edit_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $edit_student = $result->fetch_assoc();
                } else {
                    $edit_error = "No student found with ID: $edit_id";
                }
            } else {
                $edit_error = "Query execution failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_GET['edit_id'])) {
        $edit_error = "Invalid student ID provided";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_report ? 'Student Report' : 'Students Management'; ?></title>
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
            padding: 8px 18px;
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
            padding: 8px 18px;
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
            padding: 8px 18px;
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
            background: Color white;
            color: white;
            padding: 20px;
        }

        .modal-title {
            font-weight: 700;
            color: black;
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
            <h1 class="h3 mb-3 text-gray-800">Student Report</h1>
            <p class="mb-4 text-muted">Generate a report of student details by class.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Student Report</h6>
                    <div>
                        <button class="btn btn-primary me-2" onclick="window.print()"><i
                                class="fas fa-print me-2"></i>Print</button>

                        <a href="students.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to
                            Management</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Class Filter -->
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
                        <input type="hidden" name="save_pdf" id="save_pdf" value="0">
                    </form>

                    <!-- Report Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Birth Date</th>
                                    <th>Village</th>
                                    <th>District</th>
                                    <th>Province</th>
                                    <th>Parent Name</th>
                                    <th>Phone</th>
                                    <th>Class</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($row['birth_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['village']); ?></td>
                                        <td><?php echo htmlspecialchars($row['district']); ?></td>
                                        <td><?php echo htmlspecialchars($row['province']); ?></td>
                                        <td><?php echo htmlspecialchars($row['parent_name'] ?: 'N/A'); ?></td>
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
            <h1 class="h3 mb-3 text-gray-800">Students Management</h1>
            <p class="mb-4 text-muted">Effortlessly manage student records with options to add, edit, view, or delete
                details.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Students</h6>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-plus me-2"></i>Add New Student
                        </button>
                        <a href="students.php?report=1" class="btn btn-success">
                            <i class="fas fa-file-alt me-2"></i>Generate Report
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Birth Date</th>
                                    <th>Village</th>
                                    <th>District</th>
                                    <th>Province</th>
                                    <th>Parent Name</th>
                                    <th>Phone</th>
                                    <th>Class</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($row['birth_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['village']); ?></td>
                                        <td><?php echo htmlspecialchars($row['district']); ?></td>
                                        <td><?php echo htmlspecialchars($row['province']); ?></td>
                                        <td><?php echo htmlspecialchars($row['parent_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <a href="students.php?edit_id=<?php echo htmlspecialchars($row['id']); ?>"
                                                class="btn btn-warning btn-circle btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete"
                                                data-bs-toggle="modal" data-bs-target="#deleteStudentModal"
                                                data-student-id="<?php echo htmlspecialchars($row['id']); ?>">
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

            <!-- Add Student Modal -->
            <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="process_student.php">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">First Name</label>
                                            <input type="text" name="fname" class="form-control"
                                                placeholder="Enter first name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">Last Name</label>
                                            <input type="text" name="lname" class="form-control"
                                                placeholder="Enter last name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label required">Gender</label>
                                            <select name="gender" class="form-select" required>
                                                <option value="">Select Gender</option>
                                                <option value="M">Male</option>
                                                <option value="F">Female</option>
                                                <option value="O">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">Date of Birth</label>
                                            <input type="date" name="birth_date" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">Phone</label>
                                            <input type="tel" name="phone" class="form-control"
                                                placeholder="Enter phone number" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">Village</label>
                                            <input type="text" name="village" class="form-control"
                                                placeholder="Enter village" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">District</label>
                                            <input type="text" name="district" class="form-control"
                                                placeholder="Enter district" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">Province</label>
                                            <input type="text" name="province" class="form-control"
                                                placeholder="Enter province" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label required">Parent Name</label>
                                            <input type="text" name="parent_name" class="form-control"
                                                placeholder="Enter parent name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Class</label>
                                            <select name="class_id" class="form-select">
                                                <option value="">Select Class (Optional)</option>
                                                <?php
                                                $class_result->data_seek(0);
                                                while ($class = $class_result->fetch_assoc()): ?>
                                                    <option value="<?php echo htmlspecialchars($class['id']); ?>">
                                                        <?php echo htmlspecialchars($class['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="submit_add" class="btn-save"><i
                                            class="fas fa-save me-2"></i>Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Student Modal -->
            <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($edit_student): ?>
                                <form method="post" action="process_student.php">
                                    <input type="hidden" name="student_id"
                                        value="<?php echo htmlspecialchars($edit_student['id']); ?>">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">First Name</label>
                                                <input type="text" name="fname" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['fname']); ?>"
                                                    placeholder="Enter first name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">Last Name</label>
                                                <input type="text" name="lname" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['lname']); ?>"
                                                    placeholder="Enter last name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="form-label required">Gender</label>
                                                <select name="gender" class="form-select" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="M" <?php echo $edit_student['gender'] == 'M' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="F" <?php echo $edit_student['gender'] == 'F' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="O" <?php echo $edit_student['gender'] == 'O' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">Date of Birth</label>
                                                <input type="date" name="birth_date" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['birth_date']); ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">Phone</label>
                                                <input type="tel" name="phone" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['phone']); ?>"
                                                    placeholder="Enter phone number" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">Village</label>
                                                <input type="text" name="village" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['village']); ?>"
                                                    placeholder="Enter village" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">District</label>
                                                <input type="text" name="district" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['district']); ?>"
                                                    placeholder="Enter district" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">Province</label>
                                                <input type="text" name="province" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['province']); ?>"
                                                    placeholder="Enter province" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label required">Parent Name</label>
                                                <input type="text" name="parent_name" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_student['parent_name']); ?>"
                                                    placeholder="Enter parent name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Class</label>
                                                <select name="class_id" class="form-select">
                                                    <option value="">Select Class (Optional)</option>
                                                    <?php
                                                    $class_result->data_seek(0);
                                                    while ($class = $class_result->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($class['id']); ?>" <?php echo $edit_student['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($class['name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="submit_edit" class="btn-save"><i
                                                class="fas fa-save me-2"></i>Update</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="text-danger">No student data found for editing.
                                    <?php echo isset($edit_error) ? htmlspecialchars($edit_error) : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Student Modal -->
            <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteStudentModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this student? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="process_student.php">
                                <input type="hidden" name="student_id" id="delete_student_id">
                                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="submit_delete" class="btn-delete"><i
                                        class="fas fa-trash me-2"></i>Delete</button>
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

            // Populate student_id in delete modal (management view only)
            $('#deleteStudentModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var studentId = button.data('student-id');
                $('#delete_student_id').val(studentId);
            });

            // Auto-show edit modal if edit_id is in URL and student data exists (management view only)
            <?php if (!$show_report && isset($_GET['edit_id']) && $edit_student): ?>
                $('#editStudentModal').modal('show');
            <?php endif; ?>
        });
    </script>
</body>

</html>

<?php
ob_end_flush();
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>