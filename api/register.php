<?php
// Включим отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Стартуем сессию
session_start();

// Подключаем конфигурацию базы данных
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']) ? 1 : 0;
    $rules = isset($_POST['rules']) ? 1 : 0;
    
    // Простая валидация
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Имя пользователя обязательно';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Имя пользователя должно быть не менее 3 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Имя пользователя может содержать только латинские буквы, цифры и _';
    }
    
    if (empty($email)) {
        $errors[] = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email';
    }
    
    if (empty($password)) {
        $errors[] = 'Пароль обязателен';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }
    
    if (!$terms) {
        $errors[] = 'Необходимо принять условия использования';
    }
    
    if (!$rules) {
        $errors[] = 'Необходимо ознакомиться с правилами проекта';
    }
    
    // Если есть ошибки валидации
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        try {
            // Проверяем, не существует ли уже пользователь
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Пользователь с таким именем или email уже существует';
            } else {
                // Хешируем пароль
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Вставляем пользователя в базу
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
                
                if ($stmt->execute([$username, $email, $password_hash])) {
                    // Успешная регистрация
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    
                    $success = 'Регистрация успешна! Перенаправление на главную...';
                    
                    // Перенаправляем через 2 секунды
                    header("refresh:2;url=/index.php");
                } else {
                    $error = 'Ошибка при сохранении пользователя';
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - DeepWorld</title>
    <link rel="stylesheet" href="/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Специальные стили для страницы регистрации */
        .register-wrapper {
            padding: 4rem 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: calc(100vh - 200px);
        }
        
        .register-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .register-title {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            font-family: 'Space Grotesk', sans-serif;
        }
        
        .register-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .register-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        
        .register-content {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 0;
            min-height: 600px;
        }
        
        .register-form-section {
            padding: 3rem;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .register-info-section {
            padding: 3rem;
            background: linear-gradient(135deg, rgba(66, 135, 245, 0.05), rgba(52, 168, 83, 0.05));
        }
        
        .section-title {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .section-title i {
            color: #4285F4;
            font-size: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            color: white;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .form-input-wrapper {
            position: relative;
            width: 100%;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.07);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4285F4;
            background: rgba(66, 135, 245, 0.1);
            box-shadow: 0 0 0 4px rgba(66, 135, 245, 0.15);
        }
        
        .form-input.error {
            border-color: #EA4335;
            background: rgba(234, 67, 53, 0.1);
        }
        
        .form-input.success {
            border-color: #34A853;
            background: rgba(52, 168, 83, 0.1);
        }
        
        .toggle-password-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .toggle-password-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .form-feedback {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            min-height: 1.2rem;
        }
        
        .form-feedback.error {
            color: #EA4335;
        }
        
        .form-feedback.success {
            color: #34A853;
        }
        
        .password-strength {
            margin-top: 0.75rem;
        }
        
        .strength-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .strength-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease, background-color 0.5s ease;
        }
        
        .strength-text {
            font-size: 0.9rem;
            text-align: right;
            font-weight: 600;
        }
        
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .checkbox-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .checkbox-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 0.25rem;
            accent-color: #4285F4;
            cursor: pointer;
        }
        
        .checkbox-label {
            flex: 1;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            line-height: 1.5;
            cursor: pointer;
        }
        
        .checkbox-label a {
            color: #4285F4;
            text-decoration: none;
            font-weight: 600;
        }
        
        .checkbox-label a:hover {
            text-decoration: underline;
        }
        
        .register-button {
            width: 100%;
            padding: 1.25rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 12px;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(66, 135, 245, 0.3);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider-text {
            padding: 0 1rem;
            font-size: 0.9rem;
        }
        
        .social-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .social-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .social-button:hover {
            transform: translateY(-3px);
            border-color: transparent;
        }
        
        .social-button.discord:hover {
            background: #5865F2;
        }
        
        .social-button.vk:hover {
            background: #4C75A3;
        }
        
        .social-button.google:hover {
            background: #4285F4;
        }
        
        .social-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }
        
        .social-text {
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .benefit-item:last-child {
            border-bottom: none;
        }
        
        .benefit-icon {
            width: 40px;
            height: 40px;
            background: rgba(66, 135, 245, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4285F4;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .benefit-content h4 {
            color: white;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .benefit-content p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .login-link a {
            color: #4285F4;
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Адаптивность */
        @media (max-width: 1200px) {
            .register-content {
                grid-template-columns: 1fr;
            }
            
            .register-form-section {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
        }
        
        @media (max-width: 768px) {
            .register-container {
                padding: 0 1rem;
            }
            
            .register-title {
                font-size: 2.2rem;
            }
            
            .register-subtitle {
                font-size: 1.1rem;
            }
            
            .register-form-section,
            .register-info-section {
                padding: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .social-buttons {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .register-wrapper {
                padding: 2rem 0;
            }
            
            .register-title {
                font-size: 1.8rem;
            }
            
            .register-form-section,
            .register-info-section {
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
            <p class="loading-text">Загрузка формы регистрации...</p>
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
                    <li><a href="/index.php">Главная</a></li>
                    <li><a href="/pages/about.html">О нас</a></li>
                    <li><a href="/pages/servers.html">Описание серверов</a></li>
                    <li><a href="/pages/donate.html">Донат</a></li>
                    <li><a href="/pages/team.html">Наша команда</a></li>
                    <li><a href="/pages/launcher.html" class="btn btn-outline">Скачать лаунчер</a></li>
                    <li><a href="/api/login.php">Вход</a></li>
                    <li><a href="/api/register.php" class="active">Регистрация</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Основной контент -->
    <div class="register-wrapper">
        <div class="register-container">
            <div class="register-header">
                <h1 class="register-title">Присоединяйтесь к DeepWorld</h1>
                <p class="register-subtitle">Создайте аккаунт и начните своё эпическое приключение в уникальном мире Minecraft</p>
            </div>
            
            <div class="register-card">
                <div class="register-content">
                    <!-- Левая часть: Форма регистрации -->
                    <div class="register-form-section">
                        <?php if($error): ?>
                            <div class="alert alert-error" style="background: rgba(234, 67, 53, 0.1); border: 1px solid rgba(234, 67, 53, 0.3); color: #ff6b6b; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success" style="background: rgba(52, 168, 83, 0.1); border: 1px solid rgba(52, 168, 83, 0.3); color: #51cf66; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
                                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                <h3 style="margin-bottom: 0.5rem; color: white;">Регистрация успешна!</h3>
                                <p><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(empty($success)): ?>
                        <div class="section-title">
                            <i class="fas fa-user-plus"></i>
                            <span>Создание аккаунта</span>
                        </div>
                        
                        <form method="POST" action="" id="registerForm">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="username" class="form-label">Имя пользователя *</label>
                                    <div class="form-input-wrapper">
                                        <input type="text" id="username" name="username" 
                                               class="form-input" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                               required
                                               oninput="validateUsername()"
                                               placeholder="Придумайте уникальный никнейм">
                                    </div>
                                    <div id="usernameFeedback" class="form-feedback"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Email адрес *</label>
                                    <div class="form-input-wrapper">
                                        <input type="email" id="email" name="email" 
                                               class="form-input"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                               required
                                               placeholder="your@email.com">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Пароль *</label>
                                    <div class="form-input-wrapper">
                                        <input type="password" id="password" name="password" 
                                               class="form-input"
                                               required
                                               oninput="updatePasswordStrength()"
                                               placeholder="Минимум 6 символов">
                                        <button type="button" class="toggle-password-btn" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength">
                                        <div class="strength-bar">
                                            <div id="passwordStrengthBar" class="strength-fill"></div>
                                        </div>
                                        <div id="passwordStrengthText" class="strength-text"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Подтвердите пароль *</label>
                                    <div class="form-input-wrapper">
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="form-input"
                                               required
                                               oninput="validatePasswordMatch()"
                                               placeholder="Повторите пароль">
                                        <button type="button" class="toggle-password-btn" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatchFeedback" class="form-feedback"></div>
                                </div>
                            </div>
                            
                               <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <label for="terms" class="checkbox-label">
                                        Я&nbsp;принимаю&nbsp;<a href="/pages/terms.html">условия&nbsp;использования</a>&nbsp;и&nbsp;согласен&nbsp;с&nbsp;
                                        <a href="/pages/privacy.html">политикой&nbsp;конфиденциальности</a>
                                    </label>
                                </div>
                                
                                <div class="checkbox-item">
                                    <input type="checkbox" id="rules" name="rules" required>
                                    <label for="rules" class="checkbox-label">
                                        Я&nbsp;ознакомлен(а)&nbsp;с&nbsp;<a href="/pages/rules.html">правилами&nbsp;проекта</a>&nbsp;и&nbsp;обязуюсь&nbsp;их&nbsp;соблюдать
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary register-button">
                                <i class="fas fa-user-plus"></i> Зарегистрироваться
                            </button>
                        </form>
                        
                        <div class="divider">
                            <span class="divider-text">или зарегистрируйтесь через</span>
                        </div>
                        
                        <div class="social-buttons">
                            <button type="button" class="social-button discord" onclick="authWithDiscord()">
                                <i class="fab fa-discord social-icon"></i>
                                <span class="social-text">Discord</span>
                            </button>
                            
                            <button type="button" class="social-button vk" onclick="authWithVK()">
                                <i class="fab fa-vk social-icon"></i>
                                <span class="social-text">ВКонтакте</span>
                            </button>
                            
                            <button type="button" class="social-button google" onclick="authWithGoogle()">
                                <i class="fab fa-google social-icon"></i>
                                <span class="social-text">Google</span>
                            </button>
                        </div>
                        
                        <div class="login-link">
                            Уже есть аккаунт? <a href="/api/login.php">Войти в систему</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Правая часть: Информация и преимущества -->
                    <div class="register-info-section">
                        <div class="section-title">
                            <i class="fas fa-gift"></i>
                            <span>Преимущества аккаунта</span>
                        </div>
                        
                        <ul class="benefits-list">
                            <li class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Полный доступ к лаунчеру</h4>
                                    <p>Автоматическая установка всех модов, обновлений и ресурс-паков</p>
                                </div>
                            </li>
                            
                            <li class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-save"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Сохранение прогресса</h4>
                                    <p>Ваши достижения, инвентарь и постройки синхронизируются между устройствами</p>
                                </div>
                            </li>
                            
                            <li class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Участие в ивентах</h4>
                                    <p>Доступ к уникальным событиям, конкурсам с ценными призами</p>
                                </div>
                            </li>
                            
                            <li class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fab fa-discord"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Закрытый Discord</h4>
                                    <p>Доступ к приватным каналам, общение с администрацией и разработчиками</p>
                                </div>
                            </li>
                            
                            <li class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Приоритетная поддержка</h4>
                                    <p>Быстрое решение технических вопросов и помощь от команды проекта</p>
                                </div>
                            </li>
                            
                            <li class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Эксклюзивный контент</h4>
                                    <p>Ранний доступ к новым функциям, бета-тестирование обновлений</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                <div class="footer-links">
                    <div class="links-column">
                        <h4>Навигация</h4>
                        <ul>
                            <li><a href="/index.php">Главная</a></li>
                            <li><a href="/pages/about.html">О нас</a></li>
                            <li><a href="/pages/servers.html">Описание серверов</a></li>
                            <li><a href="/pages/donate.html">Донат</a></li>
                            <li><a href="/pages/team.html">Наша команда</a></li>
                        </ul>
                    </div>
                    <div class="links-column">
                        <h4>Аккаунт</h4>
                        <ul>
                            <li><a href="/api/login.php">Вход</a></li>
                            <li><a href="/api/register.php">Регистрация</a></li>
                            <li><a href="#">Восстановить пароль</a></li>
                            <li><a href="/pages/rules.html">Правила проекта</a></li>
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

    <script src="/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Скрываем загрузку
            setTimeout(() => {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.opacity = '0';
                    setTimeout(() => {
                        loading.style.display = 'none';
                    }, 300);
                }
            }, 500);
            
            // Инициализация мобильного меню
            initMobileMenu();
            
            // Инициализация валидации
            validateUsername();
            updatePasswordStrength();
            validatePasswordMatch();
        });
        
        // Валидация имени пользователя
        function validateUsername() {
            const username = document.getElementById('username');
            const feedback = document.getElementById('usernameFeedback');
            
            if (username.value.length === 0) {
                feedback.textContent = '';
                username.classList.remove('error', 'success');
                return;
            }
            
            if (username.value.length < 3) {
                feedback.textContent = 'Имя должно быть не менее 3 символов';
                feedback.className = 'form-feedback error';
                username.classList.remove('success');
                username.classList.add('error');
                return false;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username.value)) {
                feedback.textContent = 'Только латинские буквы, цифры и символ _';
                feedback.className = 'form-feedback error';
                username.classList.remove('success');
                username.classList.add('error');
                return false;
            }
            
            feedback.textContent = '✓ Имя пользователя корректно';
            feedback.className = 'form-feedback success';
            username.classList.remove('error');
            username.classList.add('success');
            return true;
        }
        
        // Обновление сложности пароля
        function updatePasswordStrength() {
            const password = document.getElementById('password');
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            if (password.value.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
                return;
            }
            
            let strength = 0;
            const messages = ['Очень слабый', 'Слабый', 'Средний', 'Сильный', 'Очень сильный'];
            const colors = ['#EA4335', '#FBBC05', '#FF6D00', '#34A853', '#0B8043'];
            
            if (password.value.length >= 6) strength++;
            if (password.value.length >= 8) strength++;
            if (/[A-Z]/.test(password.value)) strength++;
            if (/[0-9]/.test(password.value)) strength++;
            if (/[^A-Za-z0-9]/.test(password.value)) strength++;
            
            const percentage = (strength / 5) * 100;
            
            strengthBar.style.width = percentage + '%';
            strengthBar.style.backgroundColor = colors[strength - 1] || '#EA4335';
            strengthText.textContent = messages[strength - 1] || '';
            strengthText.style.color = colors[strength - 1] || '#666';
        }
        
        // Проверка совпадения паролей
        function validatePasswordMatch() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            const feedback = document.getElementById('passwordMatchFeedback');
            
            if (confirm.value.length === 0) {
                feedback.textContent = '';
                confirm.classList.remove('error', 'success');
                return;
            }
            
            if (password.value === confirm.value) {
                feedback.textContent = '✓ Пароли совпадают';
                feedback.className = 'form-feedback success';
                confirm.classList.remove('error');
                confirm.classList.add('success');
                return true;
            } else {
                feedback.textContent = '✗ Пароли не совпадают';
                feedback.className = 'form-feedback error';
                confirm.classList.remove('success');
                confirm.classList.add('error');
                return false;
            }
        }
        
        // Переключение видимости пароля
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.parentElement.querySelector('.toggle-password-btn i');
            
            if (field.type === 'password') {
                field.type = 'text';
                button.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                button.className = 'fas fa-eye';
            }
        }
        
        // Социальная авторизация
        function authWithDiscord() {
            showNotification('Авторизация через Discord скоро будет доступна!', 'info');
        }
        
        function authWithVK() {
            showNotification('Авторизация через ВКонтакте скоро будет доступна!', 'info');
        }
        
        function authWithGoogle() {
            showNotification('Авторизация через Google скоро будет доступна!', 'info');
        }
        
        // Валидация формы перед отправкой
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const usernameValid = validateUsername();
            const passwordValid = validatePasswordMatch();
            const termsChecked = document.getElementById('terms').checked;
            const rulesChecked = document.getElementById('rules').checked;
            
            if (!usernameValid) {
                e.preventDefault();
                showNotification('Пожалуйста, исправьте имя пользователя', 'error');
                return;
            }
            
            if (!passwordValid) {
                e.preventDefault();
                showNotification('Пароли не совпадают', 'error');
                return;
            }
            
            if (!termsChecked) {
                e.preventDefault();
                showNotification('Необходимо принять условия использования', 'error');
                return;
            }
            
            if (!rulesChecked) {
                e.preventDefault();
                showNotification('Необходимо ознакомиться с правилами проекта', 'error');
                return;
            }
            
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                e.preventDefault();
                showNotification('Пароль должен содержать минимум 6 символов', 'error');
                return;
            }
        });
        
        // Функция инициализации мобильного меню
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
        
        // Функция уведомлений
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
    </script>
</body>
</html>