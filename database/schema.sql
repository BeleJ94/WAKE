-- WAKE Business Suite - Schema loader
-- Execute from the project root with:
-- mysql -h127.0.0.1 -uroot < database/schema.sql

SOURCE database/migrations/001_auth_security.sql;
SOURCE database/migrations/002_finance_treasury.sql;
SOURCE database/migrations/003_construction_projects.sql;
SOURCE database/migrations/004_placement_module.sql;
SOURCE database/migrations/005_clients_sales_orders.sql;
SOURCE database/migrations/006_unified_invoicing.sql;
SOURCE database/migrations/007_notifications.sql;
SOURCE database/migrations/008_audit_security_permissions.sql;
SOURCE database/migrations/009_remove_fund_request_items.sql;
SOURCE database/migrations/010_create_fund_request_attachments.sql;
