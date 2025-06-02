<?php
include 'config/db.php';

// Handle score submission
if (isset($_POST['submit_scores'])) {
    $student_id = $_POST['student_id'];
    $month = $_POST['month'];
    $term_id = $_POST['term_id'];
    $sch_year_id = $_POST['sch_year_id'];
    $scores = $_POST['score'];
    $sub_ids = $_POST['sub_id'];

    // Prepare the SQL statement for upsert (insert or update)
    $sql = "INSERT INTO score (student_id, sub_id, score, month, term_id, sch_year_id) 
            VALUES (?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE score = ?";
    $stmt = $conn->prepare($sql);

    // Process each subject's score
    foreach ($scores as $subject => $score) {
        $sub_id = $sub_ids[$subject];
        $stmt->bind_param("ssssssi", $student_id, $sub_id, $score, $month, $term_id, $sch_year_id, $score);
        $stmt->execute();
    }

    $stmt->close();
    header("Location: scores.php");
    exit();
}

$conn->close();
?>