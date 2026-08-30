CREATE DATABASE IF NOT EXISTS linkiraq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE linkiraq;

CREATE TABLE plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  monthly_price_iqd DECIMAL(12,2) NOT NULL DEFAULT 0,
  yearly_price_iqd DECIMAL(12,2) NOT NULL DEFAULT 0,
  max_links INT NOT NULL DEFAULT 5,
  analytics_enabled TINYINT(1) NOT NULL DEFAULT 0,
  custom_theme_enabled TINYINT(1) NOT NULL DEFAULT 0,
  custom_domain_enabled TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(190) NULL UNIQUE,
  phone VARCHAR(30) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  language ENUM('ar','ku','en') NOT NULL DEFAULT 'ar',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
);

CREATE TABLE profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  title VARCHAR(120) NULL,
  bio VARCHAR(300) NULL,
  avatar_url VARCHAR(500) NULL,
  theme_key VARCHAR(80) NOT NULL DEFAULT 'calm-blue',
  background_value VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  views_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  url VARCHAR(1000) NOT NULL,
  icon VARCHAR(80) NULL,
  position INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  clicks_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_links_user_position(user_id, position),
  CONSTRAINT fk_links_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE link_clicks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  link_id BIGINT UNSIGNED NOT NULL,
  visitor_hash CHAR(64) NULL,
  country_code CHAR(2) NULL,
  device_type VARCHAR(30) NULL,
  referrer VARCHAR(500) NULL,
  clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_clicks_link_date(link_id, clicked_at),
  CONSTRAINT fk_clicks_link FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
);

CREATE TABLE subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  status ENUM('trial','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trial',
  amount_iqd DECIMAL(12,2) NOT NULL DEFAULT 0,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sub_user_status(user_id, status),
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
);

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED NULL,
  amount_iqd DECIMAL(12,2) NOT NULL,
  gateway VARCHAR(80) NULL,
  gateway_reference VARCHAR(190) NULL,
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);

INSERT INTO plans (name,slug,monthly_price_iqd,yearly_price_iqd,max_links,analytics_enabled,custom_theme_enabled,custom_domain_enabled) VALUES
('Free','free',0,0,5,0,0,0),
('Plus','plus',5000,50000,25,1,1,0),
('Pro','pro',10000,100000,999,1,1,1);
