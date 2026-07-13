<?php
/**
 * EcoRise - Blog Page
 *
 * Publishes sustainability stories, platform updates, and field insights.
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

$posts = [
    [
        'title' => 'How EcoRise Verifies and Approves Community Campaigns',
        'category' => 'Platform Guide',
        'date' => 'April 02, 2026',
        'read_time' => '5 min read',
        'image' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?q=80&w=1400&auto=format&fit=crop',
        'excerpt' => 'Every campaign on EcoRise goes through an approval process focused on clarity, realistic goals, and environmental impact. See what reviewers check before a project goes live.',
    ],
    [
        'title' => 'From Flood Alert to Field Action: Volunteer Workflow on EcoRise',
        'category' => 'Volunteer Response',
        'date' => 'March 28, 2026',
        'read_time' => '4 min read',
        'image' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?q=80&w=1400&auto=format&fit=crop',
        'excerpt' => 'Approved volunteers can join active disaster missions, coordinate by district, and track assignment status in real time. This is how community response is organized inside the platform.',
    ],
    [
        'title' => 'Donation Transparency: Reading Progress, Targets, and Impact',
        'category' => 'Donor Trust',
        'date' => 'March 20, 2026',
        'read_time' => '6 min read',
        'image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=1400&auto=format&fit=crop',
        'excerpt' => 'EcoRise campaign pages show raised amount, target goal, and progress percentage so supporters can quickly understand momentum and accountability before donating.',
    ],
    [
        'title' => 'Designing Better Disaster Opportunities for Local Volunteers',
        'category' => 'Field Practice',
        'date' => 'March 10, 2026',
        'read_time' => '3 min read',
        'image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?q=80&w=1400&auto=format&fit=crop',
        'excerpt' => 'Strong opportunities include clear relief type, location, and volunteer requirements. Better campaign setup leads to faster mobilization when emergencies happen.',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRise Blog | Platform Updates and Impact Stories</title>
    <meta name="description" content="Read EcoRise platform guides, donor transparency updates, volunteer response stories, and environmental campaign insights.">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php render_public_nav('blog'); ?>

    <section class="w3-container" style="padding: 80px 16px 30px; background: linear-gradient(140deg, #ecfdf5, #f8fafc);">
        <div class="w3-content" style="max-width:1200px;">
            <span class="w3-tag w3-round-xxlarge" style="background:#d1fae5; color:#065f46; font-weight:700; padding: 8px 16px;">EcoRise Journal</span>
            <h1 class="w3-xxxlarge" style="margin-top:18px; margin-bottom:10px;">Platform Stories from Campaigns and Volunteers</h1>
            <p class="w3-large" style="color:#475569; max-width:850px;">
                Follow how EcoRise users launch verified campaigns, support projects with transparent donations, and organize disaster relief volunteer missions across Bangladesh.
            </p>
        </div>
    </section>

    <section class="w3-container" style="padding: 40px 16px 80px;">
        <div class="w3-content" style="max-width:1200px;">
            <div class="w3-row-padding" style="margin:0 -16px;">
                <?php foreach ($posts as $post): ?>
                    <div class="w3-col l6 m12" style="margin-bottom:28px;">
                        <article class="card" style="height:100%; border-radius: 20px;">
                            <div class="card-image-container">
                                <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                <span class="card-tag"><?php echo htmlspecialchars($post['category']); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="w3-small" style="color:#64748b; font-weight:600; margin-bottom:8px;">
                                    <i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($post['date']); ?>
                                    <span style="margin:0 8px;">&bull;</span>
                                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($post['read_time']); ?>
                                </div>
                                <h3 style="margin-bottom:10px;"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p style="margin-bottom:18px;"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <a href="#" class="w3-text-green" style="font-weight:700; text-decoration:none;">Read Story <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="w3-container w3-padding-48" style="background:#0f172a; color:white;">
        <div class="w3-content" style="max-width:1200px;">
            <div class="w3-row-padding">
                <div class="w3-half">
                    <h3 class="w3-text-green" style="font-weight:800;"><i class="fas fa-leaf"></i> EcoRise</h3>
                    <p style="color:#94a3b8; max-width:560px;">A transparent crowdfunding platform for environmental action and sustainable innovation.</p>
                </div>
                <div class="w3-half w3-right-align">
                    <a href="index.php" class="w3-text-white" style="text-decoration:none; margin-right:16px;">Home</a>
                    <a href="about.php" class="w3-text-white" style="text-decoration:none; margin-right:16px;">About</a>
                    <a href="blog.php" class="w3-text-green" style="text-decoration:none; font-weight:700;">Blog</a>
                </div>
            </div>
            <hr style="border-top:1px solid rgba(255,255,255,0.1); margin-top:24px;">
            <p class="w3-small w3-center" style="color:#94a3b8; margin:0;">&copy; <?php echo date('Y'); ?> EcoRise. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
