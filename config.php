<?php
/**
 * EcoRise - Configuration File
 * 
 * Handles database connection and session management.
 * Environment: PHP 8.2+
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecorise');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set your DB password if applicable
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
define('STRIPE_SECRET_KEY', $stripeSecretKey === false ? '' : $stripeSecretKey);

// SMTP settings for email OTP verification and transactional emails.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', '587');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USER', 'anmreazul@gmail.com'); // Set sender email address, e.g. yourname@gmail.com
define('SMTP_PASS', 'dxjk qkva kjzv dgjt'); // Set SMTP password or Gmail app password
define('MAIL_FROM_ADDRESS', 'anmreazul@gmail.com'); // Usually same as SMTP_USER
define('MAIL_FROM_NAME', 'EcoRise');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set PDO error mode to exception for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Use prepared statements by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    ensure_campaign_location_columns($pdo);
    ensure_campaign_approval_columns($pdo);
    ensure_donation_payment_columns($pdo);
    ensure_campaign_volunteer_columns($pdo);
    ensure_user_profile_columns($pdo);
    ensure_volunteer_application_schema($pdo);
    ensure_user_verification_schema($pdo);
    ensure_disaster_relief_schema($pdo);
    ensure_notifications_schema($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//configure session cookie parameters for security
/**
 * Generate CSRF Token for form security
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitization function to prevent XSS
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with a message
 */
function redirect($url, $msg = '', $type = 'success') {
    if ($msg) {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Check if the user is an admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if the user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Build absolute base URL for redirects and callback URLs.
 */
function app_base_url() {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $is_https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim($dir, '/');
    if ($dir === '' || $dir === '.') {
        $dir = '';
    }
    return $scheme . '://' . $host . $dir;
}

/**
 * Ensure legacy databases have campaign location columns.
 */
function ensure_campaign_location_columns(PDO $pdo) {
    $needs_division = !column_exists($pdo, 'campaigns', 'division');
    $needs_district = !column_exists($pdo, 'campaigns', 'district');

    if (!$needs_division && !$needs_district) {
        return;
    }

    $alter_parts = [];
    if ($needs_division) {
        $alter_parts[] = "ADD COLUMN division VARCHAR(100) NULL AFTER description";
    }
    if ($needs_district) {
        $alter_parts[] = "ADD COLUMN district VARCHAR(100) NULL AFTER division";
    }

    $pdo->exec('ALTER TABLE campaigns ' . implode(', ', $alter_parts));
}

/**
 * Ensure campaigns table has approval columns for volunteer-created campaigns.
 */
function ensure_campaign_approval_columns(PDO $pdo) {
    $needs_approval = !column_exists($pdo, 'campaigns', 'approval_status');
    $needs_approved_by = !column_exists($pdo, 'campaigns', 'approved_by');
    $needs_approved_at = !column_exists($pdo, 'campaigns', 'approved_at');
    $needs_reason = !column_exists($pdo, 'campaigns', 'rejection_reason');

    if (!$needs_approval && !$needs_approved_by && !$needs_approved_at && !$needs_reason) {
        return;
    }

    $alter_parts = [];
    if ($needs_approval) {
        $alter_parts[] = "ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER status";
    }
    if ($needs_approved_by) {
        $alter_parts[] = "ADD COLUMN approved_by INT NULL AFTER created_by";
    }
    if ($needs_approved_at) {
        $alter_parts[] = "ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by";
    }
    if ($needs_reason) {
        $alter_parts[] = "ADD COLUMN rejection_reason VARCHAR(500) NULL AFTER approved_at";
    }

    if (!empty($alter_parts)) {
        try {
            $pdo->exec('ALTER TABLE campaigns ' . implode(', ', $alter_parts));
        } catch (PDOException $e) {
            // Column might already exist on some systems
            error_log("Campaign approval columns update: " . $e->getMessage());
        }
    }
}

/**
 * Ensure donations table has Stripe tracking columns.
 */
function ensure_donation_payment_columns(PDO $pdo) {
    $needs_session = !column_exists($pdo, 'donations', 'stripe_session_id');
    $needs_method = !column_exists($pdo, 'donations', 'payment_method');
    $needs_status = !column_exists($pdo, 'donations', 'payment_status');

    if ($needs_session || $needs_method || $needs_status) {
        $alter_parts = [];
        if ($needs_session) {
            $alter_parts[] = "ADD COLUMN stripe_session_id VARCHAR(255) NULL AFTER amount";
        }
        if ($needs_method) {
            $alter_parts[] = "ADD COLUMN payment_method VARCHAR(50) NULL AFTER stripe_session_id";
        }
        if ($needs_status) {
            $alter_parts[] = "ADD COLUMN payment_status VARCHAR(50) NULL AFTER payment_method";
        }
        $pdo->exec('ALTER TABLE donations ' . implode(', ', $alter_parts));
    }

    if (!index_exists($pdo, 'donations', 'uniq_donations_stripe_session')) {
        $pdo->exec('CREATE UNIQUE INDEX uniq_donations_stripe_session ON donations (stripe_session_id)');
    }
}

/**
 * Ensure volunteer application data model exists.
 */
function ensure_volunteer_application_schema(PDO $pdo) {
    if (!column_exists($pdo, 'users', 'volunteer_status')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN volunteer_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none' AFTER role");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS volunteer_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(120) NOT NULL,
        father_name VARCHAR(120) NOT NULL,
        mobile_no VARCHAR(30) NOT NULL,
        email VARCHAR(120) NOT NULL,
        occupation VARCHAR(120) NOT NULL,
        workplace_name VARCHAR(160) NULL,
        workplace_address VARCHAR(255) NULL,
        current_division VARCHAR(100) NOT NULL,
        current_district VARCHAR(100) NOT NULL,
        current_upazila VARCHAR(100) NOT NULL,
        current_union_area VARCHAR(100) NOT NULL,
        current_full_address VARCHAR(255) NOT NULL,
        permanent_division VARCHAR(100) NOT NULL,
        permanent_district VARCHAR(100) NOT NULL,
        permanent_upazila VARCHAR(100) NOT NULL,
        permanent_union_area VARCHAR(100) NOT NULL,
        permanent_full_address VARCHAR(255) NOT NULL,
        expatriate_country VARCHAR(100) NULL,
        expatriate_full_address VARCHAR(255) NULL,
        facebook_profile VARCHAR(255) NULL,
        no_facebook TINYINT(1) NOT NULL DEFAULT 0,
        linkedin_profile VARCHAR(255) NULL,
        whatsapp_number VARCHAR(30) NULL,
        telegram_number VARCHAR(30) NULL,
        education_medium VARCHAR(100) NOT NULL,
        education_level VARCHAR(100) NOT NULL,
        last_passing_year VARCHAR(10) NOT NULL,
        department_degree VARCHAR(150) NULL,
        institution_name VARCHAR(180) NOT NULL,
        worked_before TINYINT(1) NOT NULL DEFAULT 0,
        previous_project_name VARCHAR(180) NULL,
        previous_implementation_location VARCHAR(180) NULL,
        previous_project_year VARCHAR(10) NULL,
        people_benefited VARCHAR(80) NULL,
        photo_path VARCHAR(255) NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        reviewed_by INT NULL,
        reviewed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    $volunteer_columns = [
        'nid_number' => "ADD COLUMN nid_number VARCHAR(17) NULL AFTER mobile_no",
    ];

    $alter_parts = [];
    foreach ($volunteer_columns as $column => $sql) {
        if (!column_exists($pdo, 'volunteer_applications', $column)) {
            $alter_parts[] = $sql;
        }
    }

    if (!empty($alter_parts)) {
        $pdo->exec('ALTER TABLE volunteer_applications ' . implode(', ', $alter_parts));
    }
}

/**
 * Ensure users table has profile and map location columns.
 */
function ensure_user_profile_columns(PDO $pdo) {
    $user_columns = [
        'phone' => "ADD COLUMN phone VARCHAR(30) NULL AFTER email",
        'email_verified' => "ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER phone",
        'email_verified_at' => "ADD COLUMN email_verified_at TIMESTAMP NULL AFTER email_verified",
        'profile_image_path' => "ADD COLUMN profile_image_path VARCHAR(255) NULL AFTER phone",
        'division' => "ADD COLUMN division VARCHAR(100) NULL AFTER profile_image_path",
        'district' => "ADD COLUMN district VARCHAR(100) NULL AFTER division",
        'address_line' => "ADD COLUMN address_line VARCHAR(255) NULL AFTER district",
        'latitude' => "ADD COLUMN latitude DECIMAL(10,7) NULL AFTER address_line",
        'longitude' => "ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude",
    ];

    $alter_parts = [];
    foreach ($user_columns as $column => $sql) {
        if (!column_exists($pdo, 'users', $column)) {
            $alter_parts[] = $sql;
        }
    }

    if (!empty($alter_parts)) {
        $pdo->exec('ALTER TABLE users ' . implode(', ', $alter_parts));
    }
}

/**
 * Ensure volunteer verification table exists for profile KYC.
 */
function ensure_user_verification_schema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        nid_number VARCHAR(17) NOT NULL,
        nid_image_path VARCHAR(255) NOT NULL,
        face_image_path VARCHAR(255) NOT NULL,
        status ENUM('not_submitted','submitted','verified','rejected') NOT NULL DEFAULT 'submitted',
        rejection_reason VARCHAR(255) NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

/**
 * Ensure disaster relief data model exists.
 */
function ensure_disaster_relief_schema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS disaster_relief_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        location VARCHAR(200) NOT NULL,
        relief_type VARCHAR(100) NOT NULL,
        status ENUM('active','completed','pending') NOT NULL DEFAULT 'active',
        image_path VARCHAR(500) DEFAULT 'assets/disasters/default.jpg',
        volunteers_needed INT DEFAULT 0,
        volunteers_assigned INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS volunteer_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        volunteer_id INT NOT NULL,
        disaster_relief_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active','completed','declined') NOT NULL DEFAULT 'active',
        hours_contributed INT DEFAULT 0,
        notes TEXT NULL,
        FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (disaster_relief_id) REFERENCES disaster_relief_campaigns(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (volunteer_id, disaster_relief_id)
    )");
}

/**
 * Ensure campaigns table has relief_type and volunteers columns for volunteer campaigns.
 */
function ensure_campaign_volunteer_columns(PDO $pdo) {
    $needs_relief = !column_exists($pdo, 'campaigns', 'relief_type');
    $needs_volunteers = !column_exists($pdo, 'campaigns', 'volunteers_needed');

    if (!$needs_relief && !$needs_volunteers) {
        return;
    }

    $alter_parts = [];
    if ($needs_relief) {
        $alter_parts[] = "ADD COLUMN relief_type VARCHAR(100) NULL AFTER description";
    }
    if ($needs_volunteers) {
        $alter_parts[] = "ADD COLUMN volunteers_needed INT DEFAULT 0 AFTER raised_amount";
    }

    if (!empty($alter_parts)) {
        try {
            $pdo->exec('ALTER TABLE campaigns ' . implode(', ', $alter_parts));
        } catch (PDOException $e) {
            error_log("Campaign volunteer columns update: " . $e->getMessage());
        }
    }
}

/**
 * Ensure notifications table exists.
 */
function ensure_notifications_schema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(80) NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        icon VARCHAR(50) NOT NULL DEFAULT 'fa-bell',
        action_url VARCHAR(500) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    if (!index_exists($pdo, 'notifications', 'idx_notifications_user_read_created')) {
        $pdo->exec('CREATE INDEX idx_notifications_user_read_created ON notifications (user_id, is_read, created_at)');
    }

    if (!index_exists($pdo, 'notifications', 'idx_notifications_created')) {
        $pdo->exec('CREATE INDEX idx_notifications_created ON notifications (created_at)');
    }
}

/**
 * Create a notification row for a specific user.
 */
function create_notification(PDO $pdo, int $user_id, string $type, string $title, string $message, ?string $action_url = null, string $icon = 'fa-bell'): bool {
    if ($user_id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, icon, action_url) VALUES (?, ?, ?, ?, ?, ?)');
    return $stmt->execute([$user_id, $type, $title, $message, $icon, $action_url]);
}

/**
 * Create the same notification for all admins.
 */
function notify_admins(PDO $pdo, string $type, string $title, string $message, ?string $action_url = null, string $icon = 'fa-bell', ?int $exclude_user_id = null): int {
    $sql = 'SELECT id FROM users WHERE role = ?';
    $params = ['admin'];

    if ($exclude_user_id !== null && $exclude_user_id > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $exclude_user_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $admins = $stmt->fetchAll();

    $created = 0;
    foreach ($admins as $admin) {
        if (create_notification($pdo, (int) $admin['id'], $type, $title, $message, $action_url, $icon)) {
            $created++;
        }
    }

    return $created;
}

/**
 * Check if a table column exists.
 */
function column_exists(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetch();
}

/**
 * Check if an index exists for a table.
 */
function index_exists(PDO $pdo, $table, $index_name) {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1");
    $stmt->execute([$table, $index_name]);
    return (bool) $stmt->fetch();
}
?>
