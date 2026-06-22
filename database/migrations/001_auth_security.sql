CREATE DATABASE IF NOT EXISTS wake_business_suite
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wake_business_suite;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY roles_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    name VARCHAR(120) NOT NULL,
    label VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY permissions_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT role_permissions_role_fk
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE CASCADE,
    CONSTRAINT role_permissions_permission_fk
        FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY users_email_unique (email),
    KEY users_role_id_index (role_id),
    CONSTRAINT users_role_fk
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    last_activity_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    UNIQUE KEY user_sessions_session_unique (session_id),
    KEY user_sessions_user_id_index (user_id),
    CONSTRAINT user_sessions_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    metadata TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY audit_logs_user_id_index (user_id),
    KEY audit_logs_action_index (action),
    KEY audit_logs_entity_index (entity_type, entity_id),
    CONSTRAINT audit_logs_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (name, slug, description, is_active, created_at) VALUES
('Super Admin', 'super-admin', 'Accès complet à toute la plateforme.', 1, NOW()),
('Direction', 'direction', 'Pilotage stratégique, rapports et validations.', 1, NOW()),
('Finance', 'finance', 'Gestion financière, facturation et trésorerie.', 1, NOW()),
('Responsable Caisse/Banque', 'responsable-caisse-banque', 'Gestion des caisses, banques et mouvements.', 1, NOW()),
('Chef de Projet', 'chef-de-projet', 'Suivi des projets construction et chantiers.', 1, NOW()),
('RH Placement', 'rh-placement', 'Gestion du personnel placé.', 1, NOW()),
('Commercial', 'commercial', 'Gestion clients, ventes et commandes.', 1, NOW()),
('Logistique', 'logistique', 'Gestion livraisons et flux matériels.', 1, NOW());

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Dashboard', 'dashboard.view', 'Voir le dashboard', NOW()),
('Administration', 'users.view', 'Voir les utilisateurs', NOW()),
('Administration', 'users.create', 'Créer des utilisateurs', NOW()),
('Administration', 'users.edit', 'Modifier des utilisateurs', NOW()),
('Administration', 'roles.view', 'Voir les rôles', NOW()),
('Administration', 'roles.permissions', 'Gérer les permissions des rôles', NOW()),
('Finance & Trésorerie', 'finance.view', 'Voir finance et trésorerie', NOW()),
('Finance & Trésorerie', 'finance.manage', 'Gérer finance et trésorerie', NOW()),
('Demandes de fonds', 'fund_requests.view', 'Voir les demandes de fonds', NOW()),
('Demandes de fonds', 'fund_requests.approve', 'Approuver les demandes de fonds', NOW()),
('Caisses & Banques', 'cashbanks.view', 'Voir caisses et banques', NOW()),
('Caisses & Banques', 'cashbanks.manage', 'Gérer caisses et banques', NOW()),
('Projets Construction', 'projects.view', 'Voir les projets construction', NOW()),
('Projets Construction', 'projects.manage', 'Gérer les projets construction', NOW()),
('Suivi Chantier', 'sites.view', 'Voir le suivi chantier', NOW()),
('Suivi Chantier', 'sites.manage', 'Gérer le suivi chantier', NOW()),
('Personnel Placé', 'placement.view', 'Voir le personnel placé', NOW()),
('Personnel Placé', 'placement.manage', 'Gérer le personnel placé', NOW()),
('Clients', 'clients.view', 'Voir les clients', NOW()),
('Clients', 'clients.manage', 'Gérer les clients', NOW()),
('Commandes', 'orders.view', 'Voir les commandes', NOW()),
('Commandes', 'orders.manage', 'Gérer les commandes', NOW()),
('Livraisons', 'deliveries.view', 'Voir les livraisons', NOW()),
('Livraisons', 'deliveries.manage', 'Gérer les livraisons', NOW()),
('Facturation', 'invoices.view', 'Voir la facturation', NOW()),
('Facturation', 'invoices.manage', 'Gérer la facturation', NOW()),
('Rapports', 'reports.view', 'Voir les rapports', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'finance.view', 'fund_requests.approve', 'projects.view',
    'placement.view', 'clients.view', 'orders.view', 'deliveries.view',
    'invoices.view', 'reports.view'
)
WHERE roles.slug = 'direction';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'finance.view', 'finance.manage', 'fund_requests.view',
    'cashbanks.view', 'invoices.view', 'invoices.manage', 'reports.view'
)
WHERE roles.slug = 'finance';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'cashbanks.view', 'cashbanks.manage', 'fund_requests.view'
)
WHERE roles.slug = 'responsable-caisse-banque';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'projects.view', 'projects.manage', 'sites.view', 'sites.manage', 'fund_requests.view'
)
WHERE roles.slug = 'chef-de-projet';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'placement.view', 'placement.manage', 'clients.view'
)
WHERE roles.slug = 'rh-placement';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'clients.view', 'clients.manage', 'orders.view', 'orders.manage', 'invoices.view'
)
WHERE roles.slug = 'commercial';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'dashboard.view', 'orders.view', 'deliveries.view', 'deliveries.manage'
)
WHERE roles.slug = 'logistique';

INSERT IGNORE INTO users (role_id, name, email, password, status, created_at, updated_at)
SELECT roles.id,
       'Administrateur WAKE',
       'admin@wake-services.local',
       '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm',
       'active',
       NOW(),
       NOW()
FROM roles
WHERE roles.slug = 'super-admin';

