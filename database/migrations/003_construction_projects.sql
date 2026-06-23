USE wake_business_suite;

CREATE TABLE IF NOT EXISTS construction_projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_manager_id INT UNSIGNED NULL,
    reference VARCHAR(60) NOT NULL,
    name VARCHAR(180) NOT NULL,
    client_name VARCHAR(180) NOT NULL,
    contract_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    forecast_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    forecast_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    location VARCHAR(180) NOT NULL,
    status ENUM('Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Planning',
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY construction_projects_reference_unique (reference),
    KEY construction_projects_manager_index (project_manager_id),
    CONSTRAINT construction_projects_manager_fk
        FOREIGN KEY (project_manager_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_project_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_project_id INT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    unit VARCHAR(40) NOT NULL,
    planned_quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    planned_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    planned_duration_days INT UNSIGNED NOT NULL DEFAULT 0,
    progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    CONSTRAINT construction_project_tasks_project_fk
        FOREIGN KEY (construction_project_id) REFERENCES construction_projects (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    unit VARCHAR(40) NOT NULL,
    unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY construction_materials_name_unit_unique (name, unit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_project_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_project_id INT UNSIGNED NOT NULL,
    construction_material_id INT UNSIGNED NOT NULL,
    planned_quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    planned_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    CONSTRAINT construction_project_materials_project_fk
        FOREIGN KEY (construction_project_id) REFERENCES construction_projects (id)
        ON DELETE CASCADE,
    CONSTRAINT construction_project_materials_material_fk
        FOREIGN KEY (construction_material_id) REFERENCES construction_materials (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_daily_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_project_id INT UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    weather VARCHAR(120) NULL,
    remarks TEXT NULL,
    blockers TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY construction_daily_reports_project_date_unique (construction_project_id, report_date),
    CONSTRAINT construction_daily_reports_project_fk
        FOREIGN KEY (construction_project_id) REFERENCES construction_projects (id)
        ON DELETE CASCADE,
    CONSTRAINT construction_daily_reports_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_daily_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_daily_report_id INT UNSIGNED NOT NULL,
    construction_project_task_id INT UNSIGNED NOT NULL,
    executed_work VARCHAR(255) NOT NULL,
    quantity_done DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT construction_daily_progress_report_fk
        FOREIGN KEY (construction_daily_report_id) REFERENCES construction_daily_reports (id)
        ON DELETE CASCADE,
    CONSTRAINT construction_daily_progress_task_fk
        FOREIGN KEY (construction_project_task_id) REFERENCES construction_project_tasks (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_daily_consumptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_daily_report_id INT UNSIGNED NOT NULL,
    construction_material_id INT UNSIGNED NOT NULL,
    quantity_used DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT construction_daily_consumptions_report_fk
        FOREIGN KEY (construction_daily_report_id) REFERENCES construction_daily_reports (id)
        ON DELETE CASCADE,
    CONSTRAINT construction_daily_consumptions_material_fk
        FOREIGN KEY (construction_material_id) REFERENCES construction_materials (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_project_expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_project_id INT UNSIGNED NOT NULL,
    construction_daily_report_id INT UNSIGNED NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT construction_project_expenses_project_fk
        FOREIGN KEY (construction_project_id) REFERENCES construction_projects (id)
        ON DELETE CASCADE,
    CONSTRAINT construction_project_expenses_report_fk
        FOREIGN KEY (construction_daily_report_id) REFERENCES construction_daily_reports (id)
        ON DELETE SET NULL,
    CONSTRAINT construction_project_expenses_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS construction_project_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    construction_project_id INT UNSIGNED NOT NULL,
    construction_daily_report_id INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    caption VARCHAR(180) NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT construction_project_photos_project_fk
        FOREIGN KEY (construction_project_id) REFERENCES construction_projects (id)
        ON DELETE CASCADE,
    CONSTRAINT construction_project_photos_report_fk
        FOREIGN KEY (construction_daily_report_id) REFERENCES construction_daily_reports (id)
        ON DELETE SET NULL,
    CONSTRAINT construction_project_photos_uploaded_by_fk
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Projets Construction', 'projects.create', 'Créer des projets construction', NOW()),
('Projets Construction', 'projects.edit', 'Modifier des projets construction', NOW()),
('Suivi Chantier', 'sites.reports.create', 'Créer des rapports journaliers chantier', NOW()),
('Suivi Chantier', 'sites.reports.view', 'Voir les rapports journaliers chantier', NOW()),
('Rapports', 'construction.reports', 'Voir les rapports construction', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'projects.view', 'projects.create', 'projects.edit', 'projects.manage',
    'sites.view', 'sites.manage', 'sites.reports.create', 'sites.reports.view',
    'construction.reports'
)
WHERE roles.slug IN ('direction', 'chef-de-projet');

INSERT IGNORE INTO construction_materials (name, unit, unit_cost, is_active, created_at) VALUES
('Ciment CPJ', 'sac', 12.00, 1, NOW()),
('Fer à béton', 'kg', 1.80, 1, NOW()),
('Gravier', 'm3', 32.00, 1, NOW()),
('Sable', 'm3', 24.00, 1, NOW()),
('Peinture extérieure', 'seau', 48.00, 1, NOW());

INSERT INTO construction_projects
(project_manager_id, reference, name, client_name, contract_amount, forecast_cost, forecast_margin, start_date, end_date, location, status, notes, created_at, updated_at)
SELECT users.id, 'PRJ-2026-001', 'Résidence Kivu', 'Kivu Real Estate SARL', 185000.00, 136500.00, 48500.00,
       '2026-05-10', '2026-09-30', 'Lubumbashi - Golf', 'In Progress',
       'Projet de démonstration pour le cockpit construction.', NOW(), NOW()
FROM users
WHERE users.email = 'admin@wake-services.local'
  AND NOT EXISTS (SELECT 1 FROM construction_projects WHERE reference = 'PRJ-2026-001');

INSERT INTO construction_project_tasks
(construction_project_id, name, unit, planned_quantity, planned_cost, planned_duration_days, progress_percent, sort_order, created_at, updated_at)
SELECT p.id, x.name, x.unit, x.qty, x.cost, x.days, x.progress, x.sort_order, NOW(), NOW()
FROM construction_projects p
INNER JOIN (
    SELECT 'Fondations' AS name, 'm3' AS unit, 120.00 AS qty, 28500.00 AS cost, 18 AS days, 80.00 AS progress, 1 AS sort_order
    UNION ALL SELECT 'Elévation murs', 'm2', 840.00, 42000.00, 35, 58.00, 2
    UNION ALL SELECT 'Toiture', 'm2', 410.00, 31500.00, 21, 25.00, 3
    UNION ALL SELECT 'Finitions', 'lot', 1.00, 34500.00, 45, 8.00, 4
) x
WHERE p.reference = 'PRJ-2026-001'
  AND NOT EXISTS (SELECT 1 FROM construction_project_tasks WHERE construction_project_id = p.id);

INSERT INTO construction_project_materials
(construction_project_id, construction_material_id, planned_quantity, planned_cost, created_at, updated_at)
SELECT p.id, m.id,
       CASE m.name WHEN 'Ciment CPJ' THEN 950 WHEN 'Fer à béton' THEN 6800 WHEN 'Gravier' THEN 180 WHEN 'Sable' THEN 220 ELSE 60 END,
       CASE m.name WHEN 'Ciment CPJ' THEN 11400 WHEN 'Fer à béton' THEN 12240 WHEN 'Gravier' THEN 5760 WHEN 'Sable' THEN 5280 ELSE 2880 END,
       NOW(), NOW()
FROM construction_projects p
CROSS JOIN construction_materials m
WHERE p.reference = 'PRJ-2026-001'
  AND m.name IN ('Ciment CPJ', 'Fer à béton', 'Gravier', 'Sable', 'Peinture extérieure')
  AND NOT EXISTS (SELECT 1 FROM construction_project_materials WHERE construction_project_id = p.id);

