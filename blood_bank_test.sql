-- SQL schema DDL for LifeLine Blood Bank Management System (Test DB Schema)
-- You can import this file directly in phpMyAdmin or run it in your MySQL client

CREATE DATABASE IF NOT EXISTS blood_bank_test;
USE blood_bank_test;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  donor_number  VARCHAR(10) UNIQUE,             -- e.g. LL-0001, assigned after insert
  fullName      VARCHAR(100) NOT NULL,
  email         VARCHAR(100) UNIQUE NOT NULL,
  phone         VARCHAR(20) NOT NULL,
  province      VARCHAR(50) NOT NULL,
  district      VARCHAR(50) NOT NULL,
  town          VARCHAR(50) NOT NULL,
  bloodType     VARCHAR(10) NOT NULL,
  password      VARCHAR(255) NOT NULL,
  role          ENUM('admin','updater','donor','revoked') DEFAULT 'donor',
  facility_name VARCHAR(150),                   -- hospital name, only for 'updater' role
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Blood Inventory Table
CREATE TABLE IF NOT EXISTS blood_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aPos INT DEFAULT 0, aNeg INT DEFAULT 0,
  bPos INT DEFAULT 0, bNeg INT DEFAULT 0,
  oPos INT DEFAULT 0, oNeg INT DEFAULT 0,
  abPos INT DEFAULT 0, abNeg INT DEFAULT 0,
  platelets INT DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Donations Table
CREATE TABLE IF NOT EXISTS donations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  blood_type VARCHAR(10) NOT NULL,
  volume_ml INT NOT NULL,
  location VARCHAR(150),
  hemoglobin DECIMAL(5,2),
  blood_pressure VARCHAR(20),
  weight DECIMAL(5,2),
  donation_date DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Camps Table
CREATE TABLE IF NOT EXISTS camps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  location VARCHAR(200) NOT NULL,
  organizer VARCHAR(150) NOT NULL,
  description TEXT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Camp Registrations Table
CREATE TABLE IF NOT EXISTS camp_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  camp_id INT NOT NULL,
  user_id INT NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  attended BOOLEAN DEFAULT FALSE,
  UNIQUE KEY uq_camp_donor (camp_id, user_id),
  FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Contact Messages Table
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('Unread','Read','Replied') DEFAULT 'Unread',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Email Log Table
CREATE TABLE IF NOT EXISTS email_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(150) NOT NULL,
  subject VARCHAR(200),
  status ENUM('sent','failed') NOT NULL,
  error_msg TEXT,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Audit Log Table
CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(255),
  details TEXT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================
-- SEED INITIAL USER ACCOUNTS
-- ==========================================
-- Plaintext Passwords:
-- Admin:    admin@lifeline.com  -> AdminPassword123
-- Staff:    updater@lifeline.com -> UpdaterPassword123
-- Donor 1:  donor@lifeline.com   -> DonorPassword123
-- Donor 2:  jane@lifeline.com    -> DonorPassword123
-- Revoked:  revoked@lifeline.com -> RevokedPassword123
-- Donor 3:  saman@lifeline.com   -> DonorPassword123

INSERT INTO users (id, donor_number, fullName, email, phone, province, district, town, bloodType, password, role, facility_name) VALUES
(1, 'LL-0001', 'System Administrator', 'admin@lifeline.com', '+94771234567', 'Western', 'Colombo', 'Colombo 3', 'O+', '$2y$10$c/mq5Be.q43wklQaHhRAHu0M2Ka94KfQm7Qr2IrJ5H3Xf9SSoZsX6', 'admin', NULL),
(2, 'LL-0002', 'Central Hospital Updater', 'updater@lifeline.com', '+94777654321', 'Central', 'Kandy', 'Peradeniya', 'A+', '$2y$10$VoBqpqvWjH/msdawlZ9zbepddqgP4cJKVMJiHSrcSIW1czKcO7XIe', 'updater', 'Central General Hospital'),
(3, 'LL-0003', 'John Doe', 'donor@lifeline.com', '+94711112222', 'Western', 'Colombo', 'Nugegoda', 'O+', '$2y$10$fq37AKz7HTMZsbQ0C7dVpeiudoGKaI5HItDNE95cuBDGBenCnuOJi', 'donor', NULL),
(4, 'LL-0004', 'Jane Smith', 'jane@lifeline.com', '+94722223333', 'Southern', 'Galle', 'Unawatuna', 'B-', '$2y$10$fq37AKz7HTMZsbQ0C7dVpeiudoGKaI5HItDNE95cuBDGBenCnuOJi', 'donor', NULL),
(5, 'LL-0005', 'Revoked Donor', 'revoked@lifeline.com', '+94755554444', 'Northern', 'Jaffna', 'Jaffna', 'AB+', '$2y$10$NrQ56lU.TW0OiT0eEomgC.m5zTTtB7H.se0EyvhrQy127Vqm/Vwyu', 'revoked', NULL),
(6, 'LL-0006', 'Saman Perera', 'saman@lifeline.com', '+94779998888', 'Western', 'Gampaha', 'Negombo', 'A-', '$2y$10$fq37AKz7HTMZsbQ0C7dVpeiudoGKaI5HItDNE95cuBDGBenCnuOJi', 'donor', NULL)
ON DUPLICATE KEY UPDATE id=id;

