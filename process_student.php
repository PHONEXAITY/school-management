<?php
include 'config/db.php';
include 'includes/format_helpers.php'; // Include the helper functions

// Handle add student
if (isset($_POST['submit_add'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $village = $_POST['village'];
    $district = $_POST['district'];
    $province = $_POST['province'];
    $parent_name = $_POST['parent_name'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'] ?: null;

    // Use the helper function to map gender value to database format
    $gender = mapGenderForDB($gender);

    $sql = "INSERT INTO student (fname, lname, gender, birth_date, village, district, province, parent_name, phone, class_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $fname, $lname, $gender, $birth_date, $village, $district, $province, $parent_name, $phone, $class_id);

    $stmt->execute();
    $stmt->close();
    header("Location: students.php");
    exit();
}

// Handle edit student
if (isset($_POST['submit_edit'])) {
    $id = $_POST['student_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $gender = $_POST['gender'];
    
    // Use the helper function to map gender value to database format
    $gender = mapGenderForDB($gender);
    
    $birth_date = $_POST['birth_date'];
    $village = $_POST['village'];
    $district = $_POST['district'];
    $province = $_POST['province'];
    $parent_name = $_POST['parent_name'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'] ?: null;

    $sql = "UPDATE student SET fname=?, lname=?, gender=?, birth_date=?, village=?, district=?, province=?, parent_name=?, phone=?, class_id=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssi", $fname, $lname, $gender, $birth_date, $village, $district, $province, $parent_name, $phone, $class_id, $id);

    $stmt->execute();
    $stmt->close();
    header("Location: students.php");
    exit();
}

// Handle delete student
if (isset($_POST['submit_delete'])) {
    $id = $_POST['student_id'];
    $sql = "DELETE FROM student WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();
    $stmt->close();
    header("Location: students.php");
    exit();
}

$conn->close();
?>