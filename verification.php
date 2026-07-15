<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/vendor/autoload.php';

use Resend\Resend;

try {

    $data = json_decode(file_get_contents("php://input"), true);

    $recipient = $data['email'] ?? '';
    $code = $data['code'] ?? '';

    $resend = Resend::client(getenv('RESEND_API_KEY'));

    $result = $resend->emails->send([
        'from' => 'onboarding@resend.dev',
        'to' => [$recipient],
        'subject' => 'Teacher Evaluation Verification Code',
        'html' => "
            <h2>Teacher Evaluation System</h2>
            <p>Your verification code is:</p>
            <h1>$code</h1>
            <p>This code will expire soon.</p>
        "
    ]);

    echo json_encode([
        "success" => true,
        "result" => $result
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}