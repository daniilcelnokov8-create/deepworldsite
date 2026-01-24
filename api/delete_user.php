<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID пользователя не указан']);
    exit();
}

// Нельзя удалить себя
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Нельзя удалить свой аккаунт']);
    exit();
}

try {
    // Проверяем, не является ли пользователь администратором
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Нельзя удалить администратора']);
        exit();
    }
    
    // Удаляем пользователя
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Также удаляем связанные данные
    $pdo->prepare("DELETE FROM login_history WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM user_activity WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Пользователь успешно удален']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>