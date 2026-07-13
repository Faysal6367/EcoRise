<?php
/**
 * Volunteer Assignment Processor
 * 
 * Handles volunteer joining and leaving disaster relief campaigns.
 * Updates volunteer_assignments and disaster_relief_campaigns counters.
 */

require 'config.php';

// Verify user is logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please log in to join volunteer opportunities.', 'warning');
}

// Verify user is an approved volunteer
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT volunteer_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['volunteer_status'] !== 'approved') {
    redirect('volunteer_opportunities.php', 'You must be an approved volunteer to join campaigns.', 'warning');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    redirect('volunteer_opportunities.php', 'Invalid request. Please try again.', 'danger');
}

// Get request parameters
$relief_id = isset($_POST['relief_id']) ? (int)$_POST['relief_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

if (!$relief_id || !in_array($action, ['join', 'leave'])) {
    redirect('volunteer_opportunities.php', 'Invalid request parameters.', 'danger');
}

// Verify disaster relief campaign exists
$stmt = $pdo->prepare("SELECT id, title, status FROM disaster_relief_campaigns WHERE id = ?");
$stmt->execute([$relief_id]);
$disaster = $stmt->fetch();

if (!$disaster) {
    redirect('volunteer_opportunities.php', 'Disaster relief campaign not found.', 'danger');
}

if ($disaster['status'] !== 'active') {
    redirect('volunteer_opportunities.php', 'This disaster relief campaign is no longer active.', 'warning');
}

try {
    // Start transaction
    $pdo->beginTransaction();

    if ($action === 'join') {
        // Check existing assignment state
        $stmt = $pdo->prepare("SELECT id, status FROM volunteer_assignments WHERE volunteer_id = ? AND disaster_relief_id = ? LIMIT 1");
        $stmt->execute([$user_id, $relief_id]);
        $existing = $stmt->fetch();

        if ($existing && $existing['status'] === 'active') {
            $pdo->rollBack();
            redirect('volunteer_opportunities.php', 'You are already assigned to this campaign.', 'warning');
        }

        if ($existing && $existing['status'] !== 'active') {
            // Re-activate prior assignment record (declined/completed)
            $stmt = $pdo->prepare("UPDATE volunteer_assignments SET status = 'active', assigned_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$existing['id']]);
        } else {
            // Insert new assignment
            $stmt = $pdo->prepare("INSERT INTO volunteer_assignments (volunteer_id, disaster_relief_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$user_id, $relief_id]);
        }

        // Increment volunteers_assigned counter
        $stmt = $pdo->prepare("UPDATE disaster_relief_campaigns SET volunteers_assigned = volunteers_assigned + 1 WHERE id = ?");
        $stmt->execute([$relief_id]);

        try {
            notify_admins(
                $pdo,
                'admin_assignment_join',
                'Volunteer joined disaster relief',
                ($_SESSION['user_name'] ?? 'A volunteer') . ' joined "' . (string) ($disaster['title'] ?? 'Disaster Relief Campaign') . '".',
                'admin/disaster_relief.php',
                'fa-hands-helping',
                (int) $user_id
            );
        } catch (Throwable $notifyError) {
            error_log('Notification create failed (assignment join): ' . $notifyError->getMessage());
        }

        $pdo->commit();
        redirect('volunteer_opportunities.php', 'Successfully joined the disaster relief campaign! Thank you for volunteering.', 'success');

    } elseif ($action === 'leave') {
        // Check if assignment exists
        $stmt = $pdo->prepare("SELECT id FROM volunteer_assignments WHERE volunteer_id = ? AND disaster_relief_id = ? AND status = 'active'");
        $stmt->execute([$user_id, $relief_id]);
        $assignment = $stmt->fetch();

        if (!$assignment) {
            $pdo->rollBack();
            redirect('volunteer_opportunities.php', 'You are not assigned to this campaign.', 'warning');
        }

        // Update assignment status to declined
        $stmt = $pdo->prepare("UPDATE volunteer_assignments SET status = 'declined' WHERE volunteer_id = ? AND disaster_relief_id = ?");
        $stmt->execute([$user_id, $relief_id]);

        // Decrement volunteers_assigned counter
        $stmt = $pdo->prepare("UPDATE disaster_relief_campaigns SET volunteers_assigned = GREATEST(volunteers_assigned - 1, 0) WHERE id = ?");
        $stmt->execute([$relief_id]);

        try {
            notify_admins(
                $pdo,
                'admin_assignment_leave',
                'Volunteer left disaster relief',
                ($_SESSION['user_name'] ?? 'A volunteer') . ' left "' . (string) ($disaster['title'] ?? 'Disaster Relief Campaign') . '".',
                'admin/disaster_relief.php',
                'fa-person-walking-dashed-line-arrow-right',
                (int) $user_id
            );
        } catch (Throwable $notifyError) {
            error_log('Notification create failed (assignment leave): ' . $notifyError->getMessage());
        }

        $pdo->commit();
        redirect('volunteer_opportunities.php', 'You have left the disaster relief campaign.', 'success');
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Volunteer assignment error: " . $e->getMessage());
    redirect('volunteer_opportunities.php', 'An error occurred. Please try again later.', 'danger');
}

?>
