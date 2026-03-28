<?php
/**
 * Lab Equipment / Inventory module — auto-creates tables on first access.
 */
function inventoryEnsureTables() {
    $check = DBQuery("SHOW TABLES LIKE 'inventory_equipment'");
    if ($check && db_fetch_row($check) !== null) return;

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_categories` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `sort_order` INT DEFAULT 0,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_locations` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `room` VARCHAR(100) DEFAULT '',
        `building` VARCHAR(255) DEFAULT '',
        `sort_order` INT DEFAULT 0,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_equipment` (
        `equipment_id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `name` VARCHAR(500) NOT NULL,
        `description` TEXT,
        `category_id` INT DEFAULT NULL,
        `location_id` INT DEFAULT NULL,
        `serial_number` VARCHAR(100) DEFAULT '',
        `asset_tag` VARCHAR(50) DEFAULT '',
        `barcode` VARCHAR(50) DEFAULT '',
        `manufacturer` VARCHAR(255) DEFAULT '',
        `model` VARCHAR(255) DEFAULT '',
        `purchase_date` DATE DEFAULT NULL,
        `purchase_cost` DECIMAL(10,2) DEFAULT NULL,
        `warranty_expiry` DATE DEFAULT NULL,
        `condition_status` VARCHAR(50) DEFAULT 'Good',
        `total_quantity` INT NOT NULL DEFAULT 1,
        `available_quantity` INT NOT NULL DEFAULT 1,
        `is_active` CHAR(1) DEFAULT 'Y',
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_checkout` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `equipment_id` INT NOT NULL,
        `school_id` INT NOT NULL,
        `borrower_type` VARCHAR(20) NOT NULL DEFAULT 'staff',
        `borrower_id` INT NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `checkout_date` DATE NOT NULL,
        `due_date` DATE DEFAULT NULL,
        `return_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'checked_out',
        `purpose` TEXT,
        `checked_out_by` INT DEFAULT NULL,
        `returned_by` INT DEFAULT NULL,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_maintenance` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `equipment_id` INT NOT NULL,
        `school_id` INT NOT NULL,
        `maintenance_date` DATE NOT NULL,
        `maintenance_type` VARCHAR(100) DEFAULT '',
        `description` TEXT,
        `cost` DECIMAL(10,2) DEFAULT NULL,
        `performed_by` VARCHAR(255) DEFAULT '',
        `next_maintenance` DATE DEFAULT NULL,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

inventoryEnsureTables();
