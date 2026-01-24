<?php
// check_users.php - для проверки структуры таблицы
try {
    $pdo = new PDO('mysql:host=localhost;dbname=ce032318_dws;charset=utf8', 
                   'ce032318_dws', 
                   'lfybbkdkflbvbhjdbx0608%');
    
    echo "<h2>Структура таблицы users:</h2>";
    
    // 1. Показываем все поля таблицы
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Возможные варианты:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'pass') !== false) {
            echo "<li><strong>Найдено поле с паролем:</strong> " . $col['Field'] . "</li>";
        }
    }
    echo "</ul>";
    
    // 2. Показываем несколько записей для примера
    echo "<h2>Пример данных (первые 3 записи):</h2>";
    $stmt = $pdo->query("SELECT * FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($users[0]) as $key) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                // Маскируем пароль
                if (stripos($key, 'pass') !== false) {
                    echo "<td>*****</td>";
                } else {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>