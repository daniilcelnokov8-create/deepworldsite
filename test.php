<?php
// test.php - для проверки конфигурации
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Тест сервера</h1>";

// Проверка PHP
echo "<h3>Информация о PHP:</h3>";
echo "Версия PHP: " . phpversion() . "<br>";
echo "Ошибки включены: " . (ini_get('display_errors') ? 'Да' : 'Нет') . "<br>";

// Проверка БД
echo "<h3>Проверка БД:</h3>";
try {
    $host = 'localhost';
    $dbname = 'ce032318_dws';
    $username = 'ce032318_dws';
    $password = 'lfybbkdkflbvbhjdbx0608%';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Подключение к БД успешно<br>";
    
    // Проверяем таблицу users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Таблица users существует<br>";
        
        // Проверяем структуру
        $stmt = $pdo->query("DESCRIBE users");
        echo "<h4>Структура таблицы users:</h4>";
        echo "<ul>";
        while ($row = $stmt->fetch()) {
            echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "✗ Таблица users не существует<br>";
    }
    
} catch (PDOException $e) {
    echo "✗ Ошибка БД: " . $e->getMessage() . "<br>";
}

// Проверка сессий
echo "<h3>Проверка сессий:</h3>";
session_start();
echo "ID сессии: " . session_id() . "<br>";
echo "Сессия работает: " . (session_status() === PHP_SESSION_ACTIVE ? 'Да' : 'Нет') . "<br>";

// Проверка путей
echo "<h3>Проверка путей:</h3>";
echo "Текущий путь: " . __DIR__ . "<br>";
echo "Файл существует: " . (file_exists(__DIR__ . '/index.php') ? 'Да' : 'Нет') . "<br>";

echo "<hr><p>Если видите эту страницу без ошибок - сервер работает корректно.</p>";
?>