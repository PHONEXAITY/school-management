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
$pageTitle = "System Logs - School Management System";
$activePage = "system_logs";
$contentPath = "content/system_logs.php";

// Page specific CSS
$pageSpecificCSS = '
<link href="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
<style>
.log-entry {
    font-size: 0.9rem;
    line-height: 1.4;
}
.log-timestamp {
    color: #6c757d;
    font-size: 0.8rem;
}
.log-action {
    font-weight: 600;
}
.log-details {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background-color: #f8f9fc;
    border-radius: 0.35rem;
    font-size: 0.85rem;
}
</style>';

// Page specific scripts
$pageSpecificScripts = '
<script src="sb-admin-2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $("#systemLogsTable").DataTable({
        order: [[0, "desc"]],
        pageLength: 25,
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        }
    });
});
</script>';

include("includes/layout.php");
?>