<?php
// Конфигурация базы данных TimeWeb
define('DB_HOST', 'localhost'); // Изменил с 'mysql' на 'localhost'
define('DB_NAME', 'ce032318_dws'); // Имя вашей БД
define('DB_USER', 'ce032318_dws'); // Имя пользователя БД
define('DB_PASS', 'lfybbkdkflbvbhjdbx0608%'); // Пароль БД

class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                
                // Проверяем подключение
                error_log("Успешное подключение к БД: " . DB_HOST . " | " . DB_NAME);
                
            } catch (PDOException $e) {
                error_log("Ошибка подключения к базе данных: " . $e->getMessage());
                error_log("Параметры подключения:");
                error_log("Хост: " . DB_HOST);
                error_log("БД: " . DB_NAME);
                error_log("Пользователь: " . DB_USER);
                die("Ошибка подключения к базе данных. Проверьте логи.");
            }
        }
        return self::$connection;
    }
}

// Хелпер функции
function db() {
    return Database::getConnection();
}

// Функция для тестирования подключения
function testConnection() {
    try {
        $db = db();
        $stmt = $db->query("SELECT 1");
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        error_log("Тест подключения не удался: " . $e->getMessage());
        return false;
    }
}

// Автоматически тестируем подключение при загрузке файла
if (php_sapi_name() !== 'cli') {
    testConnection();
}
?>