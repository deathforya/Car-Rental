<?php
// Simple mailer helper: prefer PHPMailer (composer) with SMTP config, otherwise fall back to mail().
// Usage: send_email($to, $subject, $body, $altBody = '')

function send_email($to, $subject, $body, $altBody = '') {
    // Try to use PHPMailer if available
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            // Load optional SMTP config
            $cfg = [];
            $cfgFile = __DIR__ . '/../config/mail.php';
            if (file_exists($cfgFile)) {
                $cfg = include $cfgFile;
            }

            if (!empty($cfg) && !empty($cfg['smtp_host'])) {
                $mail->isSMTP();
                $mail->Host = $cfg['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $cfg['smtp_user'] ?? '';
                $mail->Password = $cfg['smtp_pass'] ?? '';
                $mail->SMTPSecure = $cfg['smtp_crypto'] ?? 'tls';
                $mail->Port = $cfg['smtp_port'] ?? 587;
                if (!empty($cfg['from_address'])) {
                    $mail->setFrom($cfg['from_address'], $cfg['from_name'] ?? 'DriveNow');
                }
            }

            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if ($altBody !== '') $mail->AltBody = $altBody;
            $mail->send();
            return true;
        } catch (Exception $e) {
            // fallback to mail()
        }
    }

    // fallback simple mail
    $headers = "From: no-reply@drivenow.local" . "\r\n" . "Content-Type: text/plain; charset=UTF-8";
    return @mail($to, $subject, $body, $headers);
}
