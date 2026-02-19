-- Farm Management System Database Schema
-- Run this in phpMyAdmin to create all required tables
-- InfinityFree compatible SQL

-- =============================================
-- Users Table
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'farmer', 'employee', 'advisor') DEFAULT 'employee',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Farms Table
-- =============================================
CREATE TABLE IF NOT EXISTS farms (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    total_area DECIMAL(10,2),
    description TEXT,
    user_id VARCHAR(36),
    coordinates JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Fields/Parcels Table
-- =============================================
CREATE TABLE IF NOT EXISTS fields (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    farm_id VARCHAR(36) NOT NULL,
    area DECIMAL(10,2),
    perimeter DECIMAL(10,2),
    soil_type VARCHAR(100),
    irrigation_type VARCHAR(100),
    coordinates JSON,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Crops Table
-- =============================================
CREATE TABLE IF NOT EXISTS crops (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    variety VARCHAR(255),
    field_id VARCHAR(36),
    farm_id VARCHAR(36) NOT NULL,
    planting_date DATE,
    expected_harvest DATE,
    actual_harvest DATE,
    status VARCHAR(50) DEFAULT 'active',
    area_allocated DECIMAL(10,2),
    coordinates JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_field_id (field_id),
    INDEX idx_status (status),
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Activities Table
-- =============================================
CREATE TABLE IF NOT EXISTS activities (
    id VARCHAR(36) PRIMARY KEY,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    field_id VARCHAR(36),
    crop_id VARCHAR(36),
    farm_id VARCHAR(36),
    infrastructure_id VARCHAR(36),
    activity_date DATE,
    cost DECIMAL(10,2) DEFAULT 0,
    labor_hours DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_activity_date (activity_date),
    INDEX idx_activity_type (activity_type),
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE SET NULL,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Activity Inputs (junction table)
-- =============================================
CREATE TABLE IF NOT EXISTS activity_inputs (
    id VARCHAR(36) PRIMARY KEY,
    activity_id VARCHAR(36) NOT NULL,
    input_id VARCHAR(36) NOT NULL,
    quantity_used DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'units',
    cost DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_id (activity_id),
    INDEX idx_input_id (input_id),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Harvests Table
-- =============================================
CREATE TABLE IF NOT EXISTS harvests (
    id VARCHAR(36) PRIMARY KEY,
    crop_id VARCHAR(36),
    farm_id VARCHAR(36) NOT NULL,
    harvest_date DATE,
    quantity DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'kg',
    quality_grade VARCHAR(50),
    storage_location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_crop_id (crop_id),
    INDEX idx_harvest_date (harvest_date),
    FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE SET NULL,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Infrastructure Table
-- =============================================
CREATE TABLE IF NOT EXISTS infrastructure (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    farm_id VARCHAR(36) NOT NULL,
    location VARCHAR(255),
    capacity VARCHAR(100),
    status VARCHAR(50) DEFAULT 'operational',
    notes TEXT,
    coordinates JSON,
    construction_date DATE,
    last_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_type (type),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Contracts Table
-- =============================================
CREATE TABLE IF NOT EXISTS contracts (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    contract_type VARCHAR(100),
    party_name VARCHAR(255),
    farm_id VARCHAR(36) NOT NULL,
    start_date DATE,
    end_date DATE,
    value DECIMAL(12,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    status VARCHAR(50) DEFAULT 'active',
    terms TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_status (status),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Inputs (Inventory) Table
-- =============================================
CREATE TABLE IF NOT EXISTS inputs (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    quantity DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'units',
    unit_cost DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(12,2) DEFAULT 0,
    supplier VARCHAR(255),
    purchase_date DATE,
    expiry_date DATE,
    farm_id VARCHAR(36) NOT NULL,
    storage_location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_category (category),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Cost Settings Table
-- =============================================
CREATE TABLE IF NOT EXISTS cost_settings (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    amount DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'per_unit',
    farm_id VARCHAR(36),
    is_default TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Documents Table
-- =============================================
CREATE TABLE IF NOT EXISTS documents (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    document_type VARCHAR(100),
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(100),
    farm_id VARCHAR(36),
    uploaded_by VARCHAR(36),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Farm Users (Team Members) Table
-- =============================================
CREATE TABLE IF NOT EXISTS farm_users (
    id VARCHAR(36) PRIMARY KEY,
    farm_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    role VARCHAR(100) DEFAULT 'member',
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_farm_user (farm_id, user_id),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Crop Definitions (Library) Table
-- =============================================
CREATE TABLE IF NOT EXISTS crop_definitions (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    variety VARCHAR(255),
    days_to_maturity INT,
    planting_season VARCHAR(100),
    description TEXT,
    is_default TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Infrastructure Definitions (Library) Table
-- =============================================
CREATE TABLE IF NOT EXISTS infrastructure_definitions (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    type VARCHAR(100),
    description TEXT,
    is_default TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Weather Data Table
-- =============================================
CREATE TABLE IF NOT EXISTS weather_data (
    id VARCHAR(36) PRIMARY KEY,
    farm_id VARCHAR(36) NOT NULL,
    date DATE NOT NULL,
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    precipitation DECIMAL(5,2),
    wind_speed DECIMAL(5,2),
    conditions VARCHAR(100),
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farm_id (farm_id),
    INDEX idx_date (date),
    UNIQUE KEY unique_farm_date (farm_id, date),
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Insert Default Admin User
-- Password: admin123 (hashed)
-- =============================================
INSERT INTO users (id, first_name, last_name, email, password, role, is_active) VALUES
('admin-001', 'System', 'Administrator', 'admin@farm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- =============================================
-- Done!
-- =============================================
