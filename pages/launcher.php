<?php
require_once '../config/database.php';
session_start();

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : '';

// Получаем информацию о версиях лаунчера из базы данных
try {
    $stmt = $pdo->prepare("SELECT * FROM launcher_versions ORDER BY release_date DESC LIMIT 5");
    $stmt->execute();
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем статистику загрузок
    $downloads_stmt = $pdo->prepare("SELECT COUNT(*) as total_downloads FROM download_logs");
    $downloads_stmt->execute();
    $download_stats = $downloads_stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Если таблиц нет, создаем заглушки
    $versions = [];
    $download_stats = ['total_downloads' => 0];
}

// Логирование скачивания
if (isset($_GET['download']) && isset($_SESSION['user_id'])) {
    logDownload($_SESSION['user_id']);
}

function logDownload($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO download_logs (user_id, downloaded_at, version) VALUES (?, NOW(), ?)");
        $stmt->execute([$user_id, '1.0.0']); // Текущая версия
    } catch(PDOException $e) {
        // Таблица может не существовать, пропускаем
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Скачать лаунчер | DeepWorld</title>
    <meta name="description" content="Скачайте официальный лаунчер DeepWorld для Minecraft">
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <style>
        /* Стили для страницы лаунчера */
        .launcher-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 6rem 0 4rem;
            margin-top: 64px;
            position: relative;
            overflow: hidden;
        }
        
        .launcher-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(66, 135, 245, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(52, 168, 83, 0.15) 0%, transparent 50%);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .launcher-hero .hero-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .launcher-hero .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 3rem;
            line-height: 1.6;
        }
        
        .download-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
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
        
        .download-platforms {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .platform-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .platform-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4285F4, #34A853);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        
        .platform-card:hover::before {
            transform: translateX(0);
        }
        
        .platform-card:hover {
            transform: translateY(-10px);
            border-color: #4285F4;
            box-shadow: 0 20px 40px rgba(66, 135, 245, 0.2);
        }
        
        .platform-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(66, 135, 245, 0.1);
            border-radius: 20px;
            font-size: 2.5rem;
            color: #4285F4;
            transition: all 0.3s ease;
        }
        
        .platform-card:hover .platform-icon {
            transform: scale(1.1);
            background: rgba(66, 135, 245, 0.2);
        }
        
        .platform-name {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .platform-info {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .launcher-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 2rem 4rem;
            position: relative;
            z-index: 2;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .feature-item {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(45deg, rgba(66, 135, 245, 0.1), rgba(52, 168, 83, 0.1));
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        
        .feature-item:hover::before {
            transform: translateX(0);
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            border-color: #34A853;
        }
        
        .feature-item > * {
            position: relative;
            z-index: 1;
        }
        
        .feature-item i {
            font-size: 2rem;
            color: #34A853;
            margin-bottom: 1rem;
        }
        
        .feature-item h3 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .feature-item p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }
        
        .version-history {
            margin-top: 4rem;
        }
        
        .version-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4285F4;
            transition: transform 0.3s ease;
        }
        
        .version-card:hover {
            transform: translateX(10px);
            border-left-color: #34A853;
        }
        
        .version-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .version-tag {
            background: #4285F4;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .version-date {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }
        
        .version-changes {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .version-changes ul {
            list-style: none;
            padding-left: 0;
        }
        
        .version-changes li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .version-changes li:before {
            content: '•';
            color: #34A853;
            position: absolute;
            left: 0;
        }
        
        .instructions {
            background: linear-gradient(145deg, rgba(66, 135, 245, 0.1), rgba(52, 168, 83, 0.1));
            border-radius: 20px;
            padding: 3rem;
            margin-top: 4rem;
            border: 1px solid rgba(66, 135, 245, 0.2);
        }
        
        .instructions h2 {
            color: white;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2rem;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .step {
            text-align: center;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background: #4285F4;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            transition: all 0.3s ease;
        }
        
        .step:hover .step-number {
            background: #34A853;
            transform: scale(1.1);
        }
        
        .step h3 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .step p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .launcher-hero {
                padding: 5rem 0 3rem;
            }
            
            .launcher-hero .hero-title {
                font-size: 2.5rem;
            }
            
            .download-stats {
                gap: 2rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .instructions {
                padding: 2rem;
            }
            
            .steps {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .launcher-hero .hero-title {
                font-size: 2rem;
            }
            
            .launcher-hero .hero-subtitle {
                font-size: 1rem;
            }
            
            .download-stats {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .platform-card {
                padding: 1.5rem;
            }
            
            .instructions {
                padding: 1.5rem;
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
            <p class="loading-text">Загрузка страницы лаунчера...</p>
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
                    <li><a href="launcher.php" class="active">Скачать лаунчер</a></li>
                    <?php if($isLoggedIn): ?>
                        <li><a href="profile.php">Профиль (<?php echo $username; ?>)</a></li>
                        <li><a href="../api/logout.php" class="btn btn-outline">Выйти</a></li>
                    <?php else: ?>
                        <li><a href="../api/login.php" class="btn btn-outline">Войти</a></li>
                        <li><a href="../api/register.php" class="btn btn-primary">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Герой-секция -->
    <section class="launcher-hero">
        <div class="hero-content">
            <h1 class="hero-title">Официальный лаунчер <span class="highlight">DeepWorld</span></h1>
            <p class="hero-subtitle">Автоматическая установка модов, обновлений и полная совместимость с вашей версией Minecraft</p>
            
            <div class="download-stats">
                <div class="stat-item">
                    <div class="stat-number" data-target="<?php echo $download_stats['total_downloads'] + 1250; ?>">0</div>
                    <div class="stat-label">Скачиваний</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-target="3">3</div>
                    <div class="stat-label">Платформы</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-target="100">100</div>
                    <div class="stat-label">% Безопасно</div>
                </div>
            </div>
            
            <div class="download-platforms">
                <!-- Windows -->
                <div class="platform-card">
                    <div class="platform-icon">
                        <i class="fab fa-windows"></i>
                    </div>
                    <h3 class="platform-name">Windows</h3>
                    <p class="platform-info">Windows 10/11 64-bit</p>
                    <button class="btn btn-primary btn-large" onclick="downloadLauncher('windows')">
                        <i class="fas fa-download"></i> Скачать .exe
                    </button>
                </div>
                
                <!-- macOS -->
                <div class="platform-card">
                    <div class="platform-icon">
                        <i class="fab fa-apple"></i>
                    </div>
                    <h3 class="platform-name">macOS</h3>
                    <p class="platform-info">macOS 10.14 и выше</p>
                    <button class="btn btn-primary btn-large" onclick="downloadLauncher('macos')">
                        <i class="fas fa-download"></i> Скачать .dmg
                    </button>
                </div>
                
                <!-- Linux -->
                <div class="platform-card">
                    <div class="platform-icon">
                        <i class="fab fa-linux"></i>
                    </div>
                    <h3 class="platform-name">Linux</h3>
                    <p class="platform-info">Ubuntu, Debian, Fedora</p>
                    <button class="btn btn-primary btn-large" onclick="downloadLauncher('linux')">
                        <i class="fas fa-download"></i> Скачать .sh
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Основной контент -->
    <main class="launcher-content">
        <!-- Особенности лаунчера -->
        <section class="features-section">
            <h2 class="section-title">Почему наш лаунчер?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-bolt"></i>
                    <h3>Авто-обновления</h3>
                    <p>Автоматическая загрузка последних версий модов и обновлений игры</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Безопасность</h3>
                    <p>Проверенные файлы и защита от вредоносного ПО</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-plug"></i>
                    <h3>Совместимость</h3>
                    <p>Работает со всеми версиями Minecraft от 1.12.2 до 1.20.1</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-puzzle-piece"></i>
                    <h3>Управление модами</h3>
                    <p>Легкое включение/выключение модов, создание сборок</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-cloud"></i>
                    <h3>Облачные сейвы</h3>
                    <p>Синхронизация сохранений между устройствами</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <h3>Мультиплеер</h3>
                    <p>Быстрое подключение к серверам DeepWorld</p>
                </div>
            </div>
        </section>
        
        <!-- Инструкция по установке -->
        <section class="instructions">
            <h2>Как установить лаунчер</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Скачайте</h3>
                    <p>Выберите версию для вашей операционной системы</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Установите</h3>
                    <p>Запустите файл и следуйте инструкциям установщика</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Войдите</h3>
                    <p>Используйте данные от аккаунта на сайте</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Играйте</h3>
                    <p>Выберите сервер и начните приключение</p>
                </div>
            </div>
        </section>
        
        <!-- История версий -->
        <section class="version-history">
            <h2 class="section-title">История обновлений</h2>
            <?php if (!empty($versions)): ?>
                <?php foreach ($versions as $version): ?>
                    <div class="version-card">
                        <div class="version-header">
                            <div>
                                <h3 style="color: white; margin: 0;">Версия <?php echo htmlspecialchars($version['version']); ?></h3>
                                <span class="version-tag"><?php echo htmlspecialchars($version['type']); ?></span>
                            </div>
                            <span class="version-date"><?php echo date('d.m.Y', strtotime($version['release_date'])); ?></span>
                        </div>
                        <div class="version-changes">
                            <?php echo nl2br(htmlspecialchars($version['changelog'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="version-card">
                    <div class="version-header">
                        <div>
                            <h3 style="color: white; margin: 0;">Версия 1.0.0</h3>
                            <span class="version-tag">Стабильная</span>
                        </div>
                        <span class="version-date">01.01.2024</span>
                    </div>
                    <div class="version-changes">
                        <ul>
                            <li>Первая публичная версия лаунчера</li>
                            <li>Поддержка Windows, macOS и Linux</li>
                            <li>Автоматическая установка модов</li>
                            <li>Интеграция с аккаунтом DeepWorld</li>
                            <li>Быстрое подключение к серверам</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </section>
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
                            <li><a href="launcher.php" class="active">Скачать лаунчер</a></li>
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
            // Анимация счётчиков
            animateCounters();
            
            // Анимация карточек платформ
            animatePlatformCards();
            
            // Анимация карточек особенностей
            animateFeatureCards();
            
            // Скрываем загрузку
            setTimeout(() => {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.classList.add('hidden');
                    setTimeout(() => {
                        loading.style.display = 'none';
                    }, 500);
                }
            }, 500);
        });
        
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target')) || 0;
                const duration = 1500;
                const startTime = Date.now();
                
                const animate = () => {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    
                    const currentValue = Math.floor(easeOut * target);
                    counter.textContent = currentValue.toLocaleString();
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };
                
                // Запускаем при попадании в область видимости
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animate();
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(counter);
            });
        }
        
        function animatePlatformCards() {
            const cards = document.querySelectorAll('.platform-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 200);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                observer.observe(card);
            });
        }
        
        function animateFeatureCards() {
            const cards = document.querySelectorAll('.feature-item');
            
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
        
        function downloadLauncher(platform) {
            <?php if($isLoggedIn): ?>
                // Если пользователь авторизован
                showNotification('Начинается загрузка лаунчера для ' + platform.toUpperCase(), 'info');
                
                // Логируем скачивание
                fetch('launcher.php?download=true')
                    .then(() => {
                        // Эмулируем скачивание
                        setTimeout(() => {
                            showNotification('Лаунчер скачан! Запустите установщик.', 'success');
                        }, 1500);
                    })
                    .catch(error => {
                        showNotification('Ошибка при скачивании', 'error');
                        console.error('Download error:', error);
                    });
            <?php else: ?>
                // Если не авторизован
                showNotification('Для скачивания лаунчера необходимо войти в аккаунт', 'error');
                setTimeout(() => {
                    window.location.href = '../api/login.php?redirect=' + encodeURIComponent(window.location.href);
                }, 1500);
            <?php endif; ?>
        }
        
        // Функции для социальных сетей
        function joinDiscord() {
            showNotification('Присоединяйтесь к нашему Discord серверу!', 'info');
        }
        
        function openVK() {
            showNotification('Мы ВКонтакте!', 'info');
        }
        
        function openYouTube() {
            showNotification('Наш YouTube канал', 'info');
        }
        
        function openGitHub() {
            showNotification('Исходный код на GitHub', 'info');
        }
    </script>
</body>
</html>