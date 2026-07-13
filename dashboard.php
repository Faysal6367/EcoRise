<?php
declare(strict_types=1);

/**
 * EcoRise - User Dashboard
 *
 * Displays profile details, account insights, and support activity.
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to view your dashboard.', 'error');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$csrfToken = generate_csrf_token();
$divisionOptions = ['Dhaka', 'Khulna', 'Chittagong', 'Rajshahi', 'Sylhet', 'Rangpur', 'Mymensingh', 'Barisal'];
$message = $_SESSION['msg'] ?? null;
$messageType = $_SESSION['msg_type'] ?? 'success';
unset($_SESSION['msg'], $_SESSION['msg_type']);

if (!function_exists('store_uploaded_verification_image')) {
    function store_uploaded_verification_image(array $file, string $prefix): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload the required NID image.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('NID image must be JPG, PNG, or WEBP.');
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('NID image must be under 5 MB.');
        }

        $directory = 'assets/verifications/';
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to prepare verification upload folder.');
        }

        $target = $directory . $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Failed to store the NID image.');
        }

        return $target;
    }
}

if (!function_exists('store_captured_face_image')) {
    function store_captured_face_image(string $dataUrl, string $prefix): string
    {
        if (!preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/', $dataUrl, $matches)) {
            throw new RuntimeException('Please capture a face photo with the camera.');
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            throw new RuntimeException('Face capture could not be processed.');
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw new RuntimeException('Captured face image must be under 5 MB.');
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $directory = 'assets/verifications/';
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to prepare verification upload folder.');
        }

        $target = $directory . $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (file_put_contents($target, $binary) === false) {
            throw new RuntimeException('Failed to store the captured face image.');
        }

        return $target;
    }
}

if (!function_exists('store_uploaded_profile_image')) {
    function store_uploaded_profile_image(array $file, int $userId): ?string
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

        $target = $directory . 'user_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Failed to save the profile image.');
        }

        return $target;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_profile') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('dashboard.php#profile-form', 'Your session expired. Please try again.', 'error');
    }

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^\d+\-\s]/', '', (string) ($_POST['phone'] ?? ''));
    $division = trim((string) ($_POST['division'] ?? ''));
    $district = trim((string) ($_POST['district'] ?? ''));
    $addressLine = trim((string) ($_POST['address_line'] ?? ''));
    $latitudeInput = trim((string) ($_POST['latitude'] ?? ''));
    $longitudeInput = trim((string) ($_POST['longitude'] ?? ''));

    if ($fullName === '' || !$email) {
        redirect('dashboard.php#profile-form', 'Full name and a valid email address are required.', 'error');
    }

    if ($division !== '' && !in_array($division, $divisionOptions, true)) {
        redirect('dashboard.php#profile-form', 'Please choose a valid division.', 'error');
    }

    $latitude = null;
    $longitude = null;

    if ($latitudeInput !== '') {
        if (!is_numeric($latitudeInput) || (float) $latitudeInput < -90 || (float) $latitudeInput > 90) {
            redirect('dashboard.php#profile-form', 'Latitude must be a valid number between -90 and 90.', 'error');
        }
        $latitude = round((float) $latitudeInput, 7);
    }

    if ($longitudeInput !== '') {
        if (!is_numeric($longitudeInput) || (float) $longitudeInput < -180 || (float) $longitudeInput > 180) {
            redirect('dashboard.php#profile-form', 'Longitude must be a valid number between -180 and 180.', 'error');
        }
        $longitude = round((float) $longitudeInput, 7);
    }

    if (($latitude === null) xor ($longitude === null)) {
        redirect('dashboard.php#profile-form', 'Please provide both latitude and longitude together.', 'error');
    }

    try {
        $existingProfileStmt = $pdo->prepare('SELECT profile_image_path FROM users WHERE id = ? LIMIT 1');
        $existingProfileStmt->execute([$userId]);
        $existingProfile = $existingProfileStmt->fetch();
        $profileImagePath = is_array($existingProfile) ? (string) ($existingProfile['profile_image_path'] ?? '') : '';

        $uploadedProfileImagePath = store_uploaded_profile_image($_FILES['profile_image'] ?? [], $userId);
        if ($uploadedProfileImagePath !== null) {
            if ($profileImagePath !== '' && $profileImagePath !== $uploadedProfileImagePath && file_exists($profileImagePath)) {
                @unlink($profileImagePath);
            }
            $profileImagePath = $uploadedProfileImagePath;
        }

        $updateProfileStmt = $pdo->prepare(
            'UPDATE users
             SET full_name = ?, email = ?, phone = ?, profile_image_path = ?, division = ?, district = ?, address_line = ?, latitude = ?, longitude = ?
             WHERE id = ?'
        );

        $updateProfileStmt->execute([
            sanitize($fullName),
            $email,
            sanitize($phone),
            $profileImagePath !== '' ? $profileImagePath : null,
            sanitize($division),
            sanitize($district),
            sanitize($addressLine),
            $latitude,
            $longitude,
            $userId,
        ]);
    } catch (RuntimeException $exception) {
        redirect('dashboard.php#profile-form', $exception->getMessage(), 'error');
    }

    $_SESSION['user_name'] = sanitize($fullName);
    redirect('dashboard.php#profile-form', 'Your profile has been updated successfully.', 'success');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'submit_verification') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('dashboard.php#verification-form', 'Your session expired. Please try again.', 'error');
    }

    $nidNumber = preg_replace('/\D+/', '', (string) ($_POST['verification_nid_number'] ?? ''));
    if (!preg_match('/^(?:\d{10}|\d{13}|\d{17})$/', $nidNumber)) {
        redirect('dashboard.php#verification-form', 'Bangladesh NID must contain exactly 10, 13, or 17 digits.', 'error');
    }

    $faceCapture = trim((string) ($_POST['face_capture_data'] ?? ''));

    try {
        $nidImagePath = store_uploaded_verification_image($_FILES['nid_image'] ?? [], 'nid_' . $userId);
        $faceImagePath = store_captured_face_image($faceCapture, 'face_' . $userId);

        $existingVerificationStmt = $pdo->prepare('SELECT nid_image_path, face_image_path FROM user_verifications WHERE user_id = ?');
        $existingVerificationStmt->execute([$userId]);
        $existingVerification = $existingVerificationStmt->fetch();

        $verificationStmt = $pdo->prepare(
            'INSERT INTO user_verifications (user_id, nid_number, nid_image_path, face_image_path, status, rejection_reason, submitted_at, verified_at)
             VALUES (?, ?, ?, ?, ?, NULL, NOW(), NULL)
             ON DUPLICATE KEY UPDATE
             nid_number = VALUES(nid_number),
             nid_image_path = VALUES(nid_image_path),
             face_image_path = VALUES(face_image_path),
             status = VALUES(status),
             rejection_reason = NULL,
             submitted_at = NOW(),
             verified_at = NULL'
        );
        $verificationStmt->execute([$userId, $nidNumber, $nidImagePath, $faceImagePath, 'submitted']);

        if ($existingVerification) {
            foreach (['nid_image_path', 'face_image_path'] as $pathField) {
                $oldPath = $existingVerification[$pathField] ?? '';
                if ($oldPath && is_string($oldPath) && $oldPath !== $nidImagePath && $oldPath !== $faceImagePath && file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        redirect('dashboard.php#verification-form', 'Volunteer verification submitted successfully.', 'success');
    } catch (RuntimeException $exception) {
        redirect('dashboard.php#verification-form', $exception->getMessage(), 'error');
    }
}

$userStmt = $pdo->prepare(
    'SELECT id, full_name, email, role, volunteer_status, phone, profile_image_path, division, district, address_line, latitude, longitude, created_at
     FROM users
     WHERE id = ?'
);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    redirect('logout.php', 'Unable to load your account.', 'error');
}

$volunteerStatus = $user['volunteer_status'] ?? 'none';

$verificationStmt = $pdo->prepare(
    'SELECT nid_number, nid_image_path, face_image_path, status, rejection_reason, submitted_at, verified_at
     FROM user_verifications
     WHERE user_id = ?'
);
$verificationStmt->execute([$userId]);
$verification = $verificationStmt->fetch();
$verificationStatus = $verification['status'] ?? 'not_submitted';

$stmtTotal = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM donations WHERE user_id = ?');
$stmtTotal->execute([$userId]);
$totalDonated = (float) ($stmtTotal->fetch()['total'] ?? 0);

$stmtHistory = $pdo->prepare(
    'SELECT d.amount, d.created_at, c.title, c.id AS campaign_id, c.image_path
     FROM donations d
     JOIN campaigns c ON d.campaign_id = c.id
     WHERE d.user_id = ?
     ORDER BY d.created_at DESC'
);
$stmtHistory->execute([$userId]);
$history = $stmtHistory->fetchAll();

$countSupported = count(array_unique(array_column($history, 'campaign_id')));

$stmtOwn = $pdo->prepare(
    'SELECT id, title, status, raised_amount, target_amount, division, district, image_path, created_at
     FROM campaigns
     WHERE created_by = ?
     ORDER BY created_at DESC'
);
$stmtOwn->execute([$userId]);
$myProjects = $stmtOwn->fetchAll();
$countLaunched = count($myProjects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EcoRise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --eco-900: #0f172a;
            --eco-500: #10b981;
            --eco-100: #dcfce7;
            --eco-border: rgba(15, 23, 42, 0.08);
        }

        body {
            background:
                radial-gradient(900px 480px at top right, rgba(52, 211, 153, 0.14), transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #eefbf4 100%);
            color: var(--eco-900);
        }

        .dashboard-nav {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--eco-border);
        }

        .brand-mark {
            color: var(--eco-500);
            font-weight: 800;
            text-decoration: none;
        }

        .hero-panel,
        .surface-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--eco-border);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }

        .hero-panel {
            background: linear-gradient(135deg, #0f172a 0%, #14532d 45%, #10b981 100%);
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        .hero-panel h1 {
            color: #f8fffb !important;
            line-height: 1.02;
            letter-spacing: -0.03em;
            text-shadow: 0 10px 26px rgba(2, 6, 23, 0.24);
            max-width: 13ch;
        }

        .hero-panel .text-white-50,
        .hero-panel p {
            color: rgba(241, 245, 249, 0.92) !important;
        }

        .hero-panel .badge {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #0f766e !important;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.18);
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto -80px -80px auto;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .stat-card {
            border-radius: 20px;
            border: 1px solid var(--eco-border);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.05);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: var(--eco-100);
            color: #047857;
        }

        .table-modern thead th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            border-bottom-color: rgba(148, 163, 184, 0.24);
        }

        .table-modern tbody td {
            vertical-align: middle;
            border-color: rgba(148, 163, 184, 0.18);
        }

        .project-thumb {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            object-fit: cover;
        }

        .badge-soft {
            background: #ecfdf5;
            color: #047857;
        }

        .profile-summary {
            background: linear-gradient(135deg, #ecfdf5 0%, #f8fafc 100%);
            border-radius: 20px;
            border: 1px solid rgba(16, 185, 129, 0.14);
        }

        .profile-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .profile-avatar {
            width: 78px;
            height: 78px;
            border-radius: 24px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.78);
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.16);
            background: rgba(255, 255, 255, 0.16);
        }

        .profile-avatar--fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.4rem;
            color: #0f766e;
            background: linear-gradient(135deg, #dcfce7 0%, #dbeafe 100%);
        }

        .section-title {
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .verification-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #f8fafc 100%);
            border: 1px solid rgba(16, 185, 129, 0.16);
            border-radius: 20px;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            font-weight: 700;
            font-size: 0.92rem;
        }

        .verification-badge.is-verified {
            background: #dcfce7;
            color: #166534;
        }

        .verification-badge.is-submitted {
            background: #fef3c7;
            color: #92400e;
        }

        .verification-badge.is-rejected,
        .verification-badge.is-not-submitted {
            background: #fee2e2;
            color: #991b1b;
        }

        .capture-frame {
            background: #0f172a;
            border-radius: 18px;
            overflow: hidden;
            min-height: 240px;
            position: relative;
        }

        .capture-frame video,
        .capture-frame canvas,
        .capture-frame img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            display: block;
        }

        .capture-placeholder {
            min-height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.75);
            text-align: center;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <?php render_public_nav('dashboard'); ?>

    <main class="container py-4 py-lg-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'); ?> border-0 shadow-sm rounded-4 mb-4" role="alert">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <section class="hero-panel p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4 position-relative">
                <div class="col-lg-8">
                    <span class="badge rounded-pill text-bg-light text-success mb-3">Impact dashboard</span>
                    <h1 class="display-6 fw-bold mb-3">Welcome back, <?php echo e($user['full_name']); ?>.</h1>
                    <p class="fs-5 text-white-50 mb-4">Track your giving, manage your profile, and keep your location data ready for personalized impact mapping.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-light btn-lg rounded-pill px-4" href="#profile-form">Update Profile</a>
                        <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="index.php#campaigns">Explore Campaigns</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="profile-summary p-4 text-dark">
                        <div class="profile-identity">
                            <?php if (!empty($user['profile_image_path'])): ?>
                                <img class="profile-avatar" src="<?php echo e((string) $user['profile_image_path']); ?>" alt="<?php echo e((string) $user['full_name']); ?>">
                            <?php else: ?>
                                <div class="profile-avatar profile-avatar--fallback"><?php echo e(strtoupper(substr((string) $user['full_name'], 0, 1))); ?></div>
                            <?php endif; ?>
                            <div>
                                <div class="small text-uppercase text-secondary fw-semibold mb-1">Account snapshot</div>
                                <div class="h5 fw-bold mb-1"><?php echo e((string) $user['full_name']); ?></div>
                                <div class="text-secondary small"><?php echo e((string) $user['email']); ?></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Role</span>
                            <strong><?php echo e(ucfirst((string) $user['role'])); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Volunteer</span>
                            <strong><?php echo e(ucfirst((string) $volunteerStatus)); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Verification</span>
                            <strong class="text-end">
                                <?php if ($verificationStatus === 'verified'): ?>
                                    <span class="verification-badge is-verified"><i class="fas fa-circle-check"></i> Verified</span>
                                <?php elseif ($verificationStatus === 'submitted'): ?>
                                    <span class="verification-badge is-submitted"><i class="fas fa-hourglass-half"></i> Submitted</span>
                                <?php elseif ($verificationStatus === 'rejected'): ?>
                                    <span class="verification-badge is-rejected"><i class="fas fa-circle-xmark"></i> Rejected</span>
                                <?php else: ?>
                                    <span class="verification-badge is-not-submitted"><i class="fas fa-circle-exclamation"></i> Not verified</span>
                                <?php endif; ?>
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Member since</span>
                            <strong><?php echo e(date('M Y', strtotime((string) $user['created_at']))); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 g-lg-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 p-4">
                    <div class="stat-icon mb-3"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="text-secondary small text-uppercase fw-semibold">Total contributed</div>
                    <div class="h3 mb-0 mt-2">BDT <?php echo number_format($totalDonated, 2); ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 p-4">
                    <div class="stat-icon mb-3"><i class="fas fa-seedling"></i></div>
                    <div class="text-secondary small text-uppercase fw-semibold">Projects supported</div>
                    <div class="h3 mb-0 mt-2"><?php echo $countSupported; ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 p-4">
                    <div class="stat-icon mb-3"><i class="fas fa-bullhorn"></i></div>
                    <div class="text-secondary small text-uppercase fw-semibold">Projects launched</div>
                    <div class="h3 mb-0 mt-2"><?php echo $countLaunched; ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 p-4">
                    <div class="stat-icon mb-3"><i class="fas fa-location-dot"></i></div>
                    <div class="text-secondary small text-uppercase fw-semibold">Map status</div>
                    <div class="h5 mb-0 mt-2"><?php echo ($user['latitude'] !== null && $user['longitude'] !== null) ? 'Coordinates saved' : 'Coordinates missing'; ?></div>
                </div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-lg-5">
                <div id="profile-form" class="surface-card p-4 p-lg-5 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h2 class="section-title h3 mb-2">Profile Update</h2>
                            <p class="text-secondary mb-0">Keep your contact details and map coordinates current.</p>
                        </div>
                        <span class="badge badge-soft rounded-pill px-3 py-2">Secure form</span>
                    </div>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="form_action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="full_name">Full name</label>
                            <input class="form-control form-control-lg rounded-4" id="full_name" name="full_name" type="text" required value="<?php echo e((string) $user['full_name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="email">Email address</label>
                            <input class="form-control form-control-lg rounded-4" id="email" name="email" type="email" required value="<?php echo e((string) $user['email']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="phone">Phone</label>
                            <input class="form-control rounded-4" id="phone" name="phone" type="text" inputmode="tel" value="<?php echo e((string) ($user['phone'] ?? '')); ?>" placeholder="+8801XXXXXXXXX">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="profile_image">Profile picture</label>
                            <input class="form-control rounded-4" id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Upload JPG, PNG, or WEBP (max 5 MB). Works for all accounts, including admin.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="division">Division</label>
                                <select class="form-select rounded-4" id="division" name="division">
                                    <option value="">Select division</option>
                                    <?php foreach ($divisionOptions as $division): ?>
                                        <option value="<?php echo e($division); ?>" <?php echo (($user['division'] ?? '') === $division) ? 'selected' : ''; ?>><?php echo e($division); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="district">District</label>
                                <input class="form-control rounded-4" id="district" name="district" type="text" value="<?php echo e((string) ($user['district'] ?? '')); ?>" placeholder="e.g. Dhaka">
                            </div>
                        </div>

                        <div class="mt-3 mb-3">
                            <label class="form-label fw-semibold" for="address_line">Address</label>
                            <textarea class="form-control rounded-4" id="address_line" name="address_line" rows="3" placeholder="House, road, area"><?php echo e((string) ($user['address_line'] ?? '')); ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="latitude">Latitude</label>
                                <input class="form-control rounded-4" id="latitude" name="latitude" type="text" inputmode="decimal" value="<?php echo e($user['latitude'] !== null ? (string) $user['latitude'] : ''); ?>" placeholder="23.8103317">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="longitude">Longitude</label>
                                <input class="form-control rounded-4" id="longitude" name="longitude" type="text" inputmode="decimal" value="<?php echo e($user['longitude'] !== null ? (string) $user['longitude'] : ''); ?>" placeholder="90.4125181">
                            </div>
                        </div>

                        <div class="form-text mt-2">Save both coordinates to enable personalized Google Maps centering on the homepage.</div>

                        <button class="btn btn-success btn-lg rounded-pill px-4 mt-4" type="submit">
                            <i class="fas fa-floppy-disk me-2"></i>Save profile
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div id="verification-form" class="surface-card p-4 p-lg-5 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                        <div>
                            <h2 class="section-title h3 mb-2">Volunteer Verification</h2>
                            <p class="text-secondary mb-0">Submit your NID and a live face capture to show a verification tick in your profile.</p>
                        </div>
                        <?php if ($verificationStatus === 'verified'): ?>
                            <span class="verification-badge is-verified"><i class="fas fa-badge-check"></i> Tick mark active</span>
                        <?php elseif ($verificationStatus === 'submitted'): ?>
                            <span class="verification-badge is-submitted"><i class="fas fa-hourglass-half"></i> Under review</span>
                        <?php elseif ($verificationStatus === 'rejected'): ?>
                            <span class="verification-badge is-rejected"><i class="fas fa-rotate-right"></i> Re-submit required</span>
                        <?php else: ?>
                            <span class="verification-badge is-not-submitted"><i class="fas fa-circle-exclamation"></i> Verification missing</span>
                        <?php endif; ?>
                    </div>

                    <div class="verification-card p-4 mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="small text-uppercase text-secondary fw-semibold mb-1">Profile badge</div>
                                <div class="fw-semibold">
                                    <?php echo $verificationStatus === 'verified' ? 'Tick mark shown' : 'Tick mark hidden'; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-uppercase text-secondary fw-semibold mb-1">Last submitted</div>
                                <div class="fw-semibold"><?php echo !empty($verification['submitted_at']) ? e(date('M d, Y h:i A', strtotime((string) $verification['submitted_at']))) : 'Not submitted'; ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-uppercase text-secondary fw-semibold mb-1">Status</div>
                                <div class="fw-semibold"><?php echo e(ucwords(str_replace('_', ' ', $verificationStatus))); ?></div>
                            </div>
                        </div>
                        <?php if ($verificationStatus === 'rejected' && !empty($verification['rejection_reason'])): ?>
                            <div class="alert alert-danger rounded-4 border-0 shadow-sm mt-3 mb-0"><?php echo e((string) $verification['rejection_reason']); ?></div>
                        <?php endif; ?>
                    </div>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="form_action" value="submit_verification">
                        <input type="hidden" name="face_capture_data" id="face_capture_data" value="">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="verification_nid_number">Bangladesh NID Number</label>
                                <input class="form-control rounded-4" id="verification_nid_number" name="verification_nid_number" type="text" inputmode="numeric" pattern="(?:\d{10}|\d{13}|\d{17})" maxlength="17" placeholder="10, 13, or 17 digits" value="<?php echo e((string) ($verification['nid_number'] ?? '')); ?>" required>
                                <div class="form-text">This is used for volunteer identity verification only.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="nid_image">NID Image</label>
                                <input class="form-control rounded-4" id="nid_image" name="nid_image" type="file" accept="image/jpeg,image/png,image/webp" required>
                                <div class="form-text">Upload a clear photo of your NID card front side.</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="form-label fw-semibold d-block">Current Face Verification by Camera</label>
                            <div class="capture-frame mb-3">
                                <video id="face_camera_stream" autoplay playsinline class="d-none"></video>
                                <canvas id="face_capture_canvas" class="d-none"></canvas>
                                <img id="face_capture_preview" class="d-none" alt="Captured face preview">
                                <div id="face_capture_placeholder" class="capture-placeholder">
                                    <div>
                                        <i class="fas fa-camera fs-1 mb-3"></i>
                                        <div>Start the camera and capture a live face photo.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-outline-success rounded-pill" type="button" id="start_face_camera"><i class="fas fa-video me-2"></i>Start Camera</button>
                                <button class="btn btn-success rounded-pill" type="button" id="capture_face_photo"><i class="fas fa-camera-retro me-2"></i>Capture Face</button>
                                <button class="btn btn-outline-dark rounded-pill" type="button" id="retake_face_photo"><i class="fas fa-rotate-right me-2"></i>Retake</button>
                            </div>
                            <div class="form-text mt-2" id="face_capture_status">Your browser will ask for camera permission when you start verification.</div>
                        </div>

                        <?php if (!empty($verification['nid_image_path']) || !empty($verification['face_image_path'])): ?>
                            <div class="row g-3 mt-2">
                                <?php if (!empty($verification['nid_image_path'])): ?>
                                    <div class="col-md-6">
                                        <div class="small text-uppercase text-secondary fw-semibold mb-2">Last NID Image</div>
                                        <img src="<?php echo e((string) $verification['nid_image_path']); ?>" alt="Saved NID" class="img-fluid rounded-4 border">
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($verification['face_image_path'])): ?>
                                    <div class="col-md-6">
                                        <div class="small text-uppercase text-secondary fw-semibold mb-2">Last Face Capture</div>
                                        <img src="<?php echo e((string) $verification['face_image_path']); ?>" alt="Saved face capture" class="img-fluid rounded-4 border">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-success btn-lg rounded-pill px-4 mt-4" type="submit">
                            <i class="fas fa-shield-check me-2"></i>Submit verification
                        </button>
                    </form>
                </div>

                <div class="surface-card p-4 p-lg-5 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="section-title h3 mb-0">My Campaigns</h2>
                        <a class="btn btn-outline-success rounded-pill" href="create_campaign.php">New project</a>
                    </div>

                    <?php if (!$myProjects): ?>
                        <div class="text-center py-5 text-secondary">
                            <i class="fas fa-leaf fs-1 mb-3"></i>
                            <p class="mb-3">You have not launched any projects yet.</p>
                            <a class="btn btn-success rounded-pill px-4" href="create_campaign.php">Start a campaign</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Raised</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myProjects as $project): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img class="project-thumb" src="<?php echo e((string) $project['image_path']); ?>" alt="<?php echo e((string) $project['title']); ?>" onerror="this.src='assets/campaigns/default.jpg'">
                                                    <div>
                                                        <div class="fw-semibold"><?php echo e((string) $project['title']); ?></div>
                                                        <div class="small text-secondary"><?php echo e(date('M d, Y', strtotime((string) $project['created_at']))); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge rounded-pill text-bg-light border"><?php echo e(ucfirst((string) $project['status'])); ?></span></td>
                                            <td>
                                                <div class="fw-semibold">BDT <?php echo number_format((float) $project['raised_amount'], 2); ?></div>
                                                <div class="small text-secondary">Goal BDT <?php echo number_format((float) $project['target_amount'], 2); ?></div>
                                            </td>
                                            <td><?php echo e(trim((string) (($project['district'] ?? '') . ', ' . ($project['division'] ?? '')), ', ')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="surface-card p-4 p-lg-5">
                    <h2 class="section-title h3 mb-3">Support History</h2>

                    <?php if (!$history): ?>
                        <div class="text-center py-5 text-secondary">
                            <i class="fas fa-hand-holding-heart fs-1 mb-3"></i>
                            <p class="mb-3">No donations yet. Your first contribution can start today.</p>
                            <a class="btn btn-success rounded-pill px-4" href="index.php#campaigns">Browse campaigns</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $donation): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img class="project-thumb" src="<?php echo e((string) $donation['image_path']); ?>" alt="<?php echo e((string) $donation['title']); ?>" onerror="this.src='assets/campaigns/default.jpg'">
                                                    <span class="fw-semibold"><?php echo e((string) $donation['title']); ?></span>
                                                </div>
                                            </td>
                                            <td class="fw-semibold text-success">BDT <?php echo number_format((float) $donation['amount'], 2); ?></td>
                                            <td><?php echo e(date('M d, Y', strtotime((string) $donation['created_at']))); ?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-dark rounded-pill" href="support.php?id=<?php echo (int) $donation['campaign_id']; ?>">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startButton = document.getElementById('start_face_camera');
            const captureButton = document.getElementById('capture_face_photo');
            const retakeButton = document.getElementById('retake_face_photo');
            const video = document.getElementById('face_camera_stream');
            const canvas = document.getElementById('face_capture_canvas');
            const preview = document.getElementById('face_capture_preview');
            const placeholder = document.getElementById('face_capture_placeholder');
            const hiddenInput = document.getElementById('face_capture_data');
            const status = document.getElementById('face_capture_status');
            let stream = null;

            function stopStream() {
                if (stream) {
                    stream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                    stream = null;
                }
            }

            startButton.addEventListener('click', async function() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    status.textContent = 'Camera access is not supported in this browser.';
                    return;
                }

                try {
                    stopStream();
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = stream;
                    video.classList.remove('d-none');
                    preview.classList.add('d-none');
                    placeholder.classList.add('d-none');
                    hiddenInput.value = '';
                    status.textContent = 'Camera is ready. Capture your current face photo.';
                } catch (error) {
                    status.textContent = 'Unable to access the camera. Please allow camera permission and try again.';
                }
            });

            captureButton.addEventListener('click', function() {
                if (!video.srcObject) {
                    status.textContent = 'Start the camera before capturing a face photo.';
                    return;
                }

                const width = video.videoWidth || 640;
                const height = video.videoHeight || 480;
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(video, 0, 0, width, height);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                hiddenInput.value = dataUrl;
                preview.src = dataUrl;
                preview.classList.remove('d-none');
                video.classList.add('d-none');
                placeholder.classList.add('d-none');
                stopStream();
                status.textContent = 'Face photo captured successfully.';
            });

            retakeButton.addEventListener('click', function() {
                hiddenInput.value = '';
                preview.src = '';
                preview.classList.add('d-none');
                video.classList.add('d-none');
                placeholder.classList.remove('d-none');
                status.textContent = 'You can start the camera again and retake the face photo.';
                stopStream();
            });

            window.addEventListener('beforeunload', stopStream);
        });
    </script>
</body>
</html>
