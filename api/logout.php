<?php
// api/logout.php
require_once '../config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Логируем выход
        $stmt = $pdo->prepare("
            INSERT INTO user_activity (user_id, activity_type, description, ip_address) 
            VALUES (?, 'logout', 'Выход из системы', ?)
        ");
        $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
        
        // Удаляем токен "запомнить меня" из БД
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
            
            // Удаляем cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    } catch(PDOException $e) {
        // Игнорируем ошибки при логауте
    }
}

// Удаляем все данные сессии
$_SESSION = array();

// Удаляем cookie сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную
header('Location: ../index.php');
exit();
?>