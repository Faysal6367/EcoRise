<?php
declare(strict_types=1);

/**
 * EcoRise - Become Volunteer Application Page
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to apply as a volunteer.', 'error');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$csrfToken = generate_csrf_token();

$userStmt = $pdo->prepare('SELECT full_name, email, volunteer_status FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user && $user['volunteer_status'] === 'approved') {
    redirect('volunteer_opportunities.php', 'You are already an approved volunteer.', 'success');
}

if ($user && $user['volunteer_status'] === 'pending') {
    redirect('dashboard.php', 'Your volunteer application is pending admin approval.', 'warning');
}

$appStmt = $pdo->prepare('SELECT status, created_at FROM volunteer_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$appStmt->execute([$userId]);
$latestApplication = $appStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Volunteer | EcoRise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background:
                radial-gradient(900px 520px at top right, rgba(16, 185, 129, 0.12), transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #eefbf4 100%);
            color: #0f172a;
        }
        .glass-nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(15,23,42,0.08);
        }
        .application-shell {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding-left: 16px;
            padding-right: 16px;
        }
        .hero-card,
        .form-card,
        .section-card {
            border-radius: 28px;
            border: 1px solid rgba(15,23,42,0.08);
            background: rgba(255,255,255,0.94);
            box-shadow: 0 24px 60px rgba(15,23,42,0.08);
        }
        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #14532d 45%, #10b981 100%);
            color: #fff;
            overflow: hidden;
            position: relative;
        }
        .hero-card h1 {
            color: #f8fffb !important;
            line-height: 1.02;
            letter-spacing: -0.035em;
            text-shadow: 0 10px 26px rgba(2, 6, 23, 0.24);
            max-width: 12ch;
        }
        .hero-card .text-white-50,
        .hero-card p {
            color: rgba(241, 245, 249, 0.90) !important;
        }
        .hero-card .badge {
            background: rgba(255,255,255,0.95) !important;
            color: #0f766e !important;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.18);
        }
        .hero-card::after {
            content: "";
            width: 260px;
            height: 260px;
            position: absolute;
            border-radius: 50%;
            right: -70px;
            top: -90px;
            background: rgba(255,255,255,0.1);
        }
        .section-card h3 {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .form-label {
            font-weight: 700;
            color: #334155;
        }
        .required {
            color: #dc2626;
        }
        .helper-box {
            background: #ecfdf5;
            border: 1px solid rgba(16,185,129,0.18);
            border-radius: 20px;
            color: #0f172a;
        }
        .helper-box .small,
        .helper-box .text-secondary {
            color: #475569 !important;
        }
        .form-card {
            padding: 1.25rem !important;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        .section-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.98));
            padding: 1.25rem !important;
        }
        .section-card .form-control,
        .section-card .form-select {
            min-height: 48px;
            color: #0f172a;
            border-color: #dbe4ee;
        }
        .section-card .form-control:focus,
        .section-card .form-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16,185,129,0.10);
        }
        .section-card h3 {
            color: #0f172a;
            font-size: 1.08rem;
        }
        .section-card .form-text {
            color: #64748b;
        }
        .hero-card p,
        .helper-box,
        .helper-box .text-secondary {
            color: #475569 !important;
        }
        .hero-card .text-white-50 {
            color: rgba(255,255,255,0.82) !important;
        }
        .btn-lg {
            min-width: 220px;
        }
        @media (max-width: 767.98px) {
            .form-card {
                padding: 1rem !important;
            }
            .hero-card {
                padding: 1.25rem !important;
            }
        }
    </style>
</head>
<body>
    <?php render_public_nav('volunteer-apply'); ?>

    <main class="application-shell py-4 py-lg-5">
        <section class="hero-card p-4 p-lg-5 mb-4">
            <div class="row g-4 align-items-center position-relative">
                <div class="col-lg-8">
                    <span class="badge rounded-pill text-bg-light text-success mb-3">Volunteer intake</span>
                    <h1 class="display-6 fw-bold mb-3">Apply to join EcoRise field action.</h1>
                    <p class="fs-5 text-white-50 mb-0">Complete the secure application below. We review location, skills, and identity information before enabling volunteer privileges.</p>
                </div>
                <div class="col-lg-4">
                    <div class="helper-box p-4 text-dark">
                        <div class="small text-uppercase text-secondary fw-semibold mb-2">Identity check</div>
                        <div class="fw-semibold mb-2">Bangladesh NID is required for local verification.</div>
                        <div class="small text-secondary">Accepted formats: 10, 13, or 17 digits. A Porichoy API hook can be added later without changing the form structure.</div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?php echo ($_SESSION['msg_type'] ?? 'success') === 'error' ? 'danger' : (($_SESSION['msg_type'] ?? 'success') === 'warning' ? 'warning' : 'success'); ?> rounded-4 border-0 shadow-sm mb-4">
                <?php echo e($_SESSION['msg']); unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            </div>
        <?php endif; ?>

        <?php if ($latestApplication && $latestApplication['status'] === 'pending'): ?>
            <div class="alert alert-warning rounded-4 border-0 shadow-sm mb-4">Your last volunteer request is still pending review.</div>
        <?php endif; ?>

        <div class="form-card p-3 p-lg-4">
            <form action="process_volunteer_application.php" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                <section class="section-card p-4 mb-4">
                    <h3>Personal Information</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="full_name">Name <span class="required">*</span></label>
                            <input class="form-control form-control-lg rounded-4" id="full_name" name="full_name" type="text" required value="<?php echo e((string) $user['full_name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="father_name">Father's Name <span class="required">*</span></label>
                            <input class="form-control form-control-lg rounded-4" id="father_name" name="father_name" type="text" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mobile_no">Mobile No <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="mobile_no" name="mobile_no" type="text" inputmode="tel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="email" name="email" type="email" required value="<?php echo e((string) $user['email']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="nid_number">Bangladesh NID <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="nid_number" name="nid_number" type="text" inputmode="numeric" pattern="(?:\d{10}|\d{13}|\d{17})" maxlength="17" required placeholder="Enter 10, 13, or 17 digit NID">
                            <div class="form-text">Server-side validation enforces the 10, 13, or 17 digit Bangladesh NID format. Placeholder ready for Porichoy or another verification API.</div>
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Professional Information</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="occupation">Current Occupation <span class="required">*</span></label>
                            <select class="form-select rounded-4" id="occupation" name="occupation" required>
                                <option value="">Select</option>
                                <option value="Student">Student</option>
                                <option value="Service Holder">Service Holder</option>
                                <option value="Business">Business</option>
                                <option value="Teacher">Teacher</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="workplace_name">Workplace Name</label>
                            <input class="form-control rounded-4" id="workplace_name" name="workplace_name" type="text">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="workplace_address">Workplace Address</label>
                            <input class="form-control rounded-4" id="workplace_address" name="workplace_address" type="text">
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Current Address</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="current_division">Division <span class="required">*</span></label>
                            <select id="current_division" name="current_division" class="form-select rounded-4" onchange="populateDistricts('current_division','current_district')" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="current_district">District <span class="required">*</span></label>
                            <select id="current_district" name="current_district" class="form-select rounded-4" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="current_upazila">Upazila <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="current_upazila" name="current_upazila" type="text" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="current_union_area">Union/Area <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="current_union_area" name="current_union_area" type="text" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="current_full_address">Full Address <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="current_full_address" name="current_full_address" type="text" required>
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Permanent Address</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="permanent_division">Division <span class="required">*</span></label>
                            <select id="permanent_division" name="permanent_division" class="form-select rounded-4" onchange="populateDistricts('permanent_division','permanent_district')" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="permanent_district">District <span class="required">*</span></label>
                            <select id="permanent_district" name="permanent_district" class="form-select rounded-4" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="permanent_upazila">Upazila <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="permanent_upazila" name="permanent_upazila" type="text" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="permanent_union_area">Union/Area <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="permanent_union_area" name="permanent_union_area" type="text" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="permanent_full_address">Full Address <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="permanent_full_address" name="permanent_full_address" type="text" required>
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Additional Information</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="expatriate_country">Expatriate Country</label>
                            <input class="form-control rounded-4" id="expatriate_country" name="expatriate_country" type="text">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="expatriate_full_address">Expatriate Address</label>
                            <input class="form-control rounded-4" id="expatriate_full_address" name="expatriate_full_address" type="text">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="facebook_profile">Facebook Profile</label>
                            <input class="form-control rounded-4" id="facebook_profile" name="facebook_profile" type="url" placeholder="https://facebook.com/username">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" id="no_facebook" name="no_facebook" type="checkbox" value="1">
                                <label class="form-check-label" for="no_facebook">I don't use Facebook</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="linkedin_profile">LinkedIn Profile</label>
                            <input class="form-control rounded-4" id="linkedin_profile" name="linkedin_profile" type="url" placeholder="https://linkedin.com/in/username">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="whatsapp_number">WhatsApp</label>
                            <input class="form-control rounded-4" id="whatsapp_number" name="whatsapp_number" type="text">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="telegram_number">Telegram</label>
                            <input class="form-control rounded-4" id="telegram_number" name="telegram_number" type="text">
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Educational Qualification</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="education_medium">Education Medium <span class="required">*</span></label>
                            <select class="form-select rounded-4" id="education_medium" name="education_medium" required>
                                <option value="">Select</option>
                                <option value="Bangla">Bangla</option>
                                <option value="English">English</option>
                                <option value="Arabic">Arabic</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="education_level">Education Level <span class="required">*</span></label>
                            <select class="form-select rounded-4" id="education_level" name="education_level" required>
                                <option value="">Select</option>
                                <option value="SSC">SSC</option>
                                <option value="HSC">HSC</option>
                                <option value="Honours">Honours</option>
                                <option value="Masters">Masters</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="last_passing_year">Last Passing Year <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="last_passing_year" name="last_passing_year" type="text" required placeholder="e.g. 2023">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="department_degree">Department/Degree</label>
                            <input class="form-control rounded-4" id="department_degree" name="department_degree" type="text">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="institution_name">Institution Name <span class="required">*</span></label>
                            <input class="form-control rounded-4" id="institution_name" name="institution_name" type="text" required>
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Previous Volunteering</h3>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" id="worked_before" name="worked_before" type="checkbox" value="1">
                                <label class="form-check-label" for="worked_before">I have worked as a volunteer before</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="previous_project_name">Project Name</label>
                            <input class="form-control rounded-4" id="previous_project_name" name="previous_project_name" type="text">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="previous_implementation_location">Implementation Location</label>
                            <input class="form-control rounded-4" id="previous_implementation_location" name="previous_implementation_location" type="text">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="previous_project_year">Year</label>
                            <input class="form-control rounded-4" id="previous_project_year" name="previous_project_year" type="text" placeholder="YYYY">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="people_benefited">People Benefited</label>
                            <input class="form-control rounded-4" id="people_benefited" name="people_benefited" type="text">
                        </div>
                    </div>
                </section>

                <section class="section-card p-4 mb-4">
                    <h3>Recent Photo</h3>
                    <input class="form-control rounded-4" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">Upload JPG, PNG, or WEBP up to 3 MB.</div>
                </section>

                <div class="helper-box p-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="fw-semibold mb-1">Secure application</div>
                        <div class="small text-secondary">All required fields are validated on the server, protected by CSRF, and sanitized before database storage.</div>
                    </div>
                    <button class="btn btn-success btn-lg rounded-pill px-4" type="submit">Submit application</button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const divisionDistricts = {
            "Dhaka": ["Dhaka", "Faridpur", "Gazipur", "Gopalganj", "Kishoreganj", "Madaripur", "Manikganj", "Munshiganj", "Narayanganj", "Narsingdi", "Rajbari", "Shariatpur", "Tangail"],
            "Khulna": ["Bagerhat", "Chuadanga", "Jessore", "Jhenaidah", "Khulna", "Kushtia", "Magura", "Meherpur", "Narail", "Satkhira"],
            "Chittagong": ["Bandarban", "Brahmanbaria", "Chandpur", "Chittagong", "Comilla", "Cox's Bazar", "Feni", "Khagrachhari", "Lakshmipur", "Noakhali", "Rangamati"],
            "Rajshahi": ["Bogra", "Joypurhat", "Naogaon", "Natore", "Chapainawabganj", "Pabna", "Rajshahi", "Sirajganj"],
            "Sylhet": ["Habiganj", "Moulvibazar", "Sunamganj", "Sylhet"],
            "Rangpur": ["Dinajpur", "Gaibandha", "Kurigram", "Lalmonirhat", "Nilphamari", "Panchagarh", "Rangpur", "Thakurgaon"],
            "Mymensingh": ["Jamalpur", "Mymensingh", "Netrokona", "Sherpur"],
            "Barisal": ["Jhalokati", "Barguna", "Barisal", "Bhola", "Patuakhali", "Pirojpur"]
        };

        function populateDivisionOptions(id) {
            const select = document.getElementById(id);
            select.innerHTML = '<option value="">Select</option>';
            Object.keys(divisionDistricts).forEach((division) => {
                const option = document.createElement('option');
                option.value = division;
                option.textContent = division;
                select.appendChild(option);
            });
        }

        function populateDistricts(divisionId, districtId) {
            const division = document.getElementById(divisionId).value;
            const districtSelect = document.getElementById(districtId);
            districtSelect.innerHTML = '<option value="">Select</option>';
            if (division && divisionDistricts[division]) {
                divisionDistricts[division].forEach((district) => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            populateDivisionOptions('current_division');
            populateDivisionOptions('permanent_division');
            populateDistricts('current_division', 'current_district');
            populateDistricts('permanent_division', 'permanent_district');
        });
    </script>
</body>
</html>
