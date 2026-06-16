CREATE TABLE IF NOT EXISTS otps (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mobile        VARCHAR(15) NOT NULL,
    code_hash     VARCHAR(255) NOT NULL,
    attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verified      TINYINT(1) NOT NULL DEFAULT 0,
    ip_address    VARCHAR(45) DEFAULT NULL,
    expires_at    TIMESTAMP NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_otps_mobile (mobile),
    KEY idx_otps_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
