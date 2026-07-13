<?php
/**
 * EcoRise - Process Sign In
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('signin.php', 'Invalid session. Please try again.', 'error');
    }

    $email = strtolower(sanitize($_POST['email']));
    $password = $_POST['password'] ?? '';
    $signin_email_verified = $_POST['signin_email_verified'] ?? '0';

    // Check if fields are empty
    if (empty($email) || empty($password)) {
        redirect('signin.php', 'Please fill in all fields.', 'error');
    }

    try {
        // Fetch user from DB
        $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $isAdminBypass = $email === 'admin@ecorise.com' && ($user['role'] ?? '') === 'admin';

            if (!$isAdminBypass) {
                $verification = $_SESSION['email_verification'] ?? null;
                $otpPassed = is_array($verification)
                    && ($verification['verified'] ?? false) === true
                    && !empty($verification['email'])
                    && strtolower((string) $verification['email']) === $email
                    && ((int) ($verification['expires_at'] ?? 0) >= time())
                    && $signin_email_verified === '1';

                if (!$otpPassed) {
                    redirect('signin.php', 'Please verify OTP before signing in.', 'error');
                }
            }

            if (isset($user['email_verified']) && (int) $user['email_verified'] !== 1) {
                $verifyStmt = $pdo->prepare('UPDATE users SET email_verified = 1, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?');
                $verifyStmt->execute([(int) $user['id']]);

                $user['email_verified'] = 1;
            }

            unset($_SESSION['email_verification'], $_SESSION['email_otp_last_sent_at']);

            // Log in the user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            try {
                // Avoid creating a login notification on every refresh by limiting to once every 12 hours.
                $recent_stmt = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = 'system_login' AND created_at >= (NOW() - INTERVAL 12 HOUR) LIMIT 1");
                $recent_stmt->execute([(int) $user['id']]);
                $recent = $recent_stmt->fetchColumn();

                if (!$recent) {
                    create_notification(
                        $pdo,
                        (int) $user['id'],
                        'system_login',
                        'Welcome back',
                        'You are signed in successfully. Check your notification panel for updates.',
                        $user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php',
                        'fa-right-to-bracket'
                    );
                }
            } catch (Throwable $notifyError) {
                error_log('Notification create failed (signin): ' . $notifyError->getMessage());
            }

            // Clear CSRF after success
            unset($_SESSION['csrf_token']);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('admin/index.php', 'Welcome Admin!', 'success');
            } else {
                redirect('dashboard.php', "Welcome back, " . $user['full_name'] . "!", 'success');
            }
        } else {
            redirect('signin.php', 'Invalid email or password.', 'error');
        }
    } catch (PDOException $e) {
        redirect('signin.php', 'Something went wrong. Error: ' . $e->getMessage(), 'error');
    }
} else {
    redirect('signin.php');
}
?>
