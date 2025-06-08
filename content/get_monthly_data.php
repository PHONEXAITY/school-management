<?php
// Include database connection (assuming db.php handles connection and makes $conn available)
include 'config/db.php';

header('Content-Type: application/json');

$months = [];
$registration_counts = [];
$error = '';

try {
    $selected_year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y'); // Default to current year

    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(registration_date, '%Y-%m') as month,
               COUNT(*) as registration_count
        FROM registration
        WHERE YEAR(registration_date) = ?
        GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
        ORDER BY month
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();

    $data_from_db = [];
    while ($row = $result->fetch_assoc()) {
        $data_from_db[$row['month']] = (int) $row['registration_count'];
    }
    $stmt->close();

    // Populate all 12 months, filling in 0 for months with no data
    for ($i = 1; $i <= 12; $i++) {
        $month_key = $selected_year . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $month_name = date('F', strtotime($month_key . '-01'));
        $months[] = $month_name;
        $registration_counts[] = $data_from_db[$month_key] ?? 0;
    }

} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    // In case of error, send empty arrays or default ones
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $registration_counts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

echo json_encode([
    'months' => $months,
    'registrationCounts' => $registration_counts,
    'error' => $error
]);
?>