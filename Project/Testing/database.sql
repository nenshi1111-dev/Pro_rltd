-- Complaint Management System Database
-- MySQL 5.6.20 Compatible

CREATE DATABASE IF NOT EXISTS `cms_complaints` CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `cms_complaints`;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `role` enum('admin','public') NOT NULL DEFAULT 'public',
    `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Complaints Table
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `ticket_number` varchar(20) NOT NULL,
    `subject` varchar(200) NOT NULL,
    `description` text NOT NULL,
    `category_id` int(11) NOT NULL,
    `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `status` enum('open','in-progress','resolved','closed') NOT NULL DEFAULT 'open',
    `admin_remark` text,
    `assigned_to` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ticket_number` (`ticket_number`),
    KEY `user_id` (`user_id`),
    KEY `category_id` (`category_id`),
    KEY `assigned_to` (`assigned_to`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `fk_complaints_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_complaints_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_complaints_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Complaint History Table
CREATE TABLE IF NOT EXISTS `complaint_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `complaint_id` int(11) NOT NULL,
    `changed_by` int(11) NOT NULL,
    `old_status` varchar(20) DEFAULT NULL,
    `new_status` varchar(20) DEFAULT NULL,
    `remark` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `complaint_id` (`complaint_id`),
    KEY `changed_by` (`changed_by`),
    CONSTRAINT `fk_history_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Attachments Table
CREATE TABLE IF NOT EXISTS `attachments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `complaint_id` int(11) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_size` int(11) DEFAULT NULL,
    `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `complaint_id` (`complaint_id`),
    CONSTRAINT `fk_attachments_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(50) NOT NULL,
    `setting_value` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert Default Categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Product Quality', 'Issues related to product quality or defects'),
('Delivery', 'Problems with shipping, delivery delays, or damaged items'),
('Billing', 'Payment issues, overcharges, refund requests'),
('Customer Service', 'Issues with staff behavior or service quality'),
('Technical Support', 'Technical problems with products or services'),
('General Inquiry', 'General questions or feedback'),
('Other', 'Complaints that do not fit other categories');

-- Insert Default Admin User (password: admin123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `phone`, `role`) VALUES
('admin', 'admin@example.com', '$2y$10$73RftJM3MbUWFfEHIWSSyON7t7Jpc2rougijYnWZbyxMXrwU5BZzy', 'System Administrator', '1234567890', 'admin');

-- Insert Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Complaint Management System'),
('site_email', 'support@example.com'),
('ticket_prefix', 'CMP'),
('max_upload_size', '5242880'),
('items_per_page', '10');
