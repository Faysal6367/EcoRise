<?php
declare(strict_types=1);

require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$volunteerId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$volunteer = null;

if ($volunteerId) {
    $stmt = $pdo->prepare(
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
            ua.education_level,
            ua.institution_name,
            ua.current_division,
            ua.current_district,
            ua.current_full_address,
            ua.previous_project_name,
            ua.people_benefited,
            uv.status AS verification_status,
            uv.verified_at,
            COALESCE(active_assignments.assignment_count, 0) AS active_assignments,
            COALESCE(completed_assignments.assignment_count, 0) AS completed_assignments
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
         LEFT JOIN (
            SELECT volunteer_id, COUNT(*) AS assignment_count
            FROM volunteer_assignments
            WHERE status = 'active'
            GROUP BY volunteer_id
         ) active_assignments ON active_assignments.volunteer_id = u.id
         LEFT JOIN (
            SELECT volunteer_id, COUNT(*) AS assignment_count
            FROM volunteer_assignments
            WHERE status = 'completed'
            GROUP BY volunteer_id
         ) completed_assignments ON completed_assignments.volunteer_id = u.id
         WHERE u.id = ? AND u.volunteer_status = 'approved'
         LIMIT 1"
    );
    $stmt->execute([$volunteerId]);
    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
}

function volunteer_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : 'ER';
}

$showNotFound = !$volunteer;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Details | EcoRise</title>
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
            max-width: 1180px;
            width: 100%;
            margin: 0 auto;
            padding-left: 16px;
            padding-right: 16px;
        }
        .hero-shell,
        .surface-card,
        .info-card {
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
            width: 260px;
            height: 260px;
            position: absolute;
            border-radius: 50%;
            right: -90px;
            top: -100px;
            background: rgba(255,255,255,0.08);
        }
        .hero-shell h1,
        .hero-shell .lead {
            color: #f8fffb !important;
        }
        .avatar-lg {
            width: 108px;
            height: 108px;
            border-radius: 28px;
            object-fit: cover;
            background: linear-gradient(135deg, #0f172a 0%, #0ea5a3 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.3rem;
        }
        .info-card {
            height: 100%;
        }
        .meta-label {
            color: #64748b;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }
        .meta-value {
            font-weight: 700;
            color: #0f172a;
        }
        .pill-tag {
            background: var(--eco-soft);
            color: #047857;
        }
        .status-pill {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>
    <?php render_public_nav('volunteer-detail'); ?>

    <main class="page-shell py-4 py-lg-5">
        <section class="hero-shell p-4 p-lg-5 mb-4">
            <div class="row g-4 align-items-center position-relative">
                <div class="col-lg-8">
                    <a class="btn btn-light btn-sm rounded-pill px-3 mb-3" href="volunteer_list.php"><i class="fas fa-arrow-left me-2"></i>Back to volunteer list</a>
                    <h1 class="display-6 fw-bold mb-3"><?php echo $showNotFound ? 'Volunteer profile not found' : e((string) $volunteer['full_name']); ?></h1>
                    <p class="lead mb-0"><?php echo $showNotFound ? 'The volunteer you tried to open is unavailable or not approved.' : 'This profile shows the volunteer’s basic public details and assignment summary.'; ?></p>
                </div>
            </div>
        </section>

        <?php if ($showNotFound): ?>
            <div class="surface-card p-5 text-center">
                <div class="display-6 mb-3"><i class="fas fa-user-slash text-success"></i></div>
                <h2 class="h3 fw-bold mb-2">Profile unavailable</h2>
                <p class="text-secondary mb-4">This volunteer may not exist, may not be approved, or may have been removed.</p>
                <a class="btn btn-success rounded-pill px-4" href="volunteer_list.php">Return to list</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="surface-card p-4 p-lg-5 text-center">
                        <?php if (!empty($volunteer['profile_image_path'])): ?>
                            <img class="avatar-lg mb-3" src="<?php echo e((string) $volunteer['profile_image_path']); ?>" alt="<?php echo e((string) $volunteer['full_name']); ?>" onerror="this.outerHTML='<span class=\'avatar-lg mb-3\'><?php echo e(volunteer_initials((string) $volunteer['full_name'])); ?></span>'">
                        <?php else: ?>
                            <span class="avatar-lg mb-3"><?php echo e(volunteer_initials((string) $volunteer['full_name'])); ?></span>
                        <?php endif; ?>

                        <h2 class="h3 fw-bold mb-2"><?php echo e((string) $volunteer['full_name']); ?></h2>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                            <span class="badge status-pill rounded-pill px-3 py-2">Approved volunteer</span>
                            <?php if (!empty($volunteer['verification_status'])): ?>
                                <span class="badge text-bg-light rounded-pill px-3 py-2"><?php echo e(ucfirst((string) $volunteer['verification_status'])); ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="text-secondary mb-0"><?php echo e(trim((string) ($volunteer['district'] ?: 'Bangladesh'))); ?><?php echo !empty($volunteer['division']) ? ', ' . e((string) $volunteer['division']) : ''; ?></p>
                        <p class="text-secondary small mb-0">Joined <?php echo e(date('M d, Y', strtotime((string) $volunteer['created_at']))); ?></p>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="info-card p-4 p-lg-5 mb-4">
                        <h3 class="h4 fw-bold mb-4">Profile summary</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="meta-label mb-1">Current location</div>
                                <div class="meta-value"><?php echo e(!empty($volunteer['address_line']) ? (string) $volunteer['address_line'] : 'Not provided'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-label mb-1">Occupation</div>
                                <div class="meta-value"><?php echo e(!empty($volunteer['occupation']) ? (string) $volunteer['occupation'] : 'Not provided'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-label mb-1">Workplace</div>
                                <div class="meta-value"><?php echo e(!empty($volunteer['workplace_name']) ? (string) $volunteer['workplace_name'] : 'Not provided'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-label mb-1">Education</div>
                                <div class="meta-value"><?php echo e(!empty($volunteer['education_level']) ? (string) $volunteer['education_level'] : 'Not provided'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-label mb-1">Institution</div>
                                <div class="meta-value"><?php echo e(!empty($volunteer['institution_name']) ? (string) $volunteer['institution_name'] : 'Not provided'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-label mb-1">Verification</div>
                                <div class="meta-value"><?php echo e(!empty($volunteer['verified_at']) ? date('M d, Y', strtotime((string) $volunteer['verified_at'])) : 'Pending review'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-card p-4 text-center h-100">
                                <div class="meta-label mb-2">Active assignments</div>
                                <div class="display-6 fw-bold text-success mb-0"><?php echo number_format((int) $volunteer['active_assignments']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card p-4 text-center h-100">
                                <div class="meta-label mb-2">Completed assignments</div>
                                <div class="display-6 fw-bold text-success mb-0"><?php echo number_format((int) $volunteer['completed_assignments']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card p-4 text-center h-100">
                                <div class="meta-label mb-2">Profile focus</div>
                                <div class="h5 fw-bold mb-0"><?php echo e(!empty($volunteer['previous_project_name']) ? (string) $volunteer['previous_project_name'] : 'Community support'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card p-4 p-lg-5 mt-4">
                        <h3 class="h4 fw-bold mb-4">Volunteer tags</h3>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($volunteer['current_division'])): ?><span class="badge pill-tag rounded-pill px-3 py-2"><?php echo e((string) $volunteer['current_division']); ?></span><?php endif; ?>
                            <?php if (!empty($volunteer['current_district'])): ?><span class="badge pill-tag rounded-pill px-3 py-2"><?php echo e((string) $volunteer['current_district']); ?></span><?php endif; ?>
                            <?php if (!empty($volunteer['people_benefited'])): ?><span class="badge pill-tag rounded-pill px-3 py-2"><?php echo e((string) $volunteer['people_benefited']); ?> benefited</span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>