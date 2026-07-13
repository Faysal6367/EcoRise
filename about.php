<?php
declare(strict_types=1);

/**
 * EcoRise - About Us
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Mission | EcoRise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }
        .glass-nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(15,23,42,0.08);
        }
        .hero-about {
            position: relative;
            overflow: hidden;
            min-height: 88vh;
            display: flex;
            align-items: center;
            background:
                linear-gradient(115deg, rgba(15,23,42,0.88) 0%, rgba(6,95,70,0.78) 45%, rgba(16,185,129,0.42) 100%),
                url('https://images.unsplash.com/photo-1466611653911-95081537e5b7?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
        }
        .hero-about::before,
        .hero-about::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(10px);
        }
        .hero-about::before {
            width: 260px;
            height: 260px;
            right: -70px;
            top: 10%;
            background: rgba(255,255,255,0.08);
        }
        .hero-about::after {
            width: 220px;
            height: 220px;
            left: -50px;
            bottom: -50px;
            background: rgba(16,185,129,0.18);
        }
        .hero-panel {
            position: relative;
            z-index: 1;
        }
        .hero-about h1 {
            color: #f8fffb !important;
            line-height: 0.98;
            letter-spacing: -0.04em;
            text-shadow: 0 10px 28px rgba(2, 6, 23, 0.32);
            max-width: 11ch;
        }
        .hero-about .text-white-50,
        .hero-about p {
            color: rgba(241, 245, 249, 0.92) !important;
        }
        .hero-about .badge {
            background: rgba(255,255,255,0.94) !important;
            color: #0f766e !important;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.18);
        }
        .impact-card {
            border-radius: 24px;
            border: 1px solid rgba(15,23,42,0.08);
            background: rgba(255,255,255,0.94);
            box-shadow: 0 20px 45px rgba(15,23,42,0.08);
        }
    </style>
</head>
<body>
    <?php render_public_nav('about'); ?>

    <header class="hero-about text-white">
        <div class="container hero-panel py-5">
            <div class="row justify-content-between align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-light text-success mb-3 px-3 py-2">About EcoRise</span>
                    <h1 class="display-3 fw-bold mb-4">Back the people rebuilding a livable planet.</h1>
                    <p class="fs-4 text-white-50 mb-4">EcoRise helps local environmental leaders raise funding, mobilize volunteers, and prove impact with transparency that communities can trust.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-light btn-lg rounded-pill px-4" href="index.php#campaigns">Support a campaign</a>
                        <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="opportunities.php">See opportunities</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="impact-card p-4 text-dark">
                        <div class="small text-uppercase text-secondary fw-semibold mb-3">Why people join</div>
                        <div class="d-flex gap-3 mb-3">
                            <div class="text-success fs-3"><i class="fas fa-shield-heart"></i></div>
                            <div>
                                <div class="fw-bold">Verified trust</div>
                                <div class="text-secondary small">We pair storytelling with review workflows, transparent funding, and mission visibility.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-3">
                            <div class="text-success fs-3"><i class="fas fa-earth-asia"></i></div>
                            <div>
                                <div class="fw-bold">Local action</div>
                                <div class="text-secondary small">Communities drive the work, while supporters help scale it faster.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="text-success fs-3"><i class="fas fa-seedling"></i></div>
                            <div>
                                <div class="fw-bold">Visible outcomes</div>
                                <div class="text-secondary small">Campaign owners and volunteers keep progress measurable and accountable.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="py-5 bg-white">
        <div class="container py-lg-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-6 fw-bold mb-3">Our mission</h2>
                    <p class="text-secondary fs-5">We help climate and community projects move from good intentions to funded execution. EcoRise exists to reduce the distance between people who want to help and teams already doing the work.</p>
                    <p class="text-secondary mb-0">That means better visibility for grassroots leaders, a stronger volunteer pipeline, and clearer trust signals for every supporter.</p>
                </div>
                <div class="col-lg-6">
                    <img class="img-fluid rounded-5 shadow" src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=1200&q=80" alt="EcoRise mission">
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container py-lg-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="impact-card p-4 h-100">
                        <div class="text-success fs-2 mb-3"><i class="fas fa-bullhorn"></i></div>
                        <h3 class="h4 fw-bold">Project visibility</h3>
                        <p class="text-secondary mb-0">Campaigns are presented with clearer goals, stronger storytelling, and regional relevance.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="impact-card p-4 h-100">
                        <div class="text-success fs-2 mb-3"><i class="fas fa-users"></i></div>
                        <h3 class="h4 fw-bold">Volunteer readiness</h3>
                        <p class="text-secondary mb-0">Structured volunteer workflows help teams recruit, review, and mobilize trusted people.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="impact-card p-4 h-100">
                        <div class="text-success fs-2 mb-3"><i class="fas fa-chart-line"></i></div>
                        <h3 class="h4 fw-bold">Transparent progress</h3>
                        <p class="text-secondary mb-0">Supporters can follow funding and campaign activity with less friction and more confidence.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 text-white" style="background:#0f172a;">
        <div class="container text-center">
            <h3 class="h4 fw-bold mb-3">EcoRise</h3>
            <p class="text-white-50 mb-3">Built to help environmental action feel credible, local, and immediate.</p>
            <a class="btn btn-success rounded-pill px-4" href="index.php#campaigns">Explore campaigns</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
