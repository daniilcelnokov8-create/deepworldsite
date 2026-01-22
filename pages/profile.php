<?php
session_start();
require_once '../includes/functions.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUser();
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Получаем информацию о пользователе
try {
    require_once '../config/database.php';
    $db = Database::getConnection();
    
    // Получаем донаты пользователя
    $stmt = $db->prepare("
        SELECT * FROM donations 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $donations = $stmt->fetchAll();
    
    // Получаем активные сессии
    $stmt = $db->prepare("
        SELECT * FROM user_sessions 
        WHERE user_id = ? AND expires_at > NOW()
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $sessions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
    $donations = [];
    $sessions = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | DeepWorld</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="profile-page">
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-stats">
                        <div class="stat">
                            <div class="stat-number"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                            <div class="stat-label">Дата регистрации</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo count($donations); ?></div>
                            <div class="stat-label">Поддержек проекта</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo count($sessions); ?></div>
                            <div class="stat-label">Активных сессий</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-grid">
                <!-- Токен для лаунчера -->
                <div class="profile-card">
                    <h3><i class="fas fa-key"></i> Токен для лаунчера</h3>
                    <div class="launcher-token">
                        <code id="launcherToken"><?php echo $_SESSION['launcher_token'] ?? 'Не найден'; ?></code>
                        <button class="btn btn-outline btn-small" onclick="copyToken()">
                            <i class="fas fa-copy"></i> Копировать
                        </button>
                    </div>
                    <p class="token-note">
                        <i class="fas fa-info-circle"></i> Используйте этот токен для входа в лаунчер DeepWorld
                    </p>
                </div>

                <!-- Информация аккаунта -->
                <div class="profile-card">
                    <h3><i class="fas fa-user"></i> Информация аккаунта</h3>
                    <div class="account-info">
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Статус:</span>
                            <span class="info-value status-<?php echo $user['status']; ?>">
                                <?php 
                                $statuses = [
                                    'active' => 'Активен',
                                    'pending' => 'Ожидает подтверждения',
                                    'banned' => 'Заблокирован'
                                ];
                                echo $statuses[$user['status']] ?? $user['status'];
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Последний вход:</span>
                            <span class="info-value">
                                <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Донаты -->
                <div class="profile-card">
                    <h3><i class="fas fa-heart"></i> Поддержка проекта</h3>
                    <?php if (empty($donations)): ?>
                        <p class="empty-state">Вы ещё не поддерживали проект</p>
                        <a href="donate.php" class="btn btn-primary btn-small">
                            <i class="fas fa-gift"></i> Поддержать проект
                        </a>
                    <?php else: ?>
                        <div class="donations-list">
                            <?php foreach ($donations as $donation): ?>
                                <div class="donation-item">
                                    <div class="donation-header">
                                        <span class="donation-tier"><?php echo htmlspecialchars($donation['tier']); ?></span>
                                        <span class="donation-amount"><?php echo number_format($donation['amount'], 2); ?> ₽</span>
                                    </div>
                                    <div class="donation-date">
                                        <?php echo date('d.m.Y H:i', strtotime($donation['created_at'])); ?>
                                    </div>
                                    <div class="donation-status status-<?php echo $donation['status']; ?>">
                                        <?php 
                                        $statuses = [
                                            'completed' => 'Завершено',
                                            'pending' => 'В обработке',
                                            'failed' => 'Ошибка'
                                        ];
                                        echo $statuses[$donation['status']] ?? $donation['status'];
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Активные сессии -->
                <div class="profile-card">
                    <h3><i class="fas fa-desktop"></i> Активные сессии</h3>
                    <div class="sessions-list">
                        <?php foreach ($sessions as $session): ?>
                            <div class="session-item <?php echo $session['session_token'] === $_SESSION['session_token'] ? 'current' : ''; ?>">
                                <div class="session-info">
                                    <div class="session-ip">
                                        <i class="fas fa-globe"></i> <?php echo htmlspecialchars($session['ip_address']); ?>
                                    </div>
                                    <div class="session-date">
                                        Создана: <?php echo date('d.m.Y H:i', strtotime($session['created_at'])); ?>
                                    </div>
                                    <div class="session-expires">
                                        Истекает: <?php echo date('d.m.Y H:i', strtotime($session['expires_at'])); ?>
                                    </div>
                                </div>
                                <?php if ($session['session_token'] !== $_SESSION['session_token']): ?>
                                    <form method="POST" action="logout_session.php" class="session-action">
                                        <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                        <button type="submit" class="btn btn-outline btn-small">
                                            <i class="fas fa-sign-out-alt"></i> Выйти
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Действия с аккаунтом -->
            <div class="profile-actions">
                <h3><i class="fas fa-cog"></i> Управление аккаунтом</h3>
                <div class="actions-grid">
                    <a href="change_password.php" class="action-card">
                        <i class="fas fa-key"></i>
                        <h4>Сменить пароль</h4>
                        <p>Обновите пароль для входа</p>
                    </a>
                    <a href="change_email.php" class="action-card">
                        <i class="fas fa-envelope"></i>
                        <h4>Сменить email</h4>
                        <p>Изменить email аккаунта</p>
                    </a>
                    <a href="security.php" class="action-card">
                        <i class="fas fa-shield-alt"></i>
                        <h4>Безопасность</h4>
                        <p>Настройки безопасности</p>
                    </a>
                    <a href="logout.php" class="action-card logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <h4>Выйти</h4>
                        <p>Выйти из всех устройств</p>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Копирование токена
        function copyToken() {
            const tokenElement = document.getElementById('launcherToken');
            const token = tokenElement.textContent;
            
            navigator.clipboard.writeText(token).then(() => {
                // Показываем уведомление
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-check-circle"></i>
                        <span>Токен скопирован в буфер обмена!</span>
                    </div>
                `;
                
                // Стили
                notification.style.cssText = `
                    position: fixed;
                    top: 100px;
                    right: 24px;
                    background: var(--color-dark);
                    color: white;
                    padding: 16px 24px;
                    border-radius: var(--radius-md);
                    box-shadow: var(--shadow-lg);
                    z-index: 9999;
                    transform: translateX(120%);
                    transition: transform 0.3s ease;
                `;
                
                document.body.appendChild(notification);
                
                // Анимация
                setTimeout(() => notification.style.transform = 'translateX(0)', 10);
                setTimeout(() => {
                    notification.style.transform = 'translateX(120%)';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }).catch(err => {
                console.error('Ошибка копирования:', err);
                alert('Не удалось скопировать токен');
            });
        }
    </script>
</body>
</html>