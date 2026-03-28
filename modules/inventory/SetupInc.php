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

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_field_trips` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `title` VARCHAR(500) NOT NULL,
        `description` TEXT,
        `destination` VARCHAR(500) DEFAULT '',
        `trip_date` DATE NOT NULL,
        `return_date` DATE DEFAULT NULL,
        `departure_time` VARCHAR(10) DEFAULT '',
        `return_time` VARCHAR(10) DEFAULT '',
        `cost_per_student` DECIMAL(10,2) DEFAULT 0,
        `total_budget` DECIMAL(10,2) DEFAULT 0,
        `collected_amount` DECIMAL(10,2) DEFAULT 0,
        `max_students` INT DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'planned',
        `organizer_id` INT DEFAULT NULL,
        `notes` TEXT,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_trip_students` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `trip_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `paid_amount` DECIMAL(10,2) DEFAULT 0,
        `payment_status` VARCHAR(20) DEFAULT 'pending',
        `consent_received` CHAR(1) DEFAULT 'N',
        `notes` TEXT,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_finance` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `category` VARCHAR(100) NOT NULL DEFAULT 'General',
        `title` VARCHAR(500) NOT NULL,
        `description` TEXT,
        `student_id` INT DEFAULT NULL,
        `staff_id` INT DEFAULT NULL,
        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `payment_type` VARCHAR(50) DEFAULT 'cash',
        `payment_date` DATE NOT NULL,
        `due_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'paid',
        `reference_type` VARCHAR(50) DEFAULT NULL,
        `reference_id` INT DEFAULT NULL,
        `receipt_number` VARCHAR(50) DEFAULT '',
        `notes` TEXT,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_birthdays` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `student_id` INT DEFAULT NULL,
        `staff_id` INT DEFAULT NULL,
        `event_date` DATE NOT NULL,
        `gift_budget` DECIMAL(10,2) DEFAULT 0,
        `collected_amount` DECIMAL(10,2) DEFAULT 0,
        `gift_description` VARCHAR(500) DEFAULT '',
        `status` VARCHAR(20) DEFAULT 'upcoming',
        `notes` TEXT,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default categories
    $catCheck = DBGet(DBQuery("SELECT COUNT(*) AS cnt FROM inventory_categories WHERE school_id='" . UserSchool() . "'"));
    if ((int)($catCheck[1]['CNT'] ?? 0) === 0) {
        $sid = UserSchool();
        DBQuery("INSERT INTO inventory_categories (school_id, title, sort_order) VALUES
            ('$sid', 'Lab Equipment', 1), ('$sid', 'IT Hardware', 2), ('$sid', 'Sports Equipment', 3),
            ('$sid', 'Musical Instruments', 4), ('$sid', 'Art Supplies', 5), ('$sid', 'AV Equipment', 6),
            ('$sid', 'Furniture', 7), ('$sid', 'Safety Equipment', 8)");
    }

    // ── Donations table ──────────────────────────────────────────────
    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_donations` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `donation_type` VARCHAR(50) NOT NULL DEFAULT 'money',
        `donor_name` VARCHAR(255) NOT NULL,
        `donor_type` VARCHAR(50) DEFAULT 'parent',
        `donor_id` INT DEFAULT NULL,
        `donor_contact` VARCHAR(255) DEFAULT '',
        `title` VARCHAR(500) NOT NULL,
        `description` TEXT,
        `monetary_value` DECIMAL(10,2) DEFAULT 0,
        `quantity` INT DEFAULT 1,
        `condition_status` VARCHAR(50) DEFAULT 'New',
        `donation_date` DATE NOT NULL,
        `item_type` VARCHAR(50) DEFAULT NULL,
        `book_id` INT DEFAULT NULL,
        `equipment_id` INT DEFAULT NULL,
        `beneficiary_student_id` INT DEFAULT NULL,
        `beneficiary_tag` VARCHAR(100) DEFAULT NULL,
        `receipt_number` VARCHAR(50) DEFAULT '',
        `acknowledgement_sent` CHAR(1) DEFAULT 'N',
        `status` VARCHAR(20) DEFAULT 'received',
        `notes` TEXT,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Needy children / aid tagging ─────────────────────────────────
    DBQuery("CREATE TABLE IF NOT EXISTS `inventory_student_needs` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `need_type` VARCHAR(100) NOT NULL,
        `priority` VARCHAR(20) DEFAULT 'medium',
        `description` TEXT,
        `estimated_cost` DECIMAL(10,2) DEFAULT 0,
        `funded_amount` DECIMAL(10,2) DEFAULT 0,
        `funded_by_donation_id` INT DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'open',
        `assigned_to` INT DEFAULT NULL,
        `date_identified` DATE NOT NULL,
        `date_fulfilled` DATE DEFAULT NULL,
        `notes` TEXT,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

inventoryEnsureTables();
