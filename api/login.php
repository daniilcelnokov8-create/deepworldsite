<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email']) || empty($data['password'])) {
        $response['message'] = 'Все поля обязательны';
        echo json_encode($response);
        exit;
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    try {
        $db = db();
        
        // Поиск пользователя
        $stmt = $db->prepare("SELECT id, email, username, password_hash, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response['message'] = 'Неверный email или пароль';
            echo json_encode($response);
            exit;
        }
        
        // Проверка статуса
        if ($user['status'] === 'banned') {
            $response['message'] = 'Аккаунт заблокирован';
            echo json_encode($response);
            exit;
        }
        
        if ($user['status'] === 'pending') {
            $response['message'] = 'Подтвердите email для входа';
            echo json_encode($response);
            exit;
        }
        
        // Проверка пароля
        if (!password_verify($password, $user['password_hash'])) {
            $response['message'] = 'Неверный email или пароль';
            echo json_encode($response);
            exit;
        }
        
        // Создание сессии
        $session_token = bin2hex(random_bytes(32));
        $launcher_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, launcher_token, expires_at, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $session_token,
            $launcher_token,
            $expires_at,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Обновление времени последнего входа
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['session_token'] = $session_token;
        
        $response['success'] = true;
        $response['message'] = 'Вход выполнен успешно';
        $response['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username']
        ];
        $response['tokens'] = [
            'session_token' => $session_token,
            'launcher_token' => $launcher_token
        ];
        
    } catch (PDOException $e) {
        error_log("Ошибка входа: " . $e->getMessage());
        $response['message'] = 'Ошибка сервера';
    }
} else {
    $response['message'] = 'Неверный метод запроса';
}

echo json_encode($response);
?>