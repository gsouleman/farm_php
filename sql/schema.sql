-- Farm Management System - Complete MySQL Schema for InfinityFree
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_inputs`;
DROP TABLE IF EXISTS `activities`;
DROP TABLE IF EXISTS `harvests`;
DROP TABLE IF EXISTS `crops`;
DROP TABLE IF EXISTS `infrastructure`;
DROP TABLE IF EXISTS `inputs`;
DROP TABLE IF EXISTS `fields`;
DROP TABLE IF EXISTS `farms`;
DROP TABLE IF EXISTS `contracts`;
DROP TABLE IF EXISTS `cost_settings`;
DROP TABLE IF EXISTS `crop_definitions`;
DROP TABLE IF EXISTS `documents`;
DROP TABLE IF EXISTS `farm_users`;
DROP TABLE IF EXISTS `infrastructure_definitions`;
DROP TABLE IF EXISTS `weather_data`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `spatial_ref_sys`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'farmer',
  `profile_image_url` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Farms
CREATE TABLE IF NOT EXISTS `farms` (
  `id` char(36) NOT NULL,
  `owner_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `coordinates` POINT DEFAULT NULL,
  `boundary` POLYGON DEFAULT NULL,
  `total_area` decimal(10,2) DEFAULT NULL,
  `area_unit` varchar(255) DEFAULT 'hectares',
  `farm_type` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `farms_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fields
CREATE TABLE IF NOT EXISTS `fields` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `field_number` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'active',
  `crop_id` char(36) DEFAULT NULL,
  `boundary` POLYGON NOT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `perimeter` decimal(10,2) DEFAULT NULL,
  `area_unit` varchar(255) DEFAULT 'hectares',
  `soil_type` varchar(255) DEFAULT NULL,
  `drainage` varchar(255) DEFAULT NULL,
  `slope` varchar(255) DEFAULT NULL,
  `irrigation` tinyint(1) DEFAULT '0',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `carbon_sequestration` decimal(10,2) DEFAULT '0.00',
  `water_efficiency` decimal(5,2) DEFAULT '100.00',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fields_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Infrastructure
CREATE TABLE IF NOT EXISTS `infrastructure` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `field_id` char(36) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `sub_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'operational',
  `condition` varchar(255) DEFAULT 'good',
  `material` varchar(255) DEFAULT NULL,
  `maintenance_history` json DEFAULT NULL,
  `construction_date` date DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `area_sqm` decimal(10,2) DEFAULT NULL,
  `capacity_unit` varchar(255) DEFAULT 'MT',
  `quantity` int DEFAULT '1',
  `unit_price` decimal(15,2) DEFAULT NULL,
  `acquisition_cost` decimal(15,2) DEFAULT NULL,
  `perimeter` decimal(10,2) DEFAULT NULL,
  `boundary_manual` text DEFAULT NULL,
  `boundary` POLYGON DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `infra_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inputs
CREATE TABLE IF NOT EXISTS `inputs` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `input_type` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `quantity_in_stock` decimal(15,2) DEFAULT '0.00',
  `reorder_level` decimal(15,2) DEFAULT '0.00',
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `inputs_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crops
CREATE TABLE IF NOT EXISTS `crops` (
  `id` char(36) NOT NULL,
  `field_id` char(36) NOT NULL,
  `crop_type` varchar(255) NOT NULL,
  `variety` varchar(255) DEFAULT NULL,
  `planting_date` date DEFAULT NULL,
  `expected_harvest_date` date DEFAULT NULL,
  `actual_harvest_date` date DEFAULT NULL,
  `planted_area` decimal(10,2) DEFAULT NULL,
  `planting_rate` decimal(10,2) DEFAULT NULL,
  `row_spacing` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'planted',
  `season` varchar(255) DEFAULT NULL,
  `year` int NOT NULL,
  `estimated_cost` decimal(10,2) DEFAULT '0.00',
  `boundary` POLYGON DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `crops_field_fk` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Harvests
CREATE TABLE IF NOT EXISTS `harvests` (
  `id` char(36) NOT NULL,
  `crop_id` char(36) NOT NULL,
  `harvest_date` date NOT NULL,
  `area_harvested` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `yield_per_area` decimal(10,2) DEFAULT NULL,
  `quality_grade` varchar(255) DEFAULT NULL,
  `moisture_content` decimal(5,2) DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_revenue` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `harvests_crop_fk` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activities
CREATE TABLE IF NOT EXISTS `activities` (
  `id` char(36) NOT NULL,
  `crop_id` char(36) DEFAULT NULL,
  `field_id` char(36) DEFAULT NULL,
  `infrastructure_id` char(36) DEFAULT NULL,
  `harvest_id` char(36) DEFAULT NULL,
  `farm_id` char(36) DEFAULT NULL,
  `performed_by` char(36) DEFAULT NULL,
  `activity_type` varchar(255) NOT NULL,
  `activity_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_hours` decimal(10,2) DEFAULT NULL,
  `area_covered` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(255) DEFAULT NULL,
  `work_status` varchar(255) DEFAULT NULL,
  `transaction_type` varchar(255) DEFAULT 'expense',
  `labor_cost` decimal(15,2) DEFAULT NULL,
  `material_cost` decimal(15,2) DEFAULT NULL,
  `equipment_cost` decimal(15,2) DEFAULT NULL,
  `service_cost` decimal(15,2) DEFAULT NULL,
  `transport_cost` decimal(15,2) DEFAULT NULL,
  `other_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `num_workers` int DEFAULT NULL,
  `weather_conditions` text DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `equipment_used` text DEFAULT NULL,
  `component` varchar(255) DEFAULT NULL,
  `materials_used` text DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `issues` text DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `supplier_contact` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `warranty` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `activities_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_performed_by_fk` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Inputs (Junction Table)
CREATE TABLE IF NOT EXISTS `activity_inputs` (
  `id` char(36) NOT NULL,
  `activity_id` char(36) NOT NULL,
  `input_id` char(36) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `application_rate` decimal(10,2) DEFAULT NULL,
  `application_method` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `act_in_activity_fk` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `act_in_input_fk` FOREIGN KEY (`input_id`) REFERENCES `inputs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contracts
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `contract_type` varchar(255) NOT NULL,
  `partner_name` varchar(255) NOT NULL,
  `crop_id` char(36) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `total_value` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) DEFAULT 'draft',
  `delivery_terms` text DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `contracts_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cost Settings
CREATE TABLE IF NOT EXISTS `cost_settings` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `category` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `billing_frequency` varchar(255) DEFAULT 'per_unit',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `cost_settings_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crop Definitions
CREATE TABLE IF NOT EXISTS `crop_definitions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `varieties` json DEFAULT NULL,
  `icon` varchar(255) DEFAULT '🌱',
  `color` varchar(255) DEFAULT '#4caf50',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Documents
CREATE TABLE IF NOT EXISTS `documents` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `uploaded_by` char(36) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `file_url` text NOT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `docs_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Farm Users
CREATE TABLE IF NOT EXISTS `farm_users` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'employee',
  `permissions` json DEFAULT NULL,
  `invited_by` char(36) DEFAULT NULL,
  `invitation_status` varchar(255) DEFAULT 'pending',
  `joined_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `farm_user_unique` (`farm_id`,`user_id`),
  CONSTRAINT `fu_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fu_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Infrastructure Definitions
CREATE TABLE IF NOT EXISTS `infrastructure_definitions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT '🏠',
  `color` varchar(255) DEFAULT '#795548',
  `is_active` tinyint(1) DEFAULT '1',
  `sub_types` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Weather Data
CREATE TABLE IF NOT EXISTS `weather_data` (
  `id` char(36) NOT NULL,
  `farm_id` char(36) NOT NULL,
  `date` date NOT NULL,
  `temperature_max` decimal(5,2) DEFAULT NULL,
  `temperature_min` decimal(5,2) DEFAULT NULL,
  `temperature_avg` decimal(5,2) DEFAULT NULL,
  `precipitation` decimal(10,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `wind_speed` decimal(10,2) DEFAULT NULL,
  `conditions` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `weather_farm_fk` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


SET FOREIGN_KEY_CHECKS = 1;
