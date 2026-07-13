<?php
declare(strict_types=1);

require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$stats = [
    'approved' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE volunteer_status = 'approved'")->fetchColumn(),
    'verified' => (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN user_verifications uv ON uv.user_id = u.id WHERE u.volunteer_status = 'approved' AND uv.status = 'verified'")->fetchColumn(),
    'active_assignments' => (int) $pdo->query("SELECT COUNT(*) FROM volunteer_assignments va JOIN users u ON u.id = va.volunteer_id WHERE u.volunteer_status = 'approved' AND va.status = 'active'")->fetchColumn(),
];

$volunteersStmt = $pdo->query(
    "SELECT
        u.id,
        u.full_name,
        u.profile_image_path,
        u.division,
        u.district,
        u.address_line,
        u.created_at,
        ua.occupation,
        ua.workplace_name,
        ua.current_division,
        ua.current_district,
        uv.status AS verification_status,
        uv.verified_at
     FROM users u
     LEFT JOIN volunteer_applications ua ON ua.id = (
        SELECT v1.id
        FROM volunteer_applications v1
        WHERE v1.user_id = u.id
        ORDER BY v1.created_at DESC, v1.id DESC
        LIMIT 1
     )
     LEFT JOIN user_verifications uv ON uv.id = (
        SELECT v2.id
        FROM user_verifications v2
        WHERE v2.user_id = u.id
        ORDER BY v2.submitted_at DESC, v2.id DESC
        LIMIT 1
     )
     WHERE u.volunteer_status = 'approved'
     ORDER BY u.full_name ASC"
);
$volunteers = $volunteersStmt->fetchAll(PDO::FETCH_ASSOC);

$message = $_SESSION['msg'] ?? null;
$messageType = $_SESSION['msg_type'] ?? 'success';
unset($_SESSION['msg'], $_SESSION['msg_type']);

$totalCards = count($volunteers);

function volunteer_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : 'ER';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer List | EcoRise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --eco-dark: #0f172a;
            --eco-green: #10b981;
            --eco-teal: #0ea5a3;
            --eco-soft: #ecfdf5;
            --eco-border: rgba(15, 23, 42, 0.08);
        }
        body {
            background:
                radial-gradient(900px 520px at top right, rgba(16, 185, 129, 0.12), transparent 60%),
                radial-gradient(760px 420px at 10% 20%, rgba(14, 165, 163, 0.12), transparent 68%),
                linear-gradient(180deg, #f8fafc 0%, #eefbf4 100%);
            color: var(--eco-dark);
        }
        .glass-nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--eco-border);
        }
        .page-shell {
            max-width: 1320px;
            width: 100%;
            margin: 0 auto;
            padding-left: 16px;
            padding-right: 16px;
        }
        .hero-shell,
        .surface-card,
        .volunteer-card {
            border-radius: 28px;
            border: 1px solid var(--eco-border);
            background: rgba(255,255,255,0.94);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }
        .hero-shell {
            background: linear-gradient(135deg, #0f172a 0%, #14532d 45%, #10b981 100%);
            color: #fff;
            overflow: hidden;
            position: relative;
        }
        .hero-shell::after {
            content: "";
            width: 280px;
            height: 280px;
            position: absolute;
            border-radius: 50%;
            right: -90px;
            top: -100px;
            background: rgba(255,255,255,0.08);
        }
        .hero-shell h1 {
            color: #f8fffb !important;
            line-height: 1.02;
            letter-spacing: -0.035em;
            text-shadow: 0 10px 26px rgba(2, 6, 23, 0.24);
            max-width: 12ch;
        }
        .hero-shell p,
        .hero-shell .text-white-50 {
            color: rgba(241, 245, 249, 0.92) !important;
        }
        .hero-shell .badge {
            background: rgba(255,255,255,0.95) !important;
            color: #0f766e !important;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.18);
        }
        .metric-card {
            border-radius: 20px;
            border: 1px solid var(--eco-border);
            background: #fff;
            height: 100%;
        }
        .volunteer-card {
            overflow: hidden;
            height: 100%;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .volunteer-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 28px 56px rgba(15, 23, 42, 0.12);
            border-color: rgba(16,185,129,0.18);
        }
        .avatar {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            object-fit: cover;
            background: linear-gradient(135deg, #0f172a 0%, #0ea5a3 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.05rem;
            flex: 0 0 auto;
        }
        .pill-tag {
            background: var(--eco-soft);
            color: #047857;
        }
        .status-pill {
            background: #dcfce7;
            color: #166534;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.15rem;
        }
        .search-shell {
            background: rgba(255,255,255,0.92);
            border: 1px solid var(--eco-border);
            border-radius: 22px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
        }
        .search-shell .form-control {
            min-height: 52px;
            border-color: rgba(15, 23, 42, 0.12);
        }
        .volunteer-link {
            text-decoration: none;
            color: inherit;
        }
        .volunteer-link:hover {
            color: inherit;
        }
    </style>
</head>
<body>
    <?php render_public_nav('volunteer-list'); ?>

    <main class="page-shell py-4 py-lg-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'); ?> rounded-4 border-0 shadow-sm mb-4">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <section class="hero-shell p-4 p-lg-5 mb-4">
            <div class="row g-4 align-items-center position-relative">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-light text-success mb-3">Approved volunteers</span>
                    <h1 class="display-6 fw-bold mb-3">Browse the volunteer community.</h1>
                    <p class="fs-5 text-white-50 mb-4">See the volunteers who are ready for field work, coordination, and response support. Click any profile to view the public detail card.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-light btn-lg rounded-pill px-4" href="#volunteer-grid">Open the list</a>
                        <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="<?php echo is_logged_in() ? 'volunteer_opportunities.php' : 'become_volunteer.php'; ?>"><?php echo is_logged_in() ? 'Volunteer now' : 'Join as volunteer'; ?></a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="surface-card p-4 text-dark">
                        <div class="small text-uppercase text-secondary fw-semibold mb-3">Volunteer activity</div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Approved</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['approved']); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Verified</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['verified']); ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="metric-card p-3 h-100">
                                    <div class="text-secondary small">Active assignments</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['active_assignments']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="volunteer-grid">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                <div>
                    <h2 class="display-6 fw-bold mb-2">Volunteer roster</h2>
                    <p class="text-secondary mb-0"><?php echo $totalCards > 0 ? number_format($totalCards) . ' approved volunteers available.' : 'No approved volunteers found yet.'; ?></p>
                </div>
                <a class="btn btn-outline-success rounded-pill px-4" href="dashboard.php">Back to dashboard</a>
            </div>

            <div class="search-shell p-3 p-lg-4 mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8">
                        <label class="form-label fw-semibold mb-2" for="volunteer-search">Search volunteers</label>
                        <input id="volunteer-search" class="form-control form-control-lg rounded-4" type="search" placeholder="Search by name, district, division, occupation, or workplace">
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="small text-secondary mb-1">Results</div>
                        <div class="h4 fw-bold mb-0" id="volunteer-result-count"><?php echo number_format($totalCards); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!$volunteers): ?>
                <div class="surface-card p-5 text-center text-secondary" id="volunteer-empty-state">
                    <i class="fas fa-users fs-1 mb-3"></i>
                    <p class="mb-0">Volunteer profiles will appear here after approval.</p>
                </div>
            <?php else: ?>
                <div class="grid" id="volunteer-grid-cards">
                    <?php foreach ($volunteers as $volunteer): ?>
                        <article class="volunteer-card p-4" data-volunteer-card data-search="<?php echo e(strtolower(trim(($volunteer['full_name'] ?? '') . ' ' . ($volunteer['district'] ?? '') . ' ' . ($volunteer['division'] ?? '') . ' ' . ($volunteer['occupation'] ?? '') . ' ' . ($volunteer['workplace_name'] ?? '') . ' ' . ($volunteer['current_district'] ?? '') . ' ' . ($volunteer['current_division'] ?? '')))); ?>">
                            <a class="volunteer-link" href="volunteer_detail.php?id=<?php echo (int) $volunteer['id']; ?>">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <?php if (!empty($volunteer['profile_image_path'])): ?>
                                        <img class="avatar" src="<?php echo e((string) $volunteer['profile_image_path']); ?>" alt="<?php echo e((string) $volunteer['full_name']); ?>" onerror="this.outerHTML='<span class=\'avatar\'><?php echo e(volunteer_initials((string) $volunteer['full_name'])); ?></span>'">
                                    <?php else: ?>
                                        <span class="avatar"><?php echo e(volunteer_initials((string) $volunteer['full_name'])); ?></span>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                            <h3 class="h5 fw-bold mb-0"><?php echo e((string) $volunteer['full_name']); ?></h3>
                                            <span class="badge rounded-pill status-pill px-3 py-2">Approved</span>
                                        </div>
                                        <div class="text-secondary small mb-1"><?php echo e(trim((string) ($volunteer['district'] ?: 'Bangladesh'))); ?><?php echo !empty($volunteer['division']) ? ', ' . e((string) $volunteer['division']) : ''; ?></div>
                                        <div class="text-secondary small">Joined <?php echo e(date('M d, Y', strtotime((string) $volunteer['created_at']))); ?></div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php if (!empty($volunteer['occupation'])): ?>
                                        <span class="badge pill-tag rounded-pill px-3 py-2"><?php echo e((string) $volunteer['occupation']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($volunteer['verification_status'])): ?>
                                        <span class="badge text-bg-light rounded-pill px-3 py-2"><?php echo e(ucfirst((string) $volunteer['verification_status'])); ?></span>
                                    <?php endif; ?>
                                </div>

                                <p class="text-secondary small mb-0">
                                    <?php echo e(!empty($volunteer['address_line']) ? (string) $volunteer['address_line'] : (!empty($volunteer['current_district']) ? (string) $volunteer['current_district'] . ' based volunteer' : 'Ready for volunteer assignments.')); ?>
                                </p>

                                <div class="d-flex align-items-center justify-content-between mt-4">
                                    <span class="small text-secondary">View details</span>
                                    <span class="btn btn-sm btn-outline-success rounded-pill px-3">Open profile</span>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        (function () {
            var searchInput = document.getElementById('volunteer-search');
            var cards = Array.prototype.slice.call(document.querySelectorAll('[data-volunteer-card]'));
            var resultCount = document.getElementById('volunteer-result-count');
            var emptyState = document.getElementById('volunteer-empty-state');
            if (!searchInput || !cards.length || !resultCount) {
                return;
            }

            function updateResults() {
                var query = searchInput.value.trim().toLowerCase();
                var visibleCount = 0;

                cards.forEach(function (card) {
                    var text = (card.getAttribute('data-search') || '').toLowerCase();
                    var isVisible = query === '' || text.indexOf(query) !== -1;
                    card.style.display = isVisible ? '' : 'none';
                    if (isVisible) {
                        visibleCount += 1;
                    }
                });

                resultCount.textContent = visibleCount.toString();

                if (emptyState) {
                    emptyState.style.display = cards.length === 0 ? '' : 'none';
                }
            }

            searchInput.addEventListener('input', updateResults);
            updateResults();
        }());
    </script>
</body>
</html>