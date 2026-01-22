<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$response = ['success' => false, 'message' => '', 'news' => []];

try {
    $db = db();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $limit = min(max($