<?php
// Конфигурация почты для TimeWeb
define('MAIL_HOST', 'smtp.timeweb.ru');
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'admin@deepworld.site');
define('MAIL_PASSWORD', 'lfybbkdkflbvbhjdbx0608%');
define('MAIL_FROM', 'admin@deepworld.site');
define('MAIL_FROM_NAME', 'DeepWorld');
define('MAIL_SECURE', 'ssl'); // или 'tls' для порта 587

// Тестовый email для проверки
define('TEST_EMAIL', 'ваш_личный_email@mail.ru'); // Укажите сюда свой email для тестов

// Функция для отправки почты
function sendEmail($to, $subject, $body, $isHTML = true) {
    require_once 'PHPMailer/PHPMailer.php';
    require_once 'PHPMailer/SMTP.php';
    require_once 'PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Настройки сервера
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port = MAIL_PORT;
        
        // Кодировка
        $mail->CharSet = 'UTF-8';
        
        // Отправитель
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        
        // Получатель
        if (is_array($to)) {
            foreach ($to as $email => $name) {
                $mail->addAddress($email, $name);
            }
        } else {
            $mail->addAddress($to);
        }
        
        // Формат письма
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Альтернативный текст для клиентов без HTML
        if ($isHTML) {
            $mail->AltBody = strip_tags($body);
        }
        
        // Отправка
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Ошибка отправки почты: " . $mail->ErrorInfo);
        return false;
    }
}

// Тестовая функция отправки
function testEmailSending() {
    $to = TEST_EMAIL;
    $subject = 'Тест отправки почты с DeepWorld';
    $body = '<h1>Тестовое письмо</h1>
            <p>Если вы видите это письмо, значит отправка почты с сайта DeepWorld работает корректно!</p>
            <p>Отправлено: ' . date('d.m.Y H:i:s') . '</p>';
    
    if (sendEmail($to, $subject, $body)) {
        return 'Письмо успешно отправлено на ' . TEST_EMAIL;
    } else {
        return 'Ошибка отправки письма';
    }
}
?>