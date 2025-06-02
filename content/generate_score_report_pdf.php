<?php
include 'config/db.php';

// Fetch filters from GET parameters
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$selected_student = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$selected_term = isset($_GET['term_id']) ? $_GET['term_id'] : '';
$selected_year = isset($_GET['sch_year_id']) ? $_GET['sch_year_id'] : '';

// Fetch all subjects for column headers
$subject_sql = "SELECT id, name FROM subject";
$subject_result = $conn->query($subject_sql);
if ($subject_result === false) {
    die("Error fetching subjects: " . $conn->error);
}
$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[$row['id']] = $row['name'];
}

// Fetch scores with filters
$conditions = [];
$params = [];
$types = '';
if ($selected_class) {
    $conditions[] = "st.class_id = ?";
    $params[] = $selected_class;
    $types .= 's';
}
if ($selected_student) {
    $conditions[] = "sc.student_id = ?";
    $params[] = $selected_student;
    $types .= 'i';
}
if ($selected_term) {
    $conditions[] = "sc.term_id = ?";
    $params[] = $selected_term;
    $types .= 's';
}
if ($selected_year) {
    $conditions[] = "sc.sch_year_id = ?";
    $params[] = $selected_year;
    $types .= 's';
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$sql = "SELECT 
            CONCAT(st.fname, ' ', st.lname) AS student_name, 
            c.name AS class_name, 
            sub.id AS subject_id, 
            sub.name AS subject_name, 
            sc.score, 
            sc.month, 
            t.name AS term_name, 
            y.name AS year_name 
        FROM score sc
        JOIN student st ON sc.student_id = st.id
        JOIN class c ON st.class_id = c.id
        JOIN subject sub ON sc.sub_id = sub.id
        JOIN term t ON sc.term_id = t.id
        JOIN year y ON sc.sch_year_id = y.id
        $where_clause
        ORDER BY student_name, c.name, t.name, y.name, sc.month";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result === false) {
    die("Error fetching scores: " . $conn->error);
}

// Group scores
$grouped_scores = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['student_name'] . '|' . $row['class_name'] . '|' . $row['term_name'] . '|' . $row['year_name'] . '|' . $row['month'];
    if (!isset($grouped_scores[$key])) {
        $grouped_scores[$key] = [
            'student_name' => $row['student_name'],
            'class_name' => $row['class_name'],
            'term_name' => $row['term_name'],
            'year_name' => $row['year_name'],
            'month' => $row['month'],
            'scores' => []
        ];
    }
    $grouped_scores[$key]['scores'][$row['subject_id']] = $row['score'];
}

// Generate LaTeX for PDF
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

// Building the LaTeX document
$latex_content = "\\documentclass[a4paper,12pt]{article}\n";
$latex_content .= "\\usepackage{geometry}\n";
$latex_content .= "\\geometry{margin=1in}\n";
$latex_content .= "\\usepackage{booktabs}\n";
$latex_content .= "\\usepackage{array}\n";
$latex_content .= "\\usepackage{xeCJK}\n"; // For Lao script support
$latex_content .= "\\usepackage{fontspec}\n";
$latex_content .= "\\setmainfont{Times New Roman}\n";
$latex_content .= "\\setCJKmainfont{Noto Serif Lao}\n"; // Font for Lao script
$latex_content .= "\\title{Student Score Report}\n";
$latex_content .= "\\author{}\n";
$latex_content .= "\\date{June 02, 2025}\n";
$latex_content .= "\\begin{document}\n";
$latex_content .= "\\maketitle\n";
$latex_content .= "\\section*{Score Report}\n";
$latex_content .= "Generated on: June 02, 2025 at 12:03 PM\n\n";

// Filters summary
$latex_content .= "Filters Applied:\n";
$latex_content .= "\\begin{itemize}\n";
$latex_content .= "\\item Class: " . ($selected_class ? htmlspecialchars($grouped_scores[array_key_first($grouped_scores)]['class_name']) : "All Classes") . "\n";
$latex_content .= "\\item Student: " . ($selected_student ? htmlspecialchars($grouped_scores[array_key_first($grouped_scores)]['student_name']) : "All Students") . "\n";
$latex_content .= "\\item Term: " . ($selected_term ? htmlspecialchars($grouped_scores[array_key_first($grouped_scores)]['term_name']) : "All Terms") . "\n";
$latex_content .= "\\item School Year: " . ($selected_year ? htmlspecialchars($grouped_scores[array_key_first($grouped_scores)]['year_name']) : "All Years") . "\n";
$latex_content .= "\\end{itemize}\n\n";

// Table
$latex_content .= "\\begin{table}[h]\n";
$latex_content .= "\\centering\n";
$latex_content .= "\\begin{tabular}{|l|l|l|l|l|" . str_repeat("c|", count($subjects)) . "}\n";
$latex_content .= "\\hline\n";
$latex_content .= "Student Name & Class & Term & School Year & Month";
foreach ($subjects as $subject_name) {
    $latex_content .= " & " . htmlspecialchars($subject_name);
}
$latex_content .= " \\\\\n";
$latex_content .= "\\hline\n";

foreach ($grouped_scores as $group) {
    $latex_content .= htmlspecialchars($group['student_name']) . " & ";
    $latex_content .= htmlspecialchars($group['class_name']) . " & ";
    $latex_content .= htmlspecialchars($group['term_name']) . " & ";
    $latex_content .= htmlspecialchars($group['year_name']) . " & ";
    $latex_content .= htmlspecialchars($months[$group['month']]);
    foreach ($subjects as $subject_id => $subject_name) {
        $score = isset($group['scores'][$subject_id]) ? htmlspecialchars($group['scores'][$subject_id]) : '-';
        $latex_content .= " & " . $score;
    }
    $latex_content .= " \\\\\n";
    $latex_content .= "\\hline\n";
}

$latex_content .= "\\end{tabular}\n";
$latex_content .= "\\caption{Student Scores}\n";
$latex_content .= "\\end{table}\n";
$latex_content .= "\\end{document}\n";

// Output LaTeX for PDF rendering
header('Content-Type: text/latex');
echo $latex_content;

$conn->close();
?>