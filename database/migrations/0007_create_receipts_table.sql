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
