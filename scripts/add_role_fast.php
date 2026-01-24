<?php
// scripts/add_role_fast.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Быстрый фикс для добавления роли в БД</h1>";
echo "<p style='color:blue'>Выполняется проверка и обновление базы данных...</p>";

// Подключаемся к БД
require_once '../config/database.php';

try {
    echo "<h3>1. Проверка поля 'role' в таблице users</h3>";
    
    // Проверяем существует ли поле role
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('role', $columns)) {
        echo "<p style='color:orange'>⚠ Поле 'role' не найдено. Добавляем...</p>";
        $pdo->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        echo "<p style='color:green'>✅ Поле 'role' успешно добавлено</p>";
    } else {
        echo "<p style='color:green'>✅ Поле 'role' уже существует</p>";
    }
    
    echo "<h3>2. Проверка поля 'is_active'</h3>";
    if (!in_array('is_active', $columns)) {
        echo "<p style='color:orange'>⚠ Поле 'is_active' не найдено. Добавляем...</p>";
        $pdo->query("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
        echo "<p style='color:green'>✅ Поле 'is_active' успешно добавлено</p>";
    } else {
        echo "<p style='color:green'>✅ Поле 'is_active' уже существует</p>";
    }
    
    echo "<h3>3. Назначаем роли всем пользователям</h3>";
    // Устанавливаем всем пользователям роль 'user' если она не установлена
    $stmt = $pdo->query("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");
    $affected = $stmt->rowCount();
    echo "<p>Обновлено пользователей: $affected</p>";
    
    echo "<h3>4. Назначаем администратора</h3>";
    // Ищем пользователя с email admin@deepworld.site
    $admin_email = 'admin@deepworld.site';
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    $admin_user = $stmt->fetch();
    
    if ($admin_user) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$admin_user['id']]);
        echo "<p style='color:green'>✅ Пользователь {$admin_user['username']} ({$admin_user['email']}) назначен администратором</p>";
    } else {
        echo "<p style='color:red'>❌ Пользователь с email $admin_email не найден</p>";
        
        // Покажем всех пользователей
        echo "<h4>Все пользователи в системе:</h4>";
        $stmt = $pdo->query("SELECT id, username, email FROM users ORDER BY id");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Имя</th><th>Email</th></tr>";
        while($user = $stmt->fetch()) {
            echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['email']}</td></tr>";
        }
        echo "</table>";
        
        // Форма для выбора администратора
        echo "<h4>Выберите пользователя для назначения администратором:</h4>";
        echo '<form method="post">';
        $stmt = $pdo->query("SELECT id, username, email FROM users ORDER BY id");
        while($user = $stmt->fetch()) {
            echo "<input type='radio' name='user_id' value='{$user['id']}' required> 
                  ID: {$user['id']} - {$user['username']} ({$user['email']})<br>";
        }
        echo '<button type="submit" name="make_admin" style="margin-top:10px;padding:10px 20px;background:#4285F4;color:white;border:none;border-radius:5px;cursor:pointer;">
                Назначить администратором
              </button>';
        echo '</form>';
    }
    
    // Если выбрали пользователя через форму
    if (isset($_POST['make_admin']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Получаем имя пользователя
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        echo "<p style='color:green'>✅ Пользователь {$user['username']} ({$user['email']}) успешно назначен администратором!</p>";
    }
    
    echo "<h3>5. Проверяем результат</h3>";
    $stmt = $pdo->query("SELECT id, username, email, role, is_active FROM users ORDER BY id");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background:#f2f2f2;'><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Активен</th></tr>";
    while($user = $stmt->fetch()) {
        $role_color = $user['role'] === 'admin' ? 'color:#FF0000;font-weight:bold;' : 'color:#0000FF;';
        $active_icon = $user['is_active'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td style='$role_color'>{$user['role']}</td>";
        echo "<td>{$active_icon}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>🎉 Готово! Что делать дальше:</h2>";
    echo "<ol>";
    echo "<li><a href='../api/logout.php' style='color:red;'>Выйти из системы</a> (обязательно!)</li>";
    echo "<li><a href='../api/login.php'>Войти заново</a> под администратором</li>";
    echo "<li><a href='../pages/admin-simple.php'>Проверить админ-панель</a></li>";
    echo "</ol>";
    
} catch(Exception $e) {
    echo "<p style='color:red;background:#ffe6e6;padding:10px;border-radius:5px;'>❌ ОШИБКА: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>