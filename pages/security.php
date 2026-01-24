<?php
// pages/security.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_auth();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($new_password) < 6) {
        $error = 'Новый пароль должен содержать минимум 6 символов';
    } else {
        // Проверяем текущий пароль
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            // Обновляем пароль
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Записываем активность
            $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity_type, description, ip_address) VALUES (?, 'security', 'Изменен пароль аккаунта', ?)");
            $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
            
            $message = 'Пароль успешно изменен!';
        } else {
            $error = 'Текущий пароль неверен';
        }
    }
}

// Получаем историю сессий
$stmt = $pdo->prepare("
    SELECT * FROM user_sessions 
    WHERE user_id = ? 
    ORDER BY last_activity DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll();

// Получаем историю логинов
$stmt = $pdo->prepare("
    SELECT * FROM login_history 
    WHERE user_id = ? 
    ORDER BY login_time DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$logins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Безопасность | DeepWorld</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .security-container {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        
        .security-header {
            margin-bottom: 2rem;
        }
        
        .security-header h1 {
            font-size: 2rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .security-header p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .security-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .security-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .security-sidebar {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: fit-content;
        }
        
        .security-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .security-nav li {
            margin-bottom: 0.5rem;
        }
        
        .security-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .security-nav a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .security-nav a.active {
            background: rgba(66, 135, 245, 0.1);
            color: #4285F4;
            border-left: 3px solid #4285F4;
        }
        
        .security-content {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .security-section {
            margin-bottom: 3rem;
        }
        
        .security-section:last-child {
            margin-bottom: 0;
        }
        
        .security-section h2 {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(66, 135, 245, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .security-section h2 i {
            color: #4285F4;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: white;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4285F4;
            background: rgba(66, 135, 245, 0.05);
            box-shadow: 0 0 0 3px rgba(66, 135, 245, 0.1);
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: #EA4335;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        .password-strength-text {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 0.25rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #4285F4;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #3367d6;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #EA4335;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: rgba(52, 168, 83, 0.1);
            color: #34A853;
            border: 1px solid rgba(52, 168, 83, 0.2);
        }
        
        .alert-error {
            background: rgba(234, 67, 53, 0.1);
            color: #EA4335;
            border: 1px solid rgba(234, 67, 53, 0.2);
        }
        
        .sessions-list, .logins-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .session-item, .login-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .session-header, .login-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .session-title, .login-title {
            color: white;
            font-weight: 600;
        }
        
        .session-status, .login-status {
            font-size: 0.85rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(52, 168, 83, 0.1);
            color: #34A853;
        }
        
        .status-inactive {
            background: rgba(234, 67, 53, 0.1);
            color: #EA4335;
        }
        
        .status-success {
            background: rgba(52, 168, 83, 0.1);
            color: #34A853;
        }
        
        .status-failed {
            background: rgba(234, 67, 53, 0.1);
            color: #EA4335;
        }
        
        .session-details, .login-details {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .session-details div, .login-details div {
            margin-bottom: 0.25rem;
        }
        
        .session-actions {
            margin-top: 0.75rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .security-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .option-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .option-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .option-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(66, 135, 245, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4285F4;
            font-size: 1.25rem;
        }
        
        .option-title {
            color: white;
            font-weight: 600;
            margin: 0;
        }
        
        .option-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #4285F4;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
    </style>
</head>
<body>
    <!-- Кнопка наверх -->
    <button id="scrollTop" class="scroll-top-btn">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Шапка -->
    <header>
        <div class="container">
            <div class="logo">
                <span class="logo-icon">⚔️</span>
                <div class="logo-text">
                    <h1>DeepWorld</h1>
                    <p class="tagline">Мир приключений в духе Мира Приключений</p>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Главная</a></li>
                    <li><a href="dashboard.php">Личный кабинет</a></li>
                    <li><a href="account_settings.php">Настройки</a></li>
                    <li><a href="security.php" class="active">Безопасность</a></li>
                    <?php if(is_admin()): ?>
                        <li><a href="admin.php" class="btn btn-primary">
                            <i class="fas fa-crown"></i> Админ
                        </a></li>
                    <?php endif; ?>
                    <li><a href="../api/logout.php" class="btn btn-outline">Выйти</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Основной контент -->
    <main class="security-container">
        <div class="container">
            <div class="security-header">
                <h1><i class="fas fa-shield-alt"></i> Безопасность аккаунта</h1>
                <p>Защитите свой аккаунт и отслеживайте активность</p>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="security-grid">
                <!-- Боковая панель -->
                <div class="security-sidebar">
                    <ul class="security-nav">
                        <li><a href="#password" class="active">
                            <i class="fas fa-key"></i> Смена пароля
                        </a></li>
                        <li><a href="#sessions">
                            <i class="fas fa-desktop"></i> Активные сессии
                        </a></li>
                        <li><a href="#logins">
                            <i class="fas fa-history"></i> История входов
                        </a></li>
                        <li><a href="#2fa">
                            <i class="fas fa-mobile-alt"></i> Двухфакторная аутентификация
                        </a></li>
                        <li><a href="#security-settings">
                            <i class="fas fa-cog"></i> Настройки безопасности
                        </a></li>
                    </ul>
                </div>

                <!-- Основной контент -->
                <div class="security-content">
                    <!-- Смена пароля -->
                    <div id="password" class="security-section">
                        <h2><i class="fas fa-key"></i> Изменение пароля</h2>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="form-group">
                                <label for="current_password">Текущий пароль</label>
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Новый пароль</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" required minlength="6">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="passwordStrengthText">
                                    Введите новый пароль
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Подтвердите новый пароль</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Изменить пароль
                            </button>
                        </form>
                    </div>

                    <!-- Активные сессии -->
                    <div id="sessions" class="security-section">
                        <h2><i class="fas fa-desktop"></i> Активные сессии</h2>
                        
                        <div class="sessions-list">
                            <?php if(count($sessions) > 0): ?>
                                <?php foreach($sessions as $session): ?>
                                    <div class="session-item">
                                        <div class="session-header">
                                            <div class="session-title">
                                                Сессия #<?php echo $session['id']; ?>
                                            </div>
                                            <div class="session-status <?php echo ($session['session_id'] === session_id()) ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ($session['session_id'] === session_id()) ? 'Текущая' : 'Неактивна'; ?>
                                            </div>
                                        </div>
                                        <div class="session-details">
                                            <div>IP адрес: <?php echo htmlspecialchars($session['ip_address']); ?></div>
                                            <div>Последняя активность: <?php echo date('d.m.Y H:i', strtotime($session['last_activity'])); ?></div>
                                            <div>Истекает: <?php echo date('d.m.Y H:i', strtotime($session['expires_at'])); ?></div>
                                        </div>
                                        <?php if($session['session_id'] !== session_id()): ?>
                                            <div class="session-actions">
                                                <button class="btn btn-danger" onclick="terminateSession('<?php echo $session['session_id']; ?>')">
                                                    <i class="fas fa-power-off"></i> Завершить сессию
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="session-item">
                                    <div class="session-details" style="text-align: center; color: rgba(255, 255, 255, 0.7);">
                                        Нет активных сессий
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <button class="btn btn-danger" onclick="terminateAllSessions()">
                                <i class="fas fa-power-off"></i> Завершить все сессии
                            </button>
                        </div>
                    </div>

                    <!-- Настройки безопасности -->
                    <div id="security-settings" class="security-section">
                        <h2><i class="fas fa-cog"></i> Настройки безопасности</h2>
                        
                        <div class="security-options">
                            <div class="option-card">
                                <div class="option-header">
                                    <div class="option-icon">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <h3 class="option-title">Уведомления о входе</h3>
                                </div>
                                <div class="option-description">
                                    Получать email при входе с нового устройства
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="option-card">
                                <div class="option-header">
                                    <div class="option-icon">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <h3 class="option-title">Автоматический выход</h3>
                                </div>
                                <div class="option-description">
                                    Выходить из системы при неактивности 30 минут
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="option-card">
                                <div class="option-header">
                                    <div class="option-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <h3 class="option-title">Видимость онлайн-статуса</h3>
                                </div>
                                <div class="option-description">
                                    Показывать другим пользователям, что вы онлайн
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="option-card">
                                <div class="option-header">
                                    <div class="option-icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <h3 class="option-title">Сохранять историю</h3>
                                </div>
                                <div class="option-description">
                                    Сохранять историю входов и действий
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="logo">
                        <span class="logo-icon">⚔️</span>
                        <div class="logo-text">
                            <h3>DeepWorld</h3>
                            <p>Мир приключений в духе Мира Приключений</p>
                        </div>
                    </div>
                    <p class="footer-description">Защитите свой аккаунт от несанкционированного доступа</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">Ваш IP: <?php echo $_SERVER['REMOTE_ADDR']; ?> • Последняя активность: <?php echo date('H:i:s'); ?></p>
            </div>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        // Проверка сложности пароля
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            let color = '#EA4335';
            let text = 'Очень слабый';
            
            if (password.length >= 6) {
                strength += 25;
                color = '#FF9800';
                text = 'Слабый';
            }
            
            if (password.length >= 8) {
                strength += 25;
                color = '#FFC107';
                text = 'Средний';
            }
            
            if (/[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strength += 25;
                color = '#4CAF50';
                text = 'Хороший';
            }
            
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 25;
                color = '#2196F3';
                text = 'Отличный';
            }
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });
        
        // Завершение сессии
        function terminateSession(sessionId) {
            if (confirm('Завершить эту сессию?')) {
                fetch('api/terminate_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Сессия завершена', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                });
            }
        }
        
        function terminateAllSessions() {
            if (confirm('Завершить все сессии? Вы будете вынуждены войти заново.')) {
                fetch('api/terminate_all_sessions.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Все сессии завершены', 'success');
                        setTimeout(() => {
                            window.location.href = '../api/logout.php';
                        }, 1000);
                    }
                });
            }
        }
        
        // Показ уведомлений
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Плавная прокрутка
        document.querySelectorAll('.security-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    document.querySelectorAll('.security-nav a').forEach(a => {
                        a.classList.remove('active');
                    });
                    
                    this.classList.add('active');
                    
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>