<?php
// This file adds helper functions to format gender values correctly
// Include this at the beginning of content/teachers.php

function formatGender($genderCode) {
    // The teacher table is already using 'Male', 'Female', 'Other' directly
    // This function still ensures proper formatting if short codes are used
    switch ($genderCode) {
        case 'M':
            return 'Male';
        case 'F':
            return 'Female';
        case 'O':
            return 'Other';
        case 'Male':
        case 'Female':
        case 'Other':
            return $genderCode; // Already formatted correctly
        default:
            return $genderCode;
    }
}

/**
 * Maps various gender input formats to database-compatible values
 * This function handles the conversion from display or input values to database-compatible ENUM values
 * Use this when inserting/updating database records to prevent "Data truncated" errors
 * 
 * @param string $genderValue The gender value to convert
 * @return string Database-compatible gender value ('Male', 'Female', or 'Other')
 */
function mapGenderForDB($genderValue) {
    // Handle different formats and standardize to database format 'Male', 'Female', 'Other'
    if (in_array($genderValue, ['M', 'Male', 'male', 'ชาย'])) {
        return 'Male';
    } elseif (in_array($genderValue, ['F', 'Female', 'female', 'หญิง'])) {
        return 'Female';
    } elseif (in_array($genderValue, ['O', 'Other', 'other', 'อื่นๆ'])) {
        return 'Other'; // Make sure your DB schema supports this
    } else {
        // Already in correct format or unknown
        // If we get legacy values M, F, O, convert them
        if ($genderValue === 'M') return 'Male';
        if ($genderValue === 'F') return 'Female';
        if ($genderValue === 'O') return 'Other';
        
        // Default to Male if value is completely unknown
        return $genderValue ?: 'Male';
    }
}
?>
