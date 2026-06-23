USE wake_business_suite;

CREATE TABLE IF NOT EXISTS fund_request_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fund_request_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    KEY fund_request_attachments_request_index (fund_request_id),
    CONSTRAINT fund_request_attachments_request_fk
        FOREIGN KEY (fund_request_id) REFERENCES fund_requests (id)
        ON DELETE CASCADE,
    CONSTRAINT fund_request_attachments_uploaded_by_fk
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
