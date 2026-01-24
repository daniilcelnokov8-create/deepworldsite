<?php
// includes/auth.php
session_start();

function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        // Проверяем токен "запомнить меня"
        if (isset($_COOKIE['remember_token'])) {
            global $pdo;
            
            try {
                require_once '../config/database.php';
                
                $stmt = $pdo->prepare("
                    SELECT u.id, u.username, u.email, u.role 
                    FROM user_sessions us 
                    JOIN users u ON us.user_id = u.id 
                    WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
                ");
                $stmt->execute([$_COOKIE['remember_token']]);
                
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Обновляем время последней активности сессии
                    $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
                    $stmt->execute([$_COOKIE['remember_token']]);
                    
                    return;
                }
            } catch(PDOException $e) {
                // Просто продолжаем
            }
        }
        
        header('Location: ../api/login.php');
        exit();
    }
}

function require_admin() {
    require_auth();
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

function get_user_role() {
    return $_SESSION['role'] ?? 'user';
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Функция для очистки просроченных сессий
function cleanup_expired_sessions() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at <= NOW()");
        $stmt->execute();
    } catch(PDOException $e) {
        // Игнорируем ошибки очистки
    }
}

// Вызываем очистку при каждом запросе (можно вынести в cron)
cleanup_expired_sessions();
?>