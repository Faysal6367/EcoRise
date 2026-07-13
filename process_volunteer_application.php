<?php
/**
 * EcoRise - Process Volunteer Application
 */
require_once 'config.php';

if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to apply.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('become_volunteer.php');
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect('become_volunteer.php', 'Invalid session. Please try again.', 'error');
}

$user_id = (int) $_SESSION['user_id'];

$status_stmt = $pdo->prepare("SELECT volunteer_status FROM users WHERE id = ?");
$status_stmt->execute([$user_id]);
$current_status = $status_stmt->fetchColumn();

if ($current_status === 'approved') {
    redirect('become_volunteer.php', 'You are already an approved volunteer.', 'success');
}

$latest_stmt = $pdo->prepare("SELECT status FROM volunteer_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$latest_stmt->execute([$user_id]);
$latest = $latest_stmt->fetch();

if ($latest && $latest['status'] === 'pending') {
    redirect('become_volunteer.php', 'You already have a pending volunteer request.', 'error');
}

$required_fields = [
    'full_name', 'father_name', 'mobile_no', 'nid_number', 'email', 'occupation',
    'current_division', 'current_district', 'current_upazila', 'current_union_area', 'current_full_address',
    'permanent_division', 'permanent_district', 'permanent_upazila', 'permanent_union_area', 'permanent_full_address',
    'education_medium', 'education_level', 'last_passing_year', 'institution_name'
];

$data = [];
foreach ($required_fields as $field) {
    $data[$field] = sanitize($_POST[$field] ?? '');
    if ($data[$field] === '') {
        redirect('become_volunteer.php', 'Please fill all required fields.', 'error');
    }
}

$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    redirect('become_volunteer.php', 'Please provide a valid email address.', 'error');
}

$nid_number = preg_replace('/\D+/', '', (string) ($_POST['nid_number'] ?? ''));
if (!preg_match('/^(?:\d{10}|\d{13}|\d{17})$/', $nid_number)) {
    redirect('become_volunteer.php', 'Bangladesh NID must contain exactly 10, 13, or 17 digits.', 'error');
}

$photo_path = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 3 * 1024 * 1024;

    if (!in_array($_FILES['photo']['type'], $allowed_types, true)) {
        redirect('become_volunteer.php', 'Photo format must be JPG, PNG, or WEBP.', 'error');
    }

    if ($_FILES['photo']['size'] > $max_size) {
        redirect('become_volunteer.php', 'Photo size exceeds 3MB.', 'error');
    }

    $upload_dir = 'assets/volunteers/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = 'vol_' . uniqid('', true) . '.' . $ext;
    $target_file = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
        redirect('become_volunteer.php', 'Failed to upload photo.', 'error');
    }

    $photo_path = $target_file;
}

try {
    $stmt = $pdo->prepare("INSERT INTO volunteer_applications (
        user_id, full_name, father_name, mobile_no, nid_number, email, occupation, workplace_name, workplace_address,
        current_division, current_district, current_upazila, current_union_area, current_full_address,
        permanent_division, permanent_district, permanent_upazila, permanent_union_area, permanent_full_address,
        expatriate_country, expatriate_full_address, facebook_profile, no_facebook, linkedin_profile,
        whatsapp_number, telegram_number, education_medium, education_level, last_passing_year,
        department_degree, institution_name, worked_before, previous_project_name,
        previous_implementation_location, previous_project_year, people_benefited, photo_path, status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
    )");

    $stmt->execute([
        $user_id,
        $data['full_name'],
        $data['father_name'],
        $data['mobile_no'],
        $nid_number,
        $email,
        $data['occupation'],
        sanitize($_POST['workplace_name'] ?? ''),
        sanitize($_POST['workplace_address'] ?? ''),
        $data['current_division'],
        $data['current_district'],
        $data['current_upazila'],
        $data['current_union_area'],
        $data['current_full_address'],
        $data['permanent_division'],
        $data['permanent_district'],
        $data['permanent_upazila'],
        $data['permanent_union_area'],
        $data['permanent_full_address'],
        sanitize($_POST['expatriate_country'] ?? ''),
        sanitize($_POST['expatriate_full_address'] ?? ''),
        sanitize($_POST['facebook_profile'] ?? ''),
        isset($_POST['no_facebook']) ? 1 : 0,
        sanitize($_POST['linkedin_profile'] ?? ''),
        sanitize($_POST['whatsapp_number'] ?? ''),
        sanitize($_POST['telegram_number'] ?? ''),
        $data['education_medium'],
        $data['education_level'],
        $data['last_passing_year'],
        sanitize($_POST['department_degree'] ?? ''),
        $data['institution_name'],
        isset($_POST['worked_before']) ? 1 : 0,
        sanitize($_POST['previous_project_name'] ?? ''),
        sanitize($_POST['previous_implementation_location'] ?? ''),
        sanitize($_POST['previous_project_year'] ?? ''),
        sanitize($_POST['people_benefited'] ?? ''),
        $photo_path
    ]);

    $update_status_stmt = $pdo->prepare("UPDATE users SET volunteer_status = 'pending' WHERE id = ?");
    $update_status_stmt->execute([$user_id]);

    try {
        notify_admins(
            $pdo,
            'admin_volunteer_pending',
            'New volunteer application',
            $data['full_name'] . ' submitted a volunteer application and needs review.',
            'admin/volunteers.php',
            'fa-user-check',
            $user_id
        );
    } catch (Throwable $notifyError) {
        error_log('Notification create failed (volunteer application submit): ' . $notifyError->getMessage());
    }

    redirect('become_volunteer.php', 'Application submitted successfully. Waiting for admin approval.', 'success');
} catch (PDOException $e) {
    redirect('become_volunteer.php', 'Application submission failed: ' . $e->getMessage(), 'error');
}
