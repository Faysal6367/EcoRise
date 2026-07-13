<?php
/**
 * EcoRise - Admin User Verification Processor
 */
require_once '../config.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}
//process the verification request based on the action (approve or reject)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('volunteers.php');
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect('volunteers.php', 'Invalid session.', 'error');
}

$verificationId = filter_var($_POST['verification_id'] ?? 0, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$verificationId || !in_array($action, ['approve', 'reject'], true)) {
    redirect('volunteers.php', 'Invalid verification request.', 'error');
}

$status = $action === 'approve' ? 'verified' : 'rejected';
$rejectionReason = $action === 'reject'
    ? sanitize($_POST['rejection_reason'] ?? 'Verification was rejected by admin review.')
    : null;

try {
    $pdo->beginTransaction();

    $verificationStmt = $pdo->prepare('SELECT id, user_id, status FROM user_verifications WHERE id = ? FOR UPDATE');
    $verificationStmt->execute([$verificationId]);
    $verification = $verificationStmt->fetch();

    if (!$verification) {
        $pdo->rollBack();
        redirect('volunteers.php', 'Verification request not found.', 'error');
    }

    $updateStmt = $pdo->prepare(
        'UPDATE user_verifications
         SET status = ?, rejection_reason = ?, verified_at = ?, submitted_at = submitted_at
         WHERE id = ?'
    );
    $updateStmt->execute([
        $status,
        $rejectionReason,
        $status === 'verified' ? date('Y-m-d H:i:s') : null,
        $verificationId,
    ]);

    $pdo->commit();

    $message = $status === 'verified'
        ? 'User verification approved successfully.'
        : 'User verification rejected successfully.';
    redirect('volunteers.php', $message, 'success');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('volunteers.php', 'Verification action failed: ' . $e->getMessage(), 'error');
}
?>
