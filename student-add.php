<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

include("./config/db.php");

// Set page-specific variables
$pageTitle = "Add Student - School Management System";
$activePage = "student-add";
$contentPath = "content/student-add.php";

// Include the layout template
include("includes/layout.php");
?>
