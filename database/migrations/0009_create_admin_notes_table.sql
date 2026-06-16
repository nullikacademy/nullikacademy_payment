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
