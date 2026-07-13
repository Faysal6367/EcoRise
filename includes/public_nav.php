<?php
declare(strict_types=1);

if (!function_exists('public_nav_escape')) {
    function public_nav_escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('public_nav_state')) {
    function public_nav_state(): array
    {
        static $state = null;

        if ($state !== null) {
            return $state;
        }

        $state = [
            'logged_in' => is_logged_in(),
            'is_admin' => is_admin(),
            'volunteer_status' => 'none',
            'user_name' => (string) ($_SESSION['user_name'] ?? 'EcoRise Member'),
            'profile_image_path' => '',
        ];

        if ($state['logged_in']) {
            global $pdo;

            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare('SELECT full_name, volunteer_status, profile_image_path FROM users WHERE id = ?');
                $stmt->execute([(int) $_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user) {
                    $state['user_name'] = (string) ($user['full_name'] ?? $state['user_name']);
                    $state['volunteer_status'] = (string) ($user['volunteer_status'] ?? 'none');
                    $state['profile_image_path'] = (string) ($user['profile_image_path'] ?? '');
                }
            }
        }

        $parts = preg_split('/\s+/', trim($state['user_name'])) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        $state['initials'] = $initials !== '' ? $initials : 'ER';

        return $state;
    }
}

if (!function_exists('render_public_nav')) {
    function render_public_nav(string $activePage = ''): void
    {
        $state = public_nav_state();
        $volunteerStatus = $state['volunteer_status'];
        $csrfToken = generate_csrf_token();

        $primaryLinks = [
            ['key' => 'home', 'href' => 'index.php', 'label' => 'Home'],
            ['key' => 'about', 'href' => 'about.php', 'label' => 'About'],
            ['key' => 'blog', 'href' => 'blog.php', 'label' => 'Blog'],
            ['key' => 'opportunities', 'href' => 'opportunities.php', 'label' => 'Opportunities'],
        ];

        if ($state['logged_in']) {
            $primaryLinks[] = ['key' => 'project-create', 'href' => 'create_campaign.php', 'label' => 'Start a Project', 'accent' => true];

            if (!in_array($volunteerStatus, ['approved', 'pending'], true)) {
                $primaryLinks[] = ['key' => 'volunteer-apply', 'href' => 'become_volunteer.php', 'label' => 'Become Volunteer'];
            }

            if ($volunteerStatus === 'approved') {
                $primaryLinks[] = ['key' => 'volunteer-create', 'href' => 'volunteer_create_campaign.php', 'label' => 'Create Opportunity'];
            }
        }

        $utilityLinks = [];
        if ($state['logged_in']) {
            $utilityLinks[] = ['key' => 'dashboard', 'href' => 'dashboard.php', 'label' => 'Dashboard'];
        }
        ?>
        <header class="site-nav-wrap">
            <nav class="site-nav" aria-label="Primary">
                <div class="site-nav__inner">
                    <a class="site-nav__brand" href="index.php" aria-label="EcoRise home">
                        <span class="site-nav__brand-mark"><i class="fas fa-leaf"></i></span>
                        <span class="site-nav__brand-text">
                            <strong>EcoRise</strong>
                            <small>Action for a greener future</small>
                        </span>
                    </a>

                    <button class="site-nav__toggle" type="button" aria-expanded="false" aria-controls="site-nav-menu" data-nav-toggle>
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div class="site-nav__menu" id="site-nav-menu" data-nav-menu>
                        <div class="site-nav__links">
                            <?php foreach ($primaryLinks as $link): ?>
                                <?php
                                $classes = ['site-nav__link'];
                                if ($activePage === $link['key']) {
                                    $classes[] = 'is-active';
                                }
                                if (!empty($link['accent'])) {
                                    $classes[] = 'is-accent';
                                }
                                if (!empty($link['danger'])) {
                                    $classes[] = 'is-danger';
                                }
                                ?>
                                <a class="<?php echo public_nav_escape(implode(' ', $classes)); ?>" href="<?php echo public_nav_escape($link['href']); ?>">
                                    <?php echo public_nav_escape($link['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="site-nav__tools">
                            <?php foreach ($utilityLinks as $link): ?>
                                <a class="site-nav__link site-nav__link--ghost <?php echo $activePage === $link['key'] ? 'is-active' : ''; ?>" href="<?php echo public_nav_escape($link['href']); ?>">
                                    <?php echo public_nav_escape($link['label']); ?>
                                </a>
                            <?php endforeach; ?>

                            <?php if ($state['logged_in']): ?>
                                <details class="site-nav__notification" data-notification-root data-notification-api-base="" data-csrf-token="<?php echo public_nav_escape($csrfToken); ?>">
                                    <summary class="site-nav__notification-button" aria-label="Notifications" data-notification-toggle>
                                        <i class="fas fa-bell"></i>
                                        <span class="site-nav__notification-badge" data-notification-unread hidden>0</span>
                                    </summary>
                                    <div class="site-nav__notification-panel" data-notification-dropdown>
                                        <div class="site-nav__notification-head">
                                            <strong>Notifications</strong>
                                            <button type="button" data-mark-all-read>Mark all read</button>
                                        </div>
                                        <div class="site-nav__notification-list" data-notification-list>
                                            <p class="site-nav__notification-empty">No notifications yet.</p>
                                        </div>
                                    </div>
                                </details>

                                <details class="site-nav__account">
                                    <summary class="site-nav__avatar" aria-label="Account menu">
                                        <?php if (!empty($state['profile_image_path'])): ?>
                                            <img src="<?php echo public_nav_escape($state['profile_image_path']); ?>" alt="<?php echo public_nav_escape($state['user_name']); ?>">
                                        <?php else: ?>
                                            <span><?php echo public_nav_escape($state['initials']); ?></span>
                                        <?php endif; ?>
                                    </summary>
                                    <div class="site-nav__dropdown">
                                        <div class="site-nav__dropdown-head">
                                            <strong><?php echo public_nav_escape($state['user_name']); ?></strong>
                                            <small><?php echo public_nav_escape($state['is_admin'] ? 'Administrator' : 'Member'); ?></small>
                                        </div>
                                        <a href="dashboard.php#profile-form">Profile</a>
                                        <a href="dashboard.php">Dashboard</a>
                                        <?php if ($volunteerStatus === 'approved'): ?>
                                            <a href="volunteer_opportunities.php">Volunteer Now</a>
                                        <?php endif; ?>
                                        <?php if ($state['is_admin']): ?>
                                            <a href="admin/index.php">Admin Area</a>
                                        <?php endif; ?>
                                        <a href="logout.php">Logout</a>
                                    </div>
                                </details>
                            <?php else: ?>
                                <a class="site-nav__button site-nav__button--muted" href="signin.php">Sign In</a>
                                <a class="site-nav__button" href="signup.php">Join Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
        <script>
            (function () {
                var nav = document.querySelector('.site-nav');
                if (!nav || nav.dataset.enhanced === 'true') {
                    return;
                }

                nav.dataset.enhanced = 'true';

                var toggle = nav.querySelector('[data-nav-toggle]');
                var menu = nav.querySelector('[data-nav-menu]');

                if (!toggle || !menu) {
                    return;
                }

                toggle.addEventListener('click', function () {
                    var isOpen = menu.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                document.addEventListener('click', function (event) {
                    if (!nav.contains(event.target) && menu.classList.contains('is-open')) {
                        menu.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });

                window.addEventListener('resize', function () {
                    if (window.innerWidth > 980 && menu.classList.contains('is-open')) {
                        menu.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }());
        </script>
        <?php if ($state['logged_in']): ?>
            <script src="assets/js/notifications.js"></script>
        <?php endif; ?>
        <?php
    }
}
