-- ============================================================
-- Nullik Academy — Consolidated Database Schema (MySQL 8+)
-- Generated from database/migrations/*.sql
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ---------- 0001_create_tools_table.sql ----------
CREATE TABLE IF NOT EXISTS tools (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    slug          VARCHAR(140) NOT NULL,
    logo          VARCHAR(255) DEFAULT NULL,
    description   TEXT DEFAULT NULL,
    sort_order    INT NOT NULL DEFAULT 0,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tools_slug (slug),
    KEY idx_tools_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0002_create_plans_table.sql ----------
CREATE TABLE IF NOT EXISTS plans (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tool_id       INT UNSIGNED NOT NULL,
    name          VARCHAR(120) NOT NULL,
    badge         VARCHAR(60) DEFAULT NULL,
    duration      VARCHAR(60) NOT NULL,
    duration_days INT UNSIGNED NOT NULL DEFAULT 30,
    price_usdt    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    price_irt     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    mode_type     ENUM('email_password','organization_id') NOT NULL DEFAULT 'email_password',
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    sort_order    INT NOT NULL DEFAULT 0,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_plans_tool (tool_id),
    KEY idx_plans_status (status),
    CONSTRAINT fk_plans_tool FOREIGN KEY (tool_id) REFERENCES tools (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0003_create_users_table.sql ----------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mobile        VARCHAR(15) NOT NULL,
    first_name    VARCHAR(80) DEFAULT NULL,
    last_name     VARCHAR(80) DEFAULT NULL,
    mobile_verified_at TIMESTAMP NULL DEFAULT NULL,
    orders_count  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0004_create_otps_table.sql ----------
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

-- ---------- 0005_create_orders_table.sql ----------
CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number    VARCHAR(20) NOT NULL,
    user_id         INT UNSIGNED DEFAULT NULL,
    mobile          VARCHAR(15) NOT NULL,
    first_name      VARCHAR(80) NOT NULL,
    last_name       VARCHAR(80) NOT NULL,
    tool_id         INT UNSIGNED NOT NULL,
    plan_id         INT UNSIGNED NOT NULL,
    tool_name       VARCHAR(120) NOT NULL,
    plan_name       VARCHAR(120) NOT NULL,
    mode_type       ENUM('email_password','organization_id') NOT NULL,
    email           VARCHAR(190) DEFAULT NULL,
    password_enc    TEXT DEFAULT NULL,
    organization_id VARCHAR(190) DEFAULT NULL,
    price_usdt      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    price_irt       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    payment_method  ENUM('online','card_to_card') NOT NULL DEFAULT 'card_to_card',
    receipt_path    VARCHAR(255) DEFAULT NULL,
    status          ENUM('pending_payment','pending_review','approved','rejected','delivered') NOT NULL DEFAULT 'pending_payment',
    ip_address      VARCHAR(45) DEFAULT NULL,
    user_agent      VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_orders_number (order_number),
    KEY idx_orders_status (status),
    KEY idx_orders_mobile (mobile),
    KEY idx_orders_tool (tool_id),
    KEY idx_orders_plan (plan_id),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_tool FOREIGN KEY (tool_id) REFERENCES tools (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_plan FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0006_create_order_status_history_table.sql ----------
CREATE TABLE IF NOT EXISTS order_status_history (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED NOT NULL,
    from_status   VARCHAR(30) DEFAULT NULL,
    to_status     VARCHAR(30) NOT NULL,
    changed_by    INT UNSIGNED DEFAULT NULL,
    note          VARCHAR(500) DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_order (order_id),
    CONSTRAINT fk_history_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0007_create_receipts_table.sql ----------
CREATE TABLE IF NOT EXISTS receipts (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED DEFAULT NULL,
    file_path     VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    mime_type     VARCHAR(80) DEFAULT NULL,
    size_bytes    INT UNSIGNED NOT NULL DEFAULT 0,
    token         VARCHAR(64) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_receipts_token (token),
    KEY idx_receipts_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0008_create_admins_table.sql ----------
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

-- ---------- 0009_create_admin_notes_table.sql ----------
CREATE TABLE IF NOT EXISTS admin_notes (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED NOT NULL,
    admin_id      INT UNSIGNED DEFAULT NULL,
    admin_name    VARCHAR(120) DEFAULT NULL,
    note          TEXT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notes_order (order_id),
    CONSTRAINT fk_notes_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0010_create_settings_table.sql ----------
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(120) NOT NULL,
    `value`       TEXT DEFAULT NULL,
    `group`       VARCHAR(60) NOT NULL DEFAULT 'general',
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_settings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0011_create_telegram_logs_table.sql ----------
CREATE TABLE IF NOT EXISTS telegram_logs (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED DEFAULT NULL,
    chat_id       VARCHAR(60) DEFAULT NULL,
    message       TEXT DEFAULT NULL,
    status        ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    response      TEXT DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_telegram_order (order_id),
    KEY idx_telegram_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 0012_create_sms_logs_table.sql ----------
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

-- ---------- 0013_add_color_to_tools.sql ----------
ALTER TABLE tools
    ADD COLUMN color VARCHAR(32) NOT NULL DEFAULT '#0076FA' AFTER logo;

SET FOREIGN_KEY_CHECKS=1;
