<?php
ob_start(); // Start output buffering

include 'config/db.php';

// Check if report view is requested
$show_report = isset($_GET['report']) && $_GET['report'] == '1';

// Check if PDF generation is requested for the report
if ($show_report && isset($_POST['save_pdf']) && $_POST['save_pdf'] == '1') {
    header("Location: generate_user_report_pdf.php?" . http_build_query($_POST));
    ob_end_clean();
    exit;
}

// Define the fixed roles
$roles = ['Admin', 'Teacher'];

// Report View: Fetch users with role filter
if ($show_report) {
    $selected_role = isset($_POST['role']) ? $_POST['role'] : '';
    $where_clause = $selected_role ? "WHERE u.role = ?" : "";
    $sql = "SELECT * FROM user u $where_clause ORDER BY u.id";

    if ($selected_role) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selected_role);
        $stmt->execute();
        $users_result = $stmt->get_result();
    } else {
        $users_result = $conn->query($sql);
    }
} else {
    // Management View: Fetch users
    $sql = "SELECT * FROM user";
    $users_result = $conn->query($sql);

    // Fetch user data for edit modal
    $edit_user = null;
    $edit_error = null;
    if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
        $edit_id = (int) $_GET['edit_id'];
        $sql = "SELECT * FROM user WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $edit_error = "Failed to prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $edit_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $edit_user = $result->fetch_assoc();
                } else {
                    $edit_error = "No user found with ID: $edit_id";
                }
            } else {
                $edit_error = "Query execution failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_GET['edit_id'])) {
        $edit_error = "Invalid user ID provided";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_report ? 'User Report' : 'Users Management'; ?></title>
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
            <h1 class="h3 mb-3 text-gray-800">ລາຍງານຂໍ້ມູນຜູ້ໃຊ້ລະບົບ</h1>
            <p class="mb-4 text-muted">ສ້າງລາຍງານລາຍລະອຽດຂອງຜູ້ໃຊ້ໂດຍອີງຕາມບົດບາດຂອງຜູ້ໃຊ້.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">ລາຍງານຜູ້ໃຊ້</h6>
                    <div>
                        <button class="btn btn-primary me-2" onclick="window.print()"><i
                                class="fas fa-print me-2"></i>Print</button>

                        <a href="user.php" class="btn btn-secondary"><i
                                class="fas fa-arrow-left me-2"></i>ກັບໄປໜ້າຈັດການ</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Role Filter -->
                    <form method="post" id="filter_form" class="filter-form mb-4">
                        <div class="d-flex align-items-center">
                            <label class="form-label">ສະຖານະ:</label>
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <option value="">ທັງໝົດ</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $selected_role == $role ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role); ?>
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
                                    <th>ລະຫັດ</th>
                                    <th>ຊື່ຜູ້ໃຊ້</th>
                                    <!--  <th>Password</th> -->
                                    <th>ສະຖານະສິດທິ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <!-- Note: For security, consider hashing -->
                                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Management View -->
            <h1 class="h3 mb-3 text-gray-800">Users Management</h1>
            <p class="mb-4 text-muted">Effortlessly manage user records with options to add, edit, or delete details.</p>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Users</h6>
                    <div>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Add New User
                        </button>
                        <a href="user.php?report=1" class="btn btn-success">
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
                                    <th>Username</th>

                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <!--   <td><?php echo htmlspecialchars($row['password']); ?></td> -->
                                        <!-- Note: For security, consider hashing -->
                                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                                        <td>
                                            <a href="user.php?edit_id=<?php echo htmlspecialchars($row['id']); ?>"
                                                class="btn btn-warning btn-circle btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete"
                                                data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                data-user-id="<?php echo htmlspecialchars($row['id']); ?>">
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

            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="process_user.php">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required">Username</label>
                                            <input type="text" name="username" class="form-control"
                                                placeholder="Enter username" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required">Password</label>
                                            <input type="password" name="password" class="form-control"
                                                placeholder="Enter password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required">Role</label>
                                            <select name="role" class="form-select" required>
                                                <option value="">Select Role</option>
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo htmlspecialchars($role); ?>">
                                                        <?php echo htmlspecialchars($role); ?>
                                                    </option>
                                                <?php endforeach; ?>
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

            <!-- Edit User Modal -->
            <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($edit_user): ?>
                                <form method="post" action="process_user.php">
                                    <input type="hidden" name="user_id"
                                        value="<?php echo htmlspecialchars($edit_user['id']); ?>">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label required">Username</label>
                                                <input type="text" name="username" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_user['username']); ?>"
                                                    placeholder="Enter username" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label required">Password</label>
                                                <input type="password" name="password" class="form-control"
                                                    value="<?php echo htmlspecialchars($edit_user['password']); ?>"
                                                    placeholder="Enter password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label required">Role</label>
                                                <select name="role" class="form-select" required>
                                                    <option value="">Select Role</option>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $edit_user['role'] == $role ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($role); ?>
                                                        </option>
                                                    <?php endforeach; ?>
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
                                <p class="text-danger">No user data found for editing.
                                    <?php echo isset($edit_error) ? htmlspecialchars($edit_error) : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete User Modal -->
            <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="process_user.php">
                                <input type="hidden" name="user_id" id="delete_user_id">
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

            // Populate user_id in delete modal (management view only)
            $('#deleteUserModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var userId = button.data('user-id');
                $('#delete_user_id').val(userId);
            });

            // Auto-show edit modal if edit_id is in URL and user data exists (management view only)
            <?php if (!$show_report && isset($_GET['edit_id']) && $edit_user): ?>
                $('#editUserModal').modal('show');
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