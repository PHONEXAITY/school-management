<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        if (isset($_POST['submit_add'])) {
            $username = $conn->real_escape_string($_POST['username']);
            $password = $conn->real_escape_string($_POST['password']); // In production, use password_hash($password, PASSWORD_DEFAULT)
            $role = $conn->real_escape_string($_POST['role']);

            $sql = "INSERT INTO user (username, password, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $password, $role);

            if ($stmt->execute()) {
                $message = "User added successfully.";
            } else {
                throw new Exception("Failed to add user: " . $conn->error);
            }
        } elseif (isset($_POST['submit_edit'])) {
            $user_id = (int) $_POST['user_id'];
            $username = $conn->real_escape_string($_POST['username']);
            $password = $conn->real_escape_string($_POST['password']); // In production, use password_hash($password, PASSWORD_DEFAULT)
            $role = $conn->real_escape_string($_POST['role']);

            $sql = "UPDATE user SET username = ?, password = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $password, $role, $user_id);

            if ($stmt->execute()) {
                $message = "User updated successfully.";
            } else {
                throw new Exception("Failed to update user: " . $conn->error);
            }
        } elseif (isset($_POST['submit_delete'])) {
            $user_id = (int) $_POST['user_id'];

            $sql = "DELETE FROM user WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $message = "User deleted successfully.";
            } else {
                throw new Exception("Failed to delete user: " . $conn->error);
            }
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }

    $stmt->close();
    $conn->close();

    // Redirect back to user.php with message (optional, implement session or GET parameter if needed)
    header("Location: user.php?message=" . urlencode($message));
    exit;
}
?>