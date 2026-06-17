<?php
// api/common/mailer.php
// Gmail SMTP sender based on PHPMailer.

function wu_mailer_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return trim((string) $value);
}

function wu_is_mailer_ready(): bool
{
    return wu_mailer_env('NSAMS_SMTP_HOST') !== ''
        && wu_mailer_env('NSAMS_SMTP_USER') !== ''
        && wu_mailer_env('NSAMS_SMTP_PASS') !== ''
        && wu_mailer_env('NSAMS_MAIL_FROM') !== '';
}

function wu_send_gmail_notification(string $toEmail, string $toName, string $subject, string $htmlBody, ?string &$error = null): bool
{
    $autoload = __DIR__ . '/../../../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        $error = 'PHPMailer 未安裝（vendor/autoload.php 不存在）';
        return false;
    }
    require_once $autoload;

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        $error = '找不到 PHPMailer 類別';
        return false;
    }

    if (!wu_is_mailer_ready()) {
        $error = 'SMTP 環境變數未設定完整';
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $host = wu_mailer_env('NSAMS_SMTP_HOST', 'smtp.gmail.com');
        $port = (int) wu_mailer_env('NSAMS_SMTP_PORT', '587');
        $secure = strtolower(wu_mailer_env('NSAMS_SMTP_SECURE', 'tls'));
        $from = wu_mailer_env('NSAMS_MAIL_FROM');
        $fromName = wu_mailer_env('NSAMS_MAIL_FROM_NAME', 'NSAMS 通知系統');

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = wu_mailer_env('NSAMS_SMTP_USER');
        $mail->Password = wu_mailer_env('NSAMS_SMTP_PASS');
        $mail->Port = $port;
        $mail->CharSet = 'UTF-8';

        if ($secure === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($from, $fromName);
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        return false;
    }
}
