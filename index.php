<?php
declare(strict_types=1);

/**
 * EcoRise - Homepage
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$volunteerUser = null;
$userMap = [
    'lat' => 23.8103,
    'lng' => 90.4125,
    'label' => 'EcoRise Bangladesh',
    'hasProfileLocation' => false,
];

if (is_logged_in()) {
    $userStmt = $pdo->prepare('SELECT full_name, volunteer_status, division, district, latitude, longitude FROM users WHERE id = ?');
    $userStmt->execute([(int) $_SESSION['user_id']]);
    $volunteerUser = $userStmt->fetch();

    if ($volunteerUser) {
        if ($volunteerUser['latitude'] !== null && $volunteerUser['longitude'] !== null) {
            $userMap['lat'] = (float) $volunteerUser['latitude'];
            $userMap['lng'] = (float) $volunteerUser['longitude'];
            $userMap['hasProfileLocation'] = true;
        }

        $parts = array_filter([
            $volunteerUser['full_name'] ?? null,
            $volunteerUser['district'] ?? null,
            $volunteerUser['division'] ?? null,
        ]);
        if ($parts) {
            $userMap['label'] = implode(', ', array_map(static fn($value) => (string) $value, $parts));
        }
    }
}

$mapZoom = $userMap['hasProfileLocation'] ? 14 : 7;
$mapDelta = $userMap['hasProfileLocation'] ? 0.02 : 0.8;
$mapLeft = max(-180, $userMap['lng'] - $mapDelta);
$mapRight = min(180, $userMap['lng'] + $mapDelta);
$mapBottom = max(-90, $userMap['lat'] - $mapDelta);
$mapTop = min(90, $userMap['lat'] + $mapDelta);
$userMap['embedUrl'] = sprintf(
    'https://www.openstreetmap.org/export/embed.html?bbox=%s%%2C%s%%2C%s%%2C%s&layer=mapnik&marker=%s%%2C%s',
    rawurlencode((string) $mapLeft),
    rawurlencode((string) $mapBottom),
    rawurlencode((string) $mapRight),
    rawurlencode((string) $mapTop),
    rawurlencode((string) $userMap['lat']),
    rawurlencode((string) $userMap['lng'])
);
$userMap['openUrl'] = sprintf(
    'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=%d/%s/%s',
    rawurlencode((string) $userMap['lat']),
    rawurlencode((string) $userMap['lng']),
    $mapZoom,
    rawurlencode((string) $userMap['lat']),
    rawurlencode((string) $userMap['lng'])
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRise | Sustainable Crowdfunding for a Greener Planet</title>
    <meta name="description" content="EcoRise connects supporters, volunteers, and campaign founders around environmental action.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --eco-dark: #0f172a;
            --eco-green: #10b981;
            --eco-border: rgba(15,23,42,0.08);
        }
        body {
            background: #f8fafc;
            color: var(--eco-dark);
        }
        .glass-nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--eco-border);
        }
        .hero-shell {
            background:
                linear-gradient(115deg, rgba(15,23,42,0.88) 0%, rgba(6,95,70,0.78) 46%, rgba(16,185,129,0.42) 100%),
                url('https://images.unsplash.com/photo-1473448912268-2022ce9509d8?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
            min-height: 84vh;
            display: flex;
            align-items: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .hero-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(600px 260px at 20% 24%, rgba(255,255,255,0.14), transparent 60%),
                radial-gradient(420px 240px at 85% 18%, rgba(34,211,238,0.18), transparent 60%),
                linear-gradient(180deg, rgba(15,23,42,0.10), rgba(15,23,42,0.22));
            pointer-events: none;
        }
        .hero-shell .container {
            position: relative;
            z-index: 1;
        }
        .hero-copy {
            max-width: 760px;
            padding: 2rem 2rem 2.25rem;
            border-radius: 32px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.28), rgba(15, 23, 42, 0.10));
            border: 1px solid rgba(255,255,255,0.16);
            backdrop-filter: blur(12px);
            box-shadow: 0 24px 60px rgba(2, 6, 23, 0.20);
        }
        .hero-shell .badge {
            background: rgba(255,255,255,0.94) !important;
            color: #0f766e !important;
            box-shadow: 0 12px 24px rgba(15,23,42,0.16);
            letter-spacing: 0.01em;
        }
        .hero-shell h1 {
            color: #f8fffb !important;
            line-height: 0.98;
            letter-spacing: -0.04em;
            text-shadow: 0 10px 30px rgba(2, 6, 23, 0.28);
            max-width: 11ch;
        }
        .hero-shell p {
            color: rgba(241, 245, 249, 0.92) !important;
            max-width: 720px;
        }
        .hero-shell .btn-light {
            background: linear-gradient(135deg, #ffffff 0%, #e2f8ee 100%);
            color: #0f172a;
            border: 0;
            box-shadow: 0 16px 30px rgba(15,23,42,0.18);
        }
        .hero-shell .btn-outline-light {
            border-color: rgba(255,255,255,0.55);
            color: #ffffff;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
        }
        .hero-shell .btn-outline-light:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.88);
            color: #ffffff;
        }
        .filter-card,
        .surface-card,
        .map-card {
            background: rgba(255,255,255,0.94);
            border: 1px solid var(--eco-border);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(15,23,42,0.08);
        }
        #campaign-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .campaign-card {
            background: #fff;
            border: 1px solid var(--eco-border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15,23,42,0.06);
        }
        .campaign-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        #user-location-map {
            width: 100%;
            height: 460px;
            border-radius: 24px;
            background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
            border: 0;
        }
        .loading-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #64748b;
            grid-column: 1 / -1;
        }
        .map-meta {
            background: #f8fafc;
            border: 1px solid rgba(15,23,42,0.08);
            border-radius: 18px;
            padding: 16px;
        }
            .volunteer-highlight-card {
                background: rgba(255,255,255,0.94);
                border: 1px solid var(--eco-border);
                border-radius: 28px;
                box-shadow: 0 24px 60px rgba(15,23,42,0.08);
                overflow: hidden;
                height: 100%;
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }
            .volunteer-highlight-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 30px 70px rgba(15,23,42,0.12);
            }
            .volunteer-highlight-avatar {
                width: 64px;
                height: 64px;
                border-radius: 20px;
                object-fit: cover;
                background: linear-gradient(135deg, #0f172a 0%, #0ea5a3 100%);
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 1rem;
                flex: 0 0 auto;
            }
            .volunteer-preview-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 1rem;
            }
            .section-link {
                text-decoration: none;
            }
    </style>
</head>
<body>
    <?php render_public_nav('home'); ?>

    <header class="hero-shell">
        <div class="container py-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="hero-copy">
                        <span class="badge rounded-pill mb-3 px-3 py-2">Environmental crowdfunding</span>
                        <h1 class="display-3 fw-bold mb-4">Fund the people doing the work for a greener future.</h1>
                        <p class="fs-4 mb-4">Support verified campaigns, join volunteer missions, and help communities turn local environmental action into measurable progress.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a class="btn btn-light btn-lg rounded-pill px-4" href="#campaigns">Explore missions</a>
                            <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="signup.php">Start a project</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php
    $featuredVolunteersStmt = $pdo->query(
        "SELECT u.id, u.full_name, u.profile_image_path, u.division, u.district, ua.occupation, uv.status AS verification_status
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
         ORDER BY u.created_at DESC
         LIMIT 4"
    );
    $featuredVolunteers = $featuredVolunteersStmt->fetchAll(PDO::FETCH_ASSOC);

    function index_volunteer_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'ER';
    }
    ?>

    <section class="py-5">
        <div class="container py-lg-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                <div>
                    <span class="badge rounded-pill text-bg-light text-success mb-3">Public volunteer roster</span>
                    <h2 class="display-6 fw-bold mb-2">Meet the volunteers on EcoRise.</h2>
                    <p class="text-secondary fs-5 mb-0">A public snapshot of active volunteer profiles, shown here so visitors can see the people behind the platform.</p>
                </div>
                <a class="btn btn-success rounded-pill px-4" href="volunteer_list.php">View full list</a>
            </div>

            <?php if (!$featuredVolunteers): ?>
                <div class="surface-card p-5 text-center text-secondary">
                    <i class="fas fa-hands-helping fs-1 mb-3"></i>
                    <p class="mb-0">Volunteer profiles will appear here once approvals are available.</p>
                </div>
            <?php else: ?>
                <div class="volunteer-preview-grid">
                    <?php foreach ($featuredVolunteers as $volunteer): ?>
                        <a class="section-link" href="volunteer_detail.php?id=<?php echo (int) $volunteer['id']; ?>">
                            <article class="volunteer-highlight-card p-4 h-100">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <?php if (!empty($volunteer['profile_image_path'])): ?>
                                        <img class="volunteer-highlight-avatar" src="<?php echo e((string) $volunteer['profile_image_path']); ?>" alt="<?php echo e((string) $volunteer['full_name']); ?>" onerror="this.outerHTML='<span class=\'volunteer-highlight-avatar\'><?php echo e(index_volunteer_initials((string) $volunteer['full_name'])); ?></span>'">
                                    <?php else: ?>
                                        <span class="volunteer-highlight-avatar"><?php echo e(index_volunteer_initials((string) $volunteer['full_name'])); ?></span>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="h5 fw-bold mb-1"><?php echo e((string) $volunteer['full_name']); ?></h3>
                                        <div class="text-secondary small"><?php echo e(trim((string) ($volunteer['district'] ?: 'Bangladesh'))); ?><?php echo !empty($volunteer['division']) ? ', ' . e((string) $volunteer['division']) : ''; ?></div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php if (!empty($volunteer['occupation'])): ?>
                                        <span class="badge rounded-pill text-bg-light px-3 py-2"><?php echo e((string) $volunteer['occupation']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($volunteer['verification_status'])): ?>
                                        <span class="badge rounded-pill text-bg-success px-3 py-2"><?php echo e(ucfirst((string) $volunteer['verification_status'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-secondary small mb-0">Open the profile to see the public volunteer summary.</p>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="campaigns" class="py-5">
        <div class="container py-lg-4">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Impactful missions</h2>
                <p class="text-secondary fs-5 mx-auto" style="max-width: 760px;">Find active campaigns by region and support the initiatives already restoring ecosystems, improving resilience, and organizing communities.</p>
            </div>

            <div class="filter-card p-4 p-lg-5 mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold" for="filter-division">Division</label>
                        <select id="filter-division" class="form-select form-select-lg rounded-4" onchange="updateFilterDistricts()">
                            <option value="">All Regions</option>
                            <option value="Dhaka">Dhaka</option>
                            <option value="Khulna">Khulna</option>
                            <option value="Chittagong">Chittagong</option>
                            <option value="Rajshahi">Rajshahi</option>
                            <option value="Sylhet">Sylhet</option>
                            <option value="Rangpur">Rangpur</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Barisal">Barisal</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold" for="filter-district">District</label>
                        <select id="filter-district" class="form-select form-select-lg rounded-4" disabled>
                            <option value="">All Districts</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-success btn-lg rounded-pill" onclick="fetchCampaigns()">Apply</button>
                    </div>
                </div>
            </div>

            <div id="campaign-list">
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin fs-1 text-success mb-3"></i>
                    <p class="mb-0">Curating live campaigns...</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container py-lg-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-5">
                    <div class="map-card p-4 p-lg-5 h-100">
                        <span class="badge rounded-pill text-bg-light text-success mb-3">Location Map</span>
                        <h2 class="display-6 fw-bold mb-3">Your saved location</h2>
                        <p class="text-secondary mb-3"><?php echo $userMap['hasProfileLocation'] ? 'The map below shows the coordinates saved in your profile so you can see exactly where your account is pinned.' : 'No saved profile coordinates were found, so the map falls back to Bangladesh. Update your profile to personalize it.'; ?></p>
                        <div class="small text-secondary mb-2">Current marker</div>
                        <div class="fw-semibold mb-3"><?php echo e($userMap['label']); ?></div>
                        <div class="map-meta mb-3">
                            <div class="small text-secondary mb-1">Coordinates</div>
                            <div class="fw-semibold"><?php echo e(number_format((float) $userMap['lat'], 7)); ?>, <?php echo e(number_format((float) $userMap['lng'], 7)); ?></div>
                        </div>
                        <a class="btn btn-success rounded-pill mb-3" href="<?php echo e($userMap['openUrl']); ?>" target="_blank" rel="noopener noreferrer">Open Full Map</a>
                        <?php if (!$userMap['hasProfileLocation']): ?>
                            <a class="btn btn-outline-success rounded-pill" href="dashboard.php#profile-form">Add profile coordinates</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="map-card p-3 h-100">
                        <iframe
                            id="user-location-map"
                            src="<?php echo e($userMap['embedUrl']); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="User saved location map"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 text-white" style="background:#0f172a;">
        <div class="container text-center">
            <h3 class="h4 fw-bold mb-3">EcoRise</h3>
            <p class="text-white-50 mb-0">A platform built to fund climate action, coordinate volunteers, and grow trust around local impact.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const locationData = {
            "Dhaka": ["Dhaka", "Faridpur", "Gazipur", "Gopalganj", "Kishoreganj", "Madaripur", "Manikganj", "Munshiganj", "Narayanganj", "Narsingdi", "Rajbari", "Shariatpur", "Tangail"],
            "Khulna": ["Bagerhat", "Chuadanga", "Jessore", "Jhenaidah", "Khulna", "Kushtia", "Magura", "Meherpur", "Narail", "Satkhira"],
            "Chittagong": ["Bandarban", "Brahmanbaria", "Chandpur", "Chittagong", "Comilla", "Cox's Bazar", "Feni", "Khagrachhari", "Lakshmipur", "Noakhali", "Rangamati"],
            "Rajshahi": ["Bogra", "Joypurhat", "Naogaon", "Natore", "Chapainawabganj", "Pabna", "Rajshahi", "Sirajganj"],
            "Sylhet": ["Habiganj", "Moulvibazar", "Sunamganj", "Sylhet"],
            "Rangpur": ["Dinajpur", "Gaibandha", "Kurigram", "Lalmonirhat", "Nilphamari", "Panchagarh", "Rangpur", "Thakurgaon"],
            "Mymensingh": ["Jamalpur", "Mymensingh", "Netrokona", "Sherpur"],
            "Barisal": ["Jhalokati", "Barguna", "Barisal", "Bhola", "Patuakhali", "Pirojpur"]
        };
        function updateFilterDistricts() {
            const division = document.getElementById('filter-division').value;
            const districtSelect = document.getElementById('filter-district');
            districtSelect.innerHTML = '<option value="">All Districts</option>';
            if (division && locationData[division]) {
                locationData[division].forEach((district) => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = false;
            } else {
                districtSelect.disabled = true;
            }
        }

        function renderCampaignCard(campaign) {
            const percent = Number(campaign.target_amount) > 0 ? Math.min(100, ((Number(campaign.raised_amount) / Number(campaign.target_amount)) * 100)) : 0;
            return `
                <article class="campaign-card">
                    <img src="${campaign.image_path}" alt="${campaign.title}" onerror="this.src='assets/campaigns/default.jpg'">
                    <div class="p-4">
                        <div class="small text-success fw-semibold mb-2">${campaign.district || 'Bangladesh'}, ${campaign.division || 'Nationwide'}</div>
                        <h3 class="h5 fw-bold mb-2">${campaign.title}</h3>
                        <p class="text-secondary small mb-3">${String(campaign.description).substring(0, 110)}...</p>
                        <div class="progress mb-2" role="progressbar" aria-valuenow="${percent.toFixed(1)}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width:${percent.toFixed(1)}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary mb-3">
                            <span>BDT ${Number(campaign.raised_amount).toLocaleString()}</span>
                            <span>${percent.toFixed(1)}%</span>
                        </div>
                        <a class="btn btn-success w-100 rounded-pill" href="support.php?id=${campaign.id}">Explore mission</a>
                    </div>
                </article>
            `;
        }

        function fetchCampaigns() {
            const list = document.getElementById('campaign-list');
            const division = document.getElementById('filter-division').value;
            const district = document.getElementById('filter-district').value;
            list.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin fs-1 text-success mb-3"></i>
                    <p class="mb-0">Fetching campaigns...</p>
                </div>
            `;

            fetch(`getCampaigns.php?division=${encodeURIComponent(division)}&district=${encodeURIComponent(district)}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'error') {
                        list.innerHTML = `<div class="loading-state text-danger">${data.message}</div>`;
                        return;
                    }
                    if (!data.campaigns || data.campaigns.length === 0) {
                        list.innerHTML = '<div class="loading-state"><i class="fas fa-seedling fs-1 text-secondary mb-3"></i><p class="mb-0">No campaigns found for this filter yet.</p></div>';
                        return;
                    }
                    list.innerHTML = data.campaigns.map(renderCampaignCard).join('');
                })
                .catch(() => {
                    list.innerHTML = '<div class="loading-state text-danger">Unable to load campaigns right now.</div>';
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchCampaigns();
        });
    </script>
</body>
</html>
