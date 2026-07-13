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
        redirect('disaster_relief.php', 'Invalid session.', 'error');
    }

    $action = sanitize($_POST['action']);
    if ($action === 'create' || $action === 'update') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $relief_type = sanitize($_POST['relief_type'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        $volunteers_needed = (int) ($_POST['volunteers_needed'] ?? 0);

        if (!$title || !$description || !$location || !$relief_type) {
            redirect('disaster_relief.php', 'Please fill in all required fields.', 'error');
        }

        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO disaster_relief_campaigns (title, description, location, relief_type, status, volunteers_needed) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$title, $description, $location, $relief_type, $status, $volunteers_needed]);
                redirect('disaster_relief.php', 'Disaster relief campaign created successfully!', 'success');
            }
            $stmt = $pdo->prepare('UPDATE disaster_relief_campaigns SET title = ?, description = ?, location = ?, relief_type = ?, status = ?, volunteers_needed = ? WHERE id = ?');
            $stmt->execute([$title, $description, $location, $relief_type, $status, $volunteers_needed, $id]);
            redirect('disaster_relief.php', 'Disaster relief campaign updated successfully!', 'success');
        } catch (PDOException $e) {
            redirect('disaster_relief.php', 'Error saving campaign: ' . $e->getMessage(), 'error');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM disaster_relief_campaigns WHERE id = ?')->execute([$id]);
                redirect('disaster_relief.php', 'Campaign deleted successfully!', 'success');
            } catch (PDOException $e) {
                redirect('disaster_relief.php', 'Error deleting campaign.', 'error');
            }
        }
    }
}

$campaigns = $pdo->query('SELECT * FROM disaster_relief_campaigns ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$actionButton = '<button class="btn btn-light rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#reliefModal"><i class="fas fa-plus me-2"></i>New Relief Campaign</button>';
admin_render_start('Disaster Relief', 'disaster_relief', 'Disaster Relief Management', 'Coordinate emergency response campaigns, volunteer needs, and mission status with a cleaner operations view.', $actionButton);
admin_render_flash();
?>
<section class="row g-4">
    <?php if (!$campaigns): ?>
        <div class="col-12"><div class="admin-card p-5 text-center text-secondary">No disaster relief campaigns yet.</div></div>
    <?php endif; ?>
    <?php foreach ($campaigns as $campaign): ?>
        <?php $progress = (int) $campaign['volunteers_needed'] > 0 ? round(((int) $campaign['volunteers_assigned'] / (int) $campaign['volunteers_needed']) * 100) : 100; ?>
        <?php $badge = $campaign['status'] === 'completed' ? 'badge-soft-info' : ($campaign['status'] === 'pending' ? 'badge-soft-warning' : 'badge-soft-success'); ?>
        <div class="col-lg-6">
            <article class="admin-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1"><?php echo admin_e((string) $campaign['title']); ?></h2>
                        <div class="text-secondary small"><i class="fas fa-location-dot me-1"></i><?php echo admin_e((string) $campaign['location']); ?></div>
                    </div>
                    <span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2"><?php echo admin_e(ucfirst((string) $campaign['status'])); ?></span>
                </div>
                <p class="text-secondary mb-4"><?php echo admin_e(mb_strimwidth((string) $campaign['description'], 0, 180, '...')); ?></p>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="p-3 rounded-4 bg-light"><div class="small text-secondary">Relief Type</div><div class="fw-semibold"><?php echo admin_e((string) $campaign['relief_type']); ?></div></div></div>
                    <div class="col-6"><div class="p-3 rounded-4 bg-light"><div class="small text-secondary">Progress</div><div class="fw-semibold"><?php echo $progress; ?>%</div></div></div>
                    <div class="col-6"><div class="p-3 rounded-4 bg-light"><div class="small text-secondary">Needed</div><div class="fw-semibold"><?php echo (int) $campaign['volunteers_needed']; ?></div></div></div>
                    <div class="col-6"><div class="p-3 rounded-4 bg-light"><div class="small text-secondary">Assigned</div><div class="fw-semibold"><?php echo (int) $campaign['volunteers_assigned']; ?></div></div></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-success rounded-pill" onclick="editRelief(<?php echo (int) $campaign['id']; ?>, <?php echo htmlspecialchars(json_encode($campaign['title']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($campaign['description']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($campaign['location']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($campaign['relief_type']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($campaign['status']), ENT_QUOTES); ?>, <?php echo (int) $campaign['volunteers_needed']; ?>)">Edit</button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this disaster relief campaign?');">
                        <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $campaign['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger rounded-pill">Delete</button>
                    </form>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</section>

<div class="modal fade admin-modal" id="reliefModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="reliefModalTitle">Create Relief Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrf_token); ?>">
                    <input type="hidden" name="action" id="reliefAction" value="create">
                    <input type="hidden" name="id" id="reliefId" value="">
                    <div class="mb-3"><label class="form-label fw-semibold">Title</label><input type="text" id="reliefTitle" name="title" class="form-control rounded-4" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea id="reliefDescription" name="description" class="form-control rounded-4" rows="4" required></textarea></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Location</label><input type="text" id="reliefLocation" name="location" class="form-control rounded-4" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Relief Type</label><input type="text" id="reliefType" name="relief_type" class="form-control rounded-4" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Volunteers Needed</label><input type="number" id="reliefVolunteers" name="volunteers_needed" class="form-control rounded-4" min="0" value="0"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Status</label><select id="reliefStatus" name="status" class="form-select rounded-4"><option value="active">Active</option><option value="pending">Pending</option><option value="completed">Completed</option></select></div>
                    </div>
                    <div class="text-end mt-4"><button type="submit" class="btn btn-success rounded-pill px-4">Save Campaign</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_render_end('<script>
const reliefModal = new bootstrap.Modal(document.getElementById("reliefModal"));
function editRelief(id, title, description, location, reliefType, status, volunteersNeeded) {
    document.getElementById("reliefModalTitle").textContent = "Edit Relief Campaign";
    document.getElementById("reliefAction").value = "update";
    document.getElementById("reliefId").value = id;
    document.getElementById("reliefTitle").value = JSON.parse(title);
    document.getElementById("reliefDescription").value = JSON.parse(description);
    document.getElementById("reliefLocation").value = JSON.parse(location);
    document.getElementById("reliefType").value = JSON.parse(reliefType);
    document.getElementById("reliefStatus").value = JSON.parse(status);
    document.getElementById("reliefVolunteers").value = volunteersNeeded;
    reliefModal.show();
}
document.getElementById("reliefModal").addEventListener("show.bs.modal", function (event) {
    if (!event.relatedTarget) { return; }
    document.getElementById("reliefModalTitle").textContent = "Create Relief Campaign";
    document.getElementById("reliefAction").value = "create";
    document.getElementById("reliefId").value = "";
    document.getElementById("reliefTitle").value = "";
    document.getElementById("reliefDescription").value = "";
    document.getElementById("reliefLocation").value = "";
    document.getElementById("reliefType").value = "";
    document.getElementById("reliefStatus").value = "active";
    document.getElementById("reliefVolunteers").value = 0;
});
</script>'); ?>
