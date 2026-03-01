-- =====================================================
-- Pharmacy Management System - Database Schema
-- Run this file in phpMyAdmin or MySQL CLI
-- =====================================================

CREATE DATABASE IF NOT EXISTS `pharmacy_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pharmacy_db`;

-- ----------------------------
-- Users table
-- ----------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB;

-- ----------------------------
-- Suppliers table
-- ----------------------------
CREATE TABLE `suppliers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------
-- Medicines table
-- ----------------------------
CREATE TABLE `medicines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `batch_number` VARCHAR(50) NOT NULL,
    `expiry_date` DATE NOT NULL,
    `purchase_price` DECIMAL(10,2) NOT NULL CHECK (`purchase_price` > 0),
    `sale_price` DECIMAL(10,2) NOT NULL CHECK (`sale_price` > 0),
    `quantity` INT NOT NULL DEFAULT 0 CHECK (`quantity` >= 0),
    `supplier_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_medicines_name` (`name`),
    INDEX `idx_medicines_expiry` (`expiry_date`),
    INDEX `idx_medicines_batch` (`batch_number`),
    INDEX `idx_medicines_quantity` (`quantity`),
    CONSTRAINT `fk_medicines_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------
-- Sales table
-- ----------------------------
CREATE TABLE `sales` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sale_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    INDEX `idx_sales_date` (`sale_date`),
    CONSTRAINT `fk_sales_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------
-- Sale items table
-- ----------------------------
CREATE TABLE `sale_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sale_id` INT UNSIGNED NOT NULL,
    `medicine_id` INT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL CHECK (`quantity` > 0),
    `price` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(12,2) NOT NULL,
    INDEX `idx_sale_items_sale` (`sale_id`),
    INDEX `idx_sale_items_medicine` (`medicine_id`),
    CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sale_items_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------------
-- Default admin user (password: admin123)
-- ----------------------------
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@pharmacy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ----------------------------
-- Sample suppliers
-- ----------------------------
INSERT INTO `suppliers` (`name`, `phone`, `email`, `address`) VALUES
('MedSupply Corp', '555-0101', 'sales@medsupply.com', '123 Pharma Street, Medical District'),
('HealthCare Distributors', '555-0202', 'orders@healthcaredist.com', '456 Health Ave, City Center'),
('PharmaWholesale Ltd', '555-0303', 'info@pharmawholesale.com', '789 Drug Lane, Industrial Zone');

-- ----------------------------
-- Sample medicines
-- ----------------------------
INSERT INTO `medicines` (`name`, `batch_number`, `expiry_date`, `purchase_price`, `sale_price`, `quantity`, `supplier_id`) VALUES
('Paracetamol 500mg', 'BT-2024-001', '2027-06-15', 2.50, 5.00, 500, 1),
('Amoxicillin 250mg', 'BT-2024-002', '2026-12-30', 8.00, 15.00, 200, 1),
('Ibuprofen 400mg', 'BT-2024-003', '2027-03-20', 3.00, 6.50, 350, 2),
('Omeprazole 20mg', 'BT-2024-004', '2026-08-10', 5.50, 12.00, 150, 2),
('Cetirizine 10mg', 'BT-2024-005', '2027-01-25', 1.80, 4.00, 400, 3),
('Metformin 500mg', 'BT-2024-006', '2026-05-01', 4.00, 9.00, 120, 1),
('Aspirin 75mg', 'BT-2024-007', '2026-03-15', 1.50, 3.50, 80, 2),
('Ciprofloxacin 500mg', 'BT-2024-008', '2026-04-10', 10.00, 20.00, 45, 3),
('Losartan 50mg', 'BT-2024-009', '2027-09-30', 6.00, 13.00, 250, 1),
('Azithromycin 500mg', 'BT-2024-010', '2026-02-28', 12.00, 25.00, 30, 2),
('Vitamin C 500mg', 'BT-2024-011', '2027-11-15', 2.00, 4.50, 600, 3),
('Pantoprazole 40mg', 'BT-2024-012', '2026-03-25', 4.50, 10.00, 15, 1);
