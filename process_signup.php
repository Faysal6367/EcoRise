<?php
/**
 * EcoRise - Process Sign Up
 */
require_once 'config.php';

function store_signup_profile_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Profile image upload failed. Please try again.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Profile image must be JPG, PNG, or WEBP.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Profile image must be under 5 MB.');
    }

    $directory = 'assets/profile_images/';
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to prepare profile image folder.');
    }

    $target = $directory . 'user_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to save the profile image.');
    }

    return $target;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('signup.php', 'Invalid session. Please try again.', 'error');
    }

    $full_name = sanitize($_POST['full_name']);
    $email = strtolower(sanitize($_POST['email']));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email_verified_flag = $_POST['email_verified'] ?? '0';
    $latitude_input = trim((string) ($_POST['latitude'] ?? ''));
    $longitude_input = trim((string) ($_POST['longitude'] ?? ''));

    // Simple validation
    if (empty($full_name) || empty($email) || empty($password)) {
        redirect('signup.php', 'Please fill in all required fields.', 'error');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('signup.php', 'Invalid email address.', 'error');
    }

    $verification = $_SESSION['email_verification'] ?? null;
    $isVerified = is_array($verification)
        && ($verification['verified'] ?? false) === true
        && !empty($verification['email'])
        && strtolower((string) $verification['email']) === $email
        && ((int) ($verification['expires_at'] ?? 0) >= time())
        && $email_verified_flag === '1';

    if (!$isVerified) {
        redirect('signup.php', 'Please verify your email with OTP before signing up.', 'error');
    }

    if ($password !== $confirm_password) {
        redirect('signup.php', 'Passwords do not match.', 'error');
    }

    if (strlen($password) < 6) {
        redirect('signup.php', 'Password must be at least 6 characters.', 'error');
    }

    $latitude = null;
    $longitude = null;

    if ($latitude_input !== '') {
        if (!is_numeric($latitude_input) || (float) $latitude_input < -90 || (float) $latitude_input > 90) {
            redirect('signup.php', 'Invalid latitude value received.', 'error');
        }
        $latitude = round((float) $latitude_input, 7);
    }

    if ($longitude_input !== '') {
        if (!is_numeric($longitude_input) || (float) $longitude_input < -180 || (float) $longitude_input > 180) {
            redirect('signup.php', 'Invalid longitude value received.', 'error');
        }
        $longitude = round((float) $longitude_input, 7);
    }

    if (($latitude === null) xor ($longitude === null)) {
        redirect('signup.php', 'Both latitude and longitude are required when sharing location.', 'error');
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            redirect('signup.php', 'An account already exists with this email.', 'error');
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $profile_image_path = store_signup_profile_image($_FILES['profile_image'] ?? []);

        $pdo->beginTransaction();

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, email_verified, email_verified_at, profile_image_path, latitude, longitude) VALUES (?, ?, ?, 1, NOW(), ?, ?, ?)");
        $stmt->execute([$full_name, $email, $password_hash, $profile_image_path, $latitude, $longitude]);

        $user_id = (int) $pdo->lastInsertId();
        $userStmt = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([$user_id]);
        $newUser = $userStmt->fetch();

        $pdo->commit();

        if ($newUser) {
            $_SESSION['user_id'] = $newUser['id'];
            $_SESSION['user_name'] = $newUser['full_name'];
            $_SESSION['user_email'] = $newUser['email'];
            $_SESSION['user_role'] = $newUser['role'];
            unset($_SESSION['csrf_token']);
            unset($_SESSION['email_verification'], $_SESSION['email_otp_last_sent_at']);
        }

        // Go straight to the dashboard so the uploaded photo is visible immediately.
        redirect('dashboard.php', 'Account created successfully. Welcome to your dashboard.', 'success');

    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirect('signup.php', $e->getMessage(), 'error');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirect('signup.php', 'Registration failed. Error: ' . $e->getMessage(), 'error');
    }
} else {
    redirect('signup.php');
}
?>
