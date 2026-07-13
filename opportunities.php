<?php
declare(strict_types=1);

/**
 * Opportunities Page
 *
 * Displays ways to get involved and active opportunities.
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$volunteerUser = null;
if (is_logged_in()) {
    $volunteerStmt = $pdo->prepare('SELECT volunteer_status FROM users WHERE id = ?');
    $volunteerStmt->execute([(int) $_SESSION['user_id']]);
    $volunteerUser = $volunteerStmt->fetch();
}

$totalActiveCampaigns = (int) $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active' AND approval_status = 'approved'")->fetchColumn();
$totalDonors = (int) $pdo->query('SELECT COUNT(DISTINCT user_id) FROM donations')->fetchColumn();
$totalRaised = (float) ($pdo->query('SELECT COALESCE(SUM(amount), 0) FROM donations')->fetchColumn() ?: 0);
$totalVolunteers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE volunteer_status = 'approved'")->fetchColumn();

$opportunitiesStmt = $pdo->query(
    "SELECT id, title, description, division, district, target_amount, raised_amount, relief_type, image_path, created_at
     FROM campaigns
     WHERE status = 'active' AND approval_status = 'approved'
     ORDER BY created_at DESC
     LIMIT 8"
);
$opportunities = $opportunitiesStmt->fetchAll();

$message = $_SESSION['msg'] ?? null;
$messageType = $_SESSION['msg_type'] ?? 'success';
unset($_SESSION['msg'], $_SESSION['msg_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opportunities | EcoRise</title>
    <meta name="description" content="Discover volunteer, support, and campaign opportunities across EcoRise.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --eco-dark: #0f172a;
            --eco-green: #10b981;
            --eco-soft: #ecfdf5;
            --eco-border: rgba(15, 23, 42, 0.08);
        }
        body {
            background: linear-gradient(180deg, #f8fafc 0%, #f0fdf4 100%);
            color: var(--eco-dark);
        }
        .glass-nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--eco-border);
        }
        .hero-shell {
            background: linear-gradient(135deg, #0f172a 0%, #14532d 45%, #10b981 100%);
            color: #fff;
            border-radius: 32px;
            overflow: hidden;
            position: relative;
        }
        .hero-shell h1 {
            color: #f8fffb !important;
            line-height: 1.02;
            letter-spacing: -0.035em;
            text-shadow: 0 10px 26px rgba(2, 6, 23, 0.24);
            max-width: 12ch;
        }
        .hero-shell .text-white-50,
        .hero-shell p {
            color: rgba(241, 245, 249, 0.92) !important;
        }
        .hero-shell .badge {
            background: rgba(255,255,255,0.95) !important;
            color: #0f766e !important;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.18);
        }
        .hero-shell::after {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            right: -90px;
            top: -120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .surface-card {
            background: rgba(255,255,255,0.94);
            border: 1px solid var(--eco-border);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }
        .metric-card {
            border-radius: 20px;
            border: 1px solid var(--eco-border);
            background: #fff;
        }
        .opportunity-card {
            border: 1px solid var(--eco-border);
            border-radius: 24px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            height: 100%;
        }
        .opportunity-card img {
            height: 220px;
            object-fit: cover;
            width: 100%;
        }
        .pill-tag {
            background: var(--eco-soft);
            color: #047857;
        }
        .table-modern thead th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }
        .table-modern td,
        .table-modern th {
            border-color: rgba(148, 163, 184, 0.18);
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php render_public_nav('opportunities'); ?>

    <main class="container py-4 py-lg-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'); ?> rounded-4 border-0 shadow-sm mb-4">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <section class="hero-shell p-4 p-lg-5 mb-4 mb-lg-5">
            <div class="row g-4 align-items-center position-relative">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-light text-success mb-3">Get involved</span>
                    <h1 class="display-5 fw-bold mb-3">Choose the kind of impact you want to make.</h1>
                    <p class="fs-5 text-white-50 mb-4">From verified volunteer work to funding active environmental campaigns, EcoRise gives you clear paths to contribute where help is needed most.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-light btn-lg rounded-pill px-4" href="#active-opportunities">Browse active opportunities</a>
                        <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="become_volunteer.php">Apply as volunteer</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="surface-card p-4 text-dark">
                        <div class="small text-uppercase text-secondary fw-semibold mb-3">Community momentum</div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Supporters</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalDonors); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Volunteers</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalVolunteers); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Campaigns</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalActiveCampaigns); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Raised</div>
                                    <div class="h5 mb-0">BDT <?php echo number_format($totalRaised, 0); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-4 mb-lg-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="surface-card p-4 h-100">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-4 bg-success-subtle text-success fs-3 p-3 mb-3"><i class="fas fa-users"></i></div>
                        <h2 class="h4 fw-bold">Become a volunteer</h2>
                        <p class="text-secondary">Join field operations, community outreach, and local response efforts after approval.</p>
                        <a class="btn btn-success rounded-pill px-4" href="become_volunteer.php">Start application</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="surface-card p-4 h-100">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-4 bg-warning-subtle text-warning fs-3 p-3 mb-3"><i class="fas fa-hand-holding-heart"></i></div>
                        <h2 class="h4 fw-bold">Support campaigns</h2>
                        <p class="text-secondary">Back verified projects that are already mobilizing communities and resources.</p>
                        <a class="btn btn-outline-dark rounded-pill px-4" href="index.php#campaigns">Explore campaigns</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="surface-card p-4 h-100">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-4 bg-info-subtle text-info fs-3 p-3 mb-3"><i class="fas fa-lightbulb"></i></div>
                        <h2 class="h4 fw-bold">Launch an idea</h2>
                        <p class="text-secondary">Approved volunteers can create campaigns and coordinate mission delivery directly.</p>
                        <a class="btn btn-outline-success rounded-pill px-4" href="volunteer_create_campaign.php">Create campaign</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="active-opportunities" class="mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                <div>
                    <h2 class="display-6 fw-bold mb-2">Active opportunities</h2>
                    <p class="text-secondary mb-0">A card-first view for quick scanning, with a detailed table below for comparison.</p>
                </div>
                <a class="btn btn-outline-success rounded-pill px-4" href="index.php#campaigns">See all campaigns</a>
            </div>

            <div class="row g-4 mb-4">
                <?php if (!$opportunities): ?>
                    <div class="col-12">
                        <div class="surface-card p-5 text-center text-secondary">
                            <i class="fas fa-seedling fs-1 mb-3"></i>
                            <p class="mb-0">No active opportunities are published yet.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($opportunities as $opportunity): ?>
                        <?php $progress = (float) $opportunity['target_amount'] > 0 ? min(100, (((float) $opportunity['raised_amount'] / (float) $opportunity['target_amount']) * 100)) : 0; ?>
                        <div class="col-md-6 col-xl-3">
                            <article class="opportunity-card">
                                <img src="<?php echo e((string) $opportunity['image_path']); ?>" alt="<?php echo e((string) $opportunity['title']); ?>" onerror="this.src='assets/campaigns/default.jpg'">
                                <div class="p-4">
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <?php if (!empty($opportunity['relief_type'])): ?>
                                            <span class="badge pill-tag rounded-pill px-3 py-2"><?php echo e((string) $opportunity['relief_type']); ?></span>
                                        <?php endif; ?>
                                        <span class="badge text-bg-light rounded-pill px-3 py-2"><?php echo e((string) ($opportunity['district'] ?: 'Bangladesh')); ?></span>
                                    </div>
                                    <h3 class="h5 fw-bold mb-2"><?php echo e((string) $opportunity['title']); ?></h3>
                                    <p class="text-secondary small mb-3"><?php echo e(mb_strimwidth((string) $opportunity['description'], 0, 100, '...')); ?></p>
                                    <div class="progress mb-2" role="progressbar" aria-label="Campaign progress" aria-valuenow="<?php echo (int) $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-success" style="width: <?php echo number_format($progress, 1); ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-secondary mb-3">
                                        <span>BDT <?php echo number_format((float) $opportunity['raised_amount'], 0); ?></span>
                                        <span><?php echo number_format($progress, 1); ?>%</span>
                                    </div>
                                    <a class="btn btn-success w-100 rounded-pill" href="support.php?id=<?php echo (int) $opportunity['id']; ?>">View opportunity</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($opportunities): ?>
                <div class="surface-card p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h4 fw-bold mb-0">Opportunity overview</h3>
                        <span class="text-secondary small">Latest <?php echo count($opportunities); ?> published campaigns</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Opportunity</th>
                                    <th>Location</th>
                                    <th>Focus</th>
                                    <th>Raised / Goal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($opportunities as $opportunity): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e((string) $opportunity['title']); ?></div>
                                            <div class="small text-secondary"><?php echo e(date('M d, Y', strtotime((string) $opportunity['created_at']))); ?></div>
                                        </td>
                                        <td><?php echo e(trim((string) (($opportunity['district'] ?? '') . ', ' . ($opportunity['division'] ?? '')), ', ')); ?></td>
                                        <td><?php echo e((string) ($opportunity['relief_type'] ?: 'General environmental action')); ?></td>
                                        <td>
                                            <div class="fw-semibold">BDT <?php echo number_format((float) $opportunity['raised_amount'], 0); ?></div>
                                            <div class="small text-secondary">Goal BDT <?php echo number_format((float) $opportunity['target_amount'], 0); ?></div>
                                        </td>
                                        <td class="text-end"><a class="btn btn-sm btn-outline-success rounded-pill" href="support.php?id=<?php echo (int) $opportunity['id']; ?>">Details</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
