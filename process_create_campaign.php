<?php
/**
 * EcoRise - Process Create Campaign
 * 
 * Handles user form submission for a new campaign project.
 */
require_once 'config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to start a project.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('create_campaign.php', 'Invalid session. Please try again.', 'error');
    }

    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $division = sanitize($_POST['division']);
    $district = sanitize($_POST['district']);
    $target_amount = filter_var($_POST['target_amount'], FILTER_VALIDATE_FLOAT);
    $user_id = $_SESSION['user_id'];

    // Basic validation
    if (empty($title) || empty($description) || empty($division) || empty($district) || !$target_amount) {
        redirect('create_campaign.php', 'All fields are required.', 'error');
    }

    // Image Upload Handling
    $image_path = 'assets/campaigns/default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            redirect('create_campaign.php', 'Invalid image format. Use JPG, PNG, or WEBP.', 'error');
        }

        if ($_FILES['image']['size'] > $max_size) {
            redirect('create_campaign.php', 'Image is too large. Max 5MB.', 'error');
        }

        $upload_dir = 'assets/campaigns/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('camp_', true) . '.' . $ext;
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            redirect('create_campaign.php', 'Failed to upload image.', 'error');
        }
    }

    try {
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO campaigns (title, description, division, district, target_amount, image_path, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        // Status is pending until approved by admin
        $stmt->execute([$title, $description, $division, $district, $target_amount, $image_path, $user_id]);

        try {
            notify_admins(
                $pdo,
                'admin_campaign_pending',
                'New campaign awaiting review',
                '"' . $title . '" was submitted and is waiting for approval.',
                'admin/campaign_approval.php',
                'fa-hourglass-half',
                (int) $user_id
            );
        } catch (Throwable $notifyError) {
            error_log('Notification create failed (campaign submission): ' . $notifyError->getMessage());
        }

        redirect('dashboard.php', 'Project launched successfully! It is currently pending review by administrators.', 'success');

    } catch (PDOException $e) {
        redirect('create_campaign.php', 'Failed to launch project. Error: ' . $e->getMessage(), 'error');
    }
} else {
    redirect('create_campaign.php');
}
?>
