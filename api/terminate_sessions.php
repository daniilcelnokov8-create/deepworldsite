<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_auth();

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Если передали конкретный токен сессии
if (isset($data['session_token'])) {
    $session_token = $data['session_token'];
    
    // Проверяем, что пользователь пытается завершить свою сессию
    $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ?");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch();
    
    if ($session && $session['user_id'] == $user_id) {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$session_token]);
        
        // Если это текущая сессия, разлогиниваем пользователя
        if (isset($_COOKIE['remember_token']) && $_COOKIE['remember_token'] === $session_token) {
            setcookie('remember_token', '', time() - 3600, '/');
            session_destroy();
        }
        
        echo json_encode(['success' => true, 'message' => 'Сессия завершена']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    }
} 
// Завершаем все сессии, кроме текущей
else {
    // Удаляем все сессии пользователя, кроме текущей
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token != ?");
        $stmt->execute([$user_id, $_COOKIE['remember_token']]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Все сессии завершены']);
}
?>