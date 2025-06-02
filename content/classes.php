<?php
ob_start(); // Start output buffering

include 'config/db.php';

// Check if report view is requested
$show_report = isset($_GET['report']) && $_GET['report'] == '1';

// Check if PDF generation is requested for the report
if ($show_report && isset($_POST['save_pdf']) && $_POST['save_pdf'] == '1') {
    header("Location: generate_class_report_pdf.php?" . http_build_query($_POST));
    ob_end_clean();
    exit;
}

// Fetch levels for the form (used in both views)
$level_sql = "SELECT id, name FROM levels";
$level_result = $conn->query($level_sql);
if (!$level_result) {
    die("Error fetching levels: " . $conn->error);
}

// Report View: Fetch class with level filter
if ($show_report) {
    $selected_level = isset($_POST['level_id']) ? $conn->real_escape_string($_POST['level_id']) : '';
    $where_clause = $selected_level ? "WHERE c.level_id = ?" : "";
    $sql = "SELECT c.*, l.name AS level_name 
            FROM class c 
            LEFT JOIN levels l ON c.level_id = l.id 
            $where_clause 
            ORDER BY c.id";

    if ($selected_level) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $selected_level);
        $stmt->execute();
        $class_result = $stmt->get_result();
    } else {
        $class_result = $conn->query($sql);
        if (!$class_result) {
            die("Query failed: " . $conn->error);
        }
    }
} else {
    // Management View: Fetch class
    $sql = "SELECT c.*, l.name AS level_name 
            FROM class c 
            LEFT JOIN levels l ON c.level_id = l.id 
            ORDER BY c.id";
    $class_result = $conn->query($sql);
    if (!$class_result) {
        die("Query failed: " . $conn->error);
    }

    // Debugging: Log the number of rows
    error_log("Number of class fetched: " . $class_result->num_rows);

    // Fetch class data for edit modal
    $edit_class = null;
    $edit_error = null;
    if (isset($_GET['edit_id'])) {
        $edit_id = $conn->real_escape_string($_GET['edit_id']);
        $sql = "SELECT * FROM class WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $edit_error = "Failed to prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("s", $edit_id); // Changed to "s" for VARCHAR
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $edit_class = $result->fetch_assoc();
                } else {
                    $edit_error = "No class found with ID: " . htmlspecialchars($edit_id);
                }
            } else {
                $edit_error = "Query execution failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_GET['edit_id'])) {
        $edit_error = "Invalid class ID provided";
    }
}

// Display success/error message if redirected from process_class.php
$message = isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_report ? 'Class Report' : 'Class Management'; ?></title>
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
            width: 60%;
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
            width: 100%;
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
            width: 100%;
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

        .alert {
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-2">
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'Error') === false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show"
                role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($show_report): ?>
            <!-- Report View -->
            <h1 class="h3 mb-3 text-gray-800">Class Report</h1>
            <p class="mb-4 text-muted">Generate a report of class details by level.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Class Report</h6>
                    <div>
                        <button class="btn btn-primary me-2" onclick="window.print()"><i
                                class="fas fa-print me-2"></i>Print</button>

                        <a href="classes.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to
                            Management</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Level Filter -->
                    <form method="post" id="filter_form" class="filter-form mb-4">
                        <div class="d-flex align-items-center">
                            <label class="form-label">Level:</label>
                            <select name="level_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Levels</option>
                                <?php
                                $level_result->data_seek(0);
                                while ($level = $level_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($level['id']); ?>" <?php echo $selected_level == $level['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level['name']); ?>
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
                                    <th>Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($class_result->num_rows > 0): ?>
                                    <?php while ($row = $class_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['level_name'] ?: 'N/A'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No class found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Management View -->
            <h1 class="h3 mb-3 text-gray-800">Class Management</h1>
            <p class="mb-4 text-muted">Effortlessly manage class records with options to add, edit, or delete details.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Class</h6>
                    <div>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addClassModal">
                            <i class="fas fa-plus me-2"></i>Add New Class
                        </button>
                        <a href="classes.php?report=1" class="btn btn-success">
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
                                    <th>Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($class_result->num_rows > 0): ?>
                                    <?php while ($row = $class_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['level_name'] ?: 'N/A'); ?></td>
                                            <td>
                                                <a href="classes.php?edit_id=<?php echo urlencode($row['id']); ?>"
                                                    class="btn btn-warning btn-circle btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete"
                                                    data-bs-toggle="modal" data-bs-target="#deleteClassModal"
                                                    data-class-id="<?php echo htmlspecialchars($row['id']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No class found. Please add a class to get started.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Class Modal -->
            <div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="process_class.php">
                                <div class="col g-2">
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label required">Class ID</label>
                                            <input type="text" name="id" class="form-control" placeholder="Enter class ID"
                                                maxlength="50" required>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label required">Class Name</label>
                                            <input type="text" name="name" class="form-control"
                                                placeholder="Enter class name" maxlength="100" required>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label required">Level</label>
                                            <select name="level_id" class="form-select" required>
                                                <option value="">Select Level</option>
                                                <?php
                                                $level_result->data_seek(0);
                                                while ($level = $level_result->fetch_assoc()): ?>
                                                    <option value="<?php echo htmlspecialchars($level['id']); ?>">
                                                        <?php echo htmlspecialchars($level['name']); ?>
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

            <!-- Edit Class Modal -->
            <div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($edit_class): ?>
                                <form method="post" action="process_class.php">
                                    <input type="hidden" name="class_id"
                                        value="<?php echo htmlspecialchars($edit_class['id']); ?>">
                                    <div class="col g-2">
                                        <div>
                                            <div class="form-group">
                                                <label class="form-label required">Class ID</label>
                                                <input type="text" name="id" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_class['id']); ?>" maxlength="50"
                                                    required>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="form-group">
                                                <label class="form-label required">Class Name</label>
                                                <input type="text" name="name" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_class['name']); ?>"
                                                    placeholder="Enter class name" maxlength="100" required>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="form-group">
                                                <label class="form-label required">Level</label>
                                                <select name="level_id" class="form-select" required>
                                                    <option value="">Select Level</option>
                                                    <?php
                                                    $level_result->data_seek(0);
                                                    while ($level = $level_result->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($level['id']); ?>" <?php echo $edit_class['level_id'] == $level['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($level['name']); ?>
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
                                <p class="text-danger">No class data found for editing.
                                    <?php echo isset($edit_error) ? htmlspecialchars($edit_error) : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Class Modal -->
            <div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteClassModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this class? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="process_class.php">
                                <input type="hidden" name="class_id" id="delete_class_id">
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

            // Populate class_id in delete modal (management view only)
            $('#deleteClassModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var classId = button.data('class-id');
                $('#delete_class_id').val(classId);
            });

            // Auto-show edit modal if edit_id is in URL and class data exists (management view only)
            <?php if (!$show_report && isset($_GET['edit_id']) && $edit_class): ?>
                $('#editClassModal').modal('show');
            <?php endif; ?>
        });
    </script>
</body>

</html>

<?php
ob_end_flush();
$conn->close();
?>