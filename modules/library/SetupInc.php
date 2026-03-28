<?php
/**
 * Library module — auto-creates tables on first access.
 */
function libraryEnsureTables() {
    $check = DBQuery("SHOW TABLES LIKE 'library_books'");
    if ($check && db_fetch_row($check) !== null) return;

    DBQuery("CREATE TABLE IF NOT EXISTS `library_book_categories` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `sort_order` INT DEFAULT 0,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `library_books` (
        `book_id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT NOT NULL,
        `title` VARCHAR(500) NOT NULL,
        `author` VARCHAR(255) DEFAULT '',
        `isbn` VARCHAR(20) DEFAULT '',
        `category_id` INT DEFAULT NULL,
        `publisher` VARCHAR(255) DEFAULT '',
        `publish_year` INT DEFAULT NULL,
        `total_copies` INT NOT NULL DEFAULT 1,
        `available_copies` INT NOT NULL DEFAULT 1,
        `location` VARCHAR(255) DEFAULT '',
        `notes` TEXT,
        `barcode` VARCHAR(50) DEFAULT '',
        `is_active` CHAR(1) DEFAULT 'Y',
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    DBQuery("CREATE TABLE IF NOT EXISTS `library_checkout` (
        `id` INT(8) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `book_id` INT NOT NULL,
        `school_id` INT NOT NULL,
        `borrower_type` VARCHAR(20) NOT NULL DEFAULT 'student',
        `borrower_id` INT NOT NULL,
        `checkout_date` DATE NOT NULL,
        `due_date` DATE NOT NULL,
        `return_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'checked_out',
        `notes` TEXT,
        `checked_out_by` INT DEFAULT NULL,
        `returned_by` INT DEFAULT NULL,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_by` INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

libraryEnsureTables();
