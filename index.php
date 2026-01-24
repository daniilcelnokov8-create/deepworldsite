<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем авторизацию
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeepWorld | Мир приключений в Minecraft</title>
    <meta name="description" content="Уникальный Minecraft проект с собственным лаунчером, кастомными мирами и захватывающими приключениями">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
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
            <p class="loading-text">Погружение в DeepWorld...</p>
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
                    <p class="tagline">Мир приключений в духе AdventureTime!</p>
                </div>
            </div>
                <nav>
        <ul>
            <li><a href="index.php" class="active">Главная</a></li>
            <li><a href="pages/about.html">О нас</a></li>
            <li><a href="pages/servers.html">Описание серверов</a></li>
            <li><a href="pages/donate.html">Донат</a></li>
            <li><a href="pages/team.html">Наша команда</a></li>
            <li><a href="pages/launcher.php" class="btn btn-outline">Скачать лаунчер</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="pages/profile.php">Профиль (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <li><a href="api/logout.php" class="btn btn-outline">Выйти</a></li>
            <?php else: ?>
                <li><a href="api/login.php" class="btn btn-outline">Войти</a></li>
                <li><a href="api/register.php" class="btn btn-primary">Регистрация</a></li>
            <?php endif; ?>
        </ul>
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
        </div>
    </header>

    <!-- Главный экран -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h2 class="hero-title">
                    <span class="title-line">Исследуйте</span>
                    <span class="title-line highlight">DeepWorld</span>
                    <span class="title-line">в Minecraft</span>
                </h2>
                <p class="hero-subtitle">Погрузитесь в уникальную вселенную с собственным лаунчером, кастомными мирами и захватывающими приключениями</p>
                <div class="hero-buttons">
                    <a href="pages/launcher.php" class="btn btn-primary">
                        <i class="fas fa-download"></i> Скачать лаунчер
                    </a>
                    <a href="#features" class="btn btn-outline">
                        <i class="fas fa-compass"></i> Узнать больше
                    </a>
                </div>
            </div>
            <?php if(!$isLoggedIn): ?>
            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-number" data-target="1000">0</div>
                    <div class="stat-label">Активных исследователей</div>
                </div>
                <div class="stat">
                    <div class="stat-number" data-target="50">0</div>
                    <div class="stat-label">Уникальных локаций</div>
                </div>
                <div class="stat">
                    <div class="stat-number" data-target="24">0</div>
                    <div class="stat-label">/7 онлайн сервер</div>
                </div>
            </div>
            <?php else: ?>
            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-number" data-target="1">0</div>
                    <div class="stat-label">Вы вошли как <?php echo $username; ?></div>
                </div>
                <div class="stat">
                    <a href="pages/profile.php" class="btn btn-primary">
                        <i class="fas fa-user"></i> Личный кабинет
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <div class="scroll-indicator">
                <span>Исследуйте дальше</span>
                <div class="chevron"></div>
            </div>
        </div>
    </section>

    <!-- Особенности -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Что делает <span class="highlight">DeepWorld</span> уникальным</h2>
                <p class="section-subtitle">Откройте для себя возможности нашего проекта</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Собственный лаунчер</h3>
                    <p>Автоматическая установка всех модов и обновлений. Работает на Windows, macOS и Linux</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Кастомные миры</h3>
                    <p>Уникальные биомы и локации, созданные специально для DeepWorld</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-scroll"></i>
                    </div>
                    <h3>Сюжетные квесты</h3>
                    <p>Захватывающие приключения с ветвящимся сюжетом и выбором, влияющим на мир</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Живое сообщество</h3>
                    <p>Присоединяйтесь к тысячам игроков и создавайте историю вместе</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Как начать -->
    <section id="start" class="start">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Как начать <span class="highlight">игру</span></h2>
                <p class="section-subtitle">Всего 3 простых шага до начала приключений</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-download"></i>
                        <div class="step-number">1</div>
                    </div>
                    <h3>Скачайте лаунчер</h3>
                    <p>Наша собственная разработка для Windows, macOS и Linux</p>
                    <a href="pages/launcher.php" class="step-link">Скачать →</a>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-user-plus"></i>
                        <div class="step-number">2</div>
                    </div>
                    <h3>Зарегистрируйтесь</h3>
                    <p>Создайте аккаунт на сайте для доступа ко всем возможностям</p>
                    <?php if(!$isLoggedIn): ?>
                        <a href="api/register.php" class="step-link">Регистрация →</a>
                    <?php else: ?>
                        <a href="pages/profile.php" class="step-link">Ваш профиль →</a>
                    <?php endif; ?>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-play"></i>
                        <div class="step-number">3</div>
                    </div>
                    <h3>Начните играть</h3>
                    <p>Запустите лаунчер, войдите и окунитесь в мир приключений</p>
                    <a href="pages/launcher.php" class="step-link">Начать →</a>
                </div>
            </div>
        </div>
    </section>

    <?php if(!$isLoggedIn): ?>
<!-- Блок регистрации для неавторизированных пользователей -->
<section id="register" class="register">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Начните <span class="highlight">путешествие</span> сегодня</h2>
            <p class="section-subtitle">Присоединяйтесь к сообществу исследователей DeepWorld</p>
        </div>
        
        <div class="register-content">
            <div class="register-card">
                <div class="register-card-header">
                    <div class="register-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Быстрая регистрация</h3>
                    <p>Создайте аккаунт за 30 секунд</p>
                </div>
                
                <form class="register-form" id="registerForm" action="api/register.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i> Никнейм
                            </label>
                            <input type="text" id="username" name="username" 
                                   placeholder="Ваш игровой ник" 
                                   required 
                                   pattern="[a-zA-Z0-9_]+"
                                   oninput="validateUsername(this)">
                            <div class="form-hint">Только латинские буквы, цифры и _</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" id="email" name="email" 
                                   placeholder="your@email.com" 
                                   required
                                   onblur="validateEmail(this)">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Пароль
                            </label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" 
                                       placeholder="Минимум 6 символов" 
                                       required 
                                       minlength="6"
                                       oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar"></div>
                                <span class="strength-text">Надежность пароля</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Подтверждение
                            </label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="Повторите пароль" 
                                       required 
                                       minlength="6"
                                       oninput="checkPasswordMatch()">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match" id="passwordMatch"></div>
                        </div>
                    </div>
                    
                   <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            <span class="checkbox-text">Я согласен с <a href="#" class="terms-link">правилами</a> и <a href="#" class="terms-link">политикой конфиденциальности</a></span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large btn-block register-submit">
                        <i class="fas fa-rocket"></i> Начать путешествие
                    </button>
                    
                    <div class="register-divider">
                        <span>или</span>
                    </div>
                    
                    <div class="alternative-login">
                        <p>Уже есть аккаунт?</p>
                        <a href="api/login.php" class="btn btn-outline btn-large">
                            <i class="fas fa-sign-in-alt"></i> Войти в систему
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="register-benefits">
                <div class="benefits-header">
                    <h3>Преимущества аккаунта</h3>
                    <p>Что вы получите после регистрации</p>
                </div>
                
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Доступ к лаунчеру</h4>
                            <p>Эксклюзивный лаунчер с автоматическими обновлениями</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Сохранение прогресса</h4>
                            <p>Ваши достижения и инвентарь хранятся на сервере</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Участие в ивентах</h4>
                            <p>Доступ к эксклюзивным событиям и соревнованиям</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Закрытый Discord</h4>
                            <p>Приватный канал с администрацией и другими игроками</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Приоритетная поддержка</h4>
                            <p>Быстрая помощь от администрации проекта</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Бонусы за регистрацию</h4>
                            <p>Стартовый набор для новых игроков</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    <?php else: ?>
<!-- Блок приветствия для авторизированных пользователей -->
<section id="welcome" class="welcome">
    <div class="container">
        <div class="welcome-content">
            <div class="welcome-header">
                <div class="welcome-badge">
                    <i class="fas fa-crown"></i>
                </div>
                <h2 class="welcome-title">
                    С возвращением, <span class="highlight"><?php echo $username; ?></span>!
                </h2>
                <p class="welcome-subtitle">Рады видеть вас снова в мире DeepWorld</p>
            </div>
            
            <div class="welcome-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Время приключений</h3>
                        <div class="stat-value">Готов к игре!</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Ваш прогресс</h3>
                        <div class="stat-value">Исследователь</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>В проекте с</h3>
                        <div class="stat-value">
                            <?php 
                                echo isset($_SESSION['created_at']) 
                                    ? date('d.m.Y', strtotime($_SESSION['created_at'])) 
                                    : date('d.m.Y'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="welcome-actions">
                <div class="action-card primary">
                    <div class="action-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="action-content">
                        <h3>Продолжить игру</h3>
                        <p>Запустите лаунчер и погрузитесь в приключения</p>
                        <a href="pages/launcher.html" class="btn btn-primary">
                            <i class="fas fa-gamepad"></i> Запустить игру
                        </a>
                    </div>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="action-content">
                        <h3>Управление аккаунтом</h3>
                        <p>Настройте профиль и просмотрите статистику</p>
                        <a href="pages/profile.php" class="btn btn-outline">
                            <i class="fas fa-chart-line"></i> Перейти в профиль
                        </a>
                    </div>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="action-content">
                        <h3>Сообщество</h3>
                        <p>Общайтесь с другими игроками и делитесь достижениями</p>
                        <button class="btn btn-outline" onclick="joinDiscord()">
                            <i class="fab fa-discord"></i> Присоединиться
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="quick-tips">
                <div class="tips-header">
                    <h3><i class="fas fa-lightbulb"></i> Полезные советы</h3>
                </div>
                <div class="tips-grid">
                    <div class="tip-item">
                        <div class="tip-number">1</div>
                        <p>Скачайте лаунчер для доступа ко всем серверам</p>
                    </div>
                    <div class="tip-item">
                        <div class="tip-number">2</div>
                        <p>Настройте графику для лучшей производительности</p>
                    </div>
                    <div class="tip-item">
                        <div class="tip-number">3</div>
                        <p>Присоединяйтесь к Discord для получения помощи</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

    <!-- Сообщество -->
    <section id="community" class="community">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Присоединяйтесь к <span class="highlight">сообществу</span></h2>
                <p class="section-subtitle">Общайтесь, делитесь находками и находите друзей для приключений</p>
            </div>
            <div class="community-grid">
                <a href="#" class="community-card discord" onclick="joinDiscord()">
                    <div class="card-icon">
                        <i class="fab fa-discord"></i>
                    </div>
                    <h3>Discord</h3>
                    <p>Основная площадка для общения, помощи и анонсов</p>
                    <span class="card-link">Присоединиться →</span>
                </a>
                <a href="#" class="community-card vk" onclick="openVK()">
                    <div class="card-icon">
                        <i class="fab fa-vk"></i>
                    </div>
                    <h3>ВКонтакте</h3>
                    <p>Новости проекта, анонсы и общение с сообществом</p>
                    <span class="card-link">Подписаться →</span>
                </a>
                <a href="#" class="community-card youtube" onclick="openYouTube()">
                    <div class="card-icon">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <h3>YouTube</h3>
                    <p>Гайды, обзоры, стримы и записи ивентов</p>
                    <span class="card-link">Смотреть →</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="logo">
                        <span class="logo-icon">⚔️</span>
                        <div class="logo-text">
                            <h3>DeepWorld</h3>
                            <p>Мир приключений в духе AdventureTime!</p>
                        </div>
                    </div>
                    <p class="footer-description">Проект создан с любовью к миру Minecraft и AdventureTime!</p>
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
                            <li><a href="index.php">Главная</a></li>
                            <li><a href="pages/about.html">О нас</a></li>
                            <li><a href="pages/servers.html">Описание серверов</a></li>
                            <li><a href="pages/donate.html">Донат</a></li>
                            <li><a href="pages/team.html">Наша команда</a></li>
                        </ul>
                    </div>
                    <div class="links-column">
                        <h4>Ресурсы</h4>
                        <ul>
                            <li><a href="pages/launcher.php">Скачать лаунчер</a></li>
                            <li><a href="#">Список модов</a></li>
                            <li><a href="#">Карта мира</a></li>
                            <li><a href="#">Гайды</a></li>
                            <li><a href="#">Частые вопросы</a></li>
                        </ul>
                    </div>
                    <div class="links-column">
                        <h4>Проект</h4>
                        <ul>
                            <li><a href="pages/about.html#idea">Идея проекта</a></li>
                            <li><a href="pages/team.html">Наша команда</a></li>
                            <li><a href="pages/donate.html">Поддержать проект</a></li>
                            <li><a href="#">Правила сервера</a></li>
                            <li><a href="#">Контакты</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">DeepWorld является неофициальным проектом, не связанным с Mojang AB или Cartoon Network.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Дополнительные скрипты для главной страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Анимация счётчиков
            initCounters();
            
            // Анимация карточек
            initFeatureCards();
            
            // Инициализация мобильного меню
            initMobileMenu();
            
            // Обработка формы регистрации
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault();
                        showNotification('Пароли не совпадают!', 'error');
                        return;
                    }
                    
                    if (password.value.length < 6) {
                        e.preventDefault();
                        showNotification('Пароль должен содержать минимум 6 символов!', 'error');
                        return;
                    }
                });
            }
        });

        function initCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = parseInt(counter.getAttribute('data-target'));
                    const count = parseInt(counter.textContent);
                    const increment = Math.ceil(target / 100);
                    
                    if (count < target) {
                        counter.textContent = count + increment;
                        setTimeout(updateCount, 30);
                    } else {
                        const suffix = counter.nextElementSibling?.textContent.includes('/7') ? '/7' : '';
                        counter.textContent = target + suffix;
                    }
                };
                
                // Запускаем анимацию при попадании в область видимости
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCount();
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(counter);
            });
        }

        function initFeatureCards() {
            const cards = document.querySelectorAll('.feature-card');
            
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
            }, {
                threshold: 0.1
            });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        }
        
        function initMobileMenu() {
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
        }

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
        function validateUsername(input) {
            const value = input.value;
            const regex = /^[a-zA-Z0-9_]+$/;
            
            if (!regex.test(value)) {
                input.style.borderColor = 'var(--danger-color)';
                input.style.boxShadow = '0 0 0 4px rgba(244, 67, 54, 0.1)';
            } else {
                input.style.borderColor = '';
                input.style.boxShadow = '';
            }
        }

        function validateEmail(input) {
            const value = input.value;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!regex.test(value)) {
                input.style.borderColor = 'var(--danger-color)';
                input.style.boxShadow = '0 0 0 4px rgba(244, 67, 54, 0.1)';
            } else {
                input.style.borderColor = '';
                input.style.boxShadow = '';
            }
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.querySelector('.strength-bar');
            const strengthText = document.querySelector('.strength-text');
            
            if (!strengthBar || !strengthText) return;
            
            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
            const messages = ['Очень слабый', 'Слабый', 'Средний', 'Сильный', 'Очень сильный'];
            
            const width = (score / 5) * 100;
            strengthBar.style.setProperty('--strength-width', width + '%');
            strengthBar.style.background = `linear-gradient(90deg, ${colors[score-1] || '#e74c3c'} 0%, ${colors[score-1] || '#e74c3c'} var(--strength-width), rgba(255, 255, 255, 0.1) var(--strength-width))`;
            strengthText.textContent = messages[score-1] || 'Очень слабый';
            strengthText.style.color = colors[score-1] || '#e74c3c';
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const matchElement = document.getElementById('passwordMatch');
            
            if (!password || !confirmPassword || !matchElement) return;
            
            if (confirmPassword.value === '') {
                matchElement.textContent = '';
                confirmPassword.style.borderColor = '';
                return;
            }
            
            if (password.value === confirmPassword.value) {
                matchElement.textContent = '✓ Пароли совпадают';
                matchElement.style.color = '#2ecc71';
                confirmPassword.style.borderColor = '#2ecc71';
                confirmPassword.style.boxShadow = '0 0 0 4px rgba(46, 204, 113, 0.1)';
            } else {
                matchElement.textContent = '✗ Пароли не совпадают';
                matchElement.style.color = '#e74c3c';
                confirmPassword.style.borderColor = '#e74c3c';
                confirmPassword.style.boxShadow = '0 0 0 4px rgba(231, 76, 60, 0.1)';
            }
        }

        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const button = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                button.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
    </script>
</body>
</html>