<?php
// scripts/update_session.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>🔄 Обновление данных сессии</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red'>❌ Вы не авторизованы!</p>";
    echo "<p><a href='../api/login.php'>Войдите в систему</a></p>";
    exit();
}

echo "<h3>Текущая сессия:</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;'>";
print_r($_SESSION);
echo "</pre>";

// Подключаемся к БД
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

try {
    // Получаем свежие данные пользователя из БД
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color:red'>❌ Пользователь не найден в базе данных!</p>";
        exit();
    }
    
    echo "<h3>Данные из базы данных:</h3>";
    echo "<pre style='background:#e6f7ff;padding:10px;border-radius:5px;'>";
print_r($user);
echo "</pre>";

    // Обновляем сессию
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['is_active'] = $user['is_active'];
    
    echo "<h3>✅ Сессия обновлена!</h3>";
    echo "<p>Ваша роль теперь: <strong style='color:" . ($user['role'] === 'admin' ? 'red' : 'blue') . ";'>" . $user['role'] . "</strong></p>";
    
    if ($user['role'] === 'admin') {
        echo "<div style='background:#fffacd;padding:15px;border-radius:10px;border-left:5px solid #FFD700;'>";
        echo "<h3>🎉 У вас есть права администратора!</h3>";
        echo "<p>Теперь вы можете:</p>";
        echo "<ul>";
        echo "<li><a href='../pages/admin-simple.php' style='color:green;font-weight:bold;'>Перейти в админ-панель</a></li>";
        echo "<li><a href='../index.php'>На главную</a></li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<p style='color:orange'>⚠ У вас нет прав администратора</p>";
        echo "<p><a href='../index.php'>На главную</a></p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>