<?php
// config/database.php

// Настройки подключения к базе данных
$host = 'localhost';
$dbname = 'ce032318_dws'; // Имя вашей БД
$username = 'ce032318_dws'; // Ваш пользователь БД
$password = 'lfybbkdkflbvbhjdbx0608%'; // Ваш пароль БД

// Определяем константу для режима разработки
define('DEVELOPMENT', true); // Поставьте false на продакшене

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Дополнительные настройки для надежности
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // В зависимости от режима показываем разную информацию
    if (DEVELOPMENT) {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
    } else {
        die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
    }
}

// Функция для получения подключения
function getConnection() {
    global $pdo;
    return $pdo;
}
?>