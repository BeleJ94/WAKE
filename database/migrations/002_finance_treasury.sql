USE wake_business_suite;

CREATE TABLE IF NOT EXISTS expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY expense_categories_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treasury_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    responsible_user_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('Caisse', 'Banque', 'Mobile Money', 'Autre') NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    KEY treasury_accounts_responsible_user_index (responsible_user_id),
    CONSTRAINT treasury_accounts_responsible_user_fk
        FOREIGN KEY (responsible_user_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fund_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requested_by INT UNSIGNED NOT NULL,
    treasury_account_id INT UNSIGNED NULL,
    reference VARCHAR(50) NOT NULL,
    title VARCHAR(180) NOT NULL,
    department VARCHAR(120) NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('Draft', 'Pending', 'Approved', 'Rejected', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Draft',
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    needed_at DATE NULL,
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    rejected_reason VARCHAR(255) NULL,
    paid_by INT UNSIGNED NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY fund_requests_reference_unique (reference),
    KEY fund_requests_status_index (status),
    CONSTRAINT fund_requests_requested_by_fk
        FOREIGN KEY (requested_by) REFERENCES users (id)
        ON DELETE RESTRICT,
    CONSTRAINT fund_requests_account_fk
        FOREIGN KEY (treasury_account_id) REFERENCES treasury_accounts (id)
        ON DELETE SET NULL,
    CONSTRAINT fund_requests_approved_by_fk
        FOREIGN KEY (approved_by) REFERENCES users (id)
        ON DELETE SET NULL,
    CONSTRAINT fund_requests_paid_by_fk
        FOREIGN KEY (paid_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fund_request_approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fund_request_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    action ENUM('Approved', 'Rejected', 'Cancelled', 'Submitted') NOT NULL,
    comment VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fund_request_approvals_request_fk
        FOREIGN KEY (fund_request_id) REFERENCES fund_requests (id)
        ON DELETE CASCADE,
    CONSTRAINT fund_request_approvals_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treasury_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    treasury_account_id INT UNSIGNED NOT NULL,
    fund_request_id INT UNSIGNED NULL,
    reference VARCHAR(60) NOT NULL,
    movement_type ENUM('inflow', 'outflow') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY treasury_movements_reference_unique (reference),
    KEY treasury_movements_account_index (treasury_account_id),
    CONSTRAINT treasury_movements_account_fk
        FOREIGN KEY (treasury_account_id) REFERENCES treasury_accounts (id)
        ON DELETE RESTRICT,
    CONSTRAINT treasury_movements_request_fk
        FOREIGN KEY (fund_request_id) REFERENCES fund_requests (id)
        ON DELETE SET NULL,
    CONSTRAINT treasury_movements_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_proofs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fund_request_id INT UNSIGNED NOT NULL,
    treasury_movement_id BIGINT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT payment_proofs_request_fk
        FOREIGN KEY (fund_request_id) REFERENCES fund_requests (id)
        ON DELETE CASCADE,
    CONSTRAINT payment_proofs_movement_fk
        FOREIGN KEY (treasury_movement_id) REFERENCES treasury_movements (id)
        ON DELETE SET NULL,
    CONSTRAINT payment_proofs_uploaded_by_fk
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Demandes de fonds', 'fund_requests.create', 'Créer une demande de fonds', NOW()),
('Demandes de fonds', 'fund_requests.pay', 'Effectuer le paiement d’une demande', NOW()),
('Caisses & Banques', 'cashbanks.create', 'Créer un compte de trésorerie', NOW()),
('Caisses & Banques', 'treasury_movements.view', 'Voir les mouvements de trésorerie', NOW()),
('Rapports', 'finance.reports', 'Voir les rapports finance', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'fund_requests.view', 'fund_requests.create', 'fund_requests.approve',
    'cashbanks.view', 'treasury_movements.view', 'finance.reports'
)
WHERE roles.slug = 'direction';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'fund_requests.view', 'fund_requests.create', 'cashbanks.view', 'cashbanks.create',
    'cashbanks.manage', 'fund_requests.pay', 'treasury_movements.view', 'finance.reports'
)
WHERE roles.slug IN ('finance', 'responsable-caisse-banque');

INSERT IGNORE INTO expense_categories (name, code, description, is_active, created_at) VALUES
('Matériaux construction', 'CONSTRUCTION_MATERIALS', 'Ciment, fer, agrégats et consommables chantier.', 1, NOW()),
('Transport & Logistique', 'TRANSPORT_LOGISTICS', 'Carburant, location véhicule, manutention et livraison.', 1, NOW()),
('Main d’oeuvre', 'LABOR', 'Paiement équipes, journaliers et techniciens.', 1, NOW()),
('Frais administratifs', 'ADMIN_FEES', 'Frais de bureau, communication et services.', 1, NOW()),
('Equipements & pièces', 'EQUIPMENT_PARTS', 'Matériel, équipements et pièces de rechange.', 1, NOW());

INSERT INTO treasury_accounts (responsible_user_id, name, type, currency, opening_balance, current_balance, status, notes, created_at)
SELECT users.id, 'Caisse Direction', 'Caisse', 'USD', 18450.00, 18450.00, 'active', 'Compte caisse principal.', NOW()
FROM users
WHERE users.email = 'admin@wake-services.local'
  AND NOT EXISTS (SELECT 1 FROM treasury_accounts WHERE name = 'Caisse Direction');

INSERT INTO treasury_accounts (responsible_user_id, name, type, currency, opening_balance, current_balance, status, notes, created_at)
SELECT users.id, 'Banque USD Principal', 'Banque', 'USD', 246800.00, 246800.00, 'active', 'Compte bancaire opérationnel.', NOW()
FROM users
WHERE users.email = 'admin@wake-services.local'
  AND NOT EXISTS (SELECT 1 FROM treasury_accounts WHERE name = 'Banque USD Principal');
