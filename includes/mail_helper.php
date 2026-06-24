<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hasalagayasritha.2003@gmail.com';
        $mail->Password   = 'cltv wzdq zujo uicr'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Characters & encoding
        $mail->CharSet = 'UTF-8';

        // Sender & Recipient
        $mail->setFrom('hasalagayasritha.2003@gmail.com', 'LifeLine Blood Bank');
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
