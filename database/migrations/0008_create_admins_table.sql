CREATE TABLE IF NOT EXISTS admins (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(60) NOT NULL,
    full_name       VARCHAR(120) DEFAULT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('super_admin','manager') NOT NULL DEFAULT 'manager',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    remember_token  VARCHAR(255) DEFAULT NULL,
    last_login_at   TIMESTAMP NULL DEFAULT NULL,
    last_login_ip   VARCHAR(45) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
