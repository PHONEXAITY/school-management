<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method detected.");
    header("Location: classes.php?message=" . urlencode("Invalid request method."));
    exit;
}

$conn->begin_transaction();

try {
    // Validate level_id exists in levels table
    $level_check_sql = "SELECT id FROM levels WHERE id = ?";
    $level_check_stmt = $conn->prepare($level_check_sql);
    if (!$level_check_stmt) {
        throw new Exception("Failed to prepare level check statement: " . $conn->error);
    }

    if (isset($_POST['submit_add'])) {
        $id = trim($_POST['id']);
        $name = trim($_POST['name']);
        $level_id = trim($_POST['level_id']);

        // Validate inputs
        if (empty($id)) {
            throw new Exception("Class ID is required.");
        }
        if (strlen($id) > 50) {
            throw new Exception("Class ID must not exceed 50 characters.");
        }
        if (empty($name)) {
            throw new Exception("Class name is required.");
        }
        if (strlen($name) > 100) {
            throw new Exception("Class name must not exceed 100 characters.");
        }
        if (empty($level_id)) {
            throw new Exception("Please select a valid level.");
        }

        // Check if id already exists
        $id_check_sql = "SELECT id FROM class WHERE id = ?";
        $id_check_stmt = $conn->prepare($id_check_sql);
        if (!$id_check_stmt) {
            throw new Exception("Failed to prepare ID check statement: " . $conn->error);
        }
        $id_check_stmt->bind_param("s", $id);
        $id_check_stmt->execute();
        $id_check_result = $id_check_stmt->get_result();
        if ($id_check_result->num_rows > 0) {
            throw new Exception("Class ID already exists.");
        }
        $id_check_stmt->close();

        // Check if level_id exists in levels table
        $level_check_stmt->bind_param("s", $level_id);
        $level_check_stmt->execute();
        $level_check_result = $level_check_stmt->get_result();
        if ($level_check_result->num_rows === 0) {
            throw new Exception("Selected level ID does not exist in the levels table.");
        }

        $sql = "INSERT INTO class (id, name, level_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for INSERT: " . $conn->error);
        }
        $stmt->bind_param("sss", $id, $name, $level_id);

        if ($stmt->execute()) {
            $message = "Class added successfully.";
            error_log("Class added: ID=$id, Name=$name, Level=$level_id");
        } else {
            throw new Exception("Failed to add class: " . $stmt->error);
        }
    } elseif (isset($_POST['submit_edit'])) {
        $class_id = trim($_POST['class_id']);
        $id = trim($_POST['id']);
        $name = trim($_POST['name']);
        $level_id = trim($_POST['level_id']);

        // Validate inputs
        if (empty($class_id)) {
            throw new Exception("Invalid class ID.");
        }
        if (empty($id)) {
            throw new Exception("Class ID is required.");
        }
        if (strlen($id) > 50) {
            throw new Exception("Class ID must not exceed 50 characters.");
        }
        if (empty($name)) {
            throw new Exception("Class name is required.");
        }
        if (strlen($name) > 100) {
            throw new Exception("Class name must not exceed 100 characters.");
        }
        if (empty($level_id)) {
            throw new Exception("Please select a valid level.");
        }

        // Check if new id is different and already exists
        if ($id !== $class_id) {
            $id_check_sql = "SELECT id FROM class WHERE id = ?";
            $id_check_stmt = $conn->prepare($id_check_sql);
            if (!$id_check_stmt) {
                throw new Exception("Failed to prepare ID check statement: " . $conn->error);
            }
            $id_check_stmt->bind_param("s", $id);
            $id_check_stmt->execute();
            $id_check_result = $id_check_stmt->get_result();
            if ($id_check_result->num_rows > 0) {
                throw new Exception("Class ID already exists.");
            }
            $id_check_stmt->close();
        }

        // Check if level_id exists in levels table
        $level_check_stmt->bind_param("s", $level_id);
        $level_check_stmt->execute();
        $level_check_result = $level_check_stmt->get_result();
        if ($level_check_result->num_rows === 0) {
            throw new Exception("Selected level ID does not exist in the levels table.");
        }

        $sql = "UPDATE class SET id = ?, name = ?, level_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for UPDATE: " . $conn->error);
        }
        $stmt->bind_param("ssss", $id, $name, $level_id, $class_id);

        if ($stmt->execute()) {
            $message = "Class updated successfully.";
            error_log("Class updated: ID=$id, Name=$name, Level=$level_id");
        } else {
            throw new Exception("Failed to update class: " . $stmt->error);
        }
    } elseif (isset($_POST['submit_delete'])) {
        $class_id = trim($_POST['class_id']);

        if (empty($class_id)) {
            throw new Exception("Invalid class ID.");
        }

        $sql = "DELETE FROM class WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for DELETE: " . $conn->error);
        }
        $stmt->bind_param("s", $class_id);

        if ($stmt->execute()) {
            $message = "Class deleted successfully.";
            error_log("Class deleted: ID=$class_id");
        } else {
            throw new Exception("Failed to delete class: " . $stmt->error);
        }
    } else {
        throw new Exception("Invalid action.");
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $message = "Error: " . $e->getMessage();
    error_log("Error in process_class.php: " . $e->getMessage());
}

if (isset($stmt)) {
    $stmt->close();
}
if (isset($level_check_stmt)) {
    $level_check_stmt->close();
}
$conn->close();

// Redirect back to class.php with message
header("Location: classes.php?message=");
exit;
?>