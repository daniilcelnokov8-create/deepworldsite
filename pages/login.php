<?php
require_once '../config/database.php';
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header('Location: ../api/login.php');
    exit();
}

// Получаем расширенные данные пользователя
try {
   $stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.role 
    FROM user_sessions us 
    JOIN users u ON us.user_id = u.id 
    WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
");
    
    // Если нет аватара - ставим дефолтный
    if (empty($user['avatar'])) {
        $user['avatar'] = 'default';
    }
    
} catch(PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}

// Получаем статистику пользователя (заглушка - можно подключить реальную статистику)
$stats = [
    'playtime' => rand(50, 500),
    'quests_completed' => rand(10, 100),
    'structures_built' => rand(5, 50),
    'friends_count' => rand(1, 30),
    'level' => rand(1, 50),
    'experience' => rand(0, 1000),
    'rank' => 'Исследователь'
];

// Генерируем цвет аватара на основе username
function generateAvatarColor($username) {
    $hash = crc32($username);
    $colors = [
        '#4285F4', // Синий
        '#EA4335', // Красный
        '#34A853', // Зеленый
        '#FBBC05', // Желтый
        '#8B5CF6', // Фиолетовый
        '#EC4899', // Розовый
        '#0EA5E9', // Голубой
        '#10B981', // Изумрудный
    ];
    return $colors[$hash % count($colors)];
}
$avatarColor = generateAvatarColor($user['username']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?php echo htmlspecialchars($user['username']); ?> | DeepWorld</title>
    <meta name="description" content="Личный кабинет игрока DeepWorld">
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <style>
        /* Специфичные стили для профиля */
        .profile-hero {
            position: relative;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 4rem 0 6rem;
            margin-bottom: -3rem;
            overflow: hidden;
        }
        
        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(66, 135, 245, 0.1) 0%, transparent 50%);
        }
        
        .profile-header-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                text-align: center;
                gap: 2rem;
            }
        }
        
        .avatar-container {
            position: relative;
        }
        
        .profile-avatar {
            width: 180px;
            height: 180px;
            border-radius: 20px;
            background: <?php echo $avatarColor; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            font-weight: bold;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 4px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .avatar-edit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .avatar-edit:hover {
            background: rgba(66, 135, 245, 0.9);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-name {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }
        
        .profile-rank {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #1a1a2e;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .profile-bio {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            max-width: 600px;
        }
        
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .meta-item i {
            color: #4285F4;
        }
        
        .profile-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        /* Основной контент профиля */
        .profile-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 4rem;
            position: relative;
            z-index: 2;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .profile-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border-color: rgba(66, 135, 245, 0.3);
        }
        
        .profile-card h2 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(66, 135, 245, 0.3);
        }
        
        .profile-card h2 i {
            color: #4285F4;
        }
        
        /* Статистика */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            transition: background 0.3s ease;
        }
        
        .stat-item:hover {
            background: rgba(66, 135, 245, 0.1);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4285F4;
            margin-bottom: 0.5rem;
            font-family: 'Space Grotesk', sans-serif;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        /* Прогресс уровень */
        .level-container {
            margin-top: 1.5rem;
        }
        
        .level-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .progress-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4285F4, #34A853);
            border-radius: 5px;
            width: <?php echo ($stats['experience'] % 1000) / 10; ?>%;
            transition: width 1s ease;
        }
        
        /* Настройки */
        .settings-list {
            list-style: none;
            padding: 0;
        }
        
        .settings-list li {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .settings-list li:last-child {
            border-bottom: none;
        }
        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
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
        
        .slider:before {
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
        
        input:checked + .slider {
            background-color: #4285F4;
        }
        
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        
        /* Быстрые действия */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            background: rgba(66, 135, 245, 0.1);
            border-radius: 15px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .action-btn:hover {
            background: rgba(66, 135, 245, 0.2);
            border-color: #4285F4;
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 2rem;
            color: #4285F4;
            margin-bottom: 0.75rem;
        }
        
        .action-btn span {
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
        }
        
        /* Ачивки */
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 1rem;
        }
        
        .achievement {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .achievement.unlocked {
            background: rgba(66, 135, 245, 0.2);
            color: #4285F4;
            box-shadow: 0 5px 15px rgba(66, 135, 245, 0.2);
        }
        
        .achievement:hover {
            transform: scale(1.1);
        }
        
        /* Адаптивность */
        @media (max-width: 480px) {
            .profile-avatar {
                width: 140px;
                height: 140px;
                font-size: 3rem;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Анимация загрузки -->
    <div id="loading" class="loading">
        <div class="loading-content">
            <div class="loading-logo">
                <div class="loading-dot" style="--delay: 0s;"></div>
                <div class="loading-dot" style="--delay: 0.2s;"></div>
                <div class="loading-dot" style="--delay: 0.4s;"></div>
            </div>
            <p class="loading-text">Загрузка профиля...</p>
        </div>
    </div>

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
                    <li><a href="about.html">О нас</a></li>
                    <li><a href="servers.html">Описание серверов</a></li>
                    <li><a href="donate.html">Донат</a></li>
                    <li><a href="team.html">Наша команда</a></li>
                    <li><a href="launcher.html" class="btn btn-outline">Скачать лаунчер</a></li>
                    <li><a href="profile.php" class="active">Профиль (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                    <li><a href="../api/logout.php" class="btn btn-outline">Выйти</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Герой-секция профиля -->
    <section class="profile-hero">
        <div class="profile-header-content">
            <div class="avatar-container">
                <div class="profile-avatar" id="userAvatar">
                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                </div>
                <div class="avatar-edit" onclick="changeAvatar()" title="Изменить аватар">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            
            <div class="profile-info">
                <div class="profile-title">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <span class="profile-rank"><?php echo htmlspecialchars($stats['rank']); ?></span>
                </div>
                
                <?php if(!empty($user['bio'])): ?>
                    <p class="profile-bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                <?php else: ?>
                    <p class="profile-bio">Исследователь мира DeepWorld. Добавьте описание в настройках профиля!</p>
                <?php endif; ?>
                
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Присоединился: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-gamepad"></i>
                        <span>Уровень: <?php echo $stats['level']; ?></span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <button class="btn btn-primary" onclick="editProfile()">
                        <i class="fas fa-edit"></i> Редактировать профиль
                    </button>
                    <button class="btn btn-outline" onclick="openLauncher()">
                        <i class="fas fa-download"></i> Скачать лаунчер
                    </button>
                    <button class="btn btn-outline" onclick="shareProfile()">
                        <i class="fas fa-share-alt"></i> Поделиться
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Основной контент профиля -->
    <main class="profile-content">
        <div class="profile-grid">
            <!-- Статистика -->
            <div class="profile-card">
                <h2><i class="fas fa-chart-line"></i> Статистика</h2>
                <div class="stats-container">
                    <div class="stat-item">
                        <div class="stat-value" data-target="<?php echo $stats['playtime']; ?>">0</div>
                        <div class="stat-label">Часов игры</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" data-target="<?php echo $stats['quests_completed']; ?>">0</div>
                        <div class="stat-label">Квестов</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" data-target="<?php echo $stats['structures_built']; ?>">0</div>
                        <div class="stat-label">Построек</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" data-target="<?php echo $stats['friends_count']; ?>">0</div>
                        <div class="stat-label">Друзей</div>
                    </div>
                </div>
                
                <div class="level-container">
                    <div class="level-info">
                        <span>Уровень <?php echo $stats['level']; ?></span>
                        <span><?php echo $stats['experience']; ?>/1000 опыта</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                </div>
            </div>
            
            <!-- Настройки -->
            <div class="profile-card">
                <h2><i class="fas fa-cog"></i> Настройки</h2>
                <ul class="settings-list">
                    <li>
                        <span>Уведомления по email</span>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </li>
                    <li>
                        <span>Двухфакторная аутентификация</span>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </li>
                    <li>
                        <span>Отображение онлайн-статуса</span>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </li>
                    <li>
                        <span>Приватность профиля</span>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </li>
                </ul>
            </div>
            
            <!-- Быстрые действия -->
            <div class="profile-card">
                <h2><i class="fas fa-bolt"></i> Быстрые действия</h2>
                <div class="quick-actions">
                    <a href="#" class="action-btn" onclick="changePassword()">
                        <i class="fas fa-key"></i>
                        <span>Сменить пароль</span>
                    </a>
                    <a href="#" class="action-btn" onclick="connectDiscord()">
                        <i class="fab fa-discord"></i>
                        <span>Привязать Discord</span>
                    </a>
                    <a href="#" class="action-btn" onclick="viewInventory()">
                        <i class="fas fa-box"></i>
                        <span>Инвентарь</span>
                    </a>
                    <a href="#" class="action-btn" onclick="openSupport()">
                        <i class="fas fa-headset"></i>
                        <span>Поддержка</span>
                    </a>
                </div>
            </div>
            
            <!-- Ачивки -->
            <div class="profile-card">
                <h2><i class="fas fa-trophy"></i> Достижения</h2>
                <div class="achievements-grid">
                    <?php
                    $achievements = ['🏆', '⚔️', '🛡️', '🎯', '📚', '🏰', '🗺️', '💎', '🔥', '🌟', '🎨', '⚡'];
                    for($i = 0; $i < 12; $i++): 
                        $unlocked = $i < 4; // Первые 4 ачивки разблокированы
                    ?>
                        <div class="achievement <?php echo $unlocked ? 'unlocked' : ''; ?>" 
                             title="<?php echo $unlocked ? 'Разблокировано' : 'Заблокировано'; ?>">
                            <?php echo $achievements[$i]; ?>
                        </div>
                    <?php endfor; ?>
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
                    <div class="social-links">
                        <a href="#" onclick="joinDiscord()"><i class="fab fa-discord"></i></a>
                        <a href="#" onclick="openVK()"><i class="fab fa-vk"></i></a>
                        <a href="#" onclick="openYouTube()"><i class="fab fa-youtube"></i></a>
                        <a href="#" onclick="openGitHub()"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <div class="links-column">
                        <h4>Навигация</h4>
                        <ul>
                            <li><a href="../index.php">Главная</a></li>
                            <li><a href="about.html">О нас</a></li>
                            <li><a href="servers.html">Описание серверов</a></li>
                            <li><a href="donate.html">Донат</a></li>
                            <li><a href="team.html">Наша команда</a></li>
                        </ul>
                    </div>
                    <div class="links-column">
                        <h4>Ресурсы</h4>
                        <ul>
                            <li><a href="launcher.html">Скачать лаунчер</a></li>
                            <li><a href="#">Список модов</a></li>
                            <li><a href="#">Карта мира</a></li>
                            <li><a href="#">Гайды</a></li>
                            <li><a href="#">Частые вопросы</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">DeepWorld является неофициальным проектом, не связанным с Mojang AB или Cartoon Network.</p>
            </div>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Анимация счётчиков статистики
            animateCounters();
            
            // Анимация карточек
            animateProfileCards();
            
            // Скрываем загрузку
            setTimeout(() => {
                document.getElementById('loading').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('loading').style.display = 'none';
                }, 300);
            }, 500);
        });
        
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-value');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 1500;
                const step = target / (duration / 16);
                let current = 0;
                
                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                // Запускаем при попадании в область видимости
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(counter);
            });
        }
        
        function animateProfileCards() {
            const cards = document.querySelectorAll('.profile-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        }
        
        function editProfile() {
            showNotification('Редактирование профиля скоро будет доступно!', 'info');
        }
        
        function changeAvatar() {
            showNotification('Смена аватара в разработке. Скоро можно будет загрузить свою картинку!', 'info');
        }
        
        function changePassword() {
            showNotification('Переход на страницу смены пароля...', 'info');
            setTimeout(() => {
                // Здесь можно добавить переход на страницу смены пароля
                window.location.href = '#password';
            }, 1000);
        }
        
        function connectDiscord() {
            showNotification('Привязка Discord аккаунта скоро будет доступна!', 'info');
        }
        
        function viewInventory() {
            showNotification('Просмотр инвентаря в разработке.', 'info');
        }
        
        function openSupport() {
            showNotification('Обратитесь в поддержку через Discord сервер.', 'info');
        }
        
        function openLauncher() {
            window.location.href = 'launcher.html';
        }
        
        function shareProfile() {
            if (navigator.share) {
                navigator.share({
                    title: 'Мой профиль DeepWorld',
                    text: 'Посмотрите мой профиль в DeepWorld!',
                    url: window.location.href
                });
            } else {
                showNotification('Ссылка на профиль скопирована в буфер обмена!', 'success');
                navigator.clipboard.writeText(window.location.href);
            }
        }
        
        // Функция уведомлений (должна быть в script.js)
        function showNotification(message, type = 'info') {
            // Если функция уже есть в script.js, она перезапишется
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
    </script>
</body>
</html>