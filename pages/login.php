<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Логируем все запросы
error_log("=== REGISTER API CALLED ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . json_encode(getallheaders()));
error_log("Raw Input: " . file_get_contents('php://input'));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit();
}

// Подключаем конфигурацию
require_once __DIR__ . '/../config/database.php';

// Получаем и логируем данные
$rawInput = file_get_contents('php://input');
error_log("Raw input received: " . $rawInput);

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Неверный формат данных']);
    exit();
}

error_log("Parsed input: " . print_r($input, true));

// Валидация
if (empty($input['email']) || empty($input['password']) || empty($input['username'])) {
    error_log("Validation failed: missing fields");
    echo json_encode(['success' => false, 'message' => 'Все поля обязательны для заполнения']);
    exit();
}

$email = trim($input['email']);
$password = $input['password'];
$username = trim($input['username']);

// Валидация email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Validation failed: invalid email - $email");
    echo json_encode(['success' => false, 'message' => 'Неверный формат email адреса', 'field' => 'email']);
    exit();
}

// Валидация пароля
if (strlen($password) < 6) {
    error_log("Validation failed: short password");
    echo json_encode(['success' => false, 'message' => 'Пароль должен содержать минимум 6 символов', 'field' => 'password']);
    exit();
}

// Валидация username
if (strlen($username) < 3 || strlen($username) > 20) {
    error_log("Validation failed: invalid username length - $username");
    echo json_encode(['success' => false, 'message' => 'Имя пользователя должно быть от 3 до 20 символов', 'field' => 'username']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    error_log("Validation failed: invalid username characters - $username");
    echo json_encode(['success' => false, 'message' => 'Имя пользователя может содержать только латинские буквы, цифры и нижнее подчёркивание', 'field' => 'username']);
    exit();
}

try {
    error_log("Attempting database connection...");
    $db = Database::getConnection();
    error_log("Database connection successful");
    
    // Проверка email
    error_log("Checking email: $email");
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($existing = $stmt->fetch()) {
        error_log("Email already exists, ID: " . $existing['id']);
        echo json_encode(['success' => false, 'message' => 'Этот email уже зарегистрирован', 'field' => 'email']);
        exit();
    }
    error_log("Email check passed");
    
    // Проверка username
    error_log("Checking username: $username");
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($existing = $stmt->fetch()) {
        error_log("Username already exists, ID: " . $existing['id']);
        echo json_encode(['success' => false, 'message' => 'Это имя пользователя уже занято', 'field' => 'username']);
        exit();
    }
    error_log("Username check passed");
    
    // Хеширование пароля
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");
    
    // Токен для верификации
    $verification_token = bin2hex(random_bytes(32));
    $verification_code = rand(100000, 999999); // 6-значный код
    
    // Создание пользователя
    error_log("Creating user record...");
    $stmt = $db->prepare("
        INSERT INTO users 
        (email, username, password_hash, verification_token, verification_code, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([$email, $username, $password_hash, $verification_token, $verification_code]);
    
    if (!$result) {
        error_log("Failed to execute insert statement");
        $errorInfo = $stmt->errorInfo();
        error_log("PDO error: " . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'Ошибка при создании пользователя']);
        exit();
    }
    
    $user_id = $db->lastInsertId();
    error_log("User created successfully, ID: $user_id");
    
    // Отправка email с подтверждением
    error_log("Attempting to send verification email...");
    $verification_link = "https://deepworld.site/verify.php?token=$verification_token";
    
    $subject = "Подтверждение регистрации в DeepWorld";
    $body = "
        <h2>Добро пожаловать в DeepWorld!</h2>
        <p>Благодарим вас за регистрацию на нашем проекте.</p>
        <p><strong>Ваш email:</strong> $email</p>
        <p><strong>Ваш никнейм:</strong> $username</p>
        <p><strong>Код подтверждения:</strong> <span style='font-size: 24px; font-weight: bold; color: #4ecdc4;'>$verification_code</span></p>
        <p>Или перейдите по ссылке: <a href='$verification_link'>$verification_link</a></p>
        <p>После подтверждения email вы сможете войти в лаунчер DeepWorld.</p>
        <hr>
        <p style='font-size: 12px; color: #666;'>
            Если вы не регистрировались на DeepWorld, просто проигнорируйте это письмо.
        </p>
    ";
    
    // Для теста пока не отправляем реальное письмо
    error_log("Email would be sent to: $email");
    error_log("Verification code: $verification_code");
    error_log("Verification link: $verification_link");
    
    // Пока возвращаем код в ответе для тестирования
    $response = [
        'success' => true,
        'message' => 'Регистрация успешна! Проверьте ваш email для подтверждения.',
        'user_id' => $user_id,
        'test_info' => [
            'verification_code' => $verification_code,
            'email' => $email
        ]
    ];
    
    error_log("Registration successful, response: " . print_r($response, true));
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка: ' . $e->getMessage()
    ]);
}
?>