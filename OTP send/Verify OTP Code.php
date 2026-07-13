<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

function verify_json_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    verify_json_response(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.',
    ]);
}

$emailInput = trim((string) ($_POST['email'] ?? ''));
$otpInput = preg_replace('/\D+/', '', (string) ($_POST['otp'] ?? ''));

if ($emailInput === '' || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
    verify_json_response(422, [
        'success' => false,
        'message' => 'Please provide a valid email address.',
    ]);
}

if (strlen($otpInput) !== 6) {
    verify_json_response(422, [
        'success' => false,
        'message' => 'OTP must be exactly 6 digits.',
    ]);
}

$verification = $_SESSION['email_verification'] ?? null;
if (!is_array($verification)) {
    verify_json_response(400, [
        'success' => false,
        'message' => 'No OTP request found. Please request a new code.',
    ]);
}

$storedEmail = strtolower((string) ($verification['email'] ?? ''));
$email = strtolower($emailInput);
if ($storedEmail !== $email) {
    verify_json_response(400, [
        'success' => false,
        'message' => 'This OTP was generated for a different email address.',
    ]);
}

if ((int) ($verification['expires_at'] ?? 0) < time()) {
    unset($_SESSION['email_verification']);
    verify_json_response(400, [
        'success' => false,
        'message' => 'OTP has expired. Please request a new code.',
    ]);
}

$attempts = (int) ($verification['attempts'] ?? 0);
if ($attempts >= 5) {
    unset($_SESSION['email_verification']);
    verify_json_response(429, [
        'success' => false,
        'message' => 'Too many failed attempts. Request a new OTP.',
    ]);
}

$otpHash = (string) ($verification['otp_hash'] ?? '');
if ($otpHash === '' || !password_verify($otpInput, $otpHash)) {
    $_SESSION['email_verification']['attempts'] = $attempts + 1;
    verify_json_response(401, [
        'success' => false,
        'message' => 'Invalid OTP. Please try again.',
        'remaining_attempts' => max(0, 5 - ($attempts + 1)),
    ]);
}

$_SESSION['email_verification']['verified'] = true;
$_SESSION['email_verification']['attempts'] = 0;

verify_json_response(200, [
    'success' => true,
    'message' => 'Email verified successfully.',
]);
