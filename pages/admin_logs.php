<?php
// pages/admin_logs.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_admin();

// Получаем логи
$type = $_GET['type'] ?? 'all';
$date = $_GET['date'] ?? date('Y-m-d');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Построение запроса
$where = [];
$params = [];

if ($type !== 'all') {
    $where[] = "activity_type = ?";
    $params[] = $type;
}

if (!empty($date)) {
    $where[] = "DATE(created_at) = ?";
    $params[] = $date;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Получаем логи
$stmt = $pdo->prepare("
    SELECT a.*, u.username, u.email 
    FROM user_activity a 
    LEFT JOIN users u ON a.user_id = u.id 
    $where_sql 
    ORDER BY a.created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Общее количество
$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_activity a $where_sql");
$count_params = array_slice($params, 0, count($params) - 2);
if ($count_params) {
    $count_stmt->execute($count_params);
} else {
    $count_stmt->execute();
}
$total_logs = $count_stmt->fetch()['total'];
$total_pages = ceil($total_logs / $limit);

// Типы активностей
$activity_types = [
    'login' => 'Вход в систему',
    'logout' => 'Выход из системы',
    'register' => 'Регистрация',
    'update' => 'Обновление профиля',
    'admin_action' => 'Действие администратора',
    'error' => 'Ошибка'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Системные логи | DeepWorld Админ</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logs-container {
            padding: 2rem 0;
        }
        
        .logs-header {
            background: linear-gradient(135deg, #2d1a4e 0%, #1a2e4e 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px solid #FFD700;
        }
        
        .logs-filters {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }
        
        .log-item {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid #4285F4;
            transition: all 0.3s ease;
        }
        
        .log-item:hover {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            transform: translateX(5px);
        }
        
        .log-item.login { border-left-color: #34A853; }
        .log-item.logout { border-left-color: #EA4335; }
        .log-item.error { border-left-color: #FF9800; }
        .log-item.admin_action { border-left-color: #FFD700; }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .log-type {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(66, 135, 245, 0.1);
            color: #4285F4;
        }
        
        .log-type.login { background: rgba(52, 168, 83, 0.1); color: #34A853; }
        .log-type.logout { background: rgba(234, 67, 53, 0.1); color: #EA4335; }
        .log-type.error { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
        .log-type.admin_action { background: rgba(255, 215, 0, 0.1); color: #FFD700; }
        
        .log-time {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        .log-user {
            font-weight: 600;
            color: white;
        }
        
        .log-description {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.5rem;
        }
        
        .log-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .log-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .log-meta {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Шапка -->
    <header>
        <div class="container">
            <div class="logo">
                <span class="logo-icon">⚔️</span>
                <div class="logo-text">
                    <h1>DeepWorld Админ</h1>
                    <p class="tagline">Системные логи</p>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Главная</a></li>
                    <li><a href="dashboard.php">ЛК</a></li>
                    <li><a href="admin.php">Панель</a></li>
                    <li><a href="admin_users.php">Пользователи</a></li>
                    <li><a href="admin_logs.php" class="active">Логи</a></li>
                    <li><a href="../api/logout.php" class="btn btn-outline">Выйти</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <main class="logs-container">
        <div class="container">
            <!-- Заголовок -->
            <div class="logs-header">
                <h1 style="color: white; margin-bottom: 1rem;">
                    <i class="fas fa-clipboard-list"></i> Системные логи
                </h1>
                <p style="color: rgba(255, 255, 255, 0.8);">
                    Просмотр активности пользователей и системных событий
                </p>
            </div>

            <!-- Фильтры -->
            <div class="logs-filters">
                <form method="GET" class="filters-grid">
                    <div class="filter-group">
                        <label for="type"><i class="fas fa-filter"></i> Тип события</label>
                        <select id="type" name="type">
                            <option value="all">Все события</option>
                            <?php foreach($activity_types as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date"><i class="fas fa-calendar"></i> Дата</label>
                        <input type="date" id="date" name="date" value="<?php echo $date; ?>">
                    </div>
                    <div class="filter-group" style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Применить
                        </button>
                    </div>
                </form>
            </div>

            <!-- Статистика -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div style="color: rgba(255, 255, 255, 0.8);">
                    <i class="fas fa-history"></i> Показано <?php echo count($logs); ?> из <?php echo $total_logs; ?> записей
                </div>
                <div>
                    <button class="btn btn-outline" onclick="clearOldLogs()">
                        <i class="fas fa-broom"></i> Очистить старые логи
                    </button>
                    <button class="btn btn-primary" onclick="exportLogs()">
                        <i class="fas fa-download"></i> Экспортировать
                    </button>
                </div>
            </div>

            <!-- Список логов -->
            <?php if(empty($logs)): ?>
                <div style="text-align: center; padding: 3rem; color: rgba(255, 255, 255, 0.5);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    Логи не найдены
                </div>
            <?php else: ?>
                <?php foreach($logs as $log): ?>
                <div class="log-item <?php echo $log['activity_type']; ?>">
                    <div class="log-header">
                        <div>
                            <span class="log-type <?php echo $log['activity_type']; ?>">
                                <i class="fas fa-<?php echo match($log['activity_type']) {
                                    'login' => 'sign-in-alt',
                                    'logout' => 'sign-out-alt',
                                    'register' => 'user-plus',
                                    'error' => 'exclamation-triangle',
                                    'admin_action' => 'crown',
                                    default => 'bell'
                                }; ?>"></i>
                                <?php echo $activity_types[$log['activity_type']] ?? $log['activity_type']; ?>
                            </span>
                            <?php if($log['username']): ?>
                                <span class="log-user">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($log['username']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="log-time">
                            <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                    <div class="log-description">
                        <?php echo htmlspecialchars($log['description']); ?>
                    </div>
                    <div class="log-meta">
                        <?php if($log['ip_address']): ?>
                            <span><i class="fas fa-network-wired"></i> IP: <?php echo htmlspecialchars($log['ip_address']); ?></span>
                        <?php endif; ?>
                        <?php if($log['user_id']): ?>
                            <span><i class="fas fa-id-card"></i> ID: #<?php echo $log['user_id']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

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
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 DeepWorld. Все права защищены.</p>
                <p class="disclaimer">Последнее обновление логов: <?php echo date('d.m.Y H:i:s'); ?></p>
            </div>
        </div>
    </footer>

    <script>
        function clearOldLogs() {
            if (confirm('Очистить логи старше 30 дней?')) {
                fetch('api/clear_logs.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    }
                });
            }
        }
        
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            window.open(`api/export_logs.php?${params.toString()}`, '_blank');
        }
    </script>
</body>
</html>