USE wake_business_suite;

ALTER TABLE invoices
    MODIFY status ENUM('Draft', 'Issued', 'Sent', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Sent';

UPDATE invoices
SET status = 'Sent'
WHERE status = 'Issued';

ALTER TABLE invoices
    MODIFY status ENUM('Draft', 'Sent', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Sent';

DROP PROCEDURE IF EXISTS add_invoice_column_if_missing;
DELIMITER //
CREATE PROCEDURE add_invoice_column_if_missing(IN column_name VARCHAR(64), IN ddl TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'invoices'
          AND COLUMN_NAME = column_name
    ) THEN
        SET @ddl = ddl;
        PREPARE statement FROM @ddl;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;
    END IF;
END//
DELIMITER ;

CALL add_invoice_column_if_missing('client_name_snapshot', 'ALTER TABLE invoices ADD COLUMN client_name_snapshot VARCHAR(180) NULL AFTER client_id');
CALL add_invoice_column_if_missing('client_address_snapshot', 'ALTER TABLE invoices ADD COLUMN client_address_snapshot VARCHAR(255) NULL AFTER client_name_snapshot');
CALL add_invoice_column_if_missing('source_type', 'ALTER TABLE invoices ADD COLUMN source_type ENUM(''manual'', ''sales_order'', ''placement_contract'', ''construction_project'', ''other_service'') NOT NULL DEFAULT ''manual'' AFTER sales_order_id');
CALL add_invoice_column_if_missing('source_id', 'ALTER TABLE invoices ADD COLUMN source_id BIGINT UNSIGNED NULL AFTER source_type');
CALL add_invoice_column_if_missing('payment_terms', 'ALTER TABLE invoices ADD COLUMN payment_terms VARCHAR(255) NULL AFTER notes');
CALL add_invoice_column_if_missing('sent_at', 'ALTER TABLE invoices ADD COLUMN sent_at DATETIME NULL AFTER payment_terms');
DROP PROCEDURE IF EXISTS add_invoice_column_if_missing;

UPDATE invoices
INNER JOIN clients ON clients.id = invoices.client_id
SET invoices.client_name_snapshot = COALESCE(invoices.client_name_snapshot, clients.name),
    invoices.client_address_snapshot = COALESCE(invoices.client_address_snapshot, clients.address),
    invoices.source_type = CASE WHEN invoices.sales_order_id IS NOT NULL THEN 'sales_order' ELSE invoices.source_type END,
    invoices.source_id = CASE WHEN invoices.sales_order_id IS NOT NULL THEN invoices.sales_order_id ELSE invoices.source_id END,
    invoices.payment_terms = COALESCE(invoices.payment_terms, 'Paiement à 15 jours sauf accord contractuel contraire.');

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Facturation', 'invoices.create', 'Créer des factures manuelles', NOW()),
('Facturation', 'invoices.payment', 'Enregistrer les paiements facture', NOW()),
('Facturation', 'invoices.print', 'Imprimer ou exporter les factures', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'invoices.view', 'sales_invoices.view', 'invoices.create', 'invoices.payment', 'invoices.print', 'finance.reports'
)
WHERE roles.slug IN ('direction', 'finance', 'commercial');
