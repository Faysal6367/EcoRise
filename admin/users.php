<?php
declare(strict_types=1);
require_once '../config.php';
require_once '_layout.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}

$csrf_token = generate_csrf_token();
$users = $pdo->query('SELECT id, full_name, email, role, volunteer_status, created_at FROM users ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('users.php', 'Invalid session.', 'error');
    }

    $id = (int) ($_POST['id'] ?? 0);
    if ($id && $id !== (int) $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            redirect('users.php', 'User deleted successfully.', 'success');
        } catch (PDOException $e) {
            redirect('users.php', 'Error deleting user: ' . $e->getMessage(), 'error');
        }
    }
    redirect('users.php', 'You cannot delete yourself.', 'error');
}

admin_render_start('Users', 'users', 'User Registry', 'Review member accounts, admin roles, and volunteer state across the platform.');
admin_render_flash();
?>
<section class="admin-card p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="admin-section-title h3 mb-1">All Users</h2>
            <p class="text-secondary mb-0">Professional directory of all platform members.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Profile</th><th>Email</th><th>Role</th><th>Volunteer</th><th>Joined</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                <?php if (!$users): ?><tr><td colspan="6" class="text-center text-secondary py-5">No users found.</td></tr><?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success-subtle text-success fw-bold d-inline-flex align-items-center justify-content-center" style="width:46px;height:46px;"><?php echo admin_e(strtoupper(substr((string) $user['full_name'], 0, 1))); ?></div>
                                <div class="fw-semibold"><?php echo admin_e((string) $user['full_name']); ?></div>
                            </div>
                        </td>
                        <td><?php echo admin_e((string) $user['email']); ?></td>
                        <td><span class="badge <?php echo $user['role'] === 'admin' ? 'badge-soft-info' : 'badge-soft-success'; ?> rounded-pill px-3 py-2"><?php echo admin_e(strtoupper((string) $user['role'])); ?></span></td>
                        <td><span class="badge <?php echo ($user['volunteer_status'] ?? 'none') === 'approved' ? 'badge-soft-success' : (($user['volunteer_status'] ?? 'none') === 'pending' ? 'badge-soft-warning' : 'badge-soft-danger'); ?> rounded-pill px-3 py-2"><?php echo admin_e(ucfirst((string) ($user['volunteer_status'] ?? 'none'))); ?></span></td>
                        <td class="text-secondary"><?php echo admin_e(date('M d, Y', strtotime((string) $user['created_at']))); ?></td>
                        <td class="text-end">
                            <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                                <form action="users.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this user account permanently?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="badge text-bg-light border rounded-pill px-3 py-2">Current Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_render_end(); ?>
