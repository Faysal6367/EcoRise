<?php
declare(strict_types=1);
require_once '../config.php';
require_once '_layout.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalCampaigns = (int) $pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
$totalDonated = (float) ($pdo->query('SELECT COALESCE(SUM(amount), 0) FROM donations')->fetchColumn() ?: 0);
$pendingApprovals = (int) $pdo->query("SELECT COUNT(*) FROM campaigns WHERE approval_status = 'pending'")->fetchColumn();
$recentDonations = $pdo->query(
    "SELECT d.amount, d.created_at, u.full_name, c.title
     FROM donations d
     JOIN users u ON d.user_id = u.id
     JOIN campaigns c ON d.campaign_id = c.id
     ORDER BY d.created_at DESC
     LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

admin_render_start(
    'Dashboard',
    'index',
    'Admin Command Center',
    'Monitor users, campaigns, donations, and pending approvals from one professional control room.'
);
admin_render_flash();
?>
<section class="row g-3 g-lg-4 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="admin-stat p-4"><div class="admin-stat-icon mb-3"><i class="fas fa-users"></i></div><div class="text-secondary small text-uppercase fw-semibold">Users</div><div class="h2 fw-bold mb-0"><?php echo $totalUsers; ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="admin-stat p-4"><div class="admin-stat-icon mb-3"><i class="fas fa-bullhorn"></i></div><div class="text-secondary small text-uppercase fw-semibold">Campaigns</div><div class="h2 fw-bold mb-0"><?php echo $totalCampaigns; ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="admin-stat p-4"><div class="admin-stat-icon mb-3"><i class="fas fa-hand-holding-dollar"></i></div><div class="text-secondary small text-uppercase fw-semibold">Raised</div><div class="h4 fw-bold mb-0">BDT <?php echo number_format($totalDonated, 2); ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="admin-stat p-4"><div class="admin-stat-icon mb-3"><i class="fas fa-hourglass-half"></i></div><div class="text-secondary small text-uppercase fw-semibold">Pending approvals</div><div class="h2 fw-bold mb-0"><?php echo $pendingApprovals; ?></div></div></div>
</section>

<section class="admin-card p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="admin-section-title h3 mb-1">Recent Donations</h2>
            <p class="text-secondary mb-0">Latest supporter activity across EcoRise.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Contributor</th><th>Campaign</th><th>Amount</th><th>Timestamp</th></tr></thead>
            <tbody>
                <?php if (!$recentDonations): ?>
                    <tr><td colspan="4" class="text-center text-secondary py-5">No donation activity yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentDonations as $donation): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo admin_e($donation['full_name']); ?></td>
                        <td><?php echo admin_e($donation['title']); ?></td>
                        <td class="fw-semibold text-success">BDT <?php echo number_format((float) $donation['amount'], 2); ?></td>
                        <td class="text-secondary"><?php echo admin_e(date('M d, Y h:i A', strtotime((string) $donation['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_render_end(); ?>
