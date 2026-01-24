<?php
// test_db.php - тест подключения к БД
echo "<h1>Тест подключения к базе данных</h1>";

// Вариант 1: Простое подключение
echo "<h3>Вариант 1: Простое подключение</h3>";
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=ce032318_dws;charset=utf8mb4", 
        "ce032318_dws", 
        "lfybbkdkflbvbhjdbx0608%"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Подключение успешно<br>";
    
    // Проверяем таблицы
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Найдено таблиц: " . count($tables) . "<br>";
    
    if (count($tables) > 0) {
        echo "Список таблиц:<br>";
        foreach ($tables as $table) {
            echo "- $table<br>";
        }
    }
    
    // Проверяем таблицу users
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $data = $stmt->fetch();
        echo "✓ Таблица users существует, записей: " . $data['count'] . "<br>";
    } else {
        echo "✗ Таблица users не найдена<br>";
    }
    
} catch(PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "<br>";
    echo "<pre>Код ошибки: " . $e->getCode() . "</pre>";
}

// Вариант 2: Через config файл
echo "<h3>Вариант 2: Через config/database.php</h3>";
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    
    try {
        // Проверяем что $pdo создан
        if (isset($pdo)) {
            echo "✓ Подключение через config/database.php успешно<br>";
            
            // Простой тестовый запрос
            $result = $pdo->query("SELECT VERSION() as version");
            $data = $result->fetch();
            echo "✓ Версия MySQL: " . $data['version'] . "<br>";
        } else {
            echo "✗ Переменная \$pdo не определена в config/database.php<br>";
        }
    } catch(PDOException $e) {
        echo "✗ Ошибка при тестовом запросе: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ Файл config/database.php не найден<br>";
}

// Информация о PHP
echo "<h3>Информация о среде</h3>";
echo "Версия PHP: " . phpversion() . "<br>";
echo "PDO доступен: " . (extension_loaded('pdo_mysql') ? 'Да' : 'Нет') . "<br>";
echo "MySQL доступен: " . (function_exists('mysqli_connect') ? 'Да' : 'Нет') . "<br>";

// Проверяем доступ к серверу БД
echo "<h3>Проверка доступа к серверу БД</h3>";
$test_host = 'localhost';
$test_port = 3306;

if (fsockopen($test_host, $test_port, $errno, $errstr, 10)) {
    echo "✓ Сервер MySQL доступен на $test_host:$test_port<br>";
} else {
    echo "✗ Сервер MySQL недоступен: $errstr ($errno)<br>";
}

echo "<h3>Рекомендации</h3>";
echo "1. Проверьте правильность имени базы данных: ce032318_dws<br>";
echo "2. Проверьте логин: ce032318_dws<br>";
echo "3. Проверьте пароль<br>";
echo "4. Убедитесь что пользователь имеет права на базу данных<br>";
echo "5. Проверьте хост (может быть не localhost, а IP или домен)<br>";
?>