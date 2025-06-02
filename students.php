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
$pageTitle = "Students - School Management System";
$activePage = "students";
$contentPath = "content/students.php";

// Page specific scripts for DataTables
$pageSpecificScripts = '
<!-- Page level plugins -->
<script src="sb-admin-2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="sb-admin-2/js/demo/datatables-demo.js"></script>
';

// Include the layout template
include("includes/layout.php");
?>
