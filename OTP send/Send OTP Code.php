<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

function env_value(string $key, string $default = ''): string
{
	$value = getenv($key);
	if ($value === false) {
		return $default;
	}
	return trim((string) $value);
}

function config_value(string $constantName, string $envName, string $default = ''): string
{
	if (defined($constantName)) {
		return trim((string) constant($constantName));
	}
	return env_value($envName, $default);
}

function json_response(int $status, array $payload): void
{
	http_response_code($status);
	echo json_encode($payload);
	exit;
}

function smtp_read_response($socket): string
{
	$response = '';
	while (($line = fgets($socket, 1024)) !== false) {
		$response .= $line;
		if (strlen($line) < 4 || $line[3] === ' ') {
			break;
		}
	}
	return trim($response);
}

function smtp_expect($socket, array $allowedCodes, ?string &$lastResponse = null): bool
{
	$response = smtp_read_response($socket);
	$lastResponse = $response;
	if ($response === '') {
		return false;
	}

	$code = (int) substr($response, 0, 3);
	return in_array($code, $allowedCodes, true);
}

function smtp_send_command($socket, string $command, array $allowedCodes, ?string &$lastResponse = null): bool
{
	fwrite($socket, $command . "\r\n");
	return smtp_expect($socket, $allowedCodes, $lastResponse);
}

function smtp_send_email(string $toEmail, string $subject, string $body, ?string &$error = null): bool
{
	$host = config_value('SMTP_HOST', 'SMTP_HOST', 'smtp.gmail.com');
	$port = (int) config_value('SMTP_PORT', 'SMTP_PORT', '587');
	$username = config_value('SMTP_USER', 'SMTP_USER');
	$password = config_value('SMTP_PASS', 'SMTP_PASS');
	$encryption = strtolower(config_value('SMTP_ENCRYPTION', 'SMTP_ENCRYPTION', 'tls'));
	$fromAddress = config_value('MAIL_FROM_ADDRESS', 'MAIL_FROM_ADDRESS', $username);
	$fromName = config_value('MAIL_FROM_NAME', 'MAIL_FROM_NAME', 'EcoRise');

	if ($username === '' || $password === '' || $fromAddress === '') {
		$error = 'SMTP_USER, SMTP_PASS, and MAIL_FROM_ADDRESS must be configured.';
		return false;
	}

	if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
		$error = 'MAIL_FROM_ADDRESS is not a valid email address.';
		return false;
	}

	$transportHost = $host;
	if ($encryption === 'ssl' || $port === 465) {
		$transportHost = 'ssl://' . $host;
	}

	$socket = @stream_socket_client(
		$transportHost . ':' . $port,
		$errorNumber,
		$errorText,
		15,
		STREAM_CLIENT_CONNECT
	);

	if ($socket === false) {
		$error = 'SMTP connection failed: ' . $errorText . ' (' . $errorNumber . ')';
		return false;
	}

	stream_set_timeout($socket, 20);

	$response = null;
	if (!smtp_expect($socket, [220], $response)) {
		$error = 'SMTP server greeting failed: ' . (string) $response;
		fclose($socket);
		return false;
	}

	$helloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
	if (!smtp_send_command($socket, 'EHLO ' . $helloHost, [250], $response)) {
		$error = 'SMTP EHLO failed: ' . (string) $response;
		fclose($socket);
		return false;
	}

	if ($encryption === 'tls' && $port !== 465) {
		if (!smtp_send_command($socket, 'STARTTLS', [220], $response)) {
			$error = 'SMTP STARTTLS failed: ' . (string) $response;
			fclose($socket);
			return false;
		}

		if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
			$error = 'Unable to enable TLS encryption for SMTP.';
			fclose($socket);
			return false;
		}

		if (!smtp_send_command($socket, 'EHLO ' . $helloHost, [250], $response)) {
			$error = 'SMTP EHLO after TLS failed: ' . (string) $response;
			fclose($socket);
			return false;
		}
	}

	if (!smtp_send_command($socket, 'AUTH LOGIN', [334], $response)) {
		$error = 'SMTP AUTH LOGIN failed: ' . (string) $response;
		fclose($socket);
		return false;
	}

	if (!smtp_send_command($socket, base64_encode($username), [334], $response)) {
		$error = 'SMTP username rejected: ' . (string) $response;
		fclose($socket);
		return false;
	}

	if (!smtp_send_command($socket, base64_encode($password), [235], $response)) {
		$error = 'SMTP password rejected: ' . (string) $response;
		fclose($socket);
		return false;
	}

	if (!smtp_send_command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250], $response)) {
		$error = 'SMTP MAIL FROM failed: ' . (string) $response;
		fclose($socket);
		return false;
	}

	if (!smtp_send_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251], $response)) {
		$error = 'SMTP RCPT TO failed: ' . (string) $response;
		fclose($socket);
		return false;
	}

	if (!smtp_send_command($socket, 'DATA', [354], $response)) {
		$error = 'SMTP DATA command failed: ' . (string) $response;
		fclose($socket);
		return false;
	}

	$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
	$headers = [
		'Date: ' . date('r'),
		'From: ' . $fromName . ' <' . $fromAddress . '>',
		'To: <' . $toEmail . '>',
		'Subject: ' . $encodedSubject,
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: 8bit',
	];

	$data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
	$data = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $data);

	fwrite($socket, $data . "\r\n.\r\n");
	if (!smtp_expect($socket, [250], $response)) {
		$error = 'SMTP message body rejected: ' . (string) $response;
		fclose($socket);
		return false;
	}

	smtp_send_command($socket, 'QUIT', [221], $response);
	fclose($socket);
	return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	json_response(405, [
		'success' => false,
		'message' => 'Only POST requests are allowed.',
	]);
}

$emailInput = trim((string) ($_POST['email'] ?? ''));

if ($emailInput === '' || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
	json_response(422, [
		'success' => false,
		'message' => 'Please provide a valid email address.',
	]);
}

$email = strtolower($emailInput);
$now = time();
$cooldownSeconds = 60;
$otpLifetimeSeconds = 600;

$lastSentAt = (int) ($_SESSION['email_otp_last_sent_at'] ?? 0);
if ($lastSentAt > 0 && ($now - $lastSentAt) < $cooldownSeconds) {
	$waitFor = $cooldownSeconds - ($now - $lastSentAt);
	json_response(429, [
		'success' => false,
		'message' => 'Please wait ' . $waitFor . ' seconds before requesting another OTP.',
	]);
}

$otp = (string) random_int(100000, 999999);

$subject = 'EcoRise Email Verification Code';
$message = "Hello,\n\n"
	. "Your EcoRise verification code is: " . $otp . "\n"
	. "This code will expire in 10 minutes.\n\n"
	. "If you did not request this, please ignore this email.\n\n"
	. "Thanks,\n"
	. "EcoRise Team";

$sendError = null;
$isSent = smtp_send_email($email, $subject, $message, $sendError);

if (!$isSent) {
	error_log('OTP SMTP send failed for ' . $email . ': ' . (string) $sendError);
	json_response(500, [
		'success' => false,
		'message' => 'Failed to send OTP email. Check SMTP settings and try again.',
		'error' => $sendError,
	]);
}

$_SESSION['email_verification'] = [
	'email' => $email,
	'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
	'expires_at' => $now + $otpLifetimeSeconds,
	'verified' => false,
	'attempts' => 0,
];
$_SESSION['email_otp_last_sent_at'] = $now;

json_response(200, [
	'success' => true,
	'message' => 'OTP sent successfully to your email.',
	'expires_in' => $otpLifetimeSeconds,
]);
?>