<?php
// Include database connection
include 'config/db.php'; // Assuming db.php contains the connection logic

// Handle form submissions (Add, Edit, Delete, Fetch)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $id = $_POST['termId'];
            $name = $_POST['termName'];

            // Check if ID already exists
            $checkSql = "SELECT id FROM term WHERE id = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'term ID already exists!']);
            } else {
                $sql = "INSERT INTO term (id, name) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $id, $name);
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'term added successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to add term']);
                }
            }
            $stmt->close();
            exit;
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['edittermId'];
            $name = $_POST['edittermName'];
            $sql = "UPDATE term SET name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $name, $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'term updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update term']);
            }
            $stmt->close();
            exit;
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['termId'];
            $sql = "DELETE FROM term WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'term deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete term']);
            }
            $stmt->close();
            exit;
        } elseif ($_POST['action'] == 'fetch') {
            $sql = "SELECT * FROM term";
            $result = $conn->query($sql);
            $classes = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $classes[] = $row;
                }
            }
            echo json_encode($classes);
            exit;
        }
    }
}

// Fetch all terms for initial table render
$sql = "SELECT * FROM term";
$result = $conn->query($sql);
$classes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>term Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">ຈັດການຂໍ້ມູນພາກຮຽນ</h1>
    <p class="mb-4">ຈັດການຂໍ້ມູນພາກຮຽນທັງຫມົດຢູ່ໜ້ານີ້. ທ່ານສາມາດເພີ່ມ, ແກ້ໄຂ, ເບິ່ງ ແລະ ລຶບ.</p>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">ຂໍ້ມູນພາກຮຽນ</h6>
            <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addClassModal">
                <i class="fas fa-plus"></i> ເພີ່ມພາກຮຽນ
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ລະຫັດ</th>
                            <th>ພາກຮຽນ</th>
                            <th>ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody id="classTableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add term Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1" role="dialog" aria-labelledby="addClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">ເພີ່ມພາກຮຽນ</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="addClassForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="termId">ລະຫັດ</label>
                            <input type="text" class="form-control" id="termId" name="termId" required>
                        </div>
                        <div class="form-group">
                            <label for="termName">ພາກຮຽນ</label>
                            <input type="text" class="form-control" id="termName" name="termName" required>
                        </div>
                        <input type="hidden" name="action" value="add">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-primary">ບັນທືນ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit term Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1" role="dialog" aria-labelledby="editClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="editClassModalLabel">ແກ້ໄຂຂໍ້ມູນພາກຮຽນ</h6>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="editClassForm">
                    <div class="modal-body">
                        <input type="hidden" id="edittermId" name="edittermId">
                        <div class="form-group">
                            <label for="edittermName">ພາກຮຽນ</label>
                            <input type="text" class="form-control" id="edittermName" name="edittermName" required>
                        </div>
                        <input type="hidden" name="action" value="edit">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-primary">ແກ້ໄຂ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClassModal" tabindex="-1" role="dialog" aria-labelledby="deleteClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassModalLabel">ຢືນຢັນການລຶບ</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບຂໍ້ມູນພາກຮຽນນີິ້?
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">ຍົກເລີກ</button>
                    <button class="btn btn-danger" id="confirmDelete">ລຶບ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Initialize classes from PHP
        let classes = <?php echo json_encode($classes); ?>;

        // Function to render table
        function renderTable() {
            const tbody = document.getElementById('classTableBody');
            tbody.innerHTML = '';
            if (classes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No terms available</td></tr>';
                return;
            }
            classes.forEach((cls, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${cls.id}</td>
                        <td>${cls.name}</td>
                        <td>
                 
                            <a href="#" class="btn btn-warning btn-circle btn-sm edit-btn" data-id="${cls.id}" title="Edit" data-toggle="modal" data-target="#editClassModal">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-circle btn-sm delete-btn" data-id="${cls.id}" title="Delete" data-toggle="modal" data-target="#deleteClassModal">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Function to fetch updated classes from the server
        function fetchClasses() {
            $.ajax({
                url: 'terms.php',
                type: 'POST',
                data: { action: 'fetch' },
                dataType: 'json',
                success: function (data) {
                    if (Array.isArray(data)) {
                        classes = data;
                        renderTable();
                    } else {
                        console.error('Invalid data format from fetch:', data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching classes:', error);
                    // Fallback: Refresh the page to restore UI
                    location.reload();
                }
            });
        }

        // Initial table render
        renderTable();

        // Add Class Form Submission
        $('#addClassForm').on('submit', function (e) {
            e.preventDefault();
            const termId = $('#termId').val().trim();
            const termName = $('#termName').val().trim();

            // Validate inputs
            if (!termId || !termName) {
                return;
            }

            // Submit to PHP
            $.ajax({
                url: 'terms.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    console.log('Add response:', response);
                    $('#addClassForm')[0].reset();
                    $('#addClassModal').modal('hide');
                    fetchClasses(); // Refresh the table
                },
                error: function (xhr, status, error) {
                    console.error('Error adding class:', error);
                    $('#addClassModal').modal('hide');
                    fetchClasses(); // Try to refresh anyway
                }
            });
        });

        // Edit Class Form Submission
        $('#editClassForm').on('submit', function (e) {
            e.preventDefault();
            const termName = $('#edittermName').val().trim();

            // Validate input
            if (!termName) {
                return;
            }

            // Submit to PHP
            $.ajax({
                url: 'terms.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    console.log('Edit response:', response);
                    $('#editClassModal').modal('hide');
                    fetchClasses(); // Refresh the table
                },
                error: function (xhr, status, error) {
                    console.error('Error updating class:', error);
                    $('#editClassModal').modal('hide');
                    fetchClasses(); // Try to refresh anyway
                }
            });
        });

        // Populate Edit Modal
        $(document).on('click', '.edit-btn', function () {
            const termId = $(this).data('id');
            const cls = classes.find(c => c.id === termId);
            if (cls) {
                $('#edittermId').val(cls.id);
                $('#edittermName').val(cls.name);
            }
        });

        // Delete Class
        let termIdToDelete = null;
        $(document).on('click', '.delete-btn', function () {
            termIdToDelete = $(this).data('id');
        });

        $('#confirmDelete').on('click', function () {
            // Submit to PHP
            $.ajax({
                url: 'terms.php',
                type: 'POST',
                data: { action: 'delete', termId: termIdToDelete },
                dataType: 'json',
                success: function (response) {
                    console.log('Delete response:', response);
                    $('#deleteClassModal').modal('hide');
                    fetchClasses(); // Refresh the table
                },
                error: function (xhr, status, error) {
                    console.error('Error deleting class:', error);
                    $('#deleteClassModal').modal('hide');
                    fetchClasses(); // Try to refresh anyway
                }
            });
        });

        // View Class
        $(document).on('click', '.view-btn', function () {
            const termId = $(this).data('id');
            const cls = classes.find(c => c.id === termId);
            if (cls) {
                alert(`term ID: ${cls.id}\nterm Name: ${cls.name}`);
            }
        });
    </script>
</body>

</html>