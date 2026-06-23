USE wake_business_suite;

CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(60) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(60) NULL,
    email VARCHAR(160) NULL,
    job_title VARCHAR(140) NOT NULL,
    base_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    hired_at DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY employees_code_unique (employee_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(60) NOT NULL,
    client_name VARCHAR(180) NOT NULL,
    client_contact VARCHAR(160) NULL,
    client_phone VARCHAR(80) NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('Draft', 'Active', 'Suspended', 'Expired', 'Closed') NOT NULL DEFAULT 'Draft',
    billing_day TINYINT UNSIGNED NOT NULL DEFAULT 30,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY placement_contracts_reference_unique (reference),
    CONSTRAINT placement_contracts_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_contract_employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_contract_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    position_title VARCHAR(140) NOT NULL,
    agent_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    client_rate DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    margin_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'ended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    KEY placement_contract_employees_contract_index (placement_contract_id),
    CONSTRAINT placement_contract_employees_contract_fk
        FOREIGN KEY (placement_contract_id) REFERENCES placement_contracts (id)
        ON DELETE CASCADE,
    CONSTRAINT placement_contract_employees_employee_fk
        FOREIGN KEY (employee_id) REFERENCES employees (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_attendances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_contract_employee_id INT UNSIGNED NOT NULL,
    attendance_month CHAR(7) NOT NULL,
    days_present DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    days_absent DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    overtime_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY placement_attendance_unique (placement_contract_employee_id, attendance_month),
    CONSTRAINT placement_attendances_assignment_fk
        FOREIGN KEY (placement_contract_employee_id) REFERENCES placement_contract_employees (id)
        ON DELETE CASCADE,
    CONSTRAINT placement_attendances_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_contract_id INT UNSIGNED NOT NULL,
    reference VARCHAR(60) NOT NULL,
    invoice_month CHAR(7) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    margin_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('Draft', 'Issued', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Issued',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY placement_invoices_reference_unique (reference),
    UNIQUE KEY placement_invoice_contract_month_unique (placement_contract_id, invoice_month),
    CONSTRAINT placement_invoices_contract_fk
        FOREIGN KEY (placement_contract_id) REFERENCES placement_contracts (id)
        ON DELETE CASCADE,
    CONSTRAINT placement_invoices_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_invoice_id INT UNSIGNED NOT NULL,
    placement_contract_employee_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_rate DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    agent_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    margin_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT placement_invoice_items_invoice_fk
        FOREIGN KEY (placement_invoice_id) REFERENCES placement_invoices (id)
        ON DELETE CASCADE,
    CONSTRAINT placement_invoice_items_assignment_fk
        FOREIGN KEY (placement_contract_employee_id) REFERENCES placement_contract_employees (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Personnel Placé', 'placement.employees.view', 'Voir les agents', NOW()),
('Personnel Placé', 'placement.employees.create', 'Créer des agents', NOW()),
('Personnel Placé', 'placement.contracts.view', 'Voir les contrats de placement', NOW()),
('Personnel Placé', 'placement.contracts.create', 'Créer des contrats de placement', NOW()),
('Personnel Placé', 'placement.attendance.manage', 'Gérer la présence mensuelle', NOW()),
('Personnel Placé', 'placement.invoices.manage', 'Générer les factures placement', NOW()),
('Personnel Placé', 'placement.reports', 'Voir les rapports placement', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'placement.view', 'placement.manage', 'placement.employees.view', 'placement.employees.create',
    'placement.contracts.view', 'placement.contracts.create', 'placement.attendance.manage',
    'placement.invoices.manage', 'placement.reports'
)
WHERE roles.slug IN ('direction', 'rh-placement');

INSERT IGNORE INTO employees (employee_code, first_name, last_name, phone, email, job_title, base_salary, status, hired_at, notes, created_at, updated_at) VALUES
('EMP-0001', 'Jean', 'Mukendi', '+243 810 000 001', 'jean.mukendi@wake.local', 'Agent sécurité', 420.00, 'active', '2026-01-15', 'Agent démonstration.', NOW(), NOW()),
('EMP-0002', 'Grace', 'Kabongo', '+243 810 000 002', 'grace.kabongo@wake.local', 'Assistante administrative', 520.00, 'active', '2026-02-01', 'Agent démonstration.', NOW(), NOW()),
('EMP-0003', 'Patrick', 'Ilunga', '+243 810 000 003', 'patrick.ilunga@wake.local', 'Technicien maintenance', 680.00, 'active', '2026-02-20', 'Agent démonstration.', NOW(), NOW());

INSERT INTO placement_contracts (reference, client_name, client_contact, client_phone, start_date, end_date, status, billing_day, notes, created_by, created_at, updated_at)
SELECT 'PLC-2026-001', 'Mining Partner SARL', 'Mme Rachel', '+243 820 222 100', '2026-06-01', '2026-12-31', 'Active', 30, 'Contrat démo placement.', users.id, NOW(), NOW()
FROM users
WHERE users.email = 'admin@wake-services.local'
  AND NOT EXISTS (SELECT 1 FROM placement_contracts WHERE reference = 'PLC-2026-001');

INSERT INTO placement_contract_employees (placement_contract_id, employee_id, position_title, agent_cost, client_rate, margin_amount, start_date, end_date, status, created_at, updated_at)
SELECT c.id, e.id, e.job_title, e.base_salary, e.base_salary * 1.35, (e.base_salary * 1.35) - e.base_salary, c.start_date, c.end_date, 'active', NOW(), NOW()
FROM placement_contracts c
INNER JOIN employees e ON e.employee_code IN ('EMP-0001', 'EMP-0002')
WHERE c.reference = 'PLC-2026-001'
  AND NOT EXISTS (SELECT 1 FROM placement_contract_employees WHERE placement_contract_id = c.id);

