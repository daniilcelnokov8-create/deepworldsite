<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Доступ запрещен');
}

// Параметры фильтрации
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Построение запроса
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
}

if (!empty($status)) {
    $where[] = "is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Получаем пользователей
$stmt = $pdo->prepare("
    SELECT id, username, email, role, 
           CASE is_active WHEN 1 THEN 'Активен' ELSE 'Неактивен' END as status,
           DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as created_at,
           DATE_FORMAT(last_login, '%d.%m.%Y %H:%i') as last_login
    FROM users 
    $where_sql 
    ORDER BY created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Генерируем CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_' . date('Y-m-d_H-i') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Имя пользователя', 'Email', 'Роль', 'Статус', 'Дата регистрации', 'Последний вход'], ';');

foreach ($users as $user) {
    fputcsv($output, $user, ';');
}

fclose($output);
?>