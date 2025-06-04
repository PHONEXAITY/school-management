<?php
require_once '../../config/db_pdo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$studentId = $input['student_id'] ?? '';

if (empty($studentId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Student ID is required']);
    exit;
}

try {
    // Get student information
    $stmt = $conn->prepare("
        SELECT s.id, s.fname, s.lname, s.gender, s.birth_date,
               c.name as class_name, l.name as level_name
        FROM student s
        LEFT JOIN class c ON s.class_id = c.id
        LEFT JOIN levels l ON c.level_id = l.id
        WHERE s.id = ?
    ");
    
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'ບໍ່ພົບຂໍ້ມູນນັກຮຽນ'
        ]);
        exit;
    }
    
    // Get scores using your database structure
    $stmt = $conn->prepare("
        SELECT sc.score_id, sc.score, sc.month,
               sub.name as subject_name,
               t.name as term_name,
               y.name as year_name,
               sc.created_at
        FROM score sc
        LEFT JOIN subject sub ON sc.sub_id = sub.id
        LEFT JOIN term t ON sc.term_id = t.id
        LEFT JOIN year y ON sc.sch_year_id = y.id
        WHERE sc.student_id = ?
        ORDER BY y.name DESC, t.name DESC, sub.name ASC
    ");
    
    $stmt->execute([$studentId]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize statistics
    $totalScore = 0;
    $scoreCount = 0;
    $maxScore = 0;
    $minScore = 100; // Start with max possible to find minimum
    $subjectCount = 0;
    $subjects = [];
    $passedCount = 0;
    $failedCount = 0;
    
    // Process each score and calculate statistics
    foreach ($scores as &$score) {
        $currentScore = floatval($score['score']);
        
        // Add grade and status to each score
        $score['grade'] = calculateGrade($currentScore);
        $score['status'] = $currentScore >= 50 ? 'ຜ່ານ' : 'ບໍ່ຜ່ານ';
        
        // Calculate statistics
        $totalScore += $currentScore;
        $scoreCount++;
        $maxScore = max($maxScore, $currentScore);
        $minScore = min($minScore, $currentScore);
        
        // Count unique subjects
        if (!in_array($score['subject_name'], $subjects)) {
            $subjects[] = $score['subject_name'];
            $subjectCount++;
        }
        
        // Count passed/failed
        if ($currentScore >= 50) {
            $passedCount++;
        } else {
            $failedCount++;
        }
    }
    
    // Calculate final statistics
    $averageScore = $scoreCount > 0 ? round($totalScore / $scoreCount, 1) : 0;
    $passRate = $scoreCount > 0 ? round(($passedCount / $scoreCount) * 100, 1) : 0;
    
    // If no scores, set min to 0
    if ($scoreCount === 0) {
        $minScore = 0;
    }
    
    // Prepare student data
    $student['full_name'] = $student['fname'] . ' ' . $student['lname'];
    $student['age'] = date_diff(date_create($student['birth_date']), date_create('today'))->y;
    
    // Return complete response
    echo json_encode([
        'success' => true,
        'student' => $student,
        'scores' => $scores,
        'statistics' => [
            'average_score' => $averageScore,
            'highest_score' => $maxScore,
            'lowest_score' => $minScore,
            'total_subjects' => $subjectCount,
            'total_scores' => $scoreCount,
            'passed_subjects' => $passedCount,
            'failed_subjects' => $failedCount,
            'pass_rate' => $passRate
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function calculateGrade($score) {
    if ($score >= 95) return 'A+';
    if ($score >= 90) return 'A';
    if ($score >= 85) return 'A-';
    if ($score >= 80) return 'B+';
    if ($score >= 75) return 'B';
    if ($score >= 70) return 'B-';
    if ($score >= 65) return 'C+';
    if ($score >= 60) return 'C';
    if ($score >= 55) return 'C-';
    if ($score >= 50) return 'D';
    return 'F';
}
?>