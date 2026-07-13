<?php
/**
 * Volunteer Create Campaign Page
 * 
 * Allows approved volunteers to create natural disaster volunteer opportunities.
 * Created opportunities are shown in the volunteer opportunities page.
 */

require 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please log in to create a campaign.', 'warning');
}

// Check if user is an approved volunteer
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT volunteer_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['volunteer_status'] !== 'approved') {
    redirect('become_volunteer.php', 'You must be an approved volunteer to create campaigns. Apply now!', 'warning');
}

$csrf_token = generate_csrf_token();

// Get message if exists
$message = '';
$message_type = 'success';
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    $message_type = $_SESSION['msg_type'] ?? 'success';
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}

// Location data for dropdowns
$location_data = [
    'Dhaka' => ['Dhaka', 'Gazipur', 'Narayanganj', 'Tangail'],
    'Chittagong' => ['Chittagong', 'Cox\'s Bazar', 'Noakhali', 'Feni', 'Rangamati'],
    'Khulna' => ['Khulna', 'Jessore', 'Barisal', 'Patuakhali'],
    'Rajshahi' => ['Rajshahi', 'Bogura', 'Natore', 'Naogaon'],
    'Sylhet' => ['Sylhet', 'Moulvibazar', 'Sunamganj', 'Habiganj'],
    'Mymensingh' => ['Mymensingh', 'Jamalpur', 'Sherpur', 'Netrokona']
];

    // Relief types for disaster opportunities
    $relief_types = [
        'Flood Relief' => 'Emergency Flood Assistance',
        'Cyclone Preparedness' => 'Cyclone Emergency Preparation',
        'Wildfire Response' => 'Wildfire Emergency Response',
        'Earthquake Relief' => 'Earthquake Emergency Assistance',
        'Landslide Prevention' => 'Landslide Risk Mitigation',
        'Drought Support' => 'Drought Support & Recovery',
        'Rescue Support' => 'Emergency Rescue Coordination',
        'Disaster Recovery' => 'Disaster Relief & Reconstruction',
        'Emergency Medical Aid' => 'Medical Aid During Disasters'
    ];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Disaster Opportunity - Volunteer</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background:
                radial-gradient(920px 520px at 92% -20%, rgba(56, 189, 248, 0.14), transparent 72%),
                radial-gradient(740px 420px at -10% 12%, rgba(16, 185, 129, 0.12), transparent 75%),
                linear-gradient(180deg, #f8fafc 0%, #eefbf4 100%);
            color: #0f172a;
        }

        .create-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 22px;
        }

        .campaign-form-header {
            background: linear-gradient(125deg, #0f172a 0%, #0f766e 46%, #10b981 100%);
            color: white;
            padding: 48px 0;
            margin-bottom: 18px;
            text-align: left;
            border-radius: 0 0 28px 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.2);
        }
        .campaign-form-header::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -90px;
            top: -90px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
        }
        .campaign-form-header .w3-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 22px;
            position: relative;
            z-index: 1;
        }
        .campaign-form-header h1 {
            font-size: clamp(1.85rem, 3.2vw, 2.5rem);
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #f8fffb;
            text-shadow: 0 8px 20px rgba(2, 6, 23, 0.24);
        }
        .campaign-form-header p {
            font-size: 1.02rem;
            margin: 10px 0 0 0;
            opacity: 0.96;
            max-width: 760px;
            line-height: 1.6;
            color: rgba(241, 245, 249, 0.96);
        }
        .campaign-form-header i {
            color: #bbf7d0;
        }
        .form-section {
            background: rgba(255, 255, 255, 0.94);
            padding: 28px;
            margin-bottom: 20px;
            border-radius: 22px;
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }
        .form-section h3 {
            color: #0f172a;
            border-bottom: 1px solid rgba(15, 23, 42, 0.12);
            padding-bottom: 12px;
            margin: 0 0 20px 0;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .form-section h3 i {
            color: #0f766e;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d6e2eb;
            border-radius: 12px;
            font-family: 'Manrope', 'Outfit', sans-serif;
            font-size: 1em;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.11);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .form-hint {
            font-size: 0.9em;
            color: #64748b;
            margin-top: 5px;
        }
        .required {
            color: #ef4444;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            border-left: 4px solid;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
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
            border-color: #dc2626;
            color: #721c24;
        }
        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 8px;
        }
        .btn-submit {
            background: linear-gradient(135deg, #13a765 0%, #0f766e 62%, #0284c7 100%);
            color: white;
            padding: 13px 26px;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.02em;
            transition: all 0.25s ease;
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.24);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(11, 95, 81, 0.32);
        }
        .btn-cancel {
            background: #f1f5f9;
            border: 1px solid #d6e2eb;
            color: white;
            padding: 13px 24px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.98em;
            color: #334155;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .btn-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .approval-note {
            background: linear-gradient(180deg, #ecfeff 0%, #f8fafc 100%);
            border-left: 4px solid #0ea5a3;
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 14px;
            border: 1px solid #bae6fd;
        }
        .approval-note strong {
            color: #0f766e;
        }
        .workflow-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0f766e;
            border: 1px solid #a7f3d0;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.82em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .create-shell {
                padding: 16px;
            }
            .form-row,
            .form-row-3 {
                grid-template-columns: 1fr;
            }
            .form-section {
                padding: 20px;
            }
            .button-group {
                flex-direction: column-reverse;
            }
            .btn-cancel,
            .btn-submit {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="w3-light-grey">
    <?php render_public_nav('volunteer-create'); ?>

    <!-- Header -->
    <div class="campaign-form-header">
        <div class="w3-container">
            <h1><i class="fas fa-hand-holding-heart"></i> Create Disaster Opportunity</h1>
            <p>Design a clear, verified volunteer mission card so approved responders can join quickly and safely.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="create-shell">

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo ($message_type === 'success') ? 'check-circle' : (($message_type === 'warning') ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Approval Note -->
        <div class="approval-note">
            <span class="workflow-badge"><i class="fas fa-shield-check"></i> Volunteer Workflow</span><br>
            <strong><i class="fas fa-info-circle"></i> Please Note:</strong> Your submission is for volunteer coordination only. It appears in the volunteer opportunities page and is not a fundraising campaign.
        </div>

        <form method="POST" action="process_volunteer_campaign.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Opportunity Basic Info -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Opportunity Basic Information</h3>

                <div class="form-group">
                    <label for="title">Opportunity Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Flood Response - Chittagong">
                    <div class="form-hint">Create a clear title describing the disaster response opportunity</div>
                </div>

                <div class="form-group">
                    <label for="description">Opportunity Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required placeholder="Describe the disaster situation, required volunteer activities, and safety notes."></textarea>
                    <div class="form-hint">Provide at least 100 characters with clear volunteer instructions</div>
                </div>

                <div class="form-group">
                    <label for="relief_type">Disaster Type <span class="required">*</span></label>
                    <select id="relief_type" name="relief_type" required>
                        <option value="">Select Campaign Type...</option>
                        <?php foreach ($relief_types as $type => $label): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">Choose the category that best matches this disaster response.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="division">Division <span class="required">*</span></label>
                        <select id="division" name="division" required onchange="updateDistricts()">
                            <option value="">Select Division...</option>
                            <?php foreach (array_keys($location_data) as $div): ?>
                                <option value="<?php echo $div; ?>"><?php echo $div; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="district">District <span class="required">*</span></label>
                        <select id="district" name="district" required>
                            <option value="">Select District...</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Volunteer Requirement -->
            <div class="form-section">
                <h3><i class="fas fa-users"></i> Volunteer Requirement</h3>

                <div class="form-group">
                    <label for="volunteers_needed">Volunteers Needed <span class="required">*</span></label>
                    <input type="number" id="volunteers_needed" name="volunteers_needed" required min="1" step="1" placeholder="25" value="25">
                    <div class="form-hint">Set the number of volunteers required for this opportunity.</div>
                </div>
            </div>

            <!-- Campaign Image -->
            <div class="form-section">
                <h3><i class="fas fa-image"></i> Campaign Image</h3>

                <div class="form-group">
                    <label for="image">Campaign Image (Optional)</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    <div class="form-hint">Accepted formats: JPG, PNG, WebP (Max 3MB). If not provided, a default image will be used.</div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="button-group">
                <a href="dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Create Opportunity Card
                </button>
            </div>
        </form>
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
        const locationData = <?php echo json_encode($location_data); ?>;

        function updateDistricts() {
            const division = document.getElementById('division').value;
            const districtSelect = document.getElementById('district');
            
            districtSelect.innerHTML = '<option value="">Select District...</option>';
            
            if (division && locationData[division]) {
                locationData[division].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        }
    </script>
</body>
</html>
