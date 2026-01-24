<?php
// ВСЕГДА начинаем сессию в самом начале файла
session_start();

// Подключаем конфиг базы данных
require_once '../config/database.php';

// Проверяем авторизацию БЕЗ использования include auth.php
if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован, перенаправляем на страницу логина
    header('Location: /api/login.php');
    exit();
}

// Получаем данные пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Если пользователь не найден в БД
if (!$user) {
    // Уничтожаем сессию и перенаправляем на логин
    session_destroy();
    header('Location: /api/login.php');
    exit();
}

// Получаем статистику пользователя
$stats = [
    'total_logins' => 0,
    'last_login' => $user['last_login'] ?? 'Никогда',
    'account_age' => '',
    'sessions_count' => 0
];

// Считаем количество логинов (если таблица существует)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_history WHERE user_id = ? AND success = 1");
    $stmt->execute([$user_id]);
    $stats['total_logins'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    // Таблица может не существовать, игнорируем
}

// Возраст аккаунта
if ($user['created_at']) {
    $created = new DateTime($user['created_at']);
    $now = new DateTime();
    $interval = $created->diff($now);
    
    if ($interval->y > 0) {
        $stats['account_age'] = $interval->y . ' лет';
    } elseif ($interval->m > 0) {
        $stats['account_age'] = $interval->m . ' месяцев';
    } elseif ($interval->d > 0) {
        $stats['account_age'] = $interval->d . ' дней';
    } else {
        $stats['account_age'] = 'Менее дня';
    }
}

// Получаем последние активности (если таблица существует)
try {
    $stmt = $pdo->prepare("SELECT * FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    $activities = [];
}

// Обновляем время последнего логина
try {
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
} catch (Exception $e) {
    // Игнорируем ошибку, если поле не существует
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | DeepWorld</title>
    <link rel="stylesheet" href="/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid rgba(66, 135, 245, 0.3);
        }
        
        .user-welcome {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        
        .user-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(45deg, #4285F4, #34A853);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .user-info p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }
        
        .user-role {
            display: inline-block;
            padding: 0.25rem 1rem;
            background: rgba(66, 135, 245, 0.2);
            color: #4285F4;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(66, 135, 245, 0.3);
        }
        
        .user-role.admin {
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.2), rgba(255, 165, 0, 0.2));
            color: #FFD700;
            border-color: rgba(255, 215, 0, 0.3);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            border-color: rgba(66, 135, 245, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .dashboard-card h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: white;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(66, 135, 245, 0.3);
        }
        
        .dashboard-card h2 i {
            color: #4285F4;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .stat-value {
            color: white;
            font-weight: 600;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(66, 135, 245, 0.1);
        }
        
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(66, 135, 245, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4285F4;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            color: white;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }
        
        .dashboard-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: rgba(66, 135, 245, 0.1);
            border: 1px solid rgba(66, 135, 245, 0.3);
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: rgba(66, 135, 245, 0.2);
            transform: translateY(-3px);
            border-color: #4285F4;
        }
        
        .action-btn i {
            color: #4285F4;
            font-size: 1.25rem;
        }
        
        .admin-badge {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #1a1a2e;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal.active {
            display: flex;
            animation: modalFadeIn 0.3s ease;
        }
        
        .modal-content {
            background: linear-gradient(145deg, #1a1a2e, #16213e);
            border-radius: 15px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .user-welcome {
                flex-direction: column;
                text-align: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-actions {
                grid-template-columns: 1fr;
            }
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
                    <li><a href="/index.php">Главная</a></li>
                    <li><a href="/pages/about.html">О нас</a></li>
                    <li><a href="/pages/servers.html">Описание серверов</a></li>
                    <li><a href="/pages/donate.html">Донат</a></li>
                    <li><a href="/pages/dashboard.php" class="active">Личный кабинет</a></li>
                    <?php if(($user['role'] ?? 'user') === 'admin'): ?>
                        <li><a href="/pages/admin.php" class="btn btn-primary">
                            <i class="fas fa-crown"></i> Админ-панель
                        </a></li>
                    <?php endif; ?>
                    <li><a href="/api/logout.php" class="btn btn-outline">Выйти</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Основной контент -->
    <main class="dashboard">
        <div class="container">
            <!-- Приветствие -->
            <div class="dashboard-header">
                <div class="user-welcome">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($user['username'] ?? '??', 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <h1>Добро пожаловать, <?php echo htmlspecialchars($user['username'] ?? 'Пользователь'); ?>!</h1>
                        <p><?php echo htmlspecialchars($user['email'] ?? 'Не указан'); ?></p>
                        <span class="user-role <?php echo ($user['role'] ?? 'user') === 'admin' ? 'admin' : ''; ?>">
                            <i class="fas fa-<?php echo ($user['role'] ?? 'user') === 'admin' ? 'crown' : 'user'; ?>"></i>
                            <?php echo ($user['role'] ?? 'user') === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="dashboard-grid">
                <!-- Статистика аккаунта -->
                <div class="dashboard-card">
                    <h2><i class="fas fa-chart-bar"></i> Статистика аккаунта</h2>
                    <div class="stat-item">
                        <span class="stat-label">Всего логинов:</span>
                        <span class="stat-value"><?php echo $stats['total_logins']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Последний вход:</span>
                        <span class="stat-value">
                            <?php 
                            if ($stats['last_login'] && $stats['last_login'] !== 'Никогда') {
                                echo date('d.m.Y H:i', strtotime($stats['last_login']));
                            } else {
                                echo 'Никогда';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Возраст аккаунта:</span>
                        <span class="stat-value"><?php echo $stats['account_age']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Статус аккаунта:</span>
                        <span class="stat-value" style="color: <?php echo ($user['is_active'] ?? 1) ? '#34A853' : '#EA4335'; ?>">
                            <i class="fas fa-<?php echo ($user['is_active'] ?? 1) ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo ($user['is_active'] ?? 1) ? 'Активен' : 'Заблокирован'; ?>
                        </span>
                    </div>
                </div>

                <!-- Активность -->
                <div class="dashboard-card">
                    <h2><i class="fas fa-history"></i> Последняя активность</h2>
                    <div class="activity-list">
                        <?php if(count($activities) > 0): ?>
                            <?php foreach($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php 
                                            switch($activity['activity_type'] ?? 'info') {
                                                case 'login': echo 'sign-in-alt'; break;
                                                case 'logout': echo 'sign-out-alt'; break;
                                                case 'update': echo 'edit'; break;
                                                default: echo 'bell';
                                            }
                                        ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($activity['description'] ?? 'Действие'); ?></div>
                                        <div class="activity-time">
                                            <?php echo date('d.m.Y H:i', strtotime($activity['created_at'] ?? date('Y-m-d H:i:s'))); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">Нет записей активности</div>
                                    <div class="activity-time">Начните использовать систему</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Информация об аккаунте -->
                <div class="dashboard-card">
                    <h2><i class="fas fa-user-circle"></i> Информация об аккаунте</h2>
                    <div class="stat-item">
                        <span class="stat-label">ID пользователя:</span>
                        <span class="stat-value">#<?php echo $user['id']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Дата регистрации:</span>
                        <span class="stat-value">
                            <?php echo date('d.m.Y', strtotime($user['created_at'] ?? date('Y-m-d H:i:s'))); ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Роль:</span>
                        <span class="stat-value">
                            <i class="fas fa-<?php echo ($user['role'] ?? 'user') === 'admin' ? 'crown' : 'user'; ?>"></i>
                            <?php echo ($user['role'] ?? 'user') === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">IP адрес:</span>
                        <span class="stat-value"><?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="dashboard-card">
                <h2><i class="fas fa-bolt"></i> Быстрые действия</h2>
                <div class="dashboard-actions">
                    <a href="/pages/profile.php" class="action-btn">
                        <i class="fas fa-user-edit"></i>
                        <span>Редактировать профиль</span>
                    </a>
                    <a href="/pages/security.php" class="action-btn">
                        <i class="fas fa-shield-alt"></i>
                        <span>Безопасность</span>
                    </a>
                    <a href="/pages/launcher.html" class="action-btn">
                        <i class="fas fa-download"></i>
                        <span>Скачать лаунчер</span>
                    </a>
                    <a href="#" class="action-btn" onclick="showSessions()">
                        <i class="fas fa-desktop"></i>
                        <span>Мои сессии</span>
                    </a>
                    <?php if(($user['role'] ?? 'user') === 'admin'): ?>
                        <a href="/pages/admin.php" class="action-btn" style="background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.1)); border-color: rgba(255, 215, 0, 0.3);">
                            <i class="fas fa-crown" style="color: #FFD700;"></i>
                            <span>Админ-панель</span>
                        </a>
                    <?php endif; ?>
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
                    <p class="footer-description">Проект создан с любовью к миру Minecraft и духу приключений</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">Ваш IP: <?php echo $_SERVER['REMOTE_ADDR']; ?> • Время сервера: <?php echo date('H:i:s'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Модальное окно сессий -->
    <div id="sessionsModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem; color: white;"><i class="fas fa-desktop"></i> Активные сессии</h2>
            <div id="sessionsList">
                <p style="color: rgba(255, 255, 255, 0.7); text-align: center;">Функция в разработке...</p>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button class="btn btn-primary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Закрыть
                </button>
            </div>
        </div>
    </div>

    <script src="/script.js"></script>
    <script>
        // Модальные окна
        function showSessions() {
            const modal = document.getElementById('sessionsModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Показываем заглушку
            document.getElementById('sessionsList').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: rgba(255, 255, 255, 0.7);">
                    <i class="fas fa-tools" style="font-size: 3rem; margin-bottom: 1rem; color: #4285F4;"></i>
                    <p>Функция управления сессиями находится в разработке</p>
                </div>
            `;
        }
        
        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = 'auto';
        }
        
        // Закрытие модального окна по клику вне его
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
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
        
        // Анимация загрузки
        document.addEventListener('DOMContentLoaded', function() {
            const loading = document.getElementById('loading');
            if (loading) {
                setTimeout(() => {
                    loading.style.opacity = '0';
                    setTimeout(() => {
                        loading.style.display = 'none';
                    }, 300);
                }, 1000);
            }
            
            // Инициализация мобильного меню
            const menuToggle = document.getElementById('menuToggle');
            const navMenu = document.querySelector('nav ul');
            
            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    menuToggle.innerHTML = navMenu.classList.contains('active') 
                        ? '<i class="fas fa-times"></i>' 
                        : '<i class="fas fa-bars"></i>';
                });
                
                // Закрытие меню при клике на ссылку
                document.querySelectorAll('nav a').forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('active');
                        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                    });
                });
            }
        });
    </script>
</body>
</html>