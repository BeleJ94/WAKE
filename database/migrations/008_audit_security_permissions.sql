USE wake_business_suite;

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Audit', 'audit_logs.view', 'Voir le journal d’audit', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name = 'audit_logs.view'
WHERE roles.slug IN ('super-admin', 'direction');
