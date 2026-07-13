<?php
declare(strict_types=1);

function admin_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_nav_items(): array
{
    return [
        'index' => ['href' => 'index.php', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
        'campaigns' => ['href' => 'campaigns.php', 'icon' => 'fa-bullhorn', 'label' => 'Campaigns'],
        'campaign_approval' => ['href' => 'campaign_approval.php', 'icon' => 'fa-badge-check', 'label' => 'Approvals'],
        'users' => ['href' => 'users.php', 'icon' => 'fa-users', 'label' => 'Users'],
        'volunteers' => ['href' => 'volunteers.php', 'icon' => 'fa-hands-helping', 'label' => 'Volunteers'],
        'disaster_relief' => ['href' => 'disaster_relief.php', 'icon' => 'fa-triangle-exclamation', 'label' => 'Disaster Relief'],
    ];
}

function admin_render_start(string $pageTitle, string $activeKey, string $heroTitle, string $heroSubtitle, string $heroActionHtml = ''): void
{
    $navItems = admin_nav_items();
    $csrfToken = generate_csrf_token();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo admin_e($pageTitle); ?> | EcoRise Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --admin-bg: #f4f7fb;
            --admin-surface: rgba(255,255,255,0.94);
            --admin-border: rgba(15, 23, 42, 0.08);
            --admin-sidebar: #0f172a;
            --admin-text: #0f172a;
            --admin-muted: #64748b;
            --admin-green: #10b981;
            --admin-green-dark: #047857;
            --admin-warm: #f59e0b;
            --admin-red: #ef4444;
        }

        body {
            background:
                radial-gradient(1000px 520px at top right, rgba(16, 185, 129, 0.08), transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #eef5f9 100%);
            color: var(--admin-text);
        }

        .admin-shell {
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 288px;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            border-right: 1px solid rgba(255,255,255,0.05);
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .admin-brand {
            color: #ffffff;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
        }

        .admin-brand i {
            color: var(--admin-green);
        }

        .admin-sidebar-label {
            color: rgba(148, 163, 184, 0.82);
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-size: 0.76rem;
            font-weight: 700;
            margin-top: 0.75rem;
        }

        .admin-nav {
            display: grid;
            gap: 0.4rem;
            margin-top: 1.5rem;
        }

        .admin-nav-link {
            color: #d8e1ec;
            text-decoration: none;
            border-radius: 18px;
            padding: 0.95rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .admin-nav-link:hover {
            color: #ffffff;
            background: rgba(148, 163, 184, 0.10);
        }

        .admin-nav-link.active {
            background: linear-gradient(135deg, rgba(16,185,129,0.22) 0%, rgba(16,185,129,0.12) 100%);
            color: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(16,185,129,0.25);
        }

        .admin-nav-link.active i {
            color: #6ee7b7;
        }

        .admin-content {
            margin-left: 288px;
            min-height: 100vh;
        }

        .admin-topbar {
            padding: 1.25rem 1.5rem 0;
        }

        .admin-hero {
            margin: 0 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #14532d 45%, #10b981 100%);
            color: #ffffff;
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
            position: relative;
            overflow: hidden;
        }

        .admin-hero::after {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            right: -100px;
            top: -120px;
        }

        .admin-main {
            padding: 1.5rem;
        }

        .admin-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 24px;
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.08);
        }

        .admin-stat {
            border-radius: 20px;
            border: 1px solid var(--admin-border);
            background: rgba(255,255,255,0.92);
            height: 100%;
        }

        .admin-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(16,185,129,0.12);
            color: var(--admin-green-dark);
        }

        .admin-table thead th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-muted);
            border-bottom-color: rgba(148, 163, 184, 0.2);
        }

        .admin-table tbody td {
            vertical-align: middle;
            border-color: rgba(148, 163, 184, 0.14);
        }

        .admin-thumb {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            object-fit: cover;
        }

        .badge-soft-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-soft-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-soft-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-soft-info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .admin-modal .modal-content {
            border: 1px solid var(--admin-border);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.16);
        }

        .admin-section-title {
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .admin-notification {
            position: relative;
        }

        .admin-notification summary {
            list-style: none;
        }

        .admin-notification summary::-webkit-details-marker {
            display: none;
        }

        .admin-notification-btn {
            position: relative;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--admin-border);
            background: #fff;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .admin-notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            border-radius: 999px;
            background: var(--admin-red);
            color: #fff;
            text-align: center;
            line-height: 18px;
            font-size: 0.72rem;
            font-weight: 700;
            border: 2px solid #fff;
            padding: 0 4px;
        }

        .admin-notification-panel {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: min(360px, 92vw);
            border-radius: 16px;
            background: #fff;
            border: 1px solid var(--admin-border);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.14);
            padding: 10px;
            z-index: 1080;
        }

        .admin-notification-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--admin-border);
            padding: 6px 8px 10px;
        }

        .admin-notification-head button {
            border: 0;
            background: transparent;
            font-size: 0.82rem;
            color: var(--admin-green-dark);
            font-weight: 700;
            cursor: pointer;
        }

        .admin-notification-list {
            max-height: 320px;
            overflow: auto;
            padding-top: 4px;
        }

        .admin-notification-empty {
            margin: 0;
            color: var(--admin-muted);
            padding: 14px 10px;
        }

        .admin-notification-item {
            display: block;
            text-decoration: none;
            color: var(--admin-text);
            border-radius: 12px;
            border: 1px solid transparent;
            padding: 9px 10px;
            margin-top: 6px;
        }

        .admin-notification-item.unread {
            border-color: rgba(16, 185, 129, 0.28);
            background: rgba(16, 185, 129, 0.06);
        }

        .admin-notification-item:hover {
            border-color: rgba(16, 185, 129, 0.22);
            background: rgba(16, 185, 129, 0.08);
        }

        .admin-notification-item strong,
        .admin-notification-item span,
        .admin-notification-item small {
            display: block;
        }

        .admin-notification-item span {
            color: #334155;
            margin-top: 4px;
        }

        .admin-notification-item small {
            color: var(--admin-muted);
            margin-top: 2px;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                position: static;
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid rgba(255,255,255,0.05);
            }

            .admin-content {
                margin-left: 0;
            }

            .admin-hero,
            .admin-topbar,
            .admin-main {
                margin: 0;
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .admin-hero {
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <a class="admin-brand" href="../index.php"><i class="fas fa-leaf"></i><span>EcoRise</span></a>
                    <div class="admin-sidebar-label">Admin Console</div>
                </div>
            </div>

            <nav class="admin-nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <a class="admin-nav-link <?php echo $activeKey === $key ? 'active' : ''; ?>" href="<?php echo admin_e($item['href']); ?>">
                        <i class="fas <?php echo admin_e($item['icon']); ?>"></i>
                        <span><?php echo admin_e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-4 pt-3 border-top border-secondary-subtle">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Signed in</div>
                <div class="text-white fw-semibold mb-3"><?php echo admin_e($_SESSION['user_name'] ?? 'Administrator'); ?></div>
                <a class="btn btn-outline-light rounded-pill w-100" href="../logout.php">Logout</a>
            </div>
        </aside>

        <div class="admin-content">
            <div class="admin-topbar">
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <details class="admin-notification" data-notification-root data-notification-api-base="../" data-csrf-token="<?php echo admin_e($csrfToken); ?>" data-item-class="admin-notification-item" data-empty-class="admin-notification-empty">
                        <summary class="admin-notification-btn" aria-label="Notifications" data-notification-toggle>
                            <i class="fas fa-bell"></i>
                            <span class="admin-notification-badge" data-notification-unread hidden>0</span>
                        </summary>
                        <div class="admin-notification-panel" data-notification-dropdown>
                            <div class="admin-notification-head">
                                <strong>Notifications</strong>
                                <button type="button" data-mark-all-read>Mark all read</button>
                            </div>
                            <div class="admin-notification-list" data-notification-list>
                                <p class="admin-notification-empty">No notifications yet.</p>
                            </div>
                        </div>
                    </details>
                    <a class="btn btn-light rounded-pill px-4 shadow-sm" href="../index.php">View Website</a>
                </div>
            </div>

            <section class="admin-hero">
                <div class="position-relative" style="z-index:1;">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <div class="small text-uppercase text-white-50 fw-semibold mb-2">Operations</div>
                            <h1 class="display-6 fw-bold mb-2"><?php echo admin_e($heroTitle); ?></h1>
                            <p class="mb-0 text-white-50 fs-5"><?php echo admin_e($heroSubtitle); ?></p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <?php echo $heroActionHtml; ?>
                        </div>
                    </div>
                </div>
            </section>

            <main class="admin-main">
    <?php
}

function admin_render_flash(): void
{
    if (!isset($_SESSION['msg'])) {
        return;
    }

    $type = $_SESSION['msg_type'] ?? 'success';
    $class = $type === 'error' ? 'danger' : ($type === 'warning' ? 'warning' : 'success');
    ?>
    <div class="alert alert-<?php echo admin_e($class); ?> border-0 shadow-sm rounded-4 mb-4" role="alert">
        <?php echo admin_e($_SESSION['msg']); ?>
    </div>
    <?php
    unset($_SESSION['msg'], $_SESSION['msg_type']);
}

function admin_render_end(string $extraScripts = ''): void
{
    ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <?php echo $extraScripts; ?>
</body>
</html>
    <?php
}
