<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

echo json_encode([
    'success' => true,
    'message' => 'Debug API is working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'files' => isset($_FILES) ? array_keys($_FILES) : [],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>