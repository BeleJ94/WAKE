USE wake_business_suite;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    type VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(255) NOT NULL,
    link_url VARCHAR(255) NULL,
    severity ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    unique_hash CHAR(64) NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    KEY notifications_user_read_index (user_id, read_at),
    KEY notifications_type_index (type),
    UNIQUE KEY notifications_unique_hash_unique (unique_hash),
    CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Notifications', 'notifications.view', 'Voir les notifications internes', NOW()),
('Notifications', 'notifications.manage', 'Marquer les notifications comme lues', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN ('notifications.view', 'notifications.manage')
WHERE roles.slug IN ('direction', 'finance', 'responsable-caisse-banque', 'chef-de-projet', 'rh-placement', 'commercial', 'logistique');
