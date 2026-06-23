<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

$dsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('USE wake_business_suite');

$pdo->exec(
    "ALTER TABLE invoices
     MODIFY status ENUM('Draft', 'Issued', 'Sent', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Sent'"
);
$pdo->exec("UPDATE invoices SET status = 'Sent' WHERE status = 'Issued'");
$pdo->exec(
    "ALTER TABLE invoices
     MODIFY status ENUM('Draft', 'Sent', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Sent'"
);

addColumnIfMissing($pdo, 'invoices', 'client_name_snapshot', 'ALTER TABLE invoices ADD COLUMN client_name_snapshot VARCHAR(180) NULL AFTER client_id');
addColumnIfMissing($pdo, 'invoices', 'client_address_snapshot', 'ALTER TABLE invoices ADD COLUMN client_address_snapshot VARCHAR(255) NULL AFTER client_name_snapshot');
addColumnIfMissing($pdo, 'invoices', 'source_type', "ALTER TABLE invoices ADD COLUMN source_type ENUM('manual', 'sales_order', 'placement_contract', 'construction_project', 'other_service') NOT NULL DEFAULT 'manual' AFTER sales_order_id");
addColumnIfMissing($pdo, 'invoices', 'source_id', 'ALTER TABLE invoices ADD COLUMN source_id BIGINT UNSIGNED NULL AFTER source_type');
addColumnIfMissing($pdo, 'invoices', 'payment_terms', 'ALTER TABLE invoices ADD COLUMN payment_terms VARCHAR(255) NULL AFTER notes');
addColumnIfMissing($pdo, 'invoices', 'sent_at', 'ALTER TABLE invoices ADD COLUMN sent_at DATETIME NULL AFTER payment_terms');

$pdo->exec(
    "UPDATE invoices
     INNER JOIN clients ON clients.id = invoices.client_id
     SET invoices.client_name_snapshot = COALESCE(invoices.client_name_snapshot, clients.name),
         invoices.client_address_snapshot = COALESCE(invoices.client_address_snapshot, clients.address),
         invoices.source_type = CASE WHEN invoices.sales_order_id IS NOT NULL THEN 'sales_order' ELSE invoices.source_type END,
         invoices.source_id = CASE WHEN invoices.sales_order_id IS NOT NULL THEN invoices.sales_order_id ELSE invoices.source_id END,
         invoices.payment_terms = COALESCE(invoices.payment_terms, 'Paiement à 15 jours sauf accord contractuel contraire.')"
);

$pdo->exec(
    "INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
     ('Facturation', 'invoices.create', 'Créer des factures manuelles', NOW()),
     ('Facturation', 'invoices.payment', 'Enregistrer les paiements facture', NOW()),
     ('Facturation', 'invoices.print', 'Imprimer ou exporter les factures', NOW())"
);

$pdo->exec(
    "INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
     SELECT roles.id, permissions.id, NOW()
     FROM roles CROSS JOIN permissions
     WHERE roles.slug = 'super-admin'"
);

$pdo->exec(
    "INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
     SELECT roles.id, permissions.id, NOW()
     FROM roles
     INNER JOIN permissions ON permissions.name IN (
         'invoices.view', 'sales_invoices.view', 'invoices.create', 'invoices.payment', 'invoices.print', 'finance.reports'
     )
     WHERE roles.slug IN ('direction', 'finance', 'commercial')"
);

echo "Module Facturation centralisée installé.\n";

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $ddl): void
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $statement->execute(['table' => $table, 'column' => $column]);
    if ((int) $statement->fetchColumn() === 0) {
        $pdo->exec($ddl);
    }
}
