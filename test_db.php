<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Проверка подключения к БД</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Проверка подключения к базе данных</h1>
    
    <?php
    require_once 'config/database.php';
    
    echo "<h2>Параметры подключения:</h2>";
    echo "<pre>";
    echo "Хост: " . DB_HOST . "\n";
    echo "База данных: " . DB_NAME . "\n";
    echo "Пользователь: " . DB_USER . "\n";
    echo "Пароль: " . str_repeat('*', strlen(DB_PASS)) . "\n";
    echo "</pre>";
    
    echo "<h2>Тестирование подключения:</h2>";
    
    try {
        $db = db();
        echo "<p class='success'>✓ Подключение к БД успешно установлено!</p>";
        
        // Проверяем версию MySQL
        $version = $db->query('SELECT VERSION() as version')->fetch();
        echo "<p>Версия MySQL: " . $version['version'] . "</p>";
        
        // Проверяем существующие таблицы
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>Найдены таблицы:</p><ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Таблицы не найдены. База данных пустая.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Ошибка подключения: " . $e->getMessage() . "</p>";
        echo "<h3>Рекомендации:</h3>";
        echo "<ul>";
        echo "<li>Проверьте правильность данных в config/database.php</li>";
        echo "<li>Убедитесь, что база данных создана в панели TimeWeb</li>";
        echo "<li>Проверьте, что пользователь БД имеет права на подключение</li>";
        echo "<li>В TimeWeb хост обычно 'localhost'</li>";
        echo "</ul>";
    }
    ?>
    
    <h2>Создание таблиц (если их нет):</h2>
    <form method="post">
        <button type="submit" name="create_tables">Создать таблицы</button>
    </form>
    
    <?php
    if (isset($_POST['create_tables'])) {
        echo "<h3>Создание таблиц...</h3>";
        
        try {
            $sql = file_get_contents('config/schema.sql');
            $db->exec($sql);
            echo "<p class='success'>✓ Таблицы успешно созданы!</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Ошибка создания таблиц: " . $e->getMessage() . "</p>";
        }
    }
    ?>
</body>
</html>