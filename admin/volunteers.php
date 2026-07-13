<?php
declare(strict_types=1);
require_once '../config.php';
require_once '_layout.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}

$csrf_token = generate_csrf_token();
$applications = $pdo->query('SELECT va.*, u.email AS account_email FROM volunteer_applications va JOIN users u ON u.id = va.user_id ORDER BY va.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$verifications = $pdo->query('SELECT uv.*, u.full_name, u.email FROM user_verifications uv JOIN users u ON u.id = uv.user_id ORDER BY uv.submitted_at DESC')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('volunteers.php', 'Invalid session.', 'error');
    }

    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$userId || $userId === (int) $_SESSION['user_id']) {
        redirect('volunteers.php', 'You cannot delete this account.', 'error');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        redirect('volunteers.php', 'Volunteer account deleted successfully.', 'success');
    } catch (PDOException $e) {
        redirect('volunteers.php', 'Error deleting volunteer: ' . $e->getMessage(), 'error');
    }
}

admin_render_start('Volunteers', 'volunteers', 'Volunteer & Verification Review', 'Approve volunteer applications and profile verification submissions in one place.');
admin_render_flash();
?>
<section class="admin-card p-4 p-lg-5 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="admin-section-title h3 mb-1">Volunteer Applications</h2>
            <p class="text-secondary mb-0">Review volunteer requests and assign platform access.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Applicant</th><th>Contact</th><th>Address</th><th>Status</th><th>Submitted</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                <?php if (!$applications): ?><tr><td colspan="6" class="text-center text-secondary py-5">No volunteer requests found.</td></tr><?php endif; ?>
                <?php foreach ($applications as $app): ?>
                    <?php $badge = $app['status'] === 'approved' ? 'badge-soft-success' : ($app['status'] === 'rejected' ? 'badge-soft-danger' : 'badge-soft-warning'); ?>
                    <tr>
                        <td><div class="fw-semibold"><?php echo admin_e((string) $app['full_name']); ?></div><div class="small text-secondary">Occupation: <?php echo admin_e((string) $app['occupation']); ?></div></td>
                        <td><div><?php echo admin_e((string) $app['mobile_no']); ?></div><div class="small text-secondary"><?php echo admin_e((string) $app['email']); ?></div></td>
                        <td><div><?php echo admin_e((string) $app['current_district']); ?>, <?php echo admin_e((string) $app['current_division']); ?></div><div class="small text-secondary"><?php echo admin_e((string) $app['current_full_address']); ?></div></td>
                        <td><span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2"><?php echo admin_e(ucfirst((string) $app['status'])); ?></span></td>
                        <td class="text-secondary"><?php echo admin_e(date('M d, Y h:i A', strtotime((string) $app['created_at']))); ?></td>
                        <td class="text-end">
                            <?php if ($app['status'] === 'pending'): ?>
                                <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                    <form action="process_volunteer.php" method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="id" value="<?php echo (int) $app['id']; ?>"><input type="hidden" name="action" value="approve"><button type="submit" class="btn btn-sm btn-outline-success rounded-pill">Approve</button></form>
                                    <form action="process_volunteer.php" method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="id" value="<?php echo (int) $app['id']; ?>"><input type="hidden" name="action" value="reject"><button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Reject</button></form>
                                    <form action="volunteers.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this volunteer account and all linked volunteer data?');"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?php echo (int) $app['user_id']; ?>"><button type="submit" class="btn btn-sm btn-outline-dark rounded-pill">Delete</button></form>
                                </div>
                            <?php else: ?>
                                <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                    <span class="badge text-bg-light border rounded-pill px-3 py-2">Reviewed</span>
                                    <form action="volunteers.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this volunteer account and all linked volunteer data?');"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?php echo (int) $app['user_id']; ?>"><button type="submit" class="btn btn-sm btn-outline-dark rounded-pill">Delete</button></form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-card p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="admin-section-title h3 mb-1">Profile Verification Requests</h2>
            <p class="text-secondary mb-0">Approve NID and face verification to unlock the profile verification tick.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>User</th><th>NID</th><th>Documents</th><th>Status</th><th>Submitted</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                <?php if (!$verifications): ?><tr><td colspan="6" class="text-center text-secondary py-5">No profile verification requests found.</td></tr><?php endif; ?>
                <?php foreach ($verifications as $verification): ?>
                    <?php $badge = $verification['status'] === 'verified' ? 'badge-soft-success' : ($verification['status'] === 'rejected' ? 'badge-soft-danger' : 'badge-soft-warning'); ?>
                    <tr>
                        <td><div class="fw-semibold"><?php echo admin_e((string) $verification['full_name']); ?></div><div class="small text-secondary"><?php echo admin_e((string) $verification['email']); ?></div></td>
                        <td><div class="fw-semibold"><?php echo admin_e((string) $verification['nid_number']); ?></div><?php if (!empty($verification['rejection_reason'])): ?><div class="small text-danger"><?php echo admin_e((string) $verification['rejection_reason']); ?></div><?php endif; ?></td>
                        <td>
                            <div class="d-flex gap-3 flex-wrap">
                                <a href="../<?php echo admin_e((string) $verification['nid_image_path']); ?>" target="_blank" class="text-decoration-none"><img class="admin-thumb" src="../<?php echo admin_e((string) $verification['nid_image_path']); ?>" alt="NID"></a>
                                <a href="../<?php echo admin_e((string) $verification['face_image_path']); ?>" target="_blank" class="text-decoration-none"><img class="admin-thumb" src="../<?php echo admin_e((string) $verification['face_image_path']); ?>" alt="Face"></a>
                            </div>
                        </td>
                        <td><span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2"><?php echo admin_e(ucfirst((string) $verification['status'])); ?></span></td>
                        <td class="text-secondary"><?php echo admin_e(date('M d, Y h:i A', strtotime((string) $verification['submitted_at']))); ?></td>
                        <td class="text-end">
                            <?php if ($verification['status'] === 'submitted'): ?>
                                <div class="d-inline-flex gap-2">
                                    <form action="process_verification.php" method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="verification_id" value="<?php echo (int) $verification['id']; ?>"><input type="hidden" name="action" value="approve"><button type="submit" class="btn btn-sm btn-outline-success rounded-pill">Approve</button></form>
                                    <form action="process_verification.php" method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="verification_id" value="<?php echo (int) $verification['id']; ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="rejection_reason" value="Verification did not pass admin review."><button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Reject</button></form>
                                </div>
                            <?php else: ?>
                                <span class="badge text-bg-light border rounded-pill px-3 py-2">Reviewed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_render_end(); ?>
