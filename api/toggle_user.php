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
$status = $data['status'] ?? null;

if (!$user_id || $status === null) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$status, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Статус обновлен']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>