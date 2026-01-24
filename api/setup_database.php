<?php
// api/setup_database.php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Включаем отладку ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Проверяем права администратора
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

try {
    // Проверяем и добавляем недостающие поля в таблицу users
    $alter_queries = [];
    
    // Проверяем наличие поля role
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($stmt->rowCount() == 0) {
        $alter_queries[] = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'";
    }
    
    // Проверяем наличие поля is_active
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($stmt->rowCount() == 0) {
        $alter_queries[] = "ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE";
    }
    
    // Проверяем наличие поля created_at
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if ($stmt->rowCount() == 0) {
        $alter_queries[] = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    }
    
    // Выполняем все ALTER запросы
    foreach ($alter_queries as $query) {
        $pdo->exec($query);
    }
    
    // Создаем таблицу login_history если ее нет
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT TRUE
        )
    ");
    
    echo json_encode([
        'success' => true, 
        'message' => 'База данных успешно настроена',
        'altered' => count($alter_queries)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>