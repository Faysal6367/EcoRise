<?php
/**
 * Volunteer Disaster Opportunity Submission Processor
 * 
 * Handles disaster volunteer opportunity creation from approved volunteers.
 * Created opportunities are shown on volunteer_opportunities.php.
 */

require 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please log in to create a campaign.', 'warning');
}

// Check if user is an approved volunteer
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT volunteer_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['volunteer_status'] !== 'approved') {
    redirect('become_volunteer.php', 'You must be an approved volunteer to create campaigns.', 'warning');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    redirect('volunteer_create_campaign.php', 'Invalid request. Please try again.', 'danger');
}

// Get form inputs
$title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
$description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
$relief_type = isset($_POST['relief_type']) ? sanitize($_POST['relief_type']) : '';
$division = isset($_POST['division']) ? sanitize($_POST['division']) : '';
$district = isset($_POST['district']) ? sanitize($_POST['district']) : '';
$volunteers_needed = isset($_POST['volunteers_needed']) ? (int)$_POST['volunteers_needed'] : 0;
$location = trim($district . ', ' . $division);

// Validate inputs
if (!$title || !$description || !$relief_type || !$division || !$district || $volunteers_needed < 1) {
    redirect('volunteer_create_campaign.php', 'Please fill in all required fields correctly.', 'danger');
}

// Validate description length
if (strlen($description) < 100) {
    redirect('volunteer_create_campaign.php', 'Campaign description must be at least 100 characters long.', 'danger');
}

// Handle image upload
$image_path = 'assets/disasters/default.jpg';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_name = $_FILES['image']['name'];
    $file_size = $_FILES['image']['size'];
    $file_type = mime_content_type($file_tmp);

    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 3 * 1024 * 1024; // 3MB

    if (!in_array($file_type, $allowed_types)) {
        redirect('volunteer_create_campaign.php', 'Invalid image format. Please use JPG, PNG, or WebP.', 'danger');
    }

    if ($file_size > $max_size) {
        redirect('volunteer_create_campaign.php', 'Image file is too large. Maximum size is 3MB.', 'danger');
    }

    // Create disasters directory if it doesn't exist
    if (!is_dir('assets/disasters')) {
        mkdir('assets/disasters', 0755, true);
    }

    // Generate unique filename
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_name = 'disaster_' . $user_id . '_' . time() . '.' . $ext;
    $upload_path = 'assets/disasters/' . $unique_name;

    if (move_uploaded_file($file_tmp, $upload_path)) {
        $image_path = $upload_path;
    } else {
        error_log("Failed to move uploaded disaster opportunity image");
        $image_path = 'assets/disasters/default.jpg';
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO disaster_relief_campaigns (title, description, location, relief_type, status, image_path, volunteers_needed, volunteers_assigned)
                           VALUES (?, ?, ?, ?, 'active', ?, ?, 0)");
    
    $stmt->execute([
        $title,
        $description,
        $location,
        $relief_type,
        $image_path,
        $volunteers_needed
    ]);

    redirect('volunteer_create_campaign.php', 
             'Opportunity card created successfully! It is now visible on the volunteer opportunities page.',
             'success');

} catch (PDOException $e) {
    error_log("Volunteer disaster opportunity creation error: " . $e->getMessage());
    redirect('volunteer_create_campaign.php', 'Error creating opportunity card. Please try again later.', 'danger');
}

?>
