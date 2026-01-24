<?php
// api/upload_avatar.php
require_once '../config/database.php';
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Настройки загрузки
$upload_dir = '../uploads/avatars/';
$max_file_size = 5 * 1024 * 1024; // 5MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Создаем папку если не существует
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Проверяем загрузку файла
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла']);
    exit();
}

$file = $_FILES['avatar'];

// Проверка размера файла
if ($file['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'message' => 'Файл слишком большой. Максимум 5MB']);
    exit();
}

// Проверка типа файла
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Неподдерживаемый формат файла']);
    exit();
}

// Генерируем уникальное имя файла
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Перемещаем загруженный файл
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения файла']);
    exit();
}

// Создаем миниатюру 500x500
try {
    list($width, $height) = getimagesize($filepath);
    
    // Определяем тип изображения
    switch($mime_type) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            throw new Exception('Неподдерживаемый формат изображения');
    }
    
    // Создаем квадратное изображение 500x500
    $thumb_size = 500;
    $thumb = imagecreatetruecolor($thumb_size, $thumb_size);
    
    // Делаем фон прозрачным для PNG/GIF
    if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
    }
    
    // Вычисляем размеры для обрезки
    $src_x = 0;
    $src_y = 0;
    $src_w = $width;
    $src_h = $height;
    
    if ($width > $height) {
        $src_x = ($width - $height) / 2;
        $src_w = $height;
    } elseif ($height > $width) {
        $src_y = ($height - $width) / 2;
        $src_h = $width;
    }
    
    // Копируем и изменяем размер
    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, 
                       $thumb_size, $thumb_size, $src_w, $src_h);
    
    // Сохраняем миниатюру
    $thumb_filename = 'thumb_' . $filename;
    $thumb_filepath = $upload_dir . $thumb_filename;
    
    switch($mime_type) {
        case 'image/jpeg':
            imagejpeg($thumb, $thumb_filepath, 90);
            break;
        case 'image/png':
            imagepng($thumb, $thumb_filepath, 9);
            break;
        case 'image/gif':
            imagegif($thumb, $thumb_filepath);
            break;
        case 'image/webp':
            imagewebp($thumb, $thumb_filepath, 90);
            break;
    }
    
    // Освобождаем память
    imagedestroy($source);
    imagedestroy($thumb);
    
    // Сохраняем имя файла в БД
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$thumb_filename, $user_id]);
    
    // Удаляем старый аватар если есть
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_avatar = $stmt->fetchColumn();
    
    if ($old_avatar && $old_avatar != $thumb_filename) {
        $old_path = $upload_dir . $old_avatar;
        if (file_exists($old_path)) {
            unlink($old_path);
        }
        
        // Удаляем оригинальный файл старого аватара если есть
        $old_original = str_replace('thumb_', '', $old_avatar);
        $old_original_path = $upload_dir . $old_original;
        if (file_exists($old_original_path)) {
            unlink($old_original_path);
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Аватар успешно загружен',
        'avatar_url' => '../uploads/avatars/' . $thumb_filename
    ]);
    
} catch(Exception $e) {
    // Если не удалось создать миниатюру, просто сохраняем оригинал
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$filename, $user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Аватар загружен (миниатюра не создана)',
        'avatar_url' => '../uploads/avatars/' . $filename
    ]);
}
?>