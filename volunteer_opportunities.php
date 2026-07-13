<?php
/**
 * Volunteer Opportunities Page
 * 
 * Display natural disaster relief opportunities and allow approved volunteers to sign up.
 * Only approved volunteers can access this page.
 */

require 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please log in to view volunteer opportunities.', 'warning');
}

// Check if user is an approved volunteer
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT volunteer_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['volunteer_status'] !== 'approved') {
    $status = $user['volunteer_status'] ?? 'none';
    if ($status === 'pending') {
        redirect('index.php', 'Your volunteer application is still pending approval. Please wait for admin approval.', 'warning');
    } elseif ($status === 'rejected') {
        redirect('index.php', 'Your volunteer application was rejected. Contact support for more information.', 'danger');
    } else {
        redirect('become_volunteer.php', 'You must be an approved volunteer to view opportunities. Apply now!', 'warning');
    }
}

// Fetch all active disaster relief campaigns
$stmt = $pdo->prepare("SELECT * FROM disaster_relief_campaigns WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$disasters = $stmt->fetchAll();

// Fetch volunteer's current assignments
$stmt = $pdo->prepare("SELECT disaster_relief_id FROM volunteer_assignments WHERE volunteer_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$assignments = $stmt->fetchAll();
$assigned_disaster_ids = array_column($assignments, 'disaster_relief_id');

$total_opportunities = count($disasters);
$my_active_assignments = count($assigned_disaster_ids);
$open_slots = 0;
foreach ($disasters as $disaster_item) {
    $needed = (int)($disaster_item['volunteers_needed'] ?? 0);
    $assigned = (int)($disaster_item['volunteers_assigned'] ?? 0);
    $open_slots += max(0, $needed - $assigned);
}

$relief_image_fallbacks = [
    'Flood Relief' => 'https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=1200&q=80',
    'Cyclone Preparedness' => 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=1200&q=80',
    'Wildfire Response' => 'https://images.unsplash.com/photo-1475776408506-9a5371e7a068?auto=format&fit=crop&w=1200&q=80',
    'Earthquake Relief' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1200&q=80',
    'Landslide Prevention' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80',
    'Drought Support' => 'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=1200&q=80',
];
$default_disaster_image = 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=1200&q=80';

// Get message from session if exists
$message = '';
$message_type = 'success';
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    $message_type = $_SESSION['msg_type'] ?? 'success';
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Opportunities - Natural Disaster Relief</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        :root {
            --bg: #f3f6f8;
            --surface: #ffffff;
            --surface-muted: #eef3f8;
            --text: #0f172a;
            --muted: #526071;
            --primary: #0ea5a3;
            --primary-dark: #0f766e;
            --danger: #d84c3f;
            --danger-dark: #b2382c;
            --success: #1f9d62;
            --ring: rgba(14, 165, 163, 0.18);
            --shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            --radius: 16px;
        }
        body {
            font-family: 'Manrope', sans-serif;
            color: var(--text);
            background:
                radial-gradient(980px 560px at 94% -12%, rgba(14,165,163,0.2), transparent 70%),
                radial-gradient(760px 420px at -8% 10%, rgba(216,76,63,0.14), transparent 72%),
                radial-gradient(680px 360px at 50% 118%, rgba(56,189,248,0.15), transparent 76%),
                var(--bg);
            position: relative;
            overflow-x: hidden;
        }
        body::before,
        body::after {
            content: "";
            position: fixed;
            z-index: -1;
            border-radius: 999px;
            filter: blur(8px);
            opacity: 0.55;
            pointer-events: none;
        }
        body::before {
            width: 320px;
            height: 320px;
            top: 110px;
            left: -110px;
            background: radial-gradient(circle at 30% 30%, rgba(34, 211, 238, 0.36), rgba(34, 211, 238, 0));
            animation: driftA 14s ease-in-out infinite;
        }
        body::after {
            width: 360px;
            height: 360px;
            bottom: -120px;
            right: -110px;
            background: radial-gradient(circle at 70% 70%, rgba(20, 184, 166, 0.36), rgba(20, 184, 166, 0));
            animation: driftB 18s ease-in-out infinite;
        }
        .hero-wrap {
            max-width: 1250px;
            margin: 28px auto 0;
            padding: 0 20px;
        }
        .disaster-header {
            background: linear-gradient(125deg, #0d615d 0%, #0ea5a3 52%, #38bdf8 100%);
            color: #fff;
            border-radius: 20px;
            padding: 42px 34px;
            box-shadow: 0 24px 52px rgba(15, 23, 42, 0.2);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.28);
            backdrop-filter: blur(5px);
        }
        .disaster-header::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 36px 36px;
            opacity: 0.3;
        }
        .disaster-header::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -90px;
            top: -80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
        }
        .disaster-header h1 {
            font-size: clamp(1.8rem, 3.2vw, 2.7rem);
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .disaster-header p {
            font-size: 1.04rem;
            margin: 12px 0 0;
            max-width: 760px;
            opacity: 0.95;
            line-height: 1.65;
        }
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }
        .stat-pill {
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            padding: 12px 14px;
            backdrop-filter: blur(4px);
        }
        .stat-pill .value {
            display: block;
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .stat-pill .label {
            display: block;
            margin-top: 2px;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            opacity: 0.95;
        }
        .main-wrap {
            max-width: 1250px;
            margin: 26px auto 0;
            padding: 0 20px 30px;
        }
        .alert {
            border-left-width: 5px;
            border-radius: 10px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 22px;
            padding: 6px 0 0;
        }
        .disaster-card {
            background: linear-gradient(165deg, #ffffff 0%, #f7fcff 100%);
            border: 1px solid #dbe8f1;
            border-radius: var(--radius);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.1);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            animation: fadeLift 0.6s ease forwards;
            opacity: 0;
            cursor: pointer;
        }
        .disaster-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 44px rgba(15, 23, 42, 0.16);
            border-color: #9bd7d9;
        }
        .grid-4 .disaster-card:nth-child(2) { animation-delay: 0.08s; }
        .grid-4 .disaster-card:nth-child(3) { animation-delay: 0.16s; }
        .grid-4 .disaster-card:nth-child(4) { animation-delay: 0.24s; }
        .grid-4 .disaster-card:nth-child(5) { animation-delay: 0.32s; }
        .grid-4 .disaster-card:nth-child(6) { animation-delay: 0.4s; }

        @keyframes fadeLift {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes driftA {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -14px); }
        }
        @keyframes driftB {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-16px, 18px); }
        }
        .disaster-image {
            width: 100%;
            height: 156px;
            background: linear-gradient(125deg, #0f766e 0%, #0ea5a3 60%, #34d399 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 2.65rem;
            position: relative;
            overflow: hidden;
        }
        .disaster-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .disaster-image::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(2, 6, 23, 0.08) 0%, rgba(2, 6, 23, 0.22) 100%);
            pointer-events: none;
        }
        .disaster-image i {
            position: relative;
            z-index: 1;
        }
        .disaster-content {
            padding: 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .relief-type-badge {
            display: inline-block;
            background: #ecfeff;
            color: #0f766e;
            border: 1px solid #99f6e4;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .assigned-badge {
            background: #ecfdf3;
            color: var(--success);
            border: 1px solid #a7f3d0;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .disaster-title {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.01em;
            color: #0f172a;
            line-height: 1.35;
        }
        .disaster-location {
            color: #334155;
            font-size: 0.92rem;
            margin: 0;
            font-weight: 600;
        }
        .disaster-description {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
            margin: 0;
            flex: 1;
        }
        .volunteers-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin: 0;
        }
        .volunteer-stat {
            text-align: center;
            background: linear-gradient(180deg, #eef8ff 0%, #f8fafc 100%);
            border: 1px solid #d8e6ef;
            border-radius: 12px;
            padding: 10px 8px;
        }
        .volunteer-stat .stat-number {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.1;
        }
        .volunteer-stat .stat-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            font-weight: 700;
            margin-top: 4px;
        }
        .progress-track {
            width: 100%;
            height: 9px;
            border-radius: 999px;
            background: #dbe5ef;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, #22c55e 58%, #38bdf8 100%);
            transition: width 0.3s ease;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 2px;
        }
        .view-hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #0f766e;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            background: #ecfeff;
            border: 1px solid #b6f1f0;
            border-radius: 999px;
            padding: 6px 10px;
            margin-top: 2px;
        }
        .details-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(2, 6, 23, 0.62);
            z-index: 3000;
        }
        .details-modal.show {
            display: flex;
        }
        .details-dialog {
            width: min(920px, 100%);
            max-height: 92vh;
            overflow: auto;
            border-radius: 18px;
            border: 1px solid #cfe2ef;
            background: linear-gradient(160deg, #ffffff 0%, #f6fbff 100%);
            box-shadow: 0 26px 64px rgba(15, 23, 42, 0.35);
        }
        .details-media {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
            background: linear-gradient(135deg, #0f766e 0%, #0ea5a3 60%, #34d399 100%);
        }
        .details-body {
            padding: 22px;
        }
        .details-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .close-details {
            border: none;
            background: #e2e8f0;
            color: #0f172a;
            width: 36px;
            height: 36px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
        }
        .close-details:hover {
            background: #cbd5e1;
        }
        .details-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .meta-box {
            background: #eef6fb;
            border: 1px solid #d4e7f3;
            border-radius: 12px;
            padding: 10px;
            text-align: center;
        }
        .meta-box .v {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f766e;
            line-height: 1.2;
        }
        .meta-box .k {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            font-weight: 700;
        }
        .btn-assign,
        .btn-leave {
            flex: 1;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.91rem;
            padding: 10px 14px;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.25s ease;
        }
        .btn-assign {
            background: linear-gradient(135deg, var(--danger) 0%, #f97316 100%);
            color: #fff;
            box-shadow: 0 8px 18px rgba(216, 76, 63, 0.25);
        }
        .btn-assign:hover {
            background: linear-gradient(135deg, var(--danger-dark) 0%, #ea580c 100%);
            transform: translateY(-1px);
        }
        .btn-leave {
            background: #475569;
            color: #fff;
            box-shadow: 0 8px 18px rgba(71, 85, 105, 0.25);
        }
        .btn-leave:hover {
            background: #334155;
            transform: translateY(-1px);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .no-disasters {
            text-align: center;
            padding: 68px 20px;
            border: 1px dashed #b8d3e5;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }
        .no-disasters i {
            font-size: 3em;
            color: #6b8aa1;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .hero-wrap,
            .main-wrap {
                padding-left: 14px;
                padding-right: 14px;
            }
            .disaster-header {
                padding: 26px 18px;
                border-radius: 16px;
            }
            .grid-4 {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .disaster-content {
                padding: 15px;
            }
            .top-row {
                flex-wrap: wrap;
            }
            .details-media {
                height: 220px;
            }
            .details-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php render_public_nav('volunteer-hub'); ?>

    <!-- Header Section -->
    <div class="hero-wrap">
        <div class="disaster-header">
            <h1><i class="fas fa-hand-holding-heart"></i> Volunteer Opportunities</h1>
            <p>Join coordinated disaster response missions and contribute directly where communities need support the most.</p>
            <div class="quick-stats">
                <div class="stat-pill">
                    <span class="value"><?php echo number_format($total_opportunities); ?></span>
                    <span class="label">Active Missions</span>
                </div>
                <div class="stat-pill">
                    <span class="value"><?php echo number_format($my_active_assignments); ?></span>
                    <span class="label">My Assignments</span>
                </div>
                <div class="stat-pill">
                    <span class="value"><?php echo number_format($open_slots); ?></span>
                    <span class="label">Open Volunteer Slots</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-wrap">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo ($message_type === 'success') ? 'check-circle' : (($message_type === 'warning') ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($disasters)): ?>
            <div class="no-disasters">
                <i class="fas fa-inbox"></i>
                <h2>No Active Relief Campaigns</h2>
                <p>There are currently no active disaster relief campaigns. Please check back soon.</p>
            </div>
        <?php else: ?>
            <div class="grid-4">
                <?php foreach ($disasters as $disaster): ?>
                    <?php
                    $progress = $disaster['volunteers_needed'] > 0
                        ? round(($disaster['volunteers_assigned'] / $disaster['volunteers_needed']) * 100)
                        : 100;
                    $progress = max(0, min(100, $progress));
                    $img_path = trim((string)($disaster['image_path'] ?? ''));
                    $display_image = $img_path !== ''
                        ? $img_path
                        : ($relief_image_fallbacks[$disaster['relief_type']] ?? $default_disaster_image);
                    $full_description = trim((string)($disaster['description'] ?? ''));
                    $full_description = preg_replace('/\s+/', ' ', $full_description);
                    ?>
                    <div class="disaster-card" 
                        tabindex="0"
                        data-title="<?php echo htmlspecialchars($disaster['title'], ENT_QUOTES); ?>"
                        data-relief="<?php echo htmlspecialchars($disaster['relief_type'], ENT_QUOTES); ?>"
                        data-location="<?php echo htmlspecialchars($disaster['location'], ENT_QUOTES); ?>"
                        data-description="<?php echo htmlspecialchars($full_description, ENT_QUOTES); ?>"
                        data-needed="<?php echo (int)$disaster['volunteers_needed']; ?>"
                        data-assigned="<?php echo (int)$disaster['volunteers_assigned']; ?>"
                        data-progress="<?php echo (int)$progress; ?>"
                        data-image="<?php echo htmlspecialchars($display_image, ENT_QUOTES); ?>">
                        <!-- Image Section -->
                        <div class="disaster-image">
                            <?php 
                            $icon_map = [
                                'Flood Relief' => 'fas fa-water',
                                'Cyclone Preparedness' => 'fas fa-wind',
                                'Wildfire Response' => 'fas fa-fire',
                                'Earthquake Relief' => 'fas fa-burst',
                                'Landslide Prevention' => 'fas fa-mountain',
                                'Drought Support' => 'fas fa-sun'
                            ];
                            $icon = $icon_map[$disaster['relief_type']] ?? 'fas fa-heart';
                            ?>
                            <img src="<?php echo htmlspecialchars($display_image); ?>" alt="<?php echo htmlspecialchars($disaster['title']); ?>" loading="lazy" onerror="this.src='<?php echo htmlspecialchars($default_disaster_image, ENT_QUOTES); ?>';">
                        </div>

                        <!-- Content Section -->
                        <div class="disaster-content">
                            <div class="top-row">
                                <span class="relief-type-badge">
                                    <?php echo htmlspecialchars($disaster['relief_type']); ?>
                                </span>

                                <?php if (in_array($disaster['id'], $assigned_disaster_ids)): ?>
                                    <span class="assigned-badge"><i class="fas fa-check"></i> Assigned</span>
                                <?php endif; ?>
                            </div>

                            <div class="disaster-title">
                                <?php echo htmlspecialchars($disaster['title']); ?>
                            </div>

                            <div class="disaster-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($disaster['location']); ?>
                            </div>

                            <div class="disaster-description">
                                <?php 
                                $desc_preview = trim((string)$disaster['description']);
                                echo htmlspecialchars(strlen($desc_preview) > 150 ? substr($desc_preview, 0, 150) . '...' : $desc_preview);
                                ?>
                            </div>

                            <div class="view-hint"><i class="fas fa-eye"></i> Click card to view details</div>

                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                            </div>

                            <div class="volunteers-info">
                                <div class="volunteer-stat">
                                    <div class="stat-number"><?php echo $disaster['volunteers_assigned']; ?></div>
                                    <div class="stat-label">Assigned</div>
                                </div>
                                <div class="volunteer-stat">
                                    <div class="stat-number"><?php echo $disaster['volunteers_needed']; ?></div>
                                    <div class="stat-label">Needed</div>
                                </div>
                                <div class="volunteer-stat">
                                    <div class="stat-number" id="progress-<?php echo $disaster['id']; ?>">
                                        <?php echo $progress; ?>%
                                    </div>
                                    <div class="stat-label">Progress</div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <?php if (in_array($disaster['id'], $assigned_disaster_ids)): ?>
                                    <form method="POST" action="process_volunteer_assignment.php" style="flex: 1;" onclick="event.stopPropagation();">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="relief_id" value="<?php echo $disaster['id']; ?>">
                                        <input type="hidden" name="action" value="leave">
                                        <button type="submit" class="btn-leave" onclick="event.stopPropagation(); return confirm('Are you sure you want to leave this assignment?');">
                                            <i class="fas fa-sign-out-alt"></i> Leave
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="process_volunteer_assignment.php" style="flex: 1;" onclick="event.stopPropagation();">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="relief_id" value="<?php echo $disaster['id']; ?>">
                                        <input type="hidden" name="action" value="join">
                                        <button type="submit" class="btn-assign" onclick="event.stopPropagation();">
                                            <i class="fas fa-plus-circle"></i> Volunteer
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <div id="details-modal" class="details-modal" aria-hidden="true">
        <div class="details-dialog" role="dialog" aria-modal="true" aria-label="Opportunity details">
            <img id="details-image" class="details-media" src="" alt="Mission cover">
            <div class="details-body">
                <div class="details-top">
                    <span id="details-relief" class="relief-type-badge"></span>
                    <button type="button" id="close-details" class="close-details" aria-label="Close details"><i class="fas fa-times"></i></button>
                </div>
                <h2 id="details-title" style="margin: 0 0 10px 0; color: #0f172a; font-weight: 800;"></h2>
                <p id="details-location" style="margin: 0 0 10px 0; color: #334155; font-weight: 700;"></p>
                <p id="details-description" style="margin: 0; color: #526071; line-height: 1.7;"></p>
                <div class="details-meta">
                    <div class="meta-box">
                        <div id="details-assigned" class="v"></div>
                        <div class="k">Assigned</div>
                    </div>
                    <div class="meta-box">
                        <div id="details-needed" class="v"></div>
                        <div class="k">Needed</div>
                    </div>
                    <div class="meta-box">
                        <div id="details-progress" class="v"></div>
                        <div class="k">Progress</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w3-container w3-padding-64" style="background:#0f172a; color:white; margin-top: 40px;">
        <div class="w3-content" style="max-width: 1200px;">
            <div class="w3-row-padding">
                <div class="w3-third w3-margin-bottom">
                    <h3 class="w3-xlarge w3-text-green" style="font-weight: 800;"><i class="fas fa-leaf"></i> EcoRise</h3>
                    <p class="w3-text-gray">A dedicated platform connecting volunteers to urgent environmental and disaster response missions.</p>
                    <div class="w3-padding-16">
                        <i class="fab fa-facebook-f w3-hover-text-green w3-large w3-margin-right"></i>
                        <i class="fab fa-instagram w3-hover-text-green w3-large w3-margin-right"></i>
                        <i class="fab fa-twitter w3-hover-text-green w3-large w3-margin-right"></i>
                        <i class="fab fa-linkedin-in w3-hover-text-green w3-large"></i>
                    </div>
                </div>
                <div class="w3-third w3-margin-bottom">
                    <h3 style="font-weight: 600;">Volunteer</h3>
                    <ul class="w3-ul" style="border:none">
                        <li><a href="volunteer_opportunities.php" class="w3-hover-text-green" style="text-decoration:none">Active Missions</a></li>
                        <li><a href="volunteer_create_campaign.php" class="w3-hover-text-green" style="text-decoration:none">Create Opportunity</a></li>
                        <li><a href="dashboard.php" class="w3-hover-text-green" style="text-decoration:none">My Dashboard</a></li>
                        <li><a href="support.php" class="w3-hover-text-green" style="text-decoration:none">Support</a></li>
                    </ul>
                </div>
                <div class="w3-third w3-margin-bottom">
                    <h3 style="font-weight: 600;">Trust & Safety</h3>
                    <p class="w3-text-gray">Volunteer assignments are tracked and verified to ensure reliable, transparent community response.</p>
                    <p class="w3-small w3-text-green"><i class="fas fa-shield-alt"></i> Verified Volunteer Coordination</p>
                </div>
            </div>
            <hr style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 32px;">
            <div class="w3-center w3-padding-16 w3-small w3-text-gray">
                &copy; <?php echo date("Y"); ?> EcoRise Global Crowdfunding. Created for the Future of our Planet.
            </div>
        </div>
    </footer>

    <script>
        const detailsModal = document.getElementById('details-modal');
        const closeDetailsBtn = document.getElementById('close-details');
        const cards = document.querySelectorAll('.disaster-card');

        function openDetails(card) {
            const image = card.dataset.image || '';
            document.getElementById('details-title').textContent = card.dataset.title || 'Opportunity';
            document.getElementById('details-relief').textContent = card.dataset.relief || 'Relief';
            document.getElementById('details-location').innerHTML = '<i class="fas fa-map-marker-alt"></i> ' + (card.dataset.location || 'Location not specified');
            document.getElementById('details-description').textContent = card.dataset.description || 'No description available.';
            document.getElementById('details-assigned').textContent = card.dataset.assigned || '0';
            document.getElementById('details-needed').textContent = card.dataset.needed || '0';
            document.getElementById('details-progress').textContent = (card.dataset.progress || '0') + '%';

            const imgEl = document.getElementById('details-image');
            if (image) {
                imgEl.style.display = 'block';
                imgEl.src = image;
            } else {
                imgEl.style.display = 'none';
                imgEl.removeAttribute('src');
            }

            detailsModal.classList.add('show');
            detailsModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeDetails() {
            detailsModal.classList.remove('show');
            detailsModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        cards.forEach((card) => {
            card.addEventListener('click', (event) => {
                if (event.target.closest('form, button, a, input, select, textarea')) {
                    return;
                }
                openDetails(card);
            });
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openDetails(card);
                }
            });
        });

        closeDetailsBtn.addEventListener('click', closeDetails);
        detailsModal.addEventListener('click', (event) => {
            if (event.target === detailsModal) {
                closeDetails();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && detailsModal.classList.contains('show')) {
                closeDetails();
            }
        });
    </script>
</body>
</html>
