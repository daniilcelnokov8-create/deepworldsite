<?php
// includes/user_functions.php

require_once '../config/database.php';

function getUserData($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAvatarUrl($user_data) {
    if (!empty($user_data['avatar'])) {
        // Если аватар - это URL (например, загруженный файл)
        if (filter_var($user_data['avatar'], FILTER_VALIDATE_URL)) {
            return $user_data['avatar'];
        }
        
        // Если аватар - это путь к файлу
        if (file_exists($user_data['avatar'])) {
            return $user_data['avatar'];
        }
        
        // Если это имя файла в папке uploads
        $upload_path = '../uploads/avatars/' . $user_data['avatar'];
        if (file_exists($upload_path)) {
            return '../uploads/avatars/' . $user_data['avatar'];
        }
    }
    
    // Если нет аватара, генерируем цветной аватар с инициалами
    return null;
}

function getAvatarDisplay($user_data) {
    $avatar_url = getAvatarUrl($user_data);
    
    if ($avatar_url) {
        // Возвращаем HTML для загруженного аватара
        return '<img src="' . htmlspecialchars($avatar_url) . '" alt="' . htmlspecialchars($user_data['username']) . '" style="width: 100%; height: 100%; object-fit: cover;">';
    } else {
        // Генерируем цветной аватар с инициалами
        $initials = strtoupper(substr($user_data['username'], 0, 2));
        $color = generateAvatarColor($user_data['username']);
        
        return '<div style="width: 100%; height: 100%; background: ' . $color . '; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: inherit;">' . $initials . '</div>';
    }
}

function generateAvatarColor($username) {
    $hash = crc32($username);
    $colors = [
        '#4285F4', // Синий
        '#EA4335', // Красный
        '#34A853', // Зеленый
        '#FBBC05', // Желтый
        '#8B5CF6', // Фиолетовый
        '#EC4899', // Розовый
        '#0EA5E9', // Голубой
        '#10B981', // Изумрудный
    ];
    return $colors[$hash % count($colors)];
}

function getRoleDisplay($role) {
    $roles = [
        'user' => ['icon' => 'fa-user', 'text' => 'Игрок', 'class' => 'role-user'],
        'moderator' => ['icon' => 'fa-shield-alt', 'text' => 'Модератор', 'class' => 'role-moderator'],
        'admin' => ['icon' => 'fa-crown', 'text' => 'Администратор', 'class' => 'role-admin']
    ];
    
    return $roles[$role] ?? $roles['user'];
}
?>