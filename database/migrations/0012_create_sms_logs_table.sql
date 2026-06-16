CREATE TABLE IF NOT EXISTS sms_logs (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mobile        VARCHAR(15) NOT NULL,
    pattern_code  VARCHAR(120) DEFAULT NULL,
    type          ENUM('otp','order_user','order_admin','other') NOT NULL DEFAULT 'other',
    payload       TEXT DEFAULT NULL,
    status        ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    response      TEXT DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sms_mobile (mobile),
    KEY idx_sms_type (type),
    KEY idx_sms_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
