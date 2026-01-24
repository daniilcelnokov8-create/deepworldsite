<?php
// pages/account_settings.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_auth();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    // Валидация
    if (empty($username) || empty($email)) {
        $error = 'Имя пользователя и email обязательны';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } else {
        try {
            // Проверяем, не занят ли username или email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Имя пользователя или email уже заняты';
            } else {
                // Обновляем профиль
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$username, $email, $bio, $user_id]);
                
                // Обновляем сессию
                $_SESSION['username'] = $username;
                
                // Записываем активность
                $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity_type, description, ip_address) VALUES (?, 'update', 'Обновлен профиль', ?)");
                $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
                
                $message = 'Профиль успешно обновлен!';
            }
        } catch(PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Обработка загрузки аватара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $upload_dir = '../uploads/avatars/';
    
    // Создаем директорию если нет
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = $user_id . '_' . time() . '_' . basename($_FILES['avatar']['name']);
    $target_file = $upload_dir . $file_name;
    
    // Проверяем тип файла
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Проверяем, является ли файл изображением
    $check = getimagesize($_FILES['avatar']['tmp_name']);
    if ($check === false) {
        $error = 'Файл не является изображением';
    } elseif (!in_array($imageFileType, $allowed_types)) {
        $error = 'Разрешены только JPG, JPEG, PNG и GIF файлы';
    } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        $error = 'Файл слишком большой. Максимум 2MB';
    } else {
        // Удаляем старый аватар если есть
        if (!empty($user['avatar']) && file_exists('..' . $user['avatar'])) {
            unlink('..' . $user['avatar']);
        }
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
            // Обновляем путь к аватару в БД
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute(['/uploads/avatars/' . $file_name, $user_id]);
            
            // Обновляем данные пользователя в переменной
            $user['avatar'] = '/uploads/avatars/' . $file_name;
            
            // Записываем активность
            $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity_type, description, ip_address) VALUES (?, 'update', 'Обновлен аватар', ?)");
            $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
            
            $message = 'Аватар успешно загружен!';
        } else {
            $error = 'Ошибка при загрузке файла. Проверьте права на папку uploads/';
        }
        // Отладка
    if (isset($_FILES['avatar'])) {
        error_log("=== ОТЛАДКА ЗАГРУЗКИ АВАТАРА ===");
        error_log("Загрузка файла: " . print_r($_FILES['avatar'], true));
        error_log("Директория: " . $upload_dir);
        error_log("Целевой файл: " . $target_file);
        if (file_exists($upload_dir)) {
            error_log("Права на директорию: " . substr(sprintf('%o', fileperms($upload_dir)), -4));
        } else {
            error_log("Директория НЕ существует!");
        }
        error_log("Код ошибки загрузки: " . $_FILES['avatar']['error']);
        error_log("=== КОНЕЦ ОТЛАДКИ ===");
    }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки аккаунта | DeepWorld</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        
        .settings-header {
            margin-bottom: 2rem;
        }
        
        .settings-header h1 {
            font-size: 2rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .settings-header p {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .settings-sidebar {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: fit-content;
        }
        
        .settings-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .settings-nav li {
            margin-bottom: 0.5rem;
        }
        
        .settings-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .settings-nav a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .settings-nav a.active {
            background: rgba(66, 135, 245, 0.1);
            color: #4285F4;
            border-left: 3px solid #4285F4;
        }
        
        .settings-content {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .settings-section {
            margin-bottom: 3rem;
        }
        
        .settings-section:last-child {
            margin-bottom: 0;
        }
        
        .settings-section h2 {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(66, 135, 245, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .settings-section h2 i {
            color: #4285F4;
        }
        
        .avatar-section {
            text-align: center;
        }
        
        .current-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(45deg, #4285F4, #34A853);
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: bold;
            color: white;
            border: 4px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        
        .current-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-form {
            max-width: 300px;
            margin: 0 auto;
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
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-text {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
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
        
        .btn-block {
            width: 100%;
            justify-content: center;
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
        
        .file-input {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            border-color: #4285F4;
            color: white;
        }
        
        .file-info {
            margin-top: 0.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            text-align: center;
        }
        
        .connected-accounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .account-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .account-card:hover {
            border-color: rgba(66, 135, 245, 0.3);
            transform: translateY(-3px);
        }
        
        .account-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .account-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .account-icon.discord {
            background: #5865F2;
        }
        
        .account-icon.vk {
            background: #4C75A3;
        }
        
        .account-icon.google {
            background: #4285F4;
        }
        
        .account-info h3 {
            color: white;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .account-status {
            font-size: 0.85rem;
        }
        
        .status-connected {
            color: #34A853;
        }
        
        .status-disconnected {
            color: #EA4335;
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
                    <li><a href="account_settings.php" class="active">Настройки</a></li>
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
    <main class="settings-container">
        <div class="container">
            <div class="settings-header">
                <h1><i class="fas fa-cog"></i> Настройки аккаунта</h1>
                <p>Управление настройками вашего профиля DeepWorld</p>
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

            <div class="settings-grid">
                <!-- Боковая панель -->
                <div class="settings-sidebar">
                    <ul class="settings-nav">
                        <li><a href="#profile" class="active">
                            <i class="fas fa-user"></i> Профиль
                        </a></li>
                        <li><a href="#security">
                            <i class="fas fa-shield-alt"></i> Безопасность
                        </a></li>
                        <li><a href="#notifications">
                            <i class="fas fa-bell"></i> Уведомления
                        </a></li>
                        <li><a href="#integrations">
                            <i class="fas fa-plug"></i> Интеграции
                        </a></li>
                        <li><a href="#privacy">
                            <i class="fas fa-lock"></i> Приватность
                        </a></li>
                        <li><a href="#advanced">
                            <i class="fas fa-sliders-h"></i> Дополнительно
                        </a></li>
                    </ul>
                </div>

                <!-- Основной контент -->
                <div class="settings-content">
                    <!-- Секция профиля -->
                    <div id="profile" class="settings-section">
                        <h2><i class="fas fa-user-circle"></i> Настройки профиля</h2>
                        
                        <!-- Аватар -->
                        <div class="avatar-section">
                            <div class="current-avatar" id="currentAvatar">
                                <?php if($user['avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Аватар">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data" class="avatar-form">
                                <div class="form-group">
                                    <div class="file-input">
                                        <input type="file" name="avatar" id="avatar" accept="image/*" onchange="previewAvatar(event)">
                                        <label for="avatar" class="file-input-label">
                                            <i class="fas fa-upload"></i>
                                            <span>Загрузить новый аватар</span>
                                        </label>
                                    </div>
                                    <div class="file-info">
                                        JPG, PNG или GIF. Максимум 2MB
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-block">
                                    <i class="fas fa-save"></i> Сохранить аватар
                                </button>
                            </form>
                        </div>
                        
                        <!-- Форма профиля -->
                        <form method="POST" action="" style="margin-top: 3rem;">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="form-group">
                                <label for="username">Имя пользователя</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <div class="form-text">
                                    Ваше отображаемое имя в системе. Только латинские буквы, цифры и символы _ - .
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email адрес</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <div class="form-text">
                                    На этот email будут приходить уведомления от системы.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">О себе</label>
                                <textarea id="bio" name="bio" class="form-control" 
                                          placeholder="Расскажите немного о себе..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    Максимум 500 символов. Эта информация будет видна другим пользователям.
                                </div>
                            </div>
                            
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                        </form>
                    </div>

                    <!-- Секция интеграций -->
                    <div id="integrations" class="settings-section">
                        <h2><i class="fas fa-plug"></i> Подключенные аккаунты</h2>
                        
                        <div class="connected-accounts">
                            <div class="account-card">
                                <div class="account-header">
                                    <div class="account-icon discord">
                                        <i class="fab fa-discord"></i>
                                    </div>
                                    <div class="account-info">
                                        <h3>Discord</h3>
                                        <div class="account-status status-disconnected">
                                            <i class="fas fa-times-circle"></i> Не подключено
                                        </div>
                                    </div>
                                </div>
                                <button class="btn" onclick="connectDiscord()" style="width: 100%;">
                                    <i class="fab fa-discord"></i> Подключить Discord
                                </button>
                            </div>
                            
                            <div class="account-card">
                                <div class="account-header">
                                    <div class="account-icon vk">
                                        <i class="fab fa-vk"></i>
                                    </div>
                                    <div class="account-info">
                                        <h3>ВКонтакте</h3>
                                        <div class="account-status status-disconnected">
                                            <i class="fas fa-times-circle"></i> Не подключено
                                        </div>
                                    </div>
                                </div>
                                <button class="btn" onclick="connectVK()" style="width: 100%;">
                                    <i class="fab fa-vk"></i> Подключить ВК
                                </button>
                            </div>
                            
                            <div class="account-card">
                                <div class="account-header">
                                    <div class="account-icon google">
                                        <i class="fab fa-google"></i>
                                    </div>
                                    <div class="account-info">
                                        <h3>Google</h3>
                                        <div class="account-status status-disconnected">
                                            <i class="fas fa-times-circle"></i> Не подключено
                                        </div>
                                    </div>
                                </div>
                                <button class="btn" onclick="connectGoogle()" style="width: 100%;">
                                    <i class="fab fa-google"></i> Подключить Google
                                </button>
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
                    <p class="footer-description">Управляйте своим аккаунтом и настройками</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        // Превью аватара
        function previewAvatar(event) {
            const input = event.target;
            const reader = new FileReader();
            
            reader.onload = function() {
                const avatarDiv = document.getElementById('currentAvatar');
                avatarDiv.innerHTML = `<img src="${reader.result}" alt="Аватар" style="width:100%;height:100%;object-fit:cover;">`;
            };
            
            if (input.files && input.files[0]) {
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Подключение аккаунтов
        function connectDiscord() {
            showNotification('Функция подключения Discord скоро будет доступна!', 'info');
        }
        
        function connectVK() {
            showNotification('Функция подключения ВКонтакте скоро будет доступна!', 'info');
        }
        
        function connectGoogle() {
            showNotification('Функция подключения Google скоро будет доступна!', 'info');
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
        
        // Плавная прокрутка к якорям
        document.querySelectorAll('.settings-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    // Убираем активный класс у всех ссылок
                    document.querySelectorAll('.settings-nav a').forEach(a => {
                        a.classList.remove('active');
                    });
                    
                    // Добавляем активный класс текущей ссылке
                    this.classList.add('active');
                    
                    // Прокручиваем к элементу
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Анимация загрузки
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('loading').classList.add('hidden');
            }, 1000);
        });
document.getElementById('avatarForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
    submitBtn.disabled = true;
    
    fetch('api/upload_avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем превью
            const avatarImg = document.getElementById('currentAvatar');
            avatarImg.innerHTML = `<img src="${data.avatar_url}" alt="Аватар" style="width:100%;height:100%;object-fit:cover;">`;
            
            // Обновляем аватар во всех местах страницы
            updateAllAvatars(data.avatar_urls);
            
            // Показываем уведомление
            showNotification(data.message, 'success');
            
            // Сохраняем в localStorage время обновления
            localStorage.setItem('avatar_updated', Date.now());
            
            // Отправляем событие об обновлении аватара
            window.dispatchEvent(new CustomEvent('avatarUpdated', { 
                detail: { avatar_url: data.avatar_url }
            }));
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Ошибка загрузки: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Функция для обновления всех аватаров на странице
function updateAllAvatars(avatar_urls) {
    // Обновляем все элементы с классом user-avatar
    document.querySelectorAll('.user-avatar').forEach(element => {
        const size = element.dataset.size || 'medium';
        const avatar_url = avatar_urls[size] || avatar_urls['medium'];
        
        if (avatar_url) {
            element.src = avatar_url + '?v=' + Date.now();
        }
    });
    
    // Обновляем превью в других местах
    document.querySelectorAll('.current-avatar img').forEach(img => {
        img.src = avatar_urls['large'] + '?v=' + Date.now();
    });
}

// Слушаем событие обновления аватара
window.addEventListener('avatarUpdated', function(e) {
    console.log('Аватар обновлен:', e.detail.avatar_url);
    
    // Можно отправить уведомление на другие вкладки
    if (window.BroadcastChannel) {
        const channel = new BroadcastChannel('avatar_updates');
        channel.postMessage({
            type: 'avatar_updated',
            user_id: <?php echo $user_id; ?>,
            avatar_url: e.detail.avatar_url,
            timestamp: Date.now()
        });
    }
});

// Проверяем обновления аватара при загрузке страницы
window.addEventListener('load', function() {
    const lastUpdate = localStorage.getItem('avatar_updated');
    if (lastUpdate && (Date.now() - lastUpdate) < 60000) { // Если обновляли менее минуты назад
        // Запрашиваем свежий аватар
        fetch(`api/get_avatar.php?user_id=<?php echo $user_id; ?>&t=${Date.now()}`)
            .then(response => response.json())
            .then(data => {
                if (data.avatar_url) {
                    updateAllAvatars({large: data.avatar_url});
                }
            });
    }
});
    </script>
</body>
</html>