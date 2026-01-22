<?php
// api/check_availability.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$response = ['available' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? '';
    $value = $_GET['value'] ?? '';
    
    if (empty($type) || empty($value)) {
        $response['message'] = 'Неверные параметры';
        echo json_encode($response);
        exit();
    }
    
    try {
        $db = Database::getConnection();
        
        if ($type === 'email') {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$value]);
            $response['available'] = !$stmt->fetch();
            $response['message'] = $response['available'] ? 'Email свободен' : 'Email уже занят';
        }
        elseif ($type === 'username') {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$value]);
            $response['available'] = !$stmt->fetch();
            $response['message'] = $response['available'] ? 'Имя пользователя свободно' : 'Имя пользователя уже занято';
        }
        else {
            $response['message'] = 'Неверный тип проверки';
        }
        
    } catch (PDOException $e) {
        error_log("Ошибка проверки доступности: " . $e->getMessage());
        $response['message'] = 'Ошибка сервера';
    }
} else {
    $response['message'] = 'Неверный метод запроса';
}

echo json_encode($response);
?>