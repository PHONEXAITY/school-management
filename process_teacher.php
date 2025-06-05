<?php
include 'config/db.php';

// Handle add teacher
if (isset($_POST['submit_add'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $village = $_POST['village'];
    $district = $_POST['district'];
    $province = $_POST['province'];
    $degree = $_POST['degree'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'] ?: null;
    
    // Validate and normalize gender value
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        // For compatibility with both formats
        if ($gender === 'M') {
            $gender = 'Male';
        } elseif ($gender === 'F') {
            $gender = 'Female';
        } elseif ($gender === 'O') {
            $gender = 'Other';
        } else {
            // Handle invalid gender values
            error_log("Invalid gender value: $gender. Defaulting to 'Male'");
            $gender = 'Male';
        }
    }

    $sql = "INSERT INTO teacher (fname, lname, gender, birth_date, village, district, province, degree, phone, class_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $fname, $lname, $gender, $birth_date, $village, $district, $province, $degree, $phone, $class_id);

    $stmt->execute();
    $stmt->close();
    header("Location: teachers.php");
    exit();
}

// Handle edit teacher
if (isset($_POST['submit_edit'])) {
    $id = $_POST['teacher_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $gender = $_POST['gender'];
    
    // Validate and normalize gender value
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        // For compatibility with both formats
        if ($gender === 'M') {
            $gender = 'Male';
        } elseif ($gender === 'F') {
            $gender = 'Female';
        } elseif ($gender === 'O') {
            $gender = 'Other';
        } else {
            // Handle invalid gender values
            error_log("Invalid gender value in edit: $gender. Defaulting to 'Male'");
            $gender = 'Male';
        }
    }
    
    $birth_date = $_POST['birth_date'];
    $village = $_POST['village'];
    $district = $_POST['district'];
    $province = $_POST['province'];
    $degree = $_POST['degree'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'] ?: null;

    $sql = "UPDATE teacher SET fname=?, lname=?, gender=?, birth_date=?, village=?, district=?, province=?, degree=?, phone=?, class_id=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssi", $fname, $lname, $gender, $birth_date, $village, $district, $province, $degree, $phone, $class_id, $id);

    $stmt->execute();
    $stmt->close();
    header("Location: teachers.php");
    exit();
}

// Handle delete teacher
if (isset($_POST['submit_delete'])) {
    $id = $_POST['teacher_id'];
    $sql = "DELETE FROM teacher WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();
    $stmt->close();
    header("Location: teachers.php");
    exit();
}

$conn->close();
?>