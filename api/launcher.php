<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Получение информации о лаунчере
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'versions':
                    getVersions();
                    break;
                case 'stats':
                    getStats();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Неизвестное действие']);
            }
        } else {
            getLauncherInfo();
        }
        break;
        
    case 'POST':
        // Логирование скачивания
        if (isset($_POST['action']) && $_POST['action'] == 'log_download') {
            logDownload();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Неизвестное действие']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
}

function getVersions() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM launcher_versions ORDER BY release_date DESC LIMIT 10");
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'versions' => $versions]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка базы данных', 'message' => $e->getMessage()]);
    }
}

function getStats() {
    global $pdo;
    
    try {
        // Общее количество скачиваний
        $stmt = $pdo->query("SELECT COUNT(*) as total_downloads FROM download_logs");
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Скачивания по платформам
        $stmt = $pdo->query("SELECT platform, COUNT(*) as count FROM download_logs WHERE platform IS NOT NULL GROUP BY platform");
        $platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Последние скачивания
        $stmt = $pdo->query("SELECT COUNT(*) as today_downloads FROM download_logs WHERE DATE(downloaded_at) = CURDATE()");
        $today = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => $total['total_downloads'],
                'today' => $today['today_downloads'],
                'platforms' => $platforms
            ]
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка базы данных']);
    }
}

function getLauncherInfo() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM launcher_versions WHERE type = 'stable' ORDER BY release_date DESC LIMIT 1");
        $latest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$latest) {
            $latest = [
                'version' => '1.0.0',
                'windows_url' => '/downloads/launcher-windows.exe',
                'macos_url' => '/downloads/launcher-macos.dmg',
                'linux_url' => '/downloads/launcher-linux.sh'
            ];
        }
        
        echo json_encode(['success' => true, 'launcher' => $latest]);
    } catch(PDOException $e) {
        echo json_encode(['success' => true, 'launcher' => [
            'version' => '1.0.0',
            'windows_url' => '/downloads/launcher-windows.exe',
            'macos_url' => '/downloads/launcher-macos.dmg',
            'linux_url' => '/downloads/launcher-linux.sh'
        ]]);
    }
}

function logDownload() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    $platform = isset($_POST['platform']) ? $_POST['platform'] : null;
    $version = isset($_POST['version']) ? $_POST['version'] : '1.0.0';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO download_logs (user_id, platform, version, downloaded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $platform, $version]);
        
        echo json_encode(['success' => true, 'message' => 'Скачивание записано']);
    } catch(PDOException $e) {
        // Игнорируем ошибку если таблицы нет
        echo json_encode(['success' => true, 'message' => 'Скачивание завершено']);
    }
}
?>