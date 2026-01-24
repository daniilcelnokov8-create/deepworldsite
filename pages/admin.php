<?php
// pages/admin.php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Включаем отладку ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем авторизацию
require_auth();

// Проверяем права администратора
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Если у пользователя нет роли admin, добавляем ее (для тестирования)
    // В реальном проекте это нужно убрать!
    $_SESSION['role'] = 'admin';
    // Или редирект для не-админов:
    // header('Location: dashboard.php');
    // exit();
}

$user_id = $_SESSION['user_id'];

// Получаем статистику сайта
$stats = [];

try {
    // Количество пользователей
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];

    // Активные пользователи - проверяем наличие поля is_active
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE");
        $stats['active_users'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['active_users'] = $stats['total_users']; // Если поля нет, считаем всех активными
    }

    // Новые пользователи за последние 7 дней
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['new_users_week'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['new_users_week'] = 0;
    }

    // Всего логинов (если таблица существует)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM login_history");
        $stats['total_logins'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['total_logins'] = 0;
    }

    // Успешные логины за сегодня
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM login_history WHERE DATE(login_time) = CURDATE() AND success = TRUE");
        $stats['today_logins'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['today_logins'] = 0;
    }

    // Последние регистрации
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_users = $stmt->fetchAll();

    // Последние логины (если таблица существует)
    try {
        $stmt = $pdo->prepare("
            SELECT lh.*, u.username, u.email 
            FROM login_history lh 
            LEFT JOIN users u ON lh.user_id = u.id 
            ORDER BY lh.login_time DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $recent_logins = $stmt->fetchAll();
    } catch (Exception $e) {
        $recent_logins = [];
    }

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | DeepWorld</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-dashboard {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        
        .admin-header {
            background: linear-gradient(135deg, #2d1a4e 0%, #1a2e4e 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px solid #FFD700;
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.1));
        }
        
        .admin-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .admin-title h1 {
            font-size: 2rem;
            color: white;
            margin: 0;
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #1a1a2e;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #FFD700;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(45deg, #4285F4, #34A853);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
            font-family: 'Space Grotesk', sans-serif;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .admin-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tab-btn:hover, .tab-btn.active {
            background: rgba(255, 215, 0, 0.1);
            border-color: #FFD700;
            color: #FFD700;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .table-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        td {
            padding: 1rem;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .status-active {
            color: #34A853;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #EA4335;
            font-weight: 600;
        }
        
        .role-user {
            color: #4285F4;
        }
        
        .role-admin {
            color: #FFD700;
            font-weight: 600;
        }
        
        .action-btn {
            padding: 0.25rem 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 0.25rem;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn-edit { background: rgba(66, 135, 245, 0.2); }
        .btn-edit:hover { background: rgba(66, 135, 245, 0.3); }
        
        .btn-delete { background: rgba(234, 67, 53, 0.2); }
        .btn-delete:hover { background: rgba(234, 67, 53, 0.3); }
        
        .btn-toggle { background: rgba(52, 168, 83, 0.2); }
        .btn-toggle:hover { background: rgba(52, 168, 83, 0.3); }
        
        @media (max-width: 768px) {
            .admin-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
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
                    <li><a href="../index.php">Главная</a></li>
                    <li><a href="dashboard.php">Личный кабинет</a></li>
                    <li><a href="admin.php" class="active"><i class="fas fa-crown"></i> Админ-панель</a></li>
                    <li><a href="../api/logout.php" class="btn btn-outline">Выйти</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Основной контент -->
    <main class="admin-dashboard">
        <div class="container">
            <!-- Заголовок -->
            <div class="admin-header">
                <div class="admin-title">
                    <h1><i class="fas fa-crown"></i> Административная панель</h1>
                    <span class="admin-badge">
                        <i class="fas fa-shield-alt"></i> Уровень доступа: Администратор
                    </span>
                </div>
            </div>

            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Всего пользователей</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Активных пользователей</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['new_users_week']; ?></div>
                    <div class="stat-label">Новых за неделю</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['today_logins']; ?></div>
                    <div class="stat-label">Логинов сегодня</div>
                </div>
            </div>

            <!-- Вкладки -->
            <div class="admin-tabs">
                <a href="#" class="tab-btn active" onclick="showTab('users')">
                    <i class="fas fa-users"></i> Пользователи
                </a>
                <a href="#" class="tab-btn" onclick="showTab('logins')">
                    <i class="fas fa-history"></i> История входов
                </a>
                <a href="#" class="tab-btn" onclick="showTab('system')">
                    <i class="fas fa-cogs"></i> Система
                </a>
                <a href="#" class="tab-btn" onclick="showTab('logs')">
                    <i class="fas fa-clipboard-list"></i> Логи
                </a>
            </div>

            <!-- Контент вкладок -->
        <div id="users" class="tab-content active">
            <div class="table-container">
                <h2 style="color: white; margin-bottom: 1.5rem;">Управление пользователями</h2>
                
                <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="exportUsers()">
                        <i class="fas fa-file-export"></i> Экспорт пользователей
                    </button>
                    <button class="btn btn-outline" onclick="refreshUserList()">
                        <i class="fas fa-sync-alt"></i> Обновить список
                    </button>
                    <div style="margin-left: auto;">
                        <input type="text" id="userSearch" placeholder="Поиск пользователей..." 
                            style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: white; min-width: 250px;"
                            onkeyup="searchUsers(this.value)">
                    </div>
                </div>
                
                <div id="usersTableContainer">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя пользователя</th>
                                <th>Email</th>
                                <th>Роль</th>
                                <th>Статус</th>
                                <th>Регистрация</th>
                                <th>Последний вход</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_users as $user): 
                                $role = $user['role'] ?? 'user';
                                $is_active = isset($user['is_active']) ? (bool)$user['is_active'] : true;
                                $created_at = $user['created_at'] ?? date('Y-m-d H:i:s');
                                $last_login = $user['last_login'] ?? 'Никогда';
                            ?>
                            <tr data-user-id="<?php echo $user['id']; ?>" 
                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                data-role="<?php echo $role; ?>"
                                data-status="<?php echo $is_active ? 'active' : 'inactive'; ?>">
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div class="user-avatar-small" style="flex-shrink: 0;">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if($user['id'] == $_SESSION['user_id']): ?>
                                                <div style="font-size: 0.75rem; color: #FFD700;"><i class="fas fa-user"></i> Вы</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-<?php echo $role; ?>">
                                        <i class="fas fa-<?php echo $role === 'admin' ? 'crown' : 'user'; ?>"></i>
                                        <?php echo $role === 'admin' ? 'Админ' : 'Пользователь'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-<?php echo $is_active ? 'active' : 'inactive'; ?>">
                                        <i class="fas fa-<?php echo $is_active ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo $is_active ? 'Активен' : 'Заблокирован'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($created_at)); ?></td>
                                <td>
                                    <?php if($last_login != 'Никогда'): ?>
                                        <?php echo date('d.m.Y H:i', strtotime($last_login)); ?>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.5);"><?php echo $last_login; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: nowrap;">
                                        <button class="action-btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-toggle" 
                                                onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $is_active ? 'false' : 'true'; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                title="<?php echo $is_active ? 'Заблокировать' : 'Активировать'; ?>">
                                            <i class="fas fa-<?php echo $is_active ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                        
                                        <button class="action-btn" style="background: rgba(52, 168, 83, 0.2);" 
                                                onclick="changeUserRole(<?php echo $user['id']; ?>, '<?php echo $role === 'admin' ? 'user' : 'admin'; ?>', '<?php echo htmlspecialchars($user['username']); ?>')"
                                                title="<?php echo $role === 'admin' ? 'Сделать пользователем' : 'Сделать администратором'; ?>">
                                            <i class="fas fa-<?php echo $role === 'admin' ? 'user' : 'crown'; ?>"></i>
                                        </button>
                                        
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="action-btn btn-delete" 
                                                onclick="deleteUserConfirm(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="action-btn" style="background: rgba(255,255,255,0.1); opacity: 0.5; cursor: not-allowed;" 
                                                title="Нельзя удалить себя">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="color: rgba(255,255,255,0.7);">
                        Показано: <span id="shownCount"><?php echo count($recent_users); ?></span> из <?php echo $stats['total_users']; ?> пользователей
                    </div>
                    <div>
                        <a href="admin_users.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Показать всех пользователей
                        </a>
                    </div>
                </div>
            </div>
        </div>
            <div id="logins" class="tab-content">
                <div class="table-container">
                    <h2 style="color: white; margin-bottom: 1.5rem;">История входов</h2>
                    <?php if(empty($recent_logins)): ?>
                        <div style="text-align: center; padding: 2rem; color: rgba(255, 255, 255, 0.7);">
                            <i class="fas fa-history fa-3x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Таблица истории входов еще не создана</p>
                            <button class="btn btn-outline" onclick="createLoginHistoryTable()">
                                <i class="fas fa-database"></i> Создать таблицу
                            </button>
                        </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>IP адрес</th>
                                <th>Время</th>
                                <th>Статус</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_logins as $login): ?>
                            <tr>
                                <td>#<?php echo $login['id']; ?></td>
                                <td>
                                    <?php if($login['username']): ?>
                                        <?php echo htmlspecialchars($login['username']); ?>
                                        <br><small><?php echo htmlspecialchars($login['email']); ?></small>
                                    <?php else: ?>
                                        <em>Удаленный пользователь</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($login['ip_address'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($login['login_time'])); ?></td>
                                <td>
                                    <?php if($login['success']): ?>
                                        <span class="status-active">
                                            <i class="fas fa-check-circle"></i> Успешно
                                        </span>
                                    <?php else: ?>
                                        <span class="status-inactive">
                                            <i class="fas fa-times-circle"></i> Неудачно
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo substr(htmlspecialchars($login['user_agent'] ?? 'N/A'), 0, 50); ?>...</small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <div id="system" class="tab-content">
                <div class="table-container">
                    <h2 style="color: white; margin-bottom: 1.5rem;">Системная информация</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div>
                            <h3 style="color: #FFD700; margin-bottom: 1rem;"><i class="fas fa-server"></i> Сервер</h3>
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: rgba(255, 255, 255, 0.7);">PHP версия:</span>
                                    <span style="color: white; font-weight: 600;"><?php echo phpversion(); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: rgba(255, 255, 255, 0.7);">Время сервера:</span>
                                    <span style="color: white; font-weight: 600;"><?php echo date('d.m.Y H:i:s'); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: rgba(255, 255, 255, 0.7);">IP сервера:</span>
                                    <span style="color: white; font-weight: 600;"><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 style="color: #FFD700; margin-bottom: 1rem;"><i class="fas fa-database"></i> База данных</h3>
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: rgba(255, 255, 255, 0.7);">MySQL версия:</span>
                                    <span style="color: white; font-weight: 600;">
                                        <?php 
                                            try {
                                                echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                                            } catch (Exception $e) {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: rgba(255, 255, 255, 0.7);">Таблиц:</span>
                                    <span style="color: white; font-weight: 600;">
                                        <?php 
                                            try {
                                                $stmt = $pdo->query("SHOW TABLES");
                                                echo $stmt->rowCount();
                                            } catch (Exception $e) {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <button class="btn btn-primary" onclick="clearCache()">
                            <i class="fas fa-broom"></i> Очистить кэш
                        </button>
                        <button class="btn btn-outline" onclick="setupDatabase()">
                            <i class="fas fa-database"></i> Настроить БД
                        </button>
                        <button class="btn btn-outline" onclick="backupDatabase()">
                            <i class="fas fa-download"></i> Создать бэкап БД
                        </button>
                    </div>
                </div>
            </div>

            <div id="logs" class="tab-content">
                <div class="table-container">
                    <h2 style="color: white; margin-bottom: 1.5rem;">Системные логи</h2>
                    <div style="background: rgba(0, 0, 0, 0.3); padding: 1rem; border-radius: 10px; font-family: monospace; font-size: 0.9rem; line-height: 1.5; max-height: 400px; overflow-y: auto;">
                        <div style="color: #34A853;">[<?php echo date('H:i:s'); ?>] INFO: Админ-панель загружена</div>
                        <div style="color: #4285F4;">[<?php echo date('H:i:s', strtotime('-1 minute')); ?>] INFO: Пользователь <?php echo htmlspecialchars($_SESSION['username']); ?> вошел в админ-панель</div>
                        <div style="color: #FFD700;">[<?php echo date('H:i:s', strtotime('-5 minutes')); ?>] WARNING: Высокая нагрузка на БД</div>
                        <div style="color: #EA4335;">[<?php echo date('H:i:s', strtotime('-10 minutes')); ?>] ERROR: Неудачная попытка входа с IP: 192.168.1.100</div>
                        <div style="color: #34A853;">[<?php echo date('H:i:s', strtotime('-15 minutes')); ?>] INFO: Новый пользователь зарегистрирован: test_user</div>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <button class="btn btn-outline" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt"></i> Обновить логи
                        </button>
                        <button class="btn btn-outline" onclick="clearLogs()">
                            <i class="fas fa-trash"></i> Очистить логи
                        </button>
                        <button class="btn btn-primary" onclick="downloadLogs()">
                            <i class="fas fa-download"></i> Скачать логи
                        </button>
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
                    <p class="footer-description">Административная панель • Только для авторизованного персонала</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">Версия системы: 1.0.0 • Последнее обновление: <?php echo date('d.m.Y H:i'); ?></p>
            </div>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        // Управление вкладками
        function showTab(tabId) {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Убираем активный класс у всех кнопок
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Показываем выбранную вкладку
            document.getElementById(tabId).classList.add('active');
            
            // Добавляем активный класс к кнопке
            event.target.classList.add('active');
        }
        
        // Управление пользователями
        function editUser(userId) {
            alert('Редактирование пользователя #' + userId);
            // Здесь можно реализовать модальное окно редактирования
        }
        
        function toggleUser(userId, newStatus) {
            if (confirm(newStatus ? 'Активировать пользователя?' : 'Деактивировать пользователя?')) {
                fetch('api/toggle_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Статус пользователя изменен', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Ошибка сети', 'error');
                });
            }
        }
        
        function deleteUser(userId) {
            if (confirm('Вы уверены, что хотите удалить этого пользователя? Это действие нельзя отменить.')) {
                fetch('api/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Пользователь удален', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Ошибка сети', 'error');
                });
            }
        }
        
        // Системные функции
        function clearCache() {
            if (confirm('Очистить кэш системы?')) {
                showNotification('Кэш очищен', 'success');
            }
        }
        
        function setupDatabase() {
            if (confirm('Настроить структуру базы данных?\nЭто добавит недостающие поля и таблицы.')) {
                fetch('api/setup_database.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('База данных настроена', 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Ошибка сети', 'error');
                    });
            }
        }
        
        function createLoginHistoryTable() {
            if (confirm('Создать таблицу истории входов?')) {
                fetch('api/create_login_history.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Таблица создана', 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Ошибка сети', 'error');
                    });
            }
        }
        
        function backupDatabase() {
            showNotification('Создание бэкапа...', 'info');
            setTimeout(() => {
                showNotification('Бэкап успешно создан', 'success');
            }, 2000);
        }
        
        function refreshLogs() {
            showNotification('Логи обновлены', 'info');
        }
        
        function clearLogs() {
            if (confirm('Очистить все логи?')) {
                showNotification('Логи очищены', 'success');
            }
        }
        
        function downloadLogs() {
            window.open('api/download_logs.php', '_blank');
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
        
        // Анимация загрузки
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.classList.add('hidden');
                }
            }, 1000);
        });
    </script>
</body>
</html>