-- ===================================
-- ระบบแจ้งซ่อม (Repair System)
-- Database: repair_system
-- ===================================

-- สร้าง Database
CREATE DATABASE IF NOT EXISTS repair_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE repair_system;

-- ===================================
-- ตาราง departments (แผนก)
-- ===================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================
-- ตาราง users (ผู้ใช้)
-- ===================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================
-- ตาราง repairs (การแจ้งซ่อม)
-- ===================================
CREATE TABLE repairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    device_type VARCHAR(100) NOT NULL,
    device_detail VARCHAR(255) DEFAULT NULL,
    problem TEXT NOT NULL,
    image_base64 LONGTEXT DEFAULT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================
-- ตาราง approvals (การยืนยันของ admin)
-- ===================================
CREATE TABLE approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    admin_id INT NOT NULL,
    new_status ENUM('in_progress', 'completed') NOT NULL,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_approval (repair_id, admin_id, new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================
-- ตาราง devices (ประเภทอุปกรณ์)
-- ===================================
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



