<?php
session_start();

// Check if the user is logged in and is an Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

include("./config/db.php");

// Set page-specific variables
$pageTitle = "Dashboard - School Management System";
$activePage = "dashboard";
$contentPath = "content/dashboard.php";

// Page specific scripts for charts
$pageSpecificScripts = '
<!-- Page level plugins -->
<script src="sb-admin-2/vendor/chart.js/Chart.min.js"></script>

<!-- Page level custom scripts -->
<script src="sb-admin-2/js/demo/chart-area-demo.js"></script>
<script src="sb-admin-2/js/demo/chart-pie-demo.js"></script>
<script src="sb-admin-2/js/demo/chart-bar-demo.js"></script>
<script src="sb-admin-2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="sb-admin-2/js/demo/datatables-demo.js"></script>
';

// Include the layout template
include("includes/layout.php");
?>