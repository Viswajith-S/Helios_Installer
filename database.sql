-- database.sql
-- Run this script in your local phpMyAdmin to create the `Installs` database

CREATE DATABASE IF NOT EXISTS `Installs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `Installs`;

-- --------------------------------------------------------

--
-- Table structure for table `job_compliance`
--

CREATE TABLE `job_compliance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crm_project_id` int(11) NOT NULL COMMENT 'Links to Inventory.projects.id',
  `pre_install_liability_signed` tinyint(1) NOT NULL DEFAULT 0,
  `swms_completed` tinyint(1) NOT NULL DEFAULT 0,
  `final_sign_off_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_project_id` (`crm_project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manager_tasks`
--

CREATE TABLE `manager_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_notes`
--

CREATE TABLE `job_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crm_project_id` int(11) NOT NULL COMMENT 'Links to Inventory.projects.id',
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


