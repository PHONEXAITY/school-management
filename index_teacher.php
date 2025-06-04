<?php
session_start();

// Check if the user is logged in and is a Teacher
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Teacher') {
    header("Location: login.php");
    exit;
}

include("./config/db.php");

// Set page-specific variables
$pageTitle = "Teacher Dashboard - School Management System";
$activePage = "dashboard";
$contentPath = "content/dashboard_teacher.php";

// Page specific scripts
$pageSpecificScripts = '
<!-- Page level plugins -->
<script src="sb-admin-2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="sb-admin-2/js/demo/datatables-demo.js"></script>
';

// Include the layout template
include("includes/layout.php");
?>