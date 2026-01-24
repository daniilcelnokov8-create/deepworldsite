<?php
// api/get_avatar.php
require_once '../config/database.php';
require_once '../includes/auth.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$size = $_GET['size'] ?? 'medium';

if (!$user_id) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit();
    }
}

// Получаем аватар пользователя
$stmt = $pdo->prepare("SELECT avatar, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    exit();
}

// Определяем URL аватара с timestamp для предотвращения кэширования
$avatar_url = $user['avatar'];
$timestamp = time();

if ($avatar_url && file_exists('../' . ltrim($avatar_url, '/'))) {
    $timestamp = filemtime('../' . ltrim($avatar_url, '/'));
    $avatar_url .= '?v=' . $timestamp;
} else {
    // Генерируем SVG аватар
    $avatar_url = generate_svg_avatar($user['username'], $size);
}

echo json_encode([
    'success' => true,
    'avatar_url' => $avatar_url,
    'timestamp' => $timestamp,
    'username' => $user['username']
]);

function generate_svg_avatar($username, $size) {
    $sizes = [
        'small' => ['width' => 32, 'font' => 14],
        'medium' => ['width' => 48, 'font' => 18],
        'large' => ['width' => 120, 'font' => 48],
        'xlarge' => ['width' => 150, 'font' => 60]
    ];
    
    $size_config = $sizes[$size] ?? $sizes['medium'];
    $initials = strtoupper(substr($username, 0, 2));
    
    $colors = [
        ['#4285F4', '#34A853'],
        ['#EA4335', '#FBBC05'],
        ['#8B5CF6', '#EC4899'],
        ['#0EA5E9', '#10B981'],
    ];
    
    $hash = crc32($username);
    $color_pair = $colors[$hash % count($colors)];
    
    $svg = sprintf(
        '<svg width="%d" height="%d" viewBox="0 0 %1$d %1$d" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad" x1="0%%" y1="0%%" x2="100%%" y2="100%%">
                    <stop offset="0%%" stop-color="%s"/>
                    <stop offset="100%%" stop-color="%s"/>
                </linearGradient>
            </defs>
            <rect width="%1$d" height="%1$d" fill="url(#grad)" rx="%3$d"/>
            <text x="50%%" y="55%%" text-anchor="middle" dy="0.35em" font-family="Arial" font-size="%d" font-weight="bold" fill="white">%s</text>
        </svg>',
        $size_config['width'],
        $size_config['width'],
        $size_config['width'] / 2,
        $color_pair[0],
        $color_pair[1],
        $size_config['font'],
        $initials
    );
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
?>