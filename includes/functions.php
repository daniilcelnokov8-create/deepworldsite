<?php
require_once 'config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
}

function getUser() {
    if (!isLoggedIn()) return null;
    
    try {
        $db = db();
        $stmt = $db->prepare("
            SELECT u.*, s.session_token 
            FROM users u 
            LEFT JOIN user_sessions s ON u.id = s.user_id 
            WHERE u.id = ? AND s.session_token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Ошибка получения пользователя: " . $e->getMessage());
        return null;
    }
}

function verifyLauncherToken($token) {
    try {
        $db = db();
        $stmt = $db->prepare("
            SELECT u.* 
            FROM user_sessions s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.launcher_token = ? AND s.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Ошибка верификации токена: " . $e->getMessage());
        return null;
    }
}

function getServerStatus() {
    try {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM servers ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Ошибка получения статуса серверов: " . $e->getMessage());
        return [];
    }
}

function getLatestNews($limit = 5) {
    try {
        $db = db();
        $stmt = $db->prepare("
            SELECT n.*, u.username as author_name 
            FROM news n 
            JOIN users u ON n.author_id = u.id 
            WHERE n.is_published = TRUE 
            ORDER BY n.published_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Ошибка получения новостей: " . $e->getMessage());
        return [];
    }
}

function getUserDonations($user_id) {
    try {
        $db = db();
        $stmt = $db->prepare("
            SELECT * FROM donations 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Ошибка получения донатов: " . $e->getMessage());
        return [];
    }
}
?>