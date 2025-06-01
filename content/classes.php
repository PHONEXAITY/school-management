<?php
// classes.php



include 'config/db.php'; // Include the database connection file.

// Check if the database connection ($conn) was successfully established by db.php.
// If not, terminate the script and display a generic error message.
if (!$conn) {
    // db.php should ideally handle its own connection errors and log them.
    // This die() is a fallback if $conn isn't set for some reason.
    die("Error: Database connection object not available. Check db.php.");
}

// --- Handle AJAX POST Requests ---
// This block processes all form submissions (Add, Edit, Delete) and data fetches (Classes, Levels)
// via AJAX requests. It ensures the response is always JSON.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // Set the content type header to JSON for all AJAX responses.
    header('Content-Type: application/json');

    // Use a switch statement to handle different actions based on the 'action' POST parameter.
    switch ($_POST['action']) {
        case 'add':
            // Sanitize and trim input data to prevent whitespace issues and potential XSS (though client-side validation is also key).
            $id = trim($_POST['classId'] ?? '');
            $name = trim($_POST['className'] ?? '');
            $level_id = trim($_POST['levelId'] ?? '');

            // Server-side validation: Check if required fields are empty.
            if (empty($id) || empty($name) || empty($level_id)) {
                echo json_encode(['status' => 'error', 'message' => 'All fields (ID, Name, Level) are required.']);
                exit; // Terminate script after sending response.
            }

            // Check if the Class ID already exists to prevent duplicates.
            $checkSql = "SELECT id FROM class WHERE id = ?";
            $stmt = $conn->prepare($checkSql);
            if ($stmt === false) {
                // Log detailed error for debugging purposes (server-side).
                error_log("Prepare failed for ID check: (" . $conn->errno . ") " . $conn->error);
                // Send a generic error message to the client.
                echo json_encode(['status' => 'error', 'message' => 'Database error during ID check.']);
                exit;
            }
            $stmt->bind_param("s", $id); // 's' indicates string type for the ID.
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // If ID exists, inform the client.
                echo json_encode(['status' => 'error', 'message' => 'Class ID already exists! Please use a unique ID.']);
            } else {
                // If ID is unique, proceed with insertion.
                $sql = "INSERT INTO class (id, name, level_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    error_log("Prepare failed for insert: (" . $conn->errno . ") " . $conn->error);
                    echo json_encode(['status' => 'error', 'message' => 'Database error during insert.']);
                    exit;
                }
                // 'sss' indicates all three parameters are strings for bind_param. Adjust if your 'id' or 'level_id' are integers.
                // Assuming they are Varchar/String as commonly done for IDs like 'C101', 'L1'.
                $stmt->bind_param("sss", $id, $name, $level_id);
                if ($stmt->execute()) {
                    // Success response.
                    echo json_encode(['status' => 'success', 'message' => 'Class added successfully']);
                } else {
                    // Error during execution.
                    error_log("Execute failed for insert: (" . $stmt->errno . ") " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Failed to add class: ' . $stmt->error]);
                }
            }
            $stmt->close(); // Close the prepared statement.
            exit; // Terminate script.

        case 'edit':
            // Sanitize and trim input data.
            $id = trim($_POST['editClassId'] ?? ''); // This is the ID of the class being edited.
            $name = trim($_POST['editClassName'] ?? '');
            $level_id = trim($_POST['editLevelId'] ?? '');

            // Server-side validation for edit.
            if (empty($id) || empty($name) || empty($level_id)) {
                echo json_encode(['status' => 'error', 'message' => 'All fields (ID, Name, Level) are required for editing.']);
                exit;
            }

            // SQL to update a class record.
            $sql = "UPDATE class SET name = ?, level_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Prepare failed for update: (" . $conn->errno . ") " . $conn->error);
                echo json_encode(['status' => 'error', 'message' => 'Database error during update.']);
                exit;
            }
            // 'sss' for name, level_id, and id (all strings). Adjust if your 'id' or 'level_id' are integers.
            $stmt->bind_param("sss", $name, $level_id, $id);
            if ($stmt->execute()) {
                // Check if any rows were actually affected.
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Class updated successfully']);
                } else {
                    // No rows affected means either no changes were made or the ID didn't exist.
                    echo json_encode(['status' => 'info', 'message' => 'No changes made or class not found.']);
                }
            } else {
                error_log("Execute failed for update: (" . $stmt->errno . ") " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update class: ' . $stmt->error]);
            }
            $stmt->close();
            exit;

        case 'delete':
            $id = trim($_POST['classId'] ?? '');

            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'Class ID is required for deletion.']);
                exit;
            }

            // SQL to delete a class record.
            $sql = "DELETE FROM class WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Prepare failed for delete: (" . $conn->errno . ") " . $conn->error);
                echo json_encode(['status' => 'error', 'message' => 'Database error during delete.']);
                exit;
            }
            $stmt->bind_param("s", $id); // 's' for the ID.
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Class deleted successfully']);
                } else {
                    echo json_encode(['status' => 'info', 'message' => 'Class not found or already deleted.']);
                }
            } else {
                error_log("Execute failed for delete: (" . $stmt->errno . ") " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete class: ' . $stmt->error]);
            }
            $stmt->close();
            exit;

        case 'fetch': // Fetch all classes with their associated level names for the table.
            // LEFT JOIN is used so that classes without a level_id (or if the level was deleted)
            // will still appear in the list, with 'N/A' for the level name.
            $sql = "SELECT c.id, c.name, c.level_id, l.name AS level_name FROM class c LEFT JOIN levels l ON c.level_id = l.id ORDER BY c.name ASC";
            $result = $conn->query($sql);
            $classes = [];
            if ($result) {
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $classes[] = $row;
                    }
                }
            } else {
                error_log("Error fetching classes: " . $conn->error);
                // Return an empty array on database error to prevent JS from breaking.
                echo json_encode([]);
                exit;
            }
            echo json_encode($classes); // Return the fetched classes as JSON.
            exit;

        case 'fetch_levels': // Fetch all levels for dropdown menus.
            $sql = "SELECT id, name FROM levels ORDER BY name ASC";
            $result = $conn->query($sql);
            $levels_data = [];
            if ($result) {
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $levels_data[] = $row;
                    }
                } else {
                    error_log("No levels found in the database for dropdown population.");
                }
            } else {
                error_log("Error fetching levels: " . $conn->error);
            }
            echo json_encode($levels_data); // Return the fetched levels as JSON.
            exit;

        default:
            // Handle invalid or unsupported actions.
            echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
            exit;
    }
}

// --- Initial Data Load for the Page (First Page Load, not AJAX) ---
// This block runs only when the page is first loaded directly (not via an an AJAX POST request).
// It populates the initial data that JavaScript will use to render the table and dropdowns.

// Fetch all classes for the initial table display.
$sql_classes_initial = "SELECT c.id, c.name, c.level_id, l.name AS level_name FROM class c LEFT JOIN levels l ON c.level_id = l.id ORDER BY c.name ASC";
$result_classes_initial = $conn->query($sql_classes_initial);
$classes = []; // Initialize an empty array for classes.
if ($result_classes_initial) {
    if ($result_classes_initial->num_rows > 0) {
        while ($row = $result_classes_initial->fetch_assoc()) {
            $classes[] = $row;
        }
    }
} else {
    // Log the error but don't terminate the page render for initial load.
    error_log("Error fetching initial classes data: " . $conn->error);
    // In a real application, you might set a variable here to indicate a data load error to the user.
}

// Fetch all levels for populating dropdowns in Add/Edit modals on initial page load.
$sql_levels_initial = "SELECT id, name FROM levels ORDER BY name ASC";
$result_levels_initial = $conn->query($sql_levels_initial);
$initial_levels = []; // Initialize an empty array for levels.
if ($result_levels_initial) {
    if ($result_levels_initial->num_rows > 0) {
        while ($row = $result_levels_initial->fetch_assoc()) {
            $initial_levels[] = $row;
        }
    } else {
        error_log("No levels found for initial page load from the database.");
    }
} else {
    error_log("Error fetching initial levels data: " . $conn->error);
}

// Close the database connection after all PHP operations are completed.
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom styles for better aesthetics and layout */
        body {
            font-family: "Inter", sans-serif;
            /* A modern, readable font */
        }

        .container-fluid {
            padding-top: 20px;
            /* Space from the top of the page */
        }

        .card {
            border-radius: 0.5rem;
            /* Rounded corners for card elements */
        }

        .card-header {
            background-color: #f8f9fc;
            /* Light background for card headers */
            border-bottom: 1px solid #e3e6f0;
            /* Subtle border at the bottom */
            border-radius: 0.5rem 0.5rem 0 0;
            /* Rounded top corners */
        }

        .table {
            border-radius: 0.5rem;
            overflow: hidden;
            /* Ensures rounded corners on the table itself */
            margin-bottom: 0;
            /* Remove default table bottom margin */
        }

        .table th,
        .table td {
            vertical-align: middle;
            /* Center content vertically in table cells */
            padding: 0.75rem;
            /* Padding for table cells */
        }

        .table thead th {
            background-color: #e9ecef;
            /* Light grey background for table headers */
            border-bottom: 2px solid #dee2e6;
            /* Thicker border for header separation */
        }

        .btn {
            border-radius: 0.375rem;
            /* Slightly rounded buttons */
        }

        .btn-circle {
            border-radius: 50%;
            /* Makes buttons perfectly circular */
            width: 30px;
            /* Fixed width for circular buttons */
            height: 30px;
            /* Fixed height for circular buttons */
            padding: 0;
            /* Remove padding to center icon */
            display: inline-flex;
            /* Use flexbox for centering icon */
            align-items: center;
            /* Center icon vertically */
            justify-content: center;
            /* Center icon horizontally */
        }

        .modal-content {
            border-radius: 0.5rem;
            /* Rounded corners for modal dialogs */
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
            /* Separator in modal header */
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            /* Separator in modal footer */
        }

        /* Styles for the custom notification toast */
        .notification {
            position: fixed;
            /* Fixed position relative to the viewport */
            top: 20px;
            /* 20px from the top */
            right: 20px;
            /* 20px from the right */
            z-index: 1050;
            /* Ensures it appears above Bootstrap modals */
            padding: 10px 20px;
            /* Padding inside the notification box */
            border-radius: 0.5rem;
            /* Rounded corners */
            color: white;
            /* White text color */
            display: none;
            /* Hidden by default */
            opacity: 0;
            /* Start fully transparent */
            transition: opacity 0.5s ease-in-out;
            /* Smooth fade in/out effect */
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            /* Subtle shadow for depth */
        }

        .notification.success {
            background-color: #28a745;
        }

        /* Green for success */
        .notification.error {
            background-color: #dc3545;
        }

        /* Red for error */
        .notification.info {
            background-color: #17a2b8;
        }

        /* Cyan for info */
        .notification.show {
            opacity: 1;
            display: block;
        }

        /* Make visible and opaque */
    </style>
</head>

<body>
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800">Class Management</h1>
        <p class="mb-4">Manage all class records here. You can add, edit, view, and delete class details.</p>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Classes</h6>
                <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addClassModal">
                    <i class="fas fa-plus"></i> Add New Class
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Class Name</th>
                                <th>Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="classTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addClassModal" tabindex="-1" role="dialog" aria-labelledby="addClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="addClassForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="classId">Class ID</label>
                            <input type="text" class="form-control" id="classId" name="classId" required>
                        </div>
                        <div class="form-group">
                            <label for="className">Class Name</label>
                            <input type="text" class="form-control" id="className" name="className" required>
                        </div>
                        <div class="form-group">
                            <label for="levelId">Level</label>
                            <select class="form-control" id="levelId" name="levelId" required>
                                <option value="">Select Level</option>
                            </select>
                        </div>
                        <input type="hidden" name="action" value="add">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editClassModal" tabindex="-1" role="dialog" aria-labelledby="editClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class->
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="editClassForm">
                    <div class="modal-body">
                        <input type="hidden" id="editClassId" name="editClassId">
                        <div class="form-group">
                            <label for="editClassName">Class Name</label>
                            <input type="text" class="form-control" id="editClassName" name="editClassName" required>
                        </div>
                        <div class="form-group">
                            <label for="editLevelId">Level</label>
                            <select class="form-control" id="editLevelId" name="editLevelId" required>
                                <option value="">Select Level</option>
                            </select>
                        </div>
                        <input type="hidden" name="action" value="edit">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteClassModal" tabindex="-1" role="dialog" aria-labelledby="deleteClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassModalLabel">Confirm Delete</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this class? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewClassModal" tabindex="-1" role="dialog" aria-labelledby="viewClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewClassModalLabel">Class Details</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body" id="viewClassDetailsBody">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>


    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Global JavaScript variables to hold class and level data.
        // These are initialized with data passed from PHP on the initial page load.
        // It's crucial that these JSON_ENCODE calls output valid JSON.
        let classes = <?php echo json_encode($classes); ?>;
        let levels = <?php echo json_encode($initial_levels); ?>;

        /**
         * Displays a temporary notification message on the screen.
         * This replaces traditional JavaScript alert boxes.
         * @param {string} message - The message to display.
         * @param {string} type - The type of notification ('success', 'error', 'info').
         */
        function showNotification(message, type = 'success') {
            const notification = $('#notification');
            // Remove existing type classes and add the new one.
            notification.removeClass('success error info').addClass(type).text(message);
            notification.addClass('show'); // Make the notification visible by adding the 'show' class.

            // Hide the notification after 3 seconds by removing the 'show' class.
            setTimeout(() => {
                notification.removeClass('show');
            }, 3000);
        }

        /**
         * Populates a select dropdown with level options.
         * This function is used for both the "Add Class" and "Edit Class" modals.
         * @param {string} selectId - The ID of the <select> HTML element to populate.
         * @param {string} selectedLevelId - (Optional) The ID of the level to pre-select in the dropdown.
         */
        function populateLevelDropdown(selectId, selectedLevelId = '') {
            const select = document.getElementById(selectId);
            // Clear existing options and add a default "Select Level" option.
            select.innerHTML = '<option value="">Select Level</option>';

            if (levels && levels.length > 0) {
                // Add an option for each level fetched from the database.
                levels.forEach(level => {
                    const option = document.createElement('option');
                    option.value = level.id;
                    option.textContent = level.name;
                    // If a selectedLevelId is provided, mark that option as selected.
                    if (selectedLevelId && level.id === selectedLevelId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            } else {
                // If no levels are available, add a disabled option to indicate this.
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "No levels available";
                option.disabled = true;
                select.appendChild(option);
            }
        }

        /**
         * Renders (or re-renders) the class data table using the global 'classes' array.
         * This function is called initially and after any data modification (add, edit, delete).
         */
        function renderTable() {
            const tbody = document.getElementById('classTableBody');
            tbody.innerHTML = ''; // Clear all existing rows from the table body.

            if (classes.length === 0) {
                // Display a message if there are no classes to show in the table.
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No classes available</td></tr>';
                return;
            }

            // Iterate over the classes array and dynamically build a table row for each class.
            classes.forEach((cls, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${cls.id}</td>
                        <td>${cls.name}</td>
                        <td>${cls.level_name || 'N/A'}</td> <td>
                            <a href="#" class="btn btn-info btn-circle btn-sm view-btn" data-id="${cls.id}" title="View" data-toggle="modal" data-target="#viewClassModal">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" class="btn btn-warning btn-circle btn-sm edit-btn" data-id="${cls.id}" title="Edit" data-toggle="modal" data-target="#editClassModal">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-circle btn-sm delete-btn" data-id="${cls.id}" title="Delete" data-toggle="modal" data-target="#deleteClassModal">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row; // Append the newly created row to the table body.
            });
        }

        /**
         * Fetches the latest class data from the server using an AJAX call and then re-renders the table.
         * This ensures the table always displays the most current information.
         */
        function fetchClasses() {
            $.ajax({
                url: 'classes.php', // Target PHP script for data operations
                type: 'POST',       // Use POST method for sending action
                data: { action: 'fetch' }, // Send the 'fetch' action
                dataType: 'json',   // Expect a JSON response from the server
                success: function (data) {
                    // Check if the received data is an array (expected format).
                    if (Array.isArray(data)) {
                        classes = data; // Update the global classes array with the fresh data.
                        renderTable();  // Re-render the table with the new data.
                    } else {
                        // Log and notify if the data format is unexpected.
                        console.error('Invalid data format from fetch classes:', data);
                        showNotification('Error: Could not retrieve updated class list. Data format issue.', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    // Log detailed AJAX error information to the console for debugging.
                    console.error('Error fetching classes:', status, error, xhr.responseText);
                    // Display a user-friendly error notification for the user.
                    showNotification('An error occurred while fetching classes. Please try again.', 'error');
                }
            });
        }

        /**
         * Fetches the latest level data from the server and updates dropdowns.
         * This is useful if levels can be changed elsewhere and need to be reloaded.
         * @param {function} callback - An optional callback function to execute after levels are fetched.
         */
        function fetchLevels(callback) {
            $.ajax({
                url: 'classes.php',
                type: 'POST',
                data: { action: 'fetch_levels' },
                dataType: 'json',
                success: function (data) {
                    console.log('Levels received:', data);
                    if (Array.isArray(data)) {
                        levels = data; // Update the global levels array.
                        populateLevelDropdown('levelId');      // Re-populate for Add modal.
                        populateLevelDropdown('editLevelId'); // Re-populate for Edit modal.
                        if (callback) callback(); // Execute callback if provided.
                    } else {
                        console.error('Invalid data format from fetch_levels:', data);
                        showNotification('Error: Could not load level data correctly.', 'error');
                        // Fallback options if levels cannot be loaded, informing the user.
                        $('#levelId').html('<option value="">Error loading levels</option>');
                        $('#editLevelId').html('<option value="">Error loading levels</option>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching levels:', status, error, xhr.responseText);
                    showNotification('An error occurred while fetching levels for dropdowns.', 'error');
                    $('#levelId').html('<option value="">Error loading levels</option>');
                    $('#editLevelId').html('<option value="">Error loading levels</option>');
                }
            });
        }

        // --- Document Ready: Code to run once the DOM (HTML structure) is fully loaded ---
        $(document).ready(function () {
            // Perform initial rendering of the table and populate dropdowns using data
            // that was echo'd by PHP directly into the script on page load.
            renderTable();
            populateLevelDropdown('levelId');
            populateLevelDropdown('editLevelId');

            // --- Event Handlers for Forms and Buttons ---

            // Handle submission of the Add Class form.
            $('#addClassForm').on('submit', function (e) {
                e.preventDefault(); // Prevent default browser form submission (which would refresh the page).

                // Get trimmed values from form fields for client-side validation.
                const classId = $('#classId').val().trim();
                const className = $('#className').val().trim();
                const levelId = $('#levelId').val();

                // Client-side validation: ensure fields are not empty before sending AJAX.
                if (!classId || !className || !levelId) {
                    showNotification('Please fill in all required fields for adding a class.', 'info');
                    return; // Stop execution if validation fails.
                }

                $.ajax({
                    url: 'classes.php',
                    type: 'POST',
                    data: $(this).serialize(), // Serialize form data (includes action=add and all input values).
                    dataType: 'json',          // Expect a JSON response from the server.
                    success: function (response) {
                        console.log('Add response:', response); // Log the server response.
                        if (response.status === 'success') {
                            showNotification(response.message, 'success'); // Show success notification.
                            $('#addClassForm')[0].reset();       // Clear form fields.
                            $('#addClassModal').modal('hide');   // Close the Add modal automatically.
                            fetchClasses();                      // Refresh the class table to show new data.
                        } else {
                            // Show error notification with the message from the server.
                            showNotification('Error adding class: ' + response.message, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        // Log detailed AJAX error information to the console for debugging.
                        console.error('Error adding class:', status, error, xhr.responseText);
                        // Show a generic error notification.
                        showNotification('An error occurred while adding the class. Please try again.', 'error');
                    }
                });
            });

            // Event handler for when an "Edit" button is clicked.
            // Uses event delegation (.on) because table rows are dynamically added/removed.
            $(document).on('click', '.edit-btn', function () {
                const classId = $(this).data('id'); // Get the class ID from the button's data-id attribute.
                const cls = classes.find(c => c.id === classId); // Find the corresponding class object in the local 'classes' array.

                if (cls) {
                    // Populate modal fields with existing class data.
                    $('#editClassId').val(cls.id);
                    $('#editClassName').val(cls.name);
                    // Populate levels dropdown and pre-select the current level for convenience.
                    populateLevelDropdown('editLevelId', cls.level_id);
                } else {
                    console.error('Class not found for editing:', classId);
                    showNotification('Could not find class data for editing.', 'error');
                }
            });

            // Handle submission of the Edit Class form.
            $('#editClassForm').on('submit', function (e) {
                e.preventDefault();

                const className = $('#editClassName').val().trim();
                const levelId = $('#editLevelId').val();

                // Client-side validation.
                if (!className || !levelId) {
                    showNotification('Please fill in all required fields for editing a class.', 'info');
                    return;
                }

                $.ajax({
                    url: 'classes.php',
                    type: 'POST',
                    data: $(this).serialize(), // Serialize form data (includes action=edit).
                    dataType: 'json',
                    success: function (response) {
                        console.log('Edit response:', response);
                        if (response.status === 'success' || response.status === 'info') {
                            showNotification(response.message, response.status);
                            $('#editClassModal').modal('hide'); // Close the Edit modal automatically.
                            fetchClasses();                      // Refresh the class table.
                        } else {
                            showNotification('Error updating class: ' + response.message, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error updating class:', status, error, xhr.responseText);
                        showNotification('An error occurred while updating the class.', 'error');
                    }
                });
            });

            // Variable to temporarily store the ID of the class to be deleted.
            let classIdToDelete = null;

            // When a delete button is clicked, store the class ID from its data-id attribute.
            // The Bootstrap modal will open automatically due to data-toggle="modal".
            $(document).on('click', '.delete-btn', function () {
                classIdToDelete = $(this).data('id');
            });

            // Handle the confirmation of the delete action when the "Delete" button inside the modal is clicked.
            $('#confirmDelete').on('click', function () {
                if (!classIdToDelete) {
                    showNotification('No class selected for deletion.', 'info');
                    return;
                }

                $.ajax({
                    url: 'classes.php',
                    type: 'POST',
                    data: { action: 'delete', classId: classIdToDelete }, // Send action and the ID to be deleted.
                    dataType: 'json',
                    success: function (response) {
                        console.log('Delete response:', response);
                        if (response.status === 'success' || response.status === 'info') {
                            showNotification(response.message, response.status);
                            $('#deleteClassModal').modal('hide'); // Close the Delete confirmation modal automatically.
                            classIdToDelete = null; // Clear the stored ID.
                            fetchClasses();         // Refresh the class table.
                        } else {
                            showNotification('Error deleting class: ' + response.message, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error deleting class:', status, error, xhr.responseText);
                        showNotification('An error occurred while deleting the class.', 'error');
                    }
                });
            });

            // Event handler for when a "View" button is clicked.
            // This populates the "View Class Details Modal" with information.
            $(document).on('click', '.view-btn', function () {
                const classId = $(this).data('id');
                const cls = classes.find(c => c.id === classId); // Find the class data in the local array.
                if (cls) {
                    // Build HTML to display class details.
                    const detailsHtml = `
                        <p><strong>Class ID:</strong> ${cls.id}</p>
                        <p><strong>Class Name:</strong> ${cls.name}</p>
                        <p><strong>Level:</strong> ${cls.level_name || 'N/A'}</p>
                    `;
                    $('#viewClassDetailsBody').html(detailsHtml); // Insert details into the modal body.
                    // The modal is automatically opened by data-toggle="modal" attribute on the button.
                } else {
                    showNotification('Class details not found.', 'error');
                    $('#viewClassModal').modal('hide'); // If data not found, hide the modal just in case it opened.
                }
            });
        });
    </script>
</body>

</html>