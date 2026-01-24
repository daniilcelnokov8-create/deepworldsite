<?php
// pages/admin_users.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_admin();

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Поиск и фильтрация
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Построение запроса
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
}

if (!empty($status)) {
    $where[] = "is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Получаем пользователей
$stmt = $pdo->prepare("
    SELECT * FROM users 
    $where_sql 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$users = $stmt->fetchAll();

// Общее количество для пагинации
$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $where_sql");
$count_params = array_slice($params, 0, count($params) - 2);
if ($count_params) {
    $count_stmt->execute($count_params);
} else {
    $count_stmt->execute();
}
$total_users = $count_stmt->fetch()['total'];
$total_pages = ceil($total_users / $limit);

// Статистика по ролям
$role_stats_stmt = $pdo->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$role_stats = $role_stats_stmt->fetchAll();

// Статистика по активности
$status_stats_stmt = $pdo->query("
    SELECT 
        SUM(is_active = 1) as active,
        SUM(is_active = 0) as inactive
    FROM users
");
$status_stats = $status_stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями | DeepWorld Админ</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-users {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        
        .admin-header {
            background: linear-gradient(135deg, #2d1a4e 0%, #1a2e4e 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px solid #FFD700;
        }
        
        .admin-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .admin-title h1 {
            font-size: 2rem;
            color: white;
            margin: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        /* Фильтры */
        .filters-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.05);
        }
        
        /* Таблица */
        .table-responsive {
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
            min-width: 1000px;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }
        
        td {
            padding: 1rem;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Статусы */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(52, 168, 83, 0.2);
            color: #34A853;
            border: 1px solid rgba(52, 168, 83, 0.3);
        }
        
        .status-inactive {
            background: rgba(234, 67, 53, 0.2);
            color: #EA4335;
            border: 1px solid rgba(234, 67, 53, 0.3);
        }
        
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .role-user {
            background: rgba(66, 135, 245, 0.2);
            color: #4285F4;
            border: 1px solid rgba(66, 135, 245, 0.3);
        }
        
        .role-admin {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .role-mod {
            background: rgba(123, 31, 162, 0.2);
            color: #7B1FA2;
            border: 1px solid rgba(123, 31, 162, 0.3);
        }
        
        /* Действия */
        .action-buttons-small {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-size: 0.9rem;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-view { background: rgba(66, 135, 245, 0.2); }
        .btn-view:hover { background: rgba(66, 135, 245, 0.3); }
        
        .btn-edit { background: rgba(255, 193, 7, 0.2); }
        .btn-edit:hover { background: rgba(255, 193, 7, 0.3); }
        
        .btn-toggle { background: rgba(52, 168, 83, 0.2); }
        .btn-toggle:hover { background: rgba(52, 168, 83, 0.3); }
        
        .btn-delete { background: rgba(234, 67, 53, 0.2); }
        .btn-delete:hover { background: rgba(234, 67, 53, 0.3); }
        
        /* Пагинация */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
        }
        
        .page-link.active {
            background: #FFD700;
            color: #1a1a2e;
            font-weight: 600;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card-small {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .stat-value-small {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .stat-label-small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }
        
        /* Модальное окно */
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
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Форма */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.05);
        }
        
        @media (max-width: 768px) {
            .admin-title {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                justify-content: stretch;
            }
            
            .action-buttons .btn {
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
                    <h1>DeepWorld Админ</h1>
                    <p class="tagline">Управление пользователями</p>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Главная</a></li>
                    <li><a href="dashboard.php">ЛК</a></li>
                    <li><a href="admin.php"><i class="fas fa-home"></i> Панель</a></li>
                    <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Пользователи</a></li>
                    <li><a href="admin_logs.php"><i class="fas fa-history"></i> Логи</a></li>
                    <li><a href="../api/logout.php" class="btn btn-outline">Выйти</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Основной контент -->
    <main class="admin-users">
        <div class="container">
            <!-- Заголовок -->
            <div class="admin-header">
                <div class="admin-title">
                    <h1><i class="fas fa-users-cog"></i> Управление пользователями</h1>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="showAddUserModal()">
                            <i class="fas fa-user-plus"></i> Добавить пользователя
                        </button>
                        <a href="admin.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Назад в панель
                        </a>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card-small">
                    <div class="stat-value-small"><?php echo $total_users; ?></div>
                    <div class="stat-label-small">Всего пользователей</div>
                </div>
                <?php foreach($role_stats as $stat): ?>
                <div class="stat-card-small">
                    <div class="stat-value-small"><?php echo $stat['count']; ?></div>
                    <div class="stat-label-small">
                        <?php 
                            echo match($stat['role']) {
                                'admin' => 'Администраторов',
                                'mod' => 'Модераторов',
                                default => 'Пользователей'
                            };
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="stat-card-small">
                    <div class="stat-value-small"><?php echo $status_stats['active']; ?></div>
                    <div class="stat-label-small">Активных</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value-small"><?php echo $status_stats['inactive']; ?></div>
                    <div class="stat-label-small">Неактивных</div>
                </div>
            </div>

            <!-- Фильтры -->
            <div class="filters-card">
                <form method="GET" action="" id="filterForm">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="search"><i class="fas fa-search"></i> Поиск</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Имя пользователя или email...">
                        </div>
                        <div class="filter-group">
                            <label for="role"><i class="fas fa-user-tag"></i> Роль</label>
                            <select id="role" name="role">
                                <option value="">Все роли</option>
                                <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Пользователь</option>
                                <option value="mod" <?php echo $role === 'mod' ? 'selected' : ''; ?>>Модератор</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Администратор</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="status"><i class="fas fa-circle"></i> Статус</label>
                            <select id="status" name="status">
                                <option value="">Все статусы</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Активные</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Неактивные</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Применить фильтры
                        </button>
                        <button type="button" class="btn btn-outline" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> Сбросить
                        </button>
                        <button type="button" class="btn btn-outline" onclick="exportUsers()">
                            <i class="fas fa-download"></i> Экспорт
                        </button>
                    </div>
                </form>
            </div>

            <!-- Таблица пользователей -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Регистрация</th>
                            <th>Последний вход</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: rgba(255, 255, 255, 0.5);">
                                    <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                    Пользователи не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(45deg, #4285F4, #34A853); 
                                                    display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: white;"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <?php if($user['id'] == $_SESSION['user_id']): ?>
                                                <small style="color: #FFD700;"><i class="fas fa-star"></i> Вы</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <i class="fas fa-<?php echo match($user['role']) { 'admin' => 'crown', 'mod' => 'shield-alt', default => 'user' }; ?>"></i>
                                        <?php echo match($user['role']) { 'admin' => 'Админ', 'mod' => 'Модератор', default => 'Пользователь' }; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <i class="fas fa-<?php echo $user['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo $user['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                        <br>
                                        <small style="color: rgba(255, 255, 255, 0.6);"><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if($user['last_login']): ?>
                                        <div style="font-size: 0.9rem;">
                                            <?php echo date('d.m.Y', strtotime($user['last_login'])); ?>
                                            <br>
                                            <small style="color: rgba(255, 255, 255, 0.6);"><?php echo date('H:i', strtotime($user['last_login'])); ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: rgba(255, 255, 255, 0.5); font-size: 0.9rem;">Никогда</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons-small">
                                        <button class="action-btn btn-view" onclick="viewUser(<?php echo $user['id']; ?>)" 
                                                title="Просмотр">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" 
                                                title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn btn-toggle" 
                                                onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)" 
                                                title="<?php echo $user['is_active'] ? 'Деактивировать' : 'Активировать'; ?>">
                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                        <?php if($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                        <button class="action-btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                                title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Пагинация -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>" class="page-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
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
                            <h3>DeepWorld Админ</h3>
                            <p>Панель управления пользователями</p>
                        </div>
                    </div>
                    <p class="footer-description">Показано <?php echo count($users); ?> из <?php echo $total_users; ?> пользователей • Страница <?php echo $page; ?> из <?php echo $total_pages; ?></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Модальное окно просмотра пользователя -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: white; margin: 0;"><i class="fas fa-user"></i> Просмотр пользователя</h2>
                <button onclick="closeModal()" style="background: none; border: none; color: rgba(255, 255, 255, 0.5); font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="userDetails"></div>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования пользователя -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: white; margin: 0;" id="modalTitle"><i class="fas fa-user-plus"></i> Добавить пользователя</h2>
                <button onclick="closeModal()" style="background: none; border: none; color: rgba(255, 255, 255, 0.5); font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Имя пользователя *</label>
                        <input type="text" id="username" name="username" required 
                               placeholder="Введите имя пользователя" pattern="[a-zA-Z0-9_]{3,20}">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="user@example.com">
                    </div>
                    <div class="form-group">
                        <label for="password">Парол<?php echo isset($_GET['edit']) ? 'ь (оставьте пустым, если не меняется)' : 'ь *'; ?></label>
                        <input type="password" id="password" name="password" 
                               <?php echo !isset($_GET['edit']) ? 'required' : ''; ?> 
                               placeholder="Минимум 6 символов" minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Повторите пароль">
                    </div>
                    <div class="form-group">
                        <label for="role">Роль *</label>
                        <select id="role" name="role" required>
                            <option value="user">Пользователь</option>
                            <option value="mod">Модератор</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="is_active">Статус</label>
                        <select id="is_active" name="is_active">
                            <option value="1">Активен</option>
                            <option value="0">Неактивен</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="bio">О пользователе</label>
                    <textarea id="bio" name="bio" rows="3" placeholder="Дополнительная информация..."></textarea>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal()">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../script.js"></script>
    <script>
        // Управление модальными окнами
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = 'auto';
        }
        
        // Закрытие по клику вне окна
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
        
        // Просмотр пользователя
        function viewUser(userId) {
            fetch(`api/get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    const details = `
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(45deg, #4285F4, #34A853); 
                                        display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                                ${user.username.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <h3 style="color: white; margin-bottom: 0.5rem;">${user.username}</h3>
                                <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 0.5rem;">${user.email}</p>
                                <div style="display: flex; gap: 0.5rem;">
                                    <span class="role-badge role-${user.role}" style="display: inline-flex;">
                                        <i class="fas fa-${user.role === 'admin' ? 'crown' : user.role === 'mod' ? 'shield-alt' : 'user'}"></i>
                                        ${user.role === 'admin' ? 'Админ' : user.role === 'mod' ? 'Модератор' : 'Пользователь'}
                                    </span>
                                    <span class="status-badge status-${user.is_active ? 'active' : 'inactive'}" style="display: inline-flex;">
                                        <i class="fas fa-${user.is_active ? 'check-circle' : 'times-circle'}"></i>
                                        ${user.is_active ? 'Активен' : 'Неактивен'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px;">
                                <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">ID пользователя</div>
                                <div style="color: white; font-weight: 600; font-size: 1.1rem;">#${user.id}</div>
                            </div>
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px;">
                                <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Дата регистрации</div>
                                <div style="color: white; font-weight: 600; font-size: 1.1rem;">${new Date(user.created_at).toLocaleDateString('ru-RU')}</div>
                            </div>
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px;">
                                <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Последний вход</div>
                                <div style="color: white; font-weight: 600; font-size: 1.1rem;">
                                    ${user.last_login ? new Date(user.last_login).toLocaleString('ru-RU') : 'Никогда'}
                                </div>
                            </div>
                        </div>
                        
                        ${user.bio ? `
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: white; margin-bottom: 0.5rem;">О пользователе</h4>
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 10px; color: rgba(255, 255, 255, 0.9);">
                                ${user.bio}
                            </div>
                        </div>
                        ` : ''}
                        
                        <div style="display: flex; gap: 1rem;">
                            <button class="btn btn-primary" onclick="editUser(${user.id})">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button class="btn btn-outline" onclick="closeModal()">
                                <i class="fas fa-times"></i> Закрыть
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('userDetails').innerHTML = details;
                    showModal('viewUserModal');
                });
        }
        
        // Добавление пользователя
        function showAddUserModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Добавить пользователя';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('password').required = true;
            showModal('editUserModal');
        }
        
        // Редактирование пользователя
        function editUser(userId) {
            fetch(`api/get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Редактировать пользователя';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = user.role;
                    document.getElementById('is_active').value = user.is_active ? '1' : '0';
                    document.getElementById('bio').value = user.bio || '';
                    document.getElementById('password').required = false;
                    document.getElementById('password').placeholder = 'Оставьте пустым, если не меняется';
                    
                    showModal('editUserModal');
                    closeModal(); // Закрываем окно просмотра если открыто
                });
        }
        
        // Сохранение пользователя
        function saveUser(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            // Проверка пароля
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if (password && password !== confirmPassword) {
                showNotification('Пароли не совпадают!', 'error');
                return;
            }
            
            // Если редактирование и пароль не указан, удаляем поле
            if (formData.get('id') && !password) {
                formData.delete('password');
                formData.delete('confirm_password');
            }
            
            fetch('api/save_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка сети', 'error');
            });
        }
        
        // Изменение статуса пользователя
        function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'активировать' : 'деактивировать';
            
            if (confirm(`Вы уверены, что хотите ${action} этого пользователя?`)) {
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
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }
        
        // Удаление пользователя
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
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }
        
        // Сброс фильтров
        function resetFilters() {
            window.location.href = 'admin_users.php';
        }
        
        // Экспорт пользователей
        function exportUsers() {
            const params = new URLSearchParams(window.location.search);
            window.open(`api/export_users.php?${params.toString()}`, '_blank');
        }
        
        // Уведомления
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
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Автосохранение фильтров в URL
            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                const inputs = filterForm.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.addEventListener('change', function() {
                        const formData = new FormData(filterForm);
                        const params = new URLSearchParams(formData).toString();
                        window.history.replaceState(null, '', `?${params}`);
                    });
                });
            }
        });
    </script>
</body>
</html>