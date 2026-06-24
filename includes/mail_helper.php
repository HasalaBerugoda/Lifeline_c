<?php
// Helper file for sending HTML emails using PHPMailer and Gmail SMTP

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

/**
 * Send an HTML email via Gmail SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $htmlBody HTML content of the email
 * @return array Status and error details
 */
function sendEmailPHPMailer($to, $subject, $htmlBody) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hasalagayasritha.2003@gmail.com';
        $mail->Password   = 'cltv wzdq zujo uicr';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Bypass local SSL peer verification issues in development (missing CA bundles in XAMPP)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Force UTF-8 encoding
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('hasalagayasritha.2003@gmail.com', 'LifeLine Blood Bank');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();
        return ['status' => true];
    } catch (Exception $e) {
        return [
            'status' => false,
            'error' => $mail->ErrorInfo
        ];
    }
}
