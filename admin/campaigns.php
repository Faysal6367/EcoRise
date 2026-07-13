<?php
declare(strict_types=1);
require_once '../config.php';
require_once '_layout.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}

$csrf_token = generate_csrf_token();
$campaigns = $pdo->query('SELECT * FROM campaigns ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$actionButton = '<button class="btn btn-light rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#campaignModal"><i class="fas fa-plus me-2"></i>New Campaign</button>';
admin_render_start('Campaign Management', 'campaigns', 'Campaign Management', 'Create, review, and moderate all platform campaigns from a single workspace.', $actionButton);
admin_render_flash();
?>
<section class="admin-card p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="admin-section-title h3 mb-1">All Campaigns</h2>
            <p class="text-secondary mb-0">Manage campaign status, view progress, and remove outdated records.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Campaign</th><th>Goal</th><th>Raised</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (!$campaigns): ?><tr><td colspan="5" class="text-center text-secondary py-5">No campaigns found.</td></tr><?php endif; ?>
                <?php foreach ($campaigns as $camp): ?>
                    <?php $badge = $camp['status'] === 'completed' ? 'badge-soft-info' : ($camp['status'] === 'pending' ? 'badge-soft-warning' : 'badge-soft-success'); ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img class="admin-thumb" src="../<?php echo admin_e((string) $camp['image_path']); ?>" alt="<?php echo admin_e((string) $camp['title']); ?>" onerror="this.src='../assets/campaigns/default.jpg'">
                                <div>
                                    <div class="fw-semibold"><?php echo admin_e((string) $camp['title']); ?></div>
                                    <div class="small text-secondary"><?php echo admin_e(date('M d, Y', strtotime((string) $camp['created_at']))); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="fw-semibold">BDT <?php echo number_format((float) $camp['target_amount'], 2); ?></td>
                        <td class="fw-semibold text-success">BDT <?php echo number_format((float) $camp['raised_amount'], 2); ?></td>
                        <td><span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2"><?php echo admin_e(ucfirst((string) $camp['status'])); ?></span></td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <?php if ($camp['status'] === 'pending'): ?>
                                    <form action="process_campaign.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?php echo (int) $camp['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill">Approve</button>
                                    </form>
                                <?php else: ?>
                                    <form action="process_campaign.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="id" value="<?php echo (int) $camp['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill">Pause</button>
                                    </form>
                                <?php endif; ?>
                                <a href="../support.php?id=<?php echo (int) $camp['id']; ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill">View</a>
                                <form action="process_campaign.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign permanently?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $camp['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade admin-modal" id="campaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Create Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form action="process_campaign.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label fw-semibold">Campaign Title</label><input type="text" name="title" class="form-control rounded-4" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control rounded-4" rows="4" required></textarea></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Target Amount</label><input type="number" name="target_amount" class="form-control rounded-4" min="1" step="0.01" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Campaign Image</label><input type="file" name="image" class="form-control rounded-4" accept=".jpg,.jpeg,.png,.webp" required></div>
                    </div>
                    <div class="text-end mt-4"><button type="submit" class="btn btn-success rounded-pill px-4">Create Campaign</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_render_end(); ?>
