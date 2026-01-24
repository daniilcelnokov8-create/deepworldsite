<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID пользователя не указан']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Пользователь не найден']);
        exit();
    }
    
    // Очищаем пароль
    unset($user['password']);
    
    header('Content-Type: application/json');
    echo json_encode($user);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>