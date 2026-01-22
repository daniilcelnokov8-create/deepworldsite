<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    $db = db();
    
    // Общее количество пользователей
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $totalUsers = $stmt->fetch()['total'];
    
    // Онлайн игроков (симуляция - можно подключить к реальному серверу)
    $playersCount = rand(50, 200);
    
    // Uptime сервера
    $uptime = '24'; // Можно считать реальный uptime
    
    $response['success'] = true;
    $response['totalUsers'] = (int)$totalUsers;
    $response['playersCount'] = $playersCount;
    $response['uptime'] = $uptime;
    
} catch (PDOException $e) {
    error_log("Ошибка получения статистики: " . $e->getMessage());
    $response['message'] = 'Ошибка сервера';
    
    // Значения по умолчанию
    $response['totalUsers'] = 0;
    $response['playersCount'] = 0;
    $response['uptime'] = '24';
}

echo json_encode($response);
?>