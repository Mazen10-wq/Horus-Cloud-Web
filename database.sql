-- =============================================
-- Horus Cloud - Full Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS horus_cloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE horus_cloud;

-- ---- Researchers (users who register & login) ----
CREATE TABLE IF NOT EXISTS researchers (
    id            INT           AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password      VARCHAR(255)  NOT NULL,
    institution   VARCHAR(255),
    phone         VARCHAR(20),
    status        ENUM('pending_verify','active','suspended') DEFAULT 'pending_verify',
    verify_token  VARCHAR(64),
    reset_token   VARCHAR(64),
    reset_expires TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Access Requests ----
CREATE TABLE IF NOT EXISTS access_requests (
    id              INT           AUTO_INCREMENT PRIMARY KEY,
    researcher_id   INT           NOT NULL,
    service_type    ENUM('hosting','compute') NOT NULL,
    research_field  VARCHAR(100)  NOT NULL,
    cpu_cores       VARCHAR(50),
    ram             VARCHAR(50),
    gpu             VARCHAR(100),
    duration        VARCHAR(50),
    description     TEXT          NOT NULL,
    status          ENUM('pending','approved','rejected','processing') DEFAULT 'pending',
    admin_notes     TEXT,
    ip_address      VARCHAR(45),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (researcher_id) REFERENCES researchers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_researcher (researcher_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Resources assigned to researchers ----
CREATE TABLE IF NOT EXISTS resources (
    id            INT           AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT           NOT NULL,
    request_id    INT,
    cpu_cores     VARCHAR(50),
    ram           VARCHAR(50),
    gpu           VARCHAR(100),
    storage       VARCHAR(50),
    ip_address    VARCHAR(45),
    ssh_user      VARCHAR(100),
    expires_at    TIMESTAMP NULL,
    status        ENUM('active','expired','suspended') DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (researcher_id) REFERENCES researchers(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id)    REFERENCES access_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Messages (Admin <-> Researcher) ----
CREATE TABLE IF NOT EXISTS messages (
    id            INT           AUTO_INCREMENT PRIMARY KEY,
    from_admin    TINYINT(1)    DEFAULT 0,
    researcher_id INT           NOT NULL,
    subject       VARCHAR(255),
    body          TEXT          NOT NULL,
    is_read       TINYINT(1)    DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (researcher_id) REFERENCES researchers(id) ON DELETE CASCADE,
    INDEX idx_researcher (researcher_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Admin Users ----
CREATE TABLE IF NOT EXISTS admin_users (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    role       ENUM('super_admin','admin','reviewer') DEFAULT 'admin',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin (password: Admin@Horus2024 — CHANGE THIS!)
-- Generate new: php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
INSERT INTO admin_users (username, password, email, role) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@horuscloud.edu.eg', 'super_admin')
ON DUPLICATE KEY UPDATE username = username;

-- ---- Sessions ----
CREATE TABLE IF NOT EXISTS sessions (
    id         VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id    INT,
    user_type  ENUM('researcher','admin'),
    data       TEXT,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SETUP: Create limited DB user
-- CREATE USER 'horus_user'@'localhost' IDENTIFIED BY 'StrongPass!2024';
-- GRANT SELECT,INSERT,UPDATE,DELETE ON horus_cloud.* TO 'horus_user'@'localhost';
-- FLUSH PRIVILEGES;
-- =============================================
