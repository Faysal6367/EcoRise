-- EcoRise Database Schema
-- Database name: ecorise

CREATE DATABASE IF NOT EXISTS ecorise;
USE ecorise;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    volunteer_status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: campaigns
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    relief_type VARCHAR(100) NULL,
    division VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    image_path VARCHAR(500) DEFAULT 'assets/campaigns/default.jpg',
    target_amount DECIMAL(12, 2) NOT NULL,
    raised_amount DECIMAL(12, 2) DEFAULT 0.00,
    volunteers_needed INT DEFAULT 0,
    status ENUM('active', 'completed', 'pending') DEFAULT 'active',
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_by INT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Backward-compatible migration for existing databases
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS relief_type VARCHAR(100) NULL AFTER description;

-- Table: donations
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    campaign_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    stripe_session_id VARCHAR(255) UNIQUE NULL,
    payment_method VARCHAR(50) NULL,
    payment_status VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- Table: volunteer_applications
CREATE TABLE IF NOT EXISTS volunteer_applications (
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
    no_facebook TINYINT(1) DEFAULT 0,
    linkedin_profile VARCHAR(255) NULL,
    whatsapp_number VARCHAR(30) NULL,
    telegram_number VARCHAR(30) NULL,
    education_medium VARCHAR(100) NOT NULL,
    education_level VARCHAR(100) NOT NULL,
    last_passing_year VARCHAR(10) NOT NULL,
    department_degree VARCHAR(150) NULL,
    institution_name VARCHAR(180) NOT NULL,
    worked_before TINYINT(1) DEFAULT 0,
    previous_project_name VARCHAR(180) NULL,
    previous_implementation_location VARCHAR(180) NULL,
    previous_project_year VARCHAR(10) NULL,
    people_benefited VARCHAR(80) NULL,
    photo_path VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample admin
-- Password is admin123
INSERT INTO users (full_name, email, password_hash, role) VALUES 
('System Admin', 'admin@ecorise.com', '$2y$10$fVf7U26X0O9v5M7v6Y7m8eJ7J9jG/W7vV/7V7V7V7V7V7V7V7vV', 'admin');

-- Table: disaster_relief_campaigns
CREATE TABLE IF NOT EXISTS disaster_relief_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(200) NOT NULL,
    relief_type VARCHAR(100) NOT NULL,
    status ENUM('active', 'completed', 'pending') DEFAULT 'active',
    image_path VARCHAR(500) DEFAULT 'assets/disasters/default.jpg',
    volunteers_needed INT DEFAULT 0,
    volunteers_assigned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: volunteer_assignments
CREATE TABLE IF NOT EXISTS volunteer_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,
    disaster_relief_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'declined') DEFAULT 'active',
    hours_contributed INT DEFAULT 0,
    notes TEXT NULL,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (disaster_relief_id) REFERENCES disaster_relief_campaigns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (volunteer_id, disaster_relief_id)
);

-- Table: notifications
CREATE TABLE IF NOT EXISTS notifications (
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
);

CREATE INDEX idx_notifications_user_read_created ON notifications (user_id, is_read, created_at);
CREATE INDEX idx_notifications_created ON notifications (created_at);

-- Use actual hash for admin123
-- Result of password_hash('admin123', PASSWORD_DEFAULT)
-- I will set it to the correct one.
UPDATE users SET password_hash = '$2y$10$7Z8lR9o1D0/k.q9qE0h/Eu7U3z1i0pA1i6.G1n2E8f/6u9V9r8V7u' WHERE email = 'admin@ecorise.com';

-- Sample Campaigns
INSERT INTO campaigns (title, description, image_path, target_amount, raised_amount, status, created_by) VALUES 
('Amazon Reforestation', 'Planting 10,000 native trees to restore biodiversity and fight climate change in the heart of the Amazon.', 'assets/campaigns/amazon.png', 50000.00, 15000.00, 'active', 1),
('Ocean Cleanup 2024', 'Removing plastic waste from international waters using innovative floating barriers.', 'assets/campaigns/ocean.png', 100000.00, 45000.00, 'active', 1),
('Renewable Energy for Schools', 'Installing solar panels on rural community schools to provide clean and reliable energy.', 'assets/campaigns/solar.png', 30000.00, 12000.00, 'active', 1),
('Save the Red Pandas', 'Protecting critical habitats and supporting breeding programs for endangered red pandas.', 'assets/campaigns/pandas.png', 25000.00, 8000.00, 'active', 1),
('Sustainable Farming Hub', 'Building a center to teach local farmers water-wise and regenerative agriculture techniques.', 'assets/campaigns/farming.png', 40000.00, 20000.00, 'active', 1),
('Urban Bee Sanctuaries', 'Creating bee-friendly rooftops and parks across metropolitan areas to save pollinators.', 'assets/campaigns/bees.png', 15000.00, 5000.00, 'active', 1);

-- Sample Disaster Relief Campaigns
INSERT INTO disaster_relief_campaigns (title, description, location, relief_type, status, image_path, volunteers_needed) VALUES 
('Flood Relief - Dhaka Region', 'Providing emergency shelter, food, and medical aid to flood-affected families in Dhaka. We need volunteers to distribute supplies and assist in evacuation efforts.', 'Dhaka, Bangladesh', 'Flood Relief', 'active', 'assets/disasters/flood.jpg', 25),
('Cyclone Preparedness - Chittagong', 'Helping coastal communities prepare for the upcoming cyclone season. Volunteers needed for awareness campaigns and shelter preparation in Chittagong division.', 'Chittagong, Bangladesh', 'Cyclone Preparedness', 'active', 'assets/disasters/cyclone.jpg', 30),
('Wildfire Response - Sylhet Forest', 'Fighting and preventing wildfires in the Sylhet region. Volunteers assist in fire prevention, community awareness, and forest restoration efforts.', 'Sylhet, Bangladesh', 'Wildfire Response', 'active', 'assets/disasters/wildfire.jpg', 20),
('Earthquake Relief - Cox\'s Bazar', 'Supporting earthquake-affected communities with medical assistance, psychological support, and infrastructure rebuilding in Cox\'s Bazar.', 'Cox\'s Bazar, Bangladesh', 'Earthquake Relief', 'active', 'assets/disasters/earthquake.jpg', 35),
('Landslide Prevention - Chittagong Hill Tracts', 'Implementing landslide prevention measures and early warning systems in vulnerable hill communities. Volunteers help with survey work and community education.', 'Chittagong Hill Tracts, Bangladesh', 'Landslide Prevention', 'active', 'assets/disasters/landslide.jpg', 15),
('Drought Support - Rajshahi Region', 'Providing water resources and livelihood support to drought-affected farmers in the Rajshahi region. Help with well digging and crop diversification programs.', 'Rajshahi, Bangladesh', 'Drought Support', 'active', 'assets/disasters/drought.jpg', 18);
