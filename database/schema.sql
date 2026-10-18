CREATE DATABASE IF NOT EXISTS navrang_events CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE navrang_events;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(191) NOT NULL,
  username VARCHAR(191) DEFAULT NULL,
  full_name VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','editor','viewer') NOT NULL DEFAULT 'super_admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_site_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(191) NOT NULL,
  title VARCHAR(255) NOT NULL,
  tagline VARCHAR(255) DEFAULT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  venue VARCHAR(255) NOT NULL,
  status ENUM('draft','published','archived','cancelled') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  poster_url VARCHAR(255) DEFAULT NULL,
  banner_url VARCHAR(255) DEFAULT NULL,
  logo_url VARCHAR(255) DEFAULT NULL,
  goal_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  upi_id VARCHAR(255) DEFAULT NULL,
  upi_qr_path VARCHAR(255) DEFAULT NULL,
  is_contribution_open TINYINT(1) NOT NULL DEFAULT 1,
  theme_primary VARCHAR(32) DEFAULT '#7c39e6',
  theme_secondary VARCHAR(32) DEFAULT '#f7c948',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_event_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alumni (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(191) NOT NULL,
  batch VARCHAR(100) DEFAULT NULL,
  department VARCHAR(191) DEFAULT NULL,
  degree VARCHAR(191) DEFAULT NULL,
  photo_url VARCHAR(255) DEFAULT NULL,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contributions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  contributor_name VARCHAR(191) NOT NULL,
  batch VARCHAR(100) DEFAULT NULL,
  department VARCHAR(191) DEFAULT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  transaction_ref VARCHAR(191) NOT NULL,
  payment_reference VARCHAR(191) DEFAULT NULL,
  email VARCHAR(191) DEFAULT NULL,
  photo_url VARCHAR(255) DEFAULT NULL,
  message TEXT,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  display_name TINYINT(1) NOT NULL DEFAULT 0,
  display_photo TINYINT(1) NOT NULL DEFAULT 0,
  display_batch TINYINT(1) NOT NULL DEFAULT 0,
  display_amount TINYINT(1) NOT NULL DEFAULT 0,
  display_message TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('pending_payment','pending_verification','pending','approved','verified','rejected','refunded','duplicate') NOT NULL DEFAULT 'pending_payment',
  verification_notes TEXT,
  verified_at TIMESTAMP NULL DEFAULT NULL,
  verified_by INT UNSIGNED DEFAULT NULL,
  evidence_path VARCHAR(255) DEFAULT NULL,
  consent_status ENUM('none','explicit','declined') NOT NULL DEFAULT 'none',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contrib_event (event_id),
  KEY idx_contrib_status (status),
  KEY idx_contrib_transaction (transaction_ref),
  CONSTRAINT fk_contrib_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_contrib_verifier FOREIGN KEY (verified_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_gallery (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  title VARCHAR(191) DEFAULT NULL,
  image_url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_gallery_event (event_id),
  CONSTRAINT fk_gallery_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_event_setting (event_id, setting_key),
  CONSTRAINT fk_event_setting FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(191) NOT NULL,
  note TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_admin (admin_id),
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Navrang Events'),
('tagline', 'Nine Colors. One Campus. One Celebration.'),
('homepage_title', 'NAVRANG 1.0 – Dandiya Night'),
('homepage_description', 'An alumni-centric cultural celebration and fundraiser for the campus community.'),
('contact_email', 'hello@navrang.events'),
('contact_phone', '+91 98765 43210'),
('location', 'Girls Hostel Lawn, Pondicherry University, Karaikal Campus'),
('social_facebook', 'https://facebook.com'),
('social_instagram', 'https://instagram.com'),
('footer_text', 'Celebrating campus culture, alumni pride, and community contribution.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO events (slug, title, tagline, description, event_date, start_time, end_time, venue, status, is_featured, poster_url, goal_amount, upi_id, is_contribution_open, theme_primary, theme_secondary)
VALUES (
  'navrang-1-0-dandiya-night',
  'NAVRANG 1.0 – Dandiya Night',
  'Nine Colors. One Campus. One Celebration.',
  'Celebrate dance, music, alumni pride, and campus culture in a memorable evening built on community generosity.',
  '2026-10-26',
  '16:00:00',
  '20:00:00',
  'Girls Hostel Lawn, Pondicherry University, Karaikal Campus',
  'published',
  1,
  '/assets/images/Navrang 1.0 Dandiya Night Poster.png',
  250000.00,
  'navrang@upi',
  1,
  '#7c39e6',
  '#f7c948'
)
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO event_gallery (event_id, title, image_url, sort_order)
VALUES (1, 'Poster', '/assets/images/Navrang 1.0 Dandiya Night Poster.png', 1),
       (1, 'Stage', '/assets/images/Navrang 1.0 Dandiya Night Poster.png', 2),
       (1, 'Crowd', '/assets/images/Navrang 1.0 Dandiya Night Poster.png', 3)
ON DUPLICATE KEY UPDATE image_url = VALUES(image_url);
