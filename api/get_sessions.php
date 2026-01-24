<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_auth();

$user_id = $_SESSION['user_id'];

// Исправляем запрос - используем session_token вместо session_id
$stmt = $pdo->prepare("
    SELECT * FROM user_sessions 
    WHERE user_id = ? AND expires_at > NOW() 
    ORDER BY last_activity DESC
");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll();

if (empty($sessions)) {
    echo '<p style="color: rgba(255, 255, 255, 0.7); text-align: center;">Нет активных сессий</p>';
} else {
    foreach($sessions as $session): ?>
    <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <div>
                <strong style="color: white;">Сессия #<?php echo $session['id']; ?></strong>
                <br>
                <small style="color: rgba(255, 255, 255, 0.6);">IP: <?php echo htmlspecialchars($session['ip_address']); ?></small>
            </div>
            <div>
                <?php if($session['session_token'] === ($_COOKIE['remember_token'] ?? '')): ?>
                    <span style="color: #34A853; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Текущая
                    </span>
                <?php else: ?>
                    <button onclick="terminateSession('<?php echo $session['session_token']; ?>')" 
                            style="background: rgba(234, 67, 53, 0.2); color: #EA4335; border: none; padding: 0.25rem 0.5rem; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-power-off"></i> Завершить
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.85rem;">
            <div>Последняя активность: <?php echo date('d.m.Y H:i', strtotime($session['last_activity'])); ?></div>
            <div>Истекает: <?php echo date('d.m.Y H:i', strtotime($session['expires_at'])); ?></div>
        </div>
    </div>
    <?php endforeach;
}
?>