<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $recipient = $data['email'] ?? '';
    $code = $data['code'] ?? '';

    if (!$recipient || !$code) {
        echo json_encode(['success' => false, 'message' => 'Email and code required']);
        exit;
    }

    // SMTP Config
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sharmainepagador@gmail.com';
    $mail->Password = 'dxch fixs btve myot';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('sharmainepagador@gmail.com', 'TeacherEval');
    $mail->addAddress($recipient);

    $mail->isHTML(true);
    $mail->Subject = 'Your Verification Code';
    $mail->Body = "<p>Hello,</p><p>Your verification code is: <strong>$code</strong></p>";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Email sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
}
?>
