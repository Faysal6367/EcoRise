<?php
declare(strict_types=1);
require_once '../config.php';
require_once '_layout.php';

if (!is_admin()) {
    redirect('../signin.php', 'Restricted access.', 'error');
}

$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('campaign_approval.php', 'Invalid session.', 'error');
    }

    $campaign_id = (int) ($_POST['id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    if (!$campaign_id || !in_array($action, ['approve', 'reject'], true)) {
        redirect('campaign_approval.php', 'Invalid request.', 'error');
    }

    try {
        $pdo->beginTransaction();
        $campaign_owner_stmt = $pdo->prepare('SELECT id, title, created_by FROM campaigns WHERE id = ? FOR UPDATE');
        $campaign_owner_stmt->execute([$campaign_id]);
        $campaign = $campaign_owner_stmt->fetch();

        if (!$campaign) {
            $pdo->rollBack();
            redirect('campaign_approval.php', 'Campaign not found.', 'error');
        }

        $campaignTitle = (string) ($campaign['title'] ?? 'Campaign');
        $campaignOwnerId = (int) ($campaign['created_by'] ?? 0);
        $actionUrl = '../support.php?id=' . $campaign_id;

        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE campaigns SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $campaign_id]);

            if ($campaignOwnerId > 0) {
                try {
                    create_notification(
                        $pdo,
                        $campaignOwnerId,
                        'campaign_approved',
                        'Campaign approved',
                        'Your campaign "' . $campaignTitle . '" is approved and visible to supporters.',
                        $actionUrl,
                        'fa-circle-check'
                    );
                } catch (Throwable $notifyError) {
                    error_log('Notification create failed (campaign approve): ' . $notifyError->getMessage());
                }
            }
            $message = 'Campaign approved successfully!';
        } else {
            $rejection_reason = sanitize($_POST['rejection_reason'] ?? 'Not specified');
            $stmt = $pdo->prepare("UPDATE campaigns SET approval_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $rejection_reason, $campaign_id]);

            if ($campaignOwnerId > 0) {
                try {
                    create_notification(
                        $pdo,
                        $campaignOwnerId,
                        'campaign_rejected',
                        'Campaign needs revision',
                        'Your campaign "' . $campaignTitle . '" was rejected. Reason: ' . ($rejection_reason !== '' ? $rejection_reason : 'Not specified') . '.',
                        $actionUrl,
                        'fa-triangle-exclamation'
                    );
                } catch (Throwable $notifyError) {
                    error_log('Notification create failed (campaign reject): ' . $notifyError->getMessage());
                }
            }
            $message = 'Campaign rejected.';
        }
        $pdo->commit();
        redirect('campaign_approval.php', $message, 'success');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirect('campaign_approval.php', 'Error updating campaign.', 'error');
    }
}

$pending_campaigns = $pdo->query("SELECT c.*, u.full_name, u.email FROM campaigns c JOIN users u ON c.created_by = u.id WHERE c.approval_status = 'pending' ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$approved_campaigns = $pdo->query("SELECT c.*, u.full_name, u.email, a.full_name as approved_by_name FROM campaigns c JOIN users u ON c.created_by = u.id LEFT JOIN users a ON c.approved_by = a.id WHERE c.approval_status = 'approved' AND c.created_by IN (SELECT id FROM users WHERE volunteer_status = 'approved') ORDER BY c.approved_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$rejected_campaigns = $pdo->query("SELECT c.*, u.full_name, u.email, a.full_name as approved_by_name FROM campaigns c JOIN users u ON c.created_by = u.id LEFT JOIN users a ON c.approved_by = a.id WHERE c.approval_status = 'rejected' AND c.created_by IN (SELECT id FROM users WHERE volunteer_status = 'approved') ORDER BY c.approved_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

admin_render_start('Campaign Approvals', 'campaign_approval', 'Campaign Approval Workflow', 'Review volunteer-submitted campaigns with a cleaner approval dashboard and faster decision flow.');
admin_render_flash();
?>
<ul class="nav nav-pills gap-2 mb-4" id="approvalTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#pending-pane" type="button">Pending <?php echo count($pending_campaigns); ?></button></li>
    <li class="nav-item"><button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#approved-pane" type="button">Approved <?php echo count($approved_campaigns); ?></button></li>
    <li class="nav-item"><button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#rejected-pane" type="button">Rejected <?php echo count($rejected_campaigns); ?></button></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="pending-pane">
        <div class="row g-4">
            <?php if (!$pending_campaigns): ?><div class="col-12"><div class="admin-card p-5 text-center text-secondary">No pending campaigns to review.</div></div><?php endif; ?>
            <?php foreach ($pending_campaigns as $campaign): ?>
                <div class="col-xl-6">
                    <article class="admin-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h2 class="h4 fw-bold mb-1"><?php echo admin_e((string) $campaign['title']); ?></h2><div class="small text-secondary"><?php echo admin_e((string) $campaign['full_name']); ?> � <?php echo admin_e((string) $campaign['email']); ?></div></div><span class="badge badge-soft-warning rounded-pill px-3 py-2">Pending</span></div>
                        <p class="text-secondary mb-3"><?php echo admin_e(mb_strimwidth((string) $campaign['description'], 0, 180, '...')); ?></p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><div class="p-3 rounded-4 bg-light"><div class="small text-secondary">Location</div><div class="fw-semibold"><?php echo admin_e(trim((string) (($campaign['division'] ?? '') . ', ' . ($campaign['district'] ?? '')), ', ')); ?></div></div></div>
                            <div class="col-md-6"><div class="p-3 rounded-4 bg-light"><div class="small text-secondary">Target</div><div class="fw-semibold">BDT <?php echo number_format((float) $campaign['target_amount'], 2); ?></div></div></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="id" value="<?php echo (int) $campaign['id']; ?>"><input type="hidden" name="action" value="approve"><button type="submit" class="btn btn-success rounded-pill px-4">Approve</button></form>
                            <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#rejectModal" data-campaign-id="<?php echo (int) $campaign['id']; ?>">Reject</button>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="tab-pane fade" id="approved-pane">
        <div class="admin-card p-4 p-lg-5">
            <div class="table-responsive"><table class="table admin-table align-middle mb-0"><thead><tr><th>Campaign</th><th>Founder</th><th>Approved By</th><th>Approved At</th></tr></thead><tbody><?php if (!$approved_campaigns): ?><tr><td colspan="4" class="text-center text-secondary py-5">No approved volunteer campaigns yet.</td></tr><?php endif; ?><?php foreach ($approved_campaigns as $campaign): ?><tr><td class="fw-semibold"><?php echo admin_e((string) $campaign['title']); ?></td><td><?php echo admin_e((string) $campaign['full_name']); ?></td><td><?php echo admin_e((string) ($campaign['approved_by_name'] ?? 'System')); ?></td><td class="text-secondary"><?php echo admin_e(date('M d, Y h:i A', strtotime((string) $campaign['approved_at']))); ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>
    <div class="tab-pane fade" id="rejected-pane">
        <div class="admin-card p-4 p-lg-5">
            <div class="table-responsive"><table class="table admin-table align-middle mb-0"><thead><tr><th>Campaign</th><th>Founder</th><th>Reason</th><th>Reviewed At</th></tr></thead><tbody><?php if (!$rejected_campaigns): ?><tr><td colspan="4" class="text-center text-secondary py-5">No rejected campaigns.</td></tr><?php endif; ?><?php foreach ($rejected_campaigns as $campaign): ?><tr><td class="fw-semibold"><?php echo admin_e((string) $campaign['title']); ?></td><td><?php echo admin_e((string) $campaign['full_name']); ?></td><td><?php echo admin_e((string) ($campaign['rejection_reason'] ?? 'Not specified')); ?></td><td class="text-secondary"><?php echo admin_e(date('M d, Y h:i A', strtotime((string) $campaign['approved_at']))); ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>
</div>
<div class="modal fade admin-modal" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0 px-4 pt-4"><h5 class="modal-title fw-bold">Reject Campaign</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body px-4 pb-4"><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>"><input type="hidden" name="id" id="rejectCampaignId" value=""><input type="hidden" name="action" value="reject"><div class="mb-3"><label class="form-label fw-semibold">Rejection Reason</label><textarea name="rejection_reason" class="form-control rounded-4" rows="4" placeholder="Explain why this campaign is being rejected..."></textarea></div><div class="text-end"><button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Rejection</button></div></form></div></div></div>
</div>
<?php admin_render_end('<script>
document.getElementById("rejectModal").addEventListener("show.bs.modal", function (event) {
  const button = event.relatedTarget;
  document.getElementById("rejectCampaignId").value = button.getAttribute("data-campaign-id") || "";
});
</script>'); ?>
