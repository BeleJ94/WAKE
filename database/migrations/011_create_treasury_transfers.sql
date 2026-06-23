USE wake_business_suite;

CREATE TABLE IF NOT EXISTS treasury_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(60) NOT NULL,
    source_account_id INT UNSIGNED NOT NULL,
    destination_account_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    approved_by INT UNSIGNED NULL,
    executed_by INT UNSIGNED NULL,
    status ENUM('Draft', 'Pending', 'Approved', 'Executed', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Draft',
    source_amount DECIMAL(15,2) NOT NULL,
    source_currency VARCHAR(10) NOT NULL,
    exchange_rate DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
    destination_amount DECIMAL(15,2) NOT NULL,
    destination_currency VARCHAR(10) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    rejection_reason VARCHAR(255) NULL,
    requested_at DATETIME NULL,
    approved_at DATETIME NULL,
    executed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY treasury_transfers_reference_unique (reference),
    KEY treasury_transfers_status_index (status),
    KEY treasury_transfers_source_index (source_account_id),
    KEY treasury_transfers_destination_index (destination_account_id),
    CONSTRAINT treasury_transfers_source_fk FOREIGN KEY (source_account_id) REFERENCES treasury_accounts (id) ON DELETE RESTRICT,
    CONSTRAINT treasury_transfers_destination_fk FOREIGN KEY (destination_account_id) REFERENCES treasury_accounts (id) ON DELETE RESTRICT,
    CONSTRAINT treasury_transfers_requested_by_fk FOREIGN KEY (requested_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT treasury_transfers_approved_by_fk FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT treasury_transfers_executed_by_fk FOREIGN KEY (executed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treasury_transfer_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    treasury_transfer_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    action ENUM('Created', 'Submitted', 'Approved', 'Rejected', 'Executed', 'Cancelled') NOT NULL,
    comment VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    KEY treasury_transfer_events_transfer_index (treasury_transfer_id),
    CONSTRAINT treasury_transfer_events_transfer_fk FOREIGN KEY (treasury_transfer_id) REFERENCES treasury_transfers (id) ON DELETE CASCADE,
    CONSTRAINT treasury_transfer_events_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treasury_transfer_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    treasury_transfer_id BIGINT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT treasury_transfer_attachments_transfer_fk FOREIGN KEY (treasury_transfer_id) REFERENCES treasury_transfers (id) ON DELETE CASCADE,
    CONSTRAINT treasury_transfer_attachments_user_fk FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE treasury_movements
    ADD COLUMN IF NOT EXISTS treasury_transfer_id BIGINT UNSIGNED NULL AFTER fund_request_id,
    ADD KEY IF NOT EXISTS treasury_movements_transfer_index (treasury_transfer_id);

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Transferts de fonds', 'treasury_transfers.view', 'Voir les transferts de fonds', NOW()),
('Transferts de fonds', 'treasury_transfers.create', 'Créer et soumettre des transferts', NOW()),
('Transferts de fonds', 'treasury_transfers.approve', 'Approuver ou rejeter des transferts', NOW()),
('Transferts de fonds', 'treasury_transfers.execute', 'Exécuter les transferts approuvés', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles CROSS JOIN permissions
WHERE roles.slug = 'super-admin' AND permissions.name LIKE 'treasury_transfers.%';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles INNER JOIN permissions ON permissions.name IN ('treasury_transfers.view', 'treasury_transfers.approve')
WHERE roles.slug = 'direction';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles INNER JOIN permissions ON permissions.name IN ('treasury_transfers.view', 'treasury_transfers.create')
WHERE roles.slug = 'finance';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles INNER JOIN permissions ON permissions.name IN ('treasury_transfers.view', 'treasury_transfers.create', 'treasury_transfers.execute')
WHERE roles.slug = 'responsable-caisse-banque';
