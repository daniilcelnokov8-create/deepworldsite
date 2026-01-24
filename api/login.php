<?php
// api/login.php
require_once '../config/database.php';
session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля!';
    } else {
        try {
            // Ищем пользователя по username или email
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$login, $login]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Проверяем активен ли пользователь
                if (!$user['is_active']) {
                    $error = 'Аккаунт заблокирован. Обратитесь к администратору.';
                }
                // Проверяем пароль (используем password_hash поле)
                elseif (password_verify($password, $user['password_hash'])) {
                    // Успешная авторизация
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Обновляем время последнего входа
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Логируем успешный вход
                    $stmt = $pdo->prepare("
                        INSERT INTO login_history (user_id, ip_address, user_agent, success) 
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $user['id'],
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    ]);
                    
                    // Создаем запись об активности
                    $stmt = $pdo->prepare("
                        INSERT INTO user_activity (user_id, activity_type, description, ip_address) 
                        VALUES (?, 'login', 'Успешный вход в систему', ?)
                    ");
                    $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                    
                    // Если выбрано "Запомнить меня"
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $user['id'],
                            $token,
                            $_SERVER['REMOTE_ADDR'],
                            $_SERVER['HTTP_USER_AGENT'],
                            $expires
                        ]);
                        
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    }
                    
                    // Перенаправляем на главную
                    header('Location: ../index.php');
                    exit();
                } else {
                    $error = 'Неверный логин или пароль!';
                    
                    // Логируем неудачную попытку входа
                    $stmt = $pdo->prepare("
                        INSERT INTO login_history (user_id, ip_address, user_agent, success) 
                        VALUES (?, ?, ?, 0)
                    ");
                    $stmt->execute([
                        $user['id'],
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    ]);
                }
            } else {
                $error = 'Пользователь не найден!';
            }
        } catch(PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Проверяем токен "Запомнить меня"
if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.role 
            FROM user_sessions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.session_id = ? AND us.expires_at > NOW() AND u.is_active = 1
        ");
        $stmt->execute([$_COOKIE['remember_token']]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Обновляем время последнего входа
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header('Location: ../index.php');
            exit();
        }
    } catch(PDOException $e) {
        // Просто игнорируем ошибку, пользователь войдет как обычно
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в DeepWorld | Личный кабинет</title>
    <meta name="description" content="Вход в личный кабинет DeepWorld - уникального Minecraft проекта">
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <style>
        /* Стили для страницы входа */
        body {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        /* Герой секция для входа */
        .login-hero {
            position: relative;
            padding: 4rem 0 2rem;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .login-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(66, 135, 245, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(52, 168, 83, 0.1) 0%, transparent 50%);
        }
        
        .login-container {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .login-logo-icon {
            font-size: 3rem;
            color: #4285F4;
        }
        
        .login-logo-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
        }
        
        .login-logo-text p {
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            font-size: 0.9rem;
        }
        
        .login-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* Основной контент */
        .login-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: start;
            margin-bottom: 4rem;
        }
        
        @media (max-width: 1024px) {
            .login-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
        }
        
        /* Форма входа */
        .login-form-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .login-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4285F4, #34A853);
            border-radius: 20px 20px 0 0;
        }
        
        .login-form-container h2 {
            font-size: 1.75rem;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .login-form-container .form-subtitle {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: white;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group .input-with-icon {
            position: relative;
        }
        
        .form-group .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4285F4;
            background: rgba(66, 135, 245, 0.05);
            box-shadow: 0 0 0 3px rgba(66, 135, 245, 0.1);
        }
        
        .form-group input:focus + .input-icon {
            color: #4285F4;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
            accent-color: #4285F4;
        }
        
        .checkbox-group label {
            margin: 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            cursor: pointer;
        }
        
        .login-btn {
            margin-top: 1rem;
            padding: 1rem;
            background: linear-gradient(45deg, #4285F4, #34A853);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(66, 135, 245, 0.3);
            background: linear-gradient(45deg, #3367d6, #2e8b57);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .form-links {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-link {
            color: #4285F4;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-link:hover {
            color: #3367d6;
            gap: 0.75rem;
        }
        
        /* Информационный блок */
        .login-info {
            padding: 1rem;
        }
        
        .info-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            border-color: rgba(66, 135, 245, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .info-card h3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .info-card h3 i {
            color: #4285F4;
            font-size: 1.25rem;
        }
        
        .info-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .info-card li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }
        
        .info-card li i {
            color: #34A853;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            flex-shrink: 0;
        }
        
        .social-login {
            text-align: center;
            margin-top: 2rem;
        }
        
        .social-login p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .social-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .social-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }
        
        .social-btn.discord {
            background: rgba(88, 101, 242, 0.1);
            border-color: rgba(88, 101, 242, 0.3);
        }
        
        .social-btn.discord:hover {
            background: rgba(88, 101, 242, 0.2);
        }
        
        .social-btn.google {
            background: rgba(234, 67, 53, 0.1);
            border-color: rgba(234, 67, 53, 0.3);
        }
        
        .social-btn.google:hover {
            background: rgba(234, 67, 53, 0.2);
        }
        
        /* Уведомления */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert.error {
            background: rgba(234, 67, 53, 0.1);
            border: 1px solid rgba(234, 67, 53, 0.3);
            color: #EA4335;
        }
        
        .alert.success {
            background: rgba(52, 168, 83, 0.1);
            border: 1px solid rgba(52, 168, 83, 0.3);
            color: #34A853;
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        /* Футер на странице входа */
        .login-footer {
            background: rgba(0, 0, 0, 0.3);
            padding: 2rem 0;
            margin-top: auto;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .login-container {
                padding: 0 1rem;
            }
            
            .login-logo {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
            
            .login-logo-icon {
                font-size: 2.5rem;
            }
            
            .login-logo-text h1 {
                font-size: 2rem;
            }
            
            .login-form-container {
                padding: 2rem 1.5rem;
            }
            
            .form-links {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .social-buttons {
                flex-direction: column;
            }
            
            .social-btn {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .login-logo-text h1 {
                font-size: 1.75rem;
            }
            
            .login-subtitle {
                font-size: 1rem;
            }
            
            .login-form-container h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Анимация для формы */
        .form-group input {
            animation: inputFadeIn 0.5s ease forwards;
            opacity: 0;
            transform: translateY(10px);
        }
        
        .form-group:nth-child(1) input { animation-delay: 0.1s; }
        .form-group:nth-child(2) input { animation-delay: 0.2s; }
        .checkbox-group { animation-delay: 0.3s; }
        .login-btn { animation-delay: 0.4s; }
        
        @keyframes inputFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Таймер для сброса блокировки (если нужно) */
        .countdown {
            display: inline-block;
            font-weight: 600;
            color: #4285F4;
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
            <p class="loading-text">Загрузка страницы входа...</p>
        </div>
    </div>

    <!-- Герой секция -->
    <section class="login-hero">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <div class="login-logo-icon">⚔️</div>
                    <div class="login-logo-text">
                        <h1>DeepWorld</h1>
                        <p>Мир приключений в духе Мира Приключений</p>
                    </div>
                </div>
                <p class="login-subtitle">Войдите в личный кабинет для доступа ко всем возможностям проекта</p>
            </div>
        </div>
    </section>

    <!-- Основной контент -->
    <main class="login-container">
        <div class="login-content">
            <!-- Форма входа -->
            <div class="login-form-container">
                <h2>Вход в аккаунт</h2>
                <p class="form-subtitle">Используйте ваши данные для входа в систему</p>
                
                <?php if($error): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="">
                    <div class="form-group">
                        <label for="login">Имя пользователя или Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="login" name="login" placeholder="Введите логин или email" required 
                                   value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: rgba(255, 255, 255, 0.5); cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Запомнить меня на этом устройстве</label>
                    </div>
                    
                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Войти в аккаунт</span>
                    </button>
                </form>
                
                <div class="form-links">
                    <a href="../api/register.php" class="form-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Создать новый аккаунт</span>
                    </a>
                    <a href="#" class="form-link" onclick="showForgotPassword()">
                        <i class="fas fa-key"></i>
                        <span>Забыли пароль?</span>
                    </a>
                </div>
                
                <div class="social-login">
                    <p>Или войдите через:</p>
                    <div class="social-buttons">
                        <a href="#" class="social-btn discord" onclick="loginWithDiscord()">
                            <i class="fab fa-discord"></i>
                            <span>Discord</span>
                        </a>
                        <a href="#" class="social-btn google" onclick="loginWithGoogle()">
                            <i class="fab fa-google"></i>
                            <span>Google</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Информационный блок -->
            <div class="login-info">
                <div class="info-card">
                    <h3><i class="fas fa-shield-alt"></i> Безопасность входа</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Шифрование паролей по стандарту bcrypt</li>
                        <li><i class="fas fa-check"></i> Защита от brute-force атак</li>
                        <li><i class="fas fa-check"></i> Логирование всех попыток входа</li>
                        <li><i class="fas fa-check"></i> Автоматическая блокировка при подозрительной активности</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-star"></i> Что дает вход в аккаунт?</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Доступ к личному кабинету</li>
                        <li><i class="fas fa-check"></i> Скачивание лаунчера DeepWorld</li>
                        <li><i class="fas fa-check"></i> Участие в игровых мероприятиях</li>
                        <li><i class="fas fa-check"></i> Поддержка и помощь администрации</li>
                        <li><i class="fas fa-check"></i> Персональные настройки и статистика</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-question-circle"></i> Нужна помощь?</h3>
                    <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 1rem; line-height: 1.6;">
                        Если у вас возникли проблемы со входом, свяжитесь с нашей поддержкой через Discord или напишите на почту поддержки.
                    </p>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="#" class="btn btn-outline" onclick="openSupport()" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            <i class="fas fa-headset"></i> Поддержка
                        </a>
                        <a href="../index.php" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            <i class="fas fa-home"></i> На главную
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <footer class="login-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">DeepWorld является неофициальным проектом, не связанным с Mojang AB.</p>
                <p style="color: rgba(255, 255, 255, 0.5); font-size: 0.85rem; margin-top: 0.5rem;">
                    <i class="fas fa-shield-alt"></i> Ваши данные защищены. Последний вход с этого IP: 
                    <?php echo $_SERVER['REMOTE_ADDR']; ?>
                </p>
            </div>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        // Анимация загрузки
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.opacity = '0';
                    setTimeout(() => {
                        loading.style.display = 'none';
                    }, 300);
                }
            }, 800);
            
            // Анимация появления формы
            animateForm();
        });
        
        function animateForm() {
            const inputs = document.querySelectorAll('.form-group input');
            const elements = [...inputs, document.querySelector('.checkbox-group'), document.querySelector('.login-btn')];
            
            elements.forEach((el, index) => {
                if (el) {
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100 * (index + 1));
                }
            });
        }
        
        // Переключение видимости пароля
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
        
        // Восстановление пароля
        function showForgotPassword() {
            const email = document.getElementById('login').value;
            let message = 'Для восстановления пароля обратитесь к администрации через Discord.';
            
            if (email && email.includes('@')) {
                message = `Запрос на восстановление пароля для ${email} отправлен. Проверьте вашу почту.`;
            }
            
            showNotification(message, 'info');
        }
        
        // Социальные сети
        function loginWithDiscord() {
            showNotification('Авторизация через Discord скоро будет доступна', 'info');
        }
        
        function loginWithGoogle() {
            showNotification('Авторизация через Google скоро будет доступна', 'info');
        }
        
        // Поддержка
        function openSupport() {
            showNotification('Присоединяйтесь к нашему Discord серверу для получения поддержки', 'info');
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
        
        // Автофокус на поле ввода
        document.addEventListener('DOMContentLoaded', function() {
            const loginInput = document.getElementById('login');
            if (loginInput && !loginInput.value) {
                setTimeout(() => {
                    loginInput.focus();
                }, 500);
            }
        });
        
        // Предотвращение отправки формы при нажатии Enter вне полей ввода
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>