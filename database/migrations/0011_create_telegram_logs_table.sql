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
