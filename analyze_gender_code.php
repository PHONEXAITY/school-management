<?php
// Script to identify PHP files that need to be modified after standardizing gender columns
// Created: June 5, 2025

include 'config/db.php';

echo "<h1>Gender Conversion Code Analysis</h1>";
echo "<p>This script identifies PHP files that contain gender conversion code that can be simplified after standardizing database columns.</p>";

// Function to scan directories recursively
function scanDirectory($dir, &$results) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path, $results);
        } else {
            // Only check PHP files
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $results[] = $path;
            }
        }
    }
}

// Function to check file content for gender conversion code
function checkFileForGenderCode($filePath) {
    $content = file_get_contents($filePath);
    $findings = [];
    
    // Check for formatGender function calls
    if (preg_match_all('/formatGender\s*\(\s*\$[^)]+\)/', $content, $matches)) {
        foreach ($matches[0] as $match) {
            $findings['formatGender'][] = $match;
        }
    }
    
    // Check for mapGenderForDB function calls
    if (preg_match_all('/mapGenderForDB\s*\(\s*\$[^)]+\)/', $content, $matches)) {
        foreach ($matches[0] as $match) {
            $findings['mapGenderForDB'][] = $match;
        }
    }
    
    // Check for direct gender mapping
    $genderMappingPatterns = [
        '/\$gender\s*=\s*\$[^=]+\s*===\s*[\'"]M[\'"]\s*\?\s*[\'"]Male[\'"]/', // $gender = $x === 'M' ? 'Male'
        '/\$gender\s*=\s*\$[^=]+\s*===\s*[\'"]F[\'"]\s*\?\s*[\'"]Female[\'"]/', // $gender = $x === 'F' ? 'Female'
        '/\$gender\s*=\s*[\'"]Male[\'"]\s*;/', // $gender = 'Male';
        '/\$gender\s*=\s*[\'"]Female[\'"]\s*;/', // $gender = 'Female';
        '/\$gender\s*=\s*[\'"]M[\'"]\s*;/', // $gender = 'M';
        '/\$gender\s*=\s*[\'"]F[\'"]\s*;/', // $gender = 'F';
        '/gender\s*=\s*[\'"]M[\'"]/i', // gender = 'M'
        '/gender\s*=\s*[\'"]F[\'"]/i', // gender = 'F'
        '/gender\s*=\s*[\'"]Male[\'"]/i', // gender = 'Male'
        '/gender\s*=\s*[\'"]Female[\'"]/i', // gender = 'Female'
    ];
    
    foreach ($genderMappingPatterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $match) {
                $findings['direct_mapping'][] = $match;
            }
        }
    }
    
    // Check for gender columns in SQL statements
    $sqlPatterns = [
        '/gender\s+enum/i', // CREATE/ALTER table statements
        '/gender\s*=\s*\?/i', // Prepared statements
        '/UPDATE.*gender\s*=/i', // UPDATE statements 
        '/INSERT.*gender/i', // INSERT statements
    ];
    
    foreach ($sqlPatterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $match) {
                $findings['sql'][] = $match;
            }
        }
    }
    
    return $findings;
}

// Get all PHP files in the project
$phpFiles = [];
scanDirectory('.', $phpFiles);

// Check each file
$filesToModify = [];

echo "<h2>Files Analysis Results:</h2>";

foreach ($phpFiles as $file) {
    $findings = checkFileForGenderCode($file);
    
    if (!empty($findings)) {
        $filesToModify[$file] = $findings;
    }
}

// Display results
echo "<h3>Files that may need modification (" . count($filesToModify) . "):</h3>";

if (empty($filesToModify)) {
    echo "<p>No files found that need modification.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>File</th><th>Gender Conversion Type</th><th>Occurrences</th><th>Priority</th></tr>";
    
    // Sort files by number of findings (most first)
    uasort($filesToModify, function($a, $b) {
        $aCount = array_sum(array_map('count', $a));
        $bCount = array_sum(array_map('count', $b));
        return $bCount <=> $aCount;
    });
    
    foreach ($filesToModify as $file => $findings) {
        $rowspan = count($findings);
        $first = true;
        $totalOccurrences = array_sum(array_map('count', $findings));
        
        // Determine priority
        $priority = 'Low';
        if (isset($findings['formatGender']) || isset($findings['mapGenderForDB'])) {
            $priority = 'High';
        } elseif (isset($findings['direct_mapping']) && count($findings['direct_mapping']) > 0) {
            $priority = 'Medium';
        }
        
        $priorityColor = [
            'High' => '#f44336',
            'Medium' => '#ff9800',
            'Low' => '#4caf50'
        ][$priority];
        
        foreach ($findings as $type => $occurrences) {
            if ($first) {
                $first = false;
                echo "<tr>";
                echo "<td rowspan='$rowspan'>" . htmlspecialchars(str_replace('./', '', $file)) . "</td>";
            } else {
                echo "<tr>";
            }
            
            echo "<td>" . htmlspecialchars($type) . "</td>";
            echo "<td>" . count($occurrences) . "</td>";
            
            if ($first === false && $rowspan > 1) {
                echo "<td rowspan='$rowspan' style='background-color: $priorityColor; color: white;'>$priority</td>";
            }
            
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // Generate update guidelines
    echo "<h2>Update Guidelines:</h2>";
    echo "<p>After standardizing all gender columns to enum('Male','Female','Other'), many gender conversion functions are no longer needed.</p>";
    
    echo "<h3>High Priority Updates:</h3>";
    echo "<ol>";
    echo "<li>Remove unnecessary calls to <code>formatGender()</code> and <code>mapGenderForDB()</code> functions</li>";
    echo "<li>Simplify direct gender mapping code (e.g., <code>\$gender = \$row['gender'] === 'M' ? 'Male' : 'Female';</code>)</li>";
    echo "<li>Add validation to ensure only 'Male', 'Female', and 'Other' values are accepted from forms</li>";
    echo "</ol>";
    
    echo "<h3>Example Code Changes:</h3>";
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "<span style='color: #999;'>// BEFORE:</span>
\$gender = formatGender(\$row['gender']);

<span style='color: #999;'>// AFTER:</span>
\$gender = \$row['gender']; // Already in 'Male', 'Female', 'Other' format in database

<span style='color: #999;'>// BEFORE:</span>
\$gender = \$new_student['gender'] === 'M' ? 'Male' : 'Female';

<span style='color: #999;'>// AFTER:</span>
\$gender = \$new_student['gender']; // Already in 'Male', 'Female', 'Other' format

<span style='color: #999;'>// BEFORE:</span>
&lt;option value=\"M\"&gt;Male&lt;/option&gt;
&lt;option value=\"F\"&gt;Female&lt;/option&gt;

<span style='color: #999;'>// AFTER:</span>
&lt;option value=\"Male\"&gt;Male&lt;/option&gt;
&lt;option value=\"Female\"&gt;Female&lt;/option&gt;
&lt;option value=\"Other\"&gt;Other&lt;/option&gt;
</pre>";
}

// Create a downloadable report
$reportContent = "# Gender Code Update Report\n\n";
$reportContent .= "Generated: " . date("Y-m-d H:i:s") . "\n\n";
$reportContent .= "## Files to Update\n\n";

foreach ($filesToModify as $file => $findings) {
    $file = str_replace('./', '', $file);
    $reportContent .= "### $file\n\n";
    
    foreach ($findings as $type => $occurrences) {
        $reportContent .= "- $type (" . count($occurrences) . " occurrences)\n";
        foreach ($occurrences as $code) {
            $reportContent .= "  - `" . trim($code) . "`\n";
        }
        $reportContent .= "\n";
    }
}

// Save report to file
$reportFile = "gender_code_update_report.md";
file_put_contents($reportFile, $reportContent);

echo "<p><a href='$reportFile' download style='padding:10px; background-color:#2196F3; color:white; text-decoration:none; display:inline-block;'>Download Report</a></p>";

// Show back button
echo "<p><a href='standardize_gender_columns.php' style='padding:10px; background-color:#607D8B; color:white; text-decoration:none; display:inline-block; margin-top:20px;'>Back to Gender Standardization Tool</a></p>";
?>
