<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

/**
 * Sends an email using PHPMailer and Gmail SMTP.
 *
 * @param string $to Recipient email.
 * @param string $subject Email subject.
 * @param string $htmlBody HTML content.
 * @param string|null &$errorMsg Set to error details if sending fails.
 * @return bool True if sent, false otherwise.
 */
function sendEmail($to, $subject, $htmlBody, &$errorMsg = null) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Server configuration
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER') ?: 'hasalagayasritha.2003@gmail.com';
        $mail->Password   = getenv('SMTP_PASS') ?: 'cltv wzdq zujo uicr';
        $secure           = getenv('SMTP_SECURE') ?: 'tls';
        $mail->SMTPSecure = ($secure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: 587;

        // Bypass SSL certificate verification for local SMTP environments
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Characters & encoding
        $mail->CharSet = 'UTF-8';

        // Sender & Recipient
        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'hasalagayasritha.2003@gmail.com';
        $fromName  = getenv('SMTP_FROM_NAME') ?: 'LifeLine Blood Bank';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo;
        return false;
    }
}
