<?php
session_start();
require_once '../config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Пожалуйста, введите ваш email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Пожалуйста, введите корректный email адрес';
    } else {
        try {
            $db = Database::getConnection();
            
            // Проверяем существование пользователя
            $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Генерируем токен сброса
                $reset_token = bin2hex(random_bytes(32));
                $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Сохраняем токен в базе
                $stmt = $db->prepare("
                    UPDATE users 
                    SET reset_token = ?, reset_token_expires = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$reset_token, $reset_token_expires, $user['id']]);
                
                // Ссылка для сброса пароля
                $reset_link = "https://deepworld.site/pages