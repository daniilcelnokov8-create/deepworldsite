<?php
// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Автоподключение файлов
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Подключение к БД
require_once 'database.php';
?>