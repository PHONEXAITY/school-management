<?php
// Start the session
session_start();

// Check if the user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include("./config/db.php");

// Set page-specific variables
$pageTitle = "Registration Analytics - School Management System";
$activePage = "registration_analytics";
$contentPath = "content/registration_analytics.php";

// Page specific CSS
$pageSpecificCSS = '
<link href="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
<style>
.analytics-card {
    transition: transform 0.2s ease-in-out;
}
.analytics-card:hover {
    transform: translateY(-2px);
}
.chart-container {
    position: relative;
    height: 400px;
    margin: 20px 0;
}
</style>';

// Page specific scripts
$pageSpecificScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="assets/js/registration-analytics.js"></script>';

include("includes/layout.php");
?>