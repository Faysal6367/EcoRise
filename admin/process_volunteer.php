<?php
/**
 * EcoRise - Admin Volunteer Approval Processor
 */
require_once '../config.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('volunteers.php');
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect('volunteers.php', 'Invalid session.', 'error');
}

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'reject'], true)) {
    redirect('volunteers.php', 'Invalid request.', 'error');
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$user_status = $status;

try {
    $pdo->beginTransaction();

    $app_stmt = $pdo->prepare("SELECT user_id, status FROM volunteer_applications WHERE id = ? FOR UPDATE");
    $app_stmt->execute([$id]);
    $app = $app_stmt->fetch();

    if (!$app) {
        $pdo->rollBack();
        redirect('volunteers.php', 'Application not found.', 'error');
    }

    $update_app = $pdo->prepare("UPDATE volunteer_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $update_app->execute([$status, (int) $_SESSION['user_id'], $id]);

    $update_user = $pdo->prepare("UPDATE users SET volunteer_status = ? WHERE id = ?");
    $update_user->execute([$user_status, (int) $app['user_id']]);

    try {
        if ($status === 'approved') {
            create_notification(
                $pdo,
                (int) $app['user_id'],
                'volunteer_approved',
                'Volunteer application approved',
                'Congratulations. Your volunteer application has been approved.',
                '../volunteer_opportunities.php',
                'fa-hands-helping'
            );
        } else {
            create_notification(
                $pdo,
                (int) $app['user_id'],
                'volunteer_rejected',
                'Volunteer application update',
                'Your volunteer application was not approved this time. You can apply again with updated details.',
                '../become_volunteer.php',
                'fa-circle-xmark'
            );
        }
    } catch (Throwable $notifyError) {
        error_log('Notification create failed (volunteer review): ' . $notifyError->getMessage());
    }

    $pdo->commit();

    $msg = $status === 'approved' ? 'Volunteer request approved successfully.' : 'Volunteer request rejected successfully.';
    redirect('volunteers.php', $msg, 'success');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('volunteers.php', 'Action failed: ' . $e->getMessage(), 'error');
}
