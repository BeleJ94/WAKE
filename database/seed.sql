-- WAKE Business Suite - Realistic demo data for WAKE SERVICES / RDC
-- Execute after schema.sql:
-- mysql -h127.0.0.1 -uroot wake_business_suite < database/seed.sql

USE wake_business_suite;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM notifications;
DELETE FROM payments;
DELETE FROM invoice_items;
DELETE FROM invoices;
DELETE FROM delivery_items;
DELETE FROM deliveries;
DELETE FROM sales_order_items;
DELETE FROM sales_orders;
DELETE FROM quotation_items;
DELETE FROM quotations;
DELETE FROM stock_movements;
DELETE FROM products;
DELETE FROM product_categories;
DELETE FROM clients;
DELETE FROM placement_invoice_items;
DELETE FROM placement_invoices;
DELETE FROM placement_attendances;
DELETE FROM placement_contract_employees;
DELETE FROM placement_contracts;
DELETE FROM employees;
DELETE FROM construction_project_photos;
DELETE FROM construction_project_expenses;
DELETE FROM construction_daily_consumptions;
DELETE FROM construction_daily_progress;
DELETE FROM construction_daily_reports;
DELETE FROM construction_project_materials;
DELETE FROM construction_materials;
DELETE FROM construction_project_tasks;
DELETE FROM construction_projects;
DELETE FROM payment_proofs;
DELETE FROM treasury_movements;
DELETE FROM fund_request_approvals;
DELETE FROM fund_requests;
DELETE FROM treasury_accounts;
DELETE FROM expense_categories;
DELETE FROM audit_logs;
DELETE FROM user_sessions;
DELETE FROM users;
DELETE FROM role_permissions;
DELETE FROM roles;

ALTER TABLE roles AUTO_INCREMENT = 1;
ALTER TABLE role_permissions AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE user_sessions AUTO_INCREMENT = 1;
ALTER TABLE audit_logs AUTO_INCREMENT = 1;
ALTER TABLE expense_categories AUTO_INCREMENT = 1;
ALTER TABLE clients AUTO_INCREMENT = 1;
ALTER TABLE treasury_accounts AUTO_INCREMENT = 1;
ALTER TABLE fund_request_approvals AUTO_INCREMENT = 1;
ALTER TABLE fund_requests AUTO_INCREMENT = 1;
ALTER TABLE treasury_movements AUTO_INCREMENT = 1;
ALTER TABLE payment_proofs AUTO_INCREMENT = 1;
ALTER TABLE construction_projects AUTO_INCREMENT = 1;
ALTER TABLE construction_project_tasks AUTO_INCREMENT = 1;
ALTER TABLE construction_materials AUTO_INCREMENT = 1;
ALTER TABLE construction_project_materials AUTO_INCREMENT = 1;
ALTER TABLE construction_daily_reports AUTO_INCREMENT = 1;
ALTER TABLE construction_daily_progress AUTO_INCREMENT = 1;
ALTER TABLE construction_daily_consumptions AUTO_INCREMENT = 1;
ALTER TABLE construction_project_expenses AUTO_INCREMENT = 1;
ALTER TABLE construction_project_photos AUTO_INCREMENT = 1;
ALTER TABLE employees AUTO_INCREMENT = 1;
ALTER TABLE placement_contracts AUTO_INCREMENT = 1;
ALTER TABLE placement_contract_employees AUTO_INCREMENT = 1;
ALTER TABLE placement_attendances AUTO_INCREMENT = 1;
ALTER TABLE placement_invoices AUTO_INCREMENT = 1;
ALTER TABLE placement_invoice_items AUTO_INCREMENT = 1;
ALTER TABLE product_categories AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE stock_movements AUTO_INCREMENT = 1;
ALTER TABLE quotations AUTO_INCREMENT = 1;
ALTER TABLE quotation_items AUTO_INCREMENT = 1;
ALTER TABLE sales_orders AUTO_INCREMENT = 1;
ALTER TABLE sales_order_items AUTO_INCREMENT = 1;
ALTER TABLE deliveries AUTO_INCREMENT = 1;
ALTER TABLE delivery_items AUTO_INCREMENT = 1;
ALTER TABLE invoices AUTO_INCREMENT = 1;
ALTER TABLE invoice_items AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;

-- Keep foreign key checks disabled during the full demo load because records
-- use stable IDs and are grouped by business domain for readability.

START TRANSACTION;

INSERT INTO roles (id, name, slug, description, is_active, created_at, updated_at) VALUES
(1, 'Super Admin', 'super-admin', 'Accès complet à WAKE Business Suite.', 1, NOW(), NOW()),
(2, 'Direction', 'direction', 'Pilotage stratégique, arbitrage et validation.', 1, NOW(), NOW()),
(3, 'Finance', 'finance', 'Contrôle financier, facturation et reporting.', 1, NOW(), NOW()),
(4, 'Responsable Caisse/Banque', 'responsable-caisse-banque', 'Gestion des comptes de trésorerie.', 1, NOW(), NOW()),
(5, 'Chef de Projet', 'chef-de-projet', 'Pilotage chantier et suivi des travaux.', 1, NOW(), NOW()),
(6, 'RH Placement', 'rh-placement', 'Gestion du personnel placé.', 1, NOW(), NOW()),
(7, 'Commercial', 'commercial', 'Clients, devis, commandes et ventes.', 1, NOW(), NOW()),
(8, 'Logistique', 'logistique', 'Livraisons, stock et préparation.', 1, NOW(), NOW());

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT 1, id, NOW() FROM permissions;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN (
    'dashboard.view','finance.view','fund_requests.view','fund_requests.approve','cashbanks.view',
    'projects.view','sites.view','placement.view','clients.view','orders.view','deliveries.view',
    'invoices.view','sales_invoices.view','reports.view','notifications.view','notifications.manage','audit_logs.view'
)
WHERE roles.id = 2;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN (
    'dashboard.view','finance.view','finance.manage','fund_requests.view','cashbanks.view',
    'treasury_movements.view','invoices.view','invoices.manage','sales_invoices.view',
    'invoices.create','invoices.payment','reports.view','finance.reports','notifications.view','notifications.manage'
)
WHERE roles.id = 3;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN ('dashboard.view','cashbanks.view','cashbanks.manage','fund_requests.view','fund_requests.pay','treasury_movements.view','notifications.view','notifications.manage')
WHERE roles.id = 4;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN ('dashboard.view','projects.view','projects.create','projects.edit','sites.view','sites.reports.view','sites.reports.create','construction.reports','fund_requests.view','fund_requests.create','notifications.view','notifications.manage')
WHERE roles.id = 5;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN ('dashboard.view','placement.view','placement.manage','placement.employees.view','placement.employees.create','placement.contracts.view','placement.contracts.create','placement.attendance.manage','placement.invoices.manage','placement.reports','clients.view','notifications.view','notifications.manage')
WHERE roles.id = 6;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN ('dashboard.view','clients.view','clients.create','products.view','products.create','quotations.view','quotations.create','quotations.validate','sales_orders.view','orders.view','orders.manage','invoices.view','sales_invoices.view','notifications.view','notifications.manage')
WHERE roles.id = 7;
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
JOIN permissions ON permissions.name IN ('dashboard.view','orders.view','sales_orders.view','deliveries.view','deliveries.create','deliveries.manage','products.view','notifications.view','notifications.manage')
WHERE roles.id = 8;

INSERT INTO users (id, role_id, name, email, password, status, last_login_at, created_at, updated_at) VALUES
(1, 1, 'Administrateur WAKE', 'admin@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-16 08:30:00', NOW(), NOW()),
(2, 2, 'Rachel Kalenga', 'direction@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-15 18:05:00', NOW(), NOW()),
(3, 3, 'Marc Ilunga', 'finance@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-16 07:45:00', NOW(), NOW()),
(4, 4, 'Aline Mbuyi', 'caisse@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-14 16:10:00', NOW(), NOW()),
(5, 5, 'Jean-Pierre Kabongo', 'projets@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-16 06:55:00', NOW(), NOW()),
(6, 6, 'Nadine Tshibanda', 'rh@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-13 11:20:00', NOW(), NOW()),
(7, 7, 'Patrick Mwamba', 'commercial@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-15 09:00:00', NOW(), NOW()),
(8, 8, 'Grace Ndaya', 'logistique@wake-services.local', '$2y$10$Bp73P2b8FesuGU57OuEeCO4.VQXdN.RcrWx4jT2indM498SOwA9fm', 'active', '2026-06-16 12:40:00', NOW(), NOW());

INSERT INTO expense_categories (id, name, code, description, is_active, created_at, updated_at) VALUES
(1, 'Matériaux construction', 'CONSTRUCTION_MATERIALS', 'Achats ciment, fer, sable et consommables chantier.', 1, NOW(), NOW()),
(2, 'Transport & Logistique', 'TRANSPORT_LOGISTICS', 'Carburant, manutention et courses opérationnelles.', 1, NOW(), NOW()),
(3, 'Main d’oeuvre', 'LABOR', 'Paiement équipes terrain et journaliers.', 1, NOW(), NOW()),
(4, 'Frais administratifs', 'ADMIN_FEES', 'Bureau, communication, internet et services.', 1, NOW(), NOW()),
(5, 'Equipements & pièces', 'EQUIPMENT_PARTS', 'Matériel, outillage, pièces et maintenance.', 1, NOW(), NOW());

INSERT INTO treasury_accounts (id, responsible_user_id, name, type, currency, opening_balance, current_balance, status, notes, created_at, updated_at) VALUES
(1, 4, 'Caisse Direction USD', 'Caisse', 'USD', 18500.00, 14280.00, 'active', 'Caisse opérationnelle Lubumbashi.', NOW(), NOW()),
(2, 3, 'Banque Rawbank USD', 'Banque', 'USD', 245000.00, 219450.00, 'active', 'Compte principal WAKE SERVICES.', NOW(), NOW()),
(3, 4, 'Mobile Money CDF', 'Mobile Money', 'CDF', 18000000.00, 14550000.00, 'active', 'Paiements terrain et petites dépenses.', NOW(), NOW()),
(4, 3, 'Banque Equity CDF', 'Banque', 'CDF', 95000000.00, 87200000.00, 'active', 'Compte fournisseurs locaux.', NOW(), NOW()),
(5, 4, 'Caisse Chantier USD', 'Caisse', 'USD', 9600.00, 6850.00, 'active', 'Avances chantier et transport.', NOW(), NOW());

INSERT INTO clients (id, client_code, name, contact_name, phone, email, address, tax_number, status, notes, created_at, updated_at) VALUES
(1, 'CLT-0001', 'Kivu Real Estate SARL', 'Eric Bahati', '+243 812 400 101', 'contact@kivurealestate.cd', 'Quartier Golf, Lubumbashi', 'A2201456K', 'active', 'Promoteur immobilier.', NOW(), NOW()),
(2, 'CLT-0002', 'Mining Partner SARL', 'Rachel Mutombo', '+243 820 222 100', 'operations@miningpartner.cd', 'Route Likasi, Lubumbashi', 'A2100982M', 'active', 'Client placement et fournitures.', NOW(), NOW()),
(3, 'CLT-0003', 'BuildCo RDC', 'Paul Kabasele', '+243 815 900 211', 'achats@buildco-rdc.com', 'Kampemba, Lubumbashi', 'A2203345B', 'active', 'BTP et sous-traitance.', NOW(), NOW()),
(4, 'CLT-0004', 'Katanga Logistics SA', 'Sarah Kyungu', '+243 821 330 330', 's.kyungu@katlog.cd', 'Zone industrielle, Lubumbashi', 'A2007781L', 'active', 'Logistique minière.', NOW(), NOW()),
(5, 'CLT-0005', 'Congo Agro Industries', 'Daniel Kazadi', '+243 970 450 600', 'finance@congoagro.cd', 'Kasumbalesa', 'A2301198C', 'active', 'Agro-industrie.', NOW(), NOW()),
(6, 'CLT-0006', 'Lualaba Mining Services', 'Clarisse Ngoie', '+243 999 221 447', 'procurement@lms.cd', 'Kolwezi', 'A1905512L', 'active', 'Services miniers.', NOW(), NOW()),
(7, 'CLT-0007', 'Horizon Schools RDC', 'Brice Kalonji', '+243 810 772 222', 'admin@horizonschools.cd', 'Bel-Air, Lubumbashi', 'A2308832H', 'active', 'Client construction.', NOW(), NOW()),
(8, 'CLT-0008', 'City Mall Lubumbashi', 'Diane Kapinga', '+243 821 444 119', 'facility@citymall.cd', 'Avenue Kasa-Vubu', 'A2206674C', 'active', 'Maintenance et fournitures.', NOW(), NOW()),
(9, 'CLT-0009', 'Grand Karavia Resort', 'Hugues Mukendi', '+243 999 888 310', 'maintenance@karavia.cd', 'Lac Kipopo', 'A1804419G', 'active', 'Hôtellerie.', NOW(), NOW()),
(10, 'CLT-0010', 'SNEL Sous-station Sud', 'Jean Ilunga', '+243 840 101 120', 'sud@snel.cd', 'Commune Annexe', 'A1709901S', 'active', 'Infrastructure énergie.', NOW(), NOW());

INSERT INTO fund_requests (id, requested_by, treasury_account_id, reference, title, department, purpose, status, total_amount, currency, needed_at, approved_by, approved_at, rejected_reason, paid_by, paid_at, created_at, updated_at) VALUES
(1, 5, 5, 'DF-2026-0001', 'Achat ciment chantier Golf', 'Construction', 'Réapprovisionnement ciment pour coulage dalle.', 'Paid', 4200.00, 'USD', '2026-06-03', 2, '2026-06-02 10:10:00', NULL, 4, '2026-06-03 09:00:00', '2026-06-01 08:20:00', NOW()),
(2, 8, 3, 'DF-2026-0002', 'Carburant livraisons Kolwezi', 'Logistique', 'Carburant camions et péages.', 'Paid', 6800000.00, 'CDF', '2026-06-04', 2, '2026-06-03 11:30:00', NULL, 4, '2026-06-04 08:30:00', '2026-06-02 15:10:00', NOW()),
(3, 6, 1, 'DF-2026-0003', 'Avances agents placés', 'RH Placement', 'Avances terrain agents sécurité.', 'Approved', 2350.00, 'USD', '2026-06-18', 2, '2026-06-12 10:25:00', NULL, NULL, NULL, '2026-06-11 09:00:00', NOW()),
(4, 7, NULL, 'DF-2026-0004', 'Déplacement commercial Kinshasa', 'Commercial', 'Prospection clients grands comptes.', 'Pending', 1800.00, 'USD', '2026-06-22', NULL, NULL, NULL, NULL, NULL, '2026-06-14 10:10:00', NOW()),
(5, 5, NULL, 'DF-2026-0005', 'Location compacteur', 'Construction', 'Compacteur chantier école Horizon.', 'Rejected', 3200.00, 'USD', '2026-06-15', 2, '2026-06-13 16:00:00', 'Renégocier le tarif fournisseur.', NULL, NULL, '2026-06-12 14:00:00', NOW()),
(6, 3, 2, 'DF-2026-0006', 'Paiement licences logiciel', 'Administration', 'Renouvellement outils comptables et emails.', 'Paid', 1250.00, 'USD', '2026-06-06', 2, '2026-06-05 10:30:00', NULL, 3, '2026-06-06 12:00:00', '2026-06-04 08:00:00', NOW()),
(7, 8, NULL, 'DF-2026-0007', 'Manutention livraison City Mall', 'Logistique', 'Main d’oeuvre et emballage livraison.', 'Pending', 1450000.00, 'CDF', '2026-06-19', NULL, NULL, NULL, NULL, NULL, '2026-06-15 12:20:00', NOW()),
(8, 5, 5, 'DF-2026-0008', 'Paiement journaliers fondation', 'Construction', 'Equipe journalière chantier fondation.', 'Paid', 2900.00, 'USD', '2026-06-09', 2, '2026-06-08 09:00:00', NULL, 4, '2026-06-09 17:00:00', '2026-06-07 07:30:00', NOW()),
(9, 6, NULL, 'DF-2026-0009', 'Formation agents sécurité', 'RH Placement', 'Briefing HSE et équipement agents.', 'Draft', 850.00, 'USD', '2026-06-25', NULL, NULL, NULL, NULL, NULL, '2026-06-15 09:30:00', NOW()),
(10, 3, 4, 'DF-2026-0010', 'Fournitures bureau CDF', 'Administration', 'Papeterie, internet et consommables.', 'Paid', 2350000.00, 'CDF', '2026-06-10', 2, '2026-06-09 09:20:00', NULL, 3, '2026-06-10 09:40:00', '2026-06-08 11:40:00', NOW()),
(11, 5, 2, 'DF-2026-0011', 'Fer à béton chantier Karavia', 'Construction', 'Achat complémentaire fer à béton.', 'Approved', 7600.00, 'USD', '2026-06-20', 2, '2026-06-16 08:40:00', NULL, NULL, NULL, '2026-06-15 10:00:00', NOW()),
(12, 8, 1, 'DF-2026-0012', 'Réparation pickup logistique', 'Logistique', 'Remplacement pneus et entretien.', 'Paid', 980.00, 'USD', '2026-06-11', 2, '2026-06-10 15:15:00', NULL, 4, '2026-06-11 10:00:00', '2026-06-09 12:10:00', NOW()),
(13, 7, NULL, 'DF-2026-0013', 'Echantillons matériel client', 'Commercial', 'Démonstration équipements client minier.', 'Pending', 2100.00, 'USD', '2026-06-21', NULL, NULL, NULL, NULL, NULL, '2026-06-16 13:45:00', NOW()),
(14, 5, 2, 'DF-2026-0014', 'Achat outillage chantier SNEL', 'Construction', 'Outillage électrique et sécurité.', 'Approved', 5400.00, 'USD', '2026-06-24', 2, '2026-06-16 17:20:00', NULL, NULL, NULL, '2026-06-16 09:50:00', NOW()),
(15, 6, NULL, 'DF-2026-0015', 'Uniformes agents placés', 'RH Placement', 'Tenues pour nouveaux agents affectés.', 'Pending', 3700.00, 'USD', '2026-06-23', NULL, NULL, NULL, NULL, NULL, '2026-06-16 15:30:00', NOW());

INSERT INTO fund_request_approvals (fund_request_id, user_id, action, comment, created_at) VALUES
(1, 2, 'Approved', 'Prioritaire chantier.', '2026-06-02 10:10:00'),
(2, 2, 'Approved', 'Livraisons critiques.', '2026-06-03 11:30:00'),
(3, 2, 'Approved', 'Paiement avant affectation.', '2026-06-12 10:25:00'),
(5, 2, 'Rejected', 'Renégocier le tarif fournisseur.', '2026-06-13 16:00:00'),
(6, 2, 'Approved', 'OK finance.', '2026-06-05 10:30:00'),
(8, 2, 'Approved', 'Equipe bétonnage validée.', '2026-06-08 09:00:00'),
(10, 2, 'Approved', 'Fournitures mensuelles.', '2026-06-09 09:20:00'),
(11, 2, 'Approved', 'Approvisionnement à accélérer.', '2026-06-16 08:40:00'),
(12, 2, 'Approved', 'Véhicule indispensable.', '2026-06-10 15:15:00'),
(14, 2, 'Approved', 'Outillage validé.', '2026-06-16 17:20:00');

INSERT INTO treasury_movements (treasury_account_id, fund_request_id, reference, movement_type, amount, balance_before, balance_after, description, created_by, created_at) VALUES
(5, 1, 'TRM-20260603-001', 'outflow', 4200.00, 11050.00, 6850.00, 'Paiement ciment chantier Golf', 4, '2026-06-03 09:00:00'),
(3, 2, 'TRM-20260604-001', 'outflow', 6800000.00, 21350000.00, 14550000.00, 'Carburant livraisons Kolwezi', 4, '2026-06-04 08:30:00'),
(2, 6, 'TRM-20260606-001', 'outflow', 1250.00, 220700.00, 219450.00, 'Licences logiciel', 3, '2026-06-06 12:00:00'),
(5, 8, 'TRM-20260609-001', 'outflow', 2900.00, 9750.00, 6850.00, 'Paiement journaliers chantier', 4, '2026-06-09 17:00:00'),
(4, 10, 'TRM-20260610-001', 'outflow', 2350000.00, 89550000.00, 87200000.00, 'Fournitures bureau', 3, '2026-06-10 09:40:00'),
(1, 12, 'TRM-20260611-001', 'outflow', 980.00, 15260.00, 14280.00, 'Réparation pickup logistique', 4, '2026-06-11 10:00:00');

COMMIT;

START TRANSACTION;

INSERT INTO invoices (id, client_id, client_name_snapshot, client_address_snapshot, sales_order_id, source_type, source_id, reference, invoice_date, due_date, status, subtotal, tax_amount, total_amount, paid_amount, estimated_margin, notes, payment_terms, sent_at, created_by, created_at, updated_at) VALUES
(1,2,'Mining Partner SARL','Route Likasi, Lubumbashi',1,'sales_order',1,'FAC-2026-0001','2026-06-03','2026-06-18','Paid',8500,0,8500,8500,2300,'Facture EPI site minier.','Paiement à 15 jours.', '2026-06-03 09:00:00',7,NOW(),NOW()),
(2,3,'BuildCo RDC','Kampemba, Lubumbashi',2,'sales_order',2,'FAC-2026-0002','2026-06-05','2026-06-20','Partially Paid',12300,0,12300,4300,3300,'Facture partielle matériaux entrepôt.','Paiement à 15 jours.', '2026-06-05 10:00:00',7,NOW(),NOW()),
(3,8,'City Mall Lubumbashi','Avenue Kasa-Vubu',3,'sales_order',3,'FAC-2026-0003','2026-05-20','2026-06-04','Overdue',6400,0,6400,600,1850,'Maintenance sécurité et incendie.','Paiement à 15 jours.', '2026-05-20 09:00:00',7,NOW(),NOW()),
(4,6,'Lualaba Mining Services','Kolwezi',4,'sales_order',4,'FAC-2026-0004','2026-06-08','2026-06-23','Paid',18400,0,18400,18400,5100,'Equipements industriels.','Paiement à 15 jours.', '2026-06-08 08:30:00',7,NOW(),NOW()),
(5,9,'Grand Karavia Resort','Lac Kipopo',5,'sales_order',5,'FAC-2026-0005','2026-06-09','2026-06-24','Partially Paid',3100,0,3100,1500,900,'Acompte matériel hôtel.','Paiement à 15 jours.', '2026-06-09 14:00:00',7,NOW(),NOW()),
(6,10,'SNEL Sous-station Sud','Commune Annexe',6,'sales_order',6,'FAC-2026-0006','2026-06-10','2026-06-25','Partially Paid',9250,0,9250,2250,2450,'Câbles et équipements techniques.','Paiement à 15 jours.', '2026-06-10 11:20:00',7,NOW(),NOW()),
(7,5,'Congo Agro Industries','Kasumbalesa',7,'sales_order',7,'FAC-2026-0007','2026-06-11','2026-06-26','Partially Paid',15000,0,15000,7500,4100,'Fournitures et maintenance agro.','Paiement à 15 jours.', '2026-06-11 10:00:00',7,NOW(),NOW()),
(8,4,'Katanga Logistics SA','Zone industrielle, Lubumbashi',8,'sales_order',8,'FAC-2026-0008','2026-06-12','2026-06-27','Paid',4200,0,4200,4200,1200,'Outillage logistique urgent.','Paiement à 15 jours.', '2026-06-12 09:30:00',7,NOW(),NOW()),
(9,1,'Kivu Real Estate SARL','Quartier Golf, Lubumbashi',NULL,'construction_project',1,'FAC-2026-0009','2026-05-15','2026-05-30','Overdue',5600,0,5600,0,1600,'Situation travaux Résidence Kivu.','Paiement situation à 15 jours.', '2026-05-15 09:15:00',3,NOW(),NOW()),
(10,3,'BuildCo RDC','Kampemba, Lubumbashi',NULL,'construction_project',2,'FAC-2026-0010','2026-06-01','2026-06-16','Partially Paid',22200,0,22200,12200,6400,'Situation plateforme entrepôt.','Paiement situation à 15 jours.', '2026-06-01 16:00:00',3,NOW(),NOW()),
(11,7,'Horizon Schools RDC','Bel-Air, Lubumbashi',NULL,'construction_project',3,'FAC-2026-0011','2026-06-07','2026-06-22','Paid',7800,0,7800,7800,2200,'Réhabilitation salles de classe.','Paiement à 15 jours.', '2026-06-07 10:45:00',3,NOW(),NOW()),
(12,9,'Grand Karavia Resort','Lac Kipopo',NULL,'construction_project',4,'FAC-2026-0012','2026-06-13','2026-06-28','Partially Paid',3450,0,3450,1000,980,'Complément menuiserie aluminium.','Paiement à 15 jours.', '2026-06-13 15:00:00',3,NOW(),NOW()),
(13,2,'Mining Partner SARL','Route Likasi, Lubumbashi',NULL,'placement_contract',1,'FPL-2026-0013','2026-06-30','2026-07-15','Partially Paid',8200,0,8200,4100,2200,'Facturation mensuelle agents placés.','Paiement à 15 jours.', '2026-06-30 08:00:00',6,NOW(),NOW()),
(14,6,'Lualaba Mining Services','Kolwezi',NULL,'placement_contract',5,'FPL-2026-0014','2026-06-30','2026-07-15','Partially Paid',36000,0,36000,20000,9100,'Facturation techniciens et sécurité industrielle.','Paiement à 15 jours.', '2026-06-30 08:30:00',6,NOW(),NOW()),
(15,5,'Congo Agro Industries','Kasumbalesa',NULL,'other_service',NULL,'FAC-2026-0015','2026-06-16','2026-07-01','Draft',1250,0,1250,0,380,'Prestation assistance inventaire.','Paiement à 15 jours.', NULL,3,NOW(),NOW());

INSERT INTO invoice_items (invoice_id, product_id, description, quantity, unit_price, unit_cost, tax_rate, line_subtotal, line_tax, line_total, line_margin, created_at) VALUES
(1,17,'EPI et équipements sécurité',1,8500,6200,0,8500,0,8500,2300,NOW()),
(2,1,'Matériaux entrepôt BuildCo',1,12300,9000,0,12300,0,12300,3300,NOW()),
(3,40,'Maintenance sécurité City Mall',1,6400,4550,0,6400,0,6400,1850,NOW()),
(4,37,'Equipements industriels Lualaba',1,18400,13300,0,18400,0,18400,5100,NOW()),
(5,13,'Acompte matériel hôtel',1,3100,2200,0,3100,0,3100,900,NOW()),
(6,20,'Câbles et équipements SNEL',1,9250,6800,0,9250,0,9250,2450,NOW()),
(7,31,'Fournitures et maintenance Agro',1,15000,10900,0,15000,0,15000,4100,NOW()),
(8,23,'Outillage Katanga Logistics',1,4200,3000,0,4200,0,4200,1200,NOW()),
(9,NULL,'Situation travaux Résidence Kivu',1,5600,4000,0,5600,0,5600,1600,NOW()),
(10,NULL,'Situation plateforme entrepôt BuildCo',1,22200,15800,0,22200,0,22200,6400,NOW()),
(11,NULL,'Travaux réhabilitation Horizon Schools',1,7800,5600,0,7800,0,7800,2200,NOW()),
(12,NULL,'Complément menuiserie Grand Karavia',1,3450,2470,0,3450,0,3450,980,NOW()),
(13,NULL,'Agents placés Mining Partner - Juin 2026',1,8200,6000,0,8200,0,8200,2200,NOW()),
(14,NULL,'Techniciens et sécurité Lualaba - Juin 2026',1,36000,26900,0,36000,0,36000,9100,NOW()),
(15,NULL,'Assistance inventaire Congo Agro',1,1250,870,0,1250,0,1250,380,NOW());

INSERT INTO payments (id, invoice_id, reference, payment_date, amount, method, notes, created_by, created_at) VALUES
(1,1,'PAY-2026-0001','2026-06-05',5000,'Banque','Virement Rawbank.',3,NOW()),
(2,1,'PAY-2026-0002','2026-06-12',3500,'Banque','Solde facture.',3,NOW()),
(3,2,'PAY-2026-0003','2026-06-10',2500,'Banque','Acompte BuildCo.',3,NOW()),
(4,2,'PAY-2026-0004','2026-06-15',1800,'Mobile Money','Complément CDF converti.',3,NOW()),
(5,3,'PAY-2026-0005','2026-06-01',600,'Cash','Paiement partiel retard.',3,NOW()),
(6,4,'PAY-2026-0006','2026-06-12',10000,'Banque','Acompte Lualaba.',3,NOW()),
(7,4,'PAY-2026-0007','2026-06-18',8400,'Banque','Solde Lualaba.',3,NOW()),
(8,5,'PAY-2026-0008','2026-06-14',1500,'Cash','Acompte Karavia.',3,NOW()),
(9,6,'PAY-2026-0009','2026-06-16',2250,'Banque','Acompte SNEL.',3,NOW()),
(10,7,'PAY-2026-0010','2026-06-13',4000,'Banque','Paiement Congo Agro.',3,NOW()),
(11,7,'PAY-2026-0011','2026-06-17',3500,'Mobile Money','Complément Congo Agro.',3,NOW()),
(12,8,'PAY-2026-0012','2026-06-14',4200,'Banque','Paiement complet.',3,NOW()),
(13,10,'PAY-2026-0013','2026-06-06',7200,'Banque','Situation BuildCo 1.',3,NOW()),
(14,10,'PAY-2026-0014','2026-06-14',5000,'Banque','Situation BuildCo 2.',3,NOW()),
(15,11,'PAY-2026-0015','2026-06-12',7800,'Banque','Paiement Horizon.',3,NOW()),
(16,12,'PAY-2026-0016','2026-06-16',1000,'Cash','Acompte Karavia travaux.',3,NOW()),
(17,13,'PAY-2026-0017','2026-07-02',2500,'Banque','Acompte placement Mining.',3,NOW()),
(18,13,'PAY-2026-0018','2026-07-08',1600,'Mobile Money','Complément placement.',3,NOW()),
(19,14,'PAY-2026-0019','2026-07-03',12000,'Banque','Acompte Lualaba placement.',3,NOW()),
(20,14,'PAY-2026-0020','2026-07-10',8000,'Banque','Deuxième acompte Lualaba.',3,NOW());

INSERT INTO notifications (user_id, type, title, message, link_url, severity, entity_type, entity_id, unique_hash, read_at, created_at) VALUES
(NULL,'invoice_overdue','Facture en retard','FAC-2026-0003 reste partiellement impayée.','/invoices/show?id=3','danger','invoice',3,'seed_invoice_overdue_3',NULL,NOW()),
(NULL,'project_delay','Projet en retard','PRJ-2026-004 accuse un retard potentiel.','/construction/projects/show?id=4','danger','construction_project',4,'seed_project_delay_4',NULL,NOW()),
(NULL,'delivery_pending','Livraison en attente','BL-2026-0012 est encore en préparation.','/deliveries/index','warning','delivery',12,'seed_delivery_pending_12',NULL,NOW()),
(NULL,'placement_contract_expiring','Contrat placement bientôt expiré','PLC-2026-003 expire bientôt.','/placement/contracts/show?id=3','warning','placement_contract',3,'seed_placement_expiring_3',NULL,NOW());

COMMIT;

-- Expected demo counts:
-- roles 8, users 8, clients 10, treasury_accounts 5, fund_requests 15,
-- construction_projects 5, construction_project_tasks 30, construction_materials 40,
-- construction_daily_reports 20, employees 25, placement_contracts 6,
-- products 50, quotations 10, sales_orders 8, deliveries 12,
-- invoices 15, payments 20.

START TRANSACTION;

INSERT INTO product_categories (id, name, description, is_active, created_at, updated_at) VALUES
(1,'Matériaux construction','Matériaux et consommables chantier.',1,NOW(),NOW()),
(2,'Équipements sécurité','EPI, sécurité et signalisation.',1,NOW(),NOW()),
(3,'Outillage','Outillage manuel et électrique.',1,NOW(),NOW()),
(4,'Pièces & maintenance','Pièces de rechange et maintenance.',1,NOW(),NOW()),
(5,'Équipements industriels','Matériel et équipements pour clients miniers.',1,NOW(),NOW());

INSERT INTO products (id, product_category_id, sku, name, unit, cost_price, sale_price, stock_quantity, reorder_level, status, created_at, updated_at) VALUES
(1,1,'MAT-CIM-001','Ciment CPJ 42.5','sac',10.50,13.00,1200,250,'active',NOW(),NOW()),
(2,1,'MAT-FER-008','Fer à béton HA8','kg',1.25,1.65,8500,1200,'active',NOW(),NOW()),
(3,1,'MAT-FER-010','Fer à béton HA10','kg',1.35,1.78,7600,1000,'active',NOW(),NOW()),
(4,1,'MAT-FER-012','Fer à béton HA12','kg',1.50,1.95,6800,1000,'active',NOW(),NOW()),
(5,1,'MAT-FER-016','Fer à béton HA16','kg',1.65,2.15,5400,800,'active',NOW(),NOW()),
(6,1,'MAT-SAB-001','Sable fin','m3',19.00,26.00,260,40,'active',NOW(),NOW()),
(7,1,'MAT-GRA-001','Gravier 5/15','m3',27.00,36.00,220,35,'active',NOW(),NOW()),
(8,1,'MAT-BRI-001','Briques cuites','u',0.20,0.32,45000,8000,'active',NOW(),NOW()),
(9,1,'MAT-BLO-015','Bloc béton 15','u',0.55,0.85,18000,3000,'active',NOW(),NOW()),
(10,1,'MAT-BLO-020','Bloc béton 20','u',0.68,1.05,12000,2500,'active',NOW(),NOW()),
(11,1,'MAT-TOLE-001','Tôle bac alu','m2',14.50,19.50,1850,250,'active',NOW(),NOW()),
(12,1,'MAT-CAR-001','Carrelage standard','m2',10.00,15.00,950,120,'active',NOW(),NOW()),
(13,1,'MAT-PEI-001','Peinture intérieure','seau',35.00,45.00,210,35,'active',NOW(),NOW()),
(14,1,'MAT-PEI-002','Peinture extérieure','seau',40.00,52.00,160,30,'active',NOW(),NOW()),
(15,1,'MAT-PVC-110','Tube PVC 110','m',4.10,6.00,900,150,'active',NOW(),NOW()),
(16,1,'MAT-PVC-050','Tube PVC 50','m',2.30,3.70,1100,150,'active',NOW(),NOW()),
(17,2,'SEC-CAS-001','Casque chantier','u',5.50,9.00,380,80,'active',NOW(),NOW()),
(18,2,'SEC-GIL-001','Gilet réfléchissant','u',4.00,7.00,460,100,'active',NOW(),NOW()),
(19,2,'SEC-BOT-001','Bottes sécurité','paire',16.00,25.00,240,60,'active',NOW(),NOW()),
(20,2,'SEC-GAN-001','Gants manutention','paire',2.20,4.00,900,150,'active',NOW(),NOW()),
(21,2,'SEC-LUN-001','Lunettes protection','u',3.10,5.50,320,80,'active',NOW(),NOW()),
(22,2,'SEC-RUB-001','Ruban signalisation','rouleau',4.80,8.00,180,40,'active',NOW(),NOW()),
(23,3,'OUT-PER-001','Perceuse béton','u',75.00,112.00,34,8,'active',NOW(),NOW()),
(24,3,'OUT-MEU-001','Meuleuse 230mm','u',68.00,99.00,28,8,'active',NOW(),NOW()),
(25,3,'OUT-BET-001','Bétonnière 350L','u',620.00,790.00,6,2,'active',NOW(),NOW()),
(26,3,'OUT-NIV-001','Niveau laser','u',180.00,245.00,12,3,'active',NOW(),NOW()),
(27,3,'OUT-ECH-001','Echelle aluminium 6m','u',95.00,135.00,18,4,'active',NOW(),NOW()),
(28,3,'OUT-PEL-001','Pelle chantier','u',7.00,12.00,150,30,'active',NOW(),NOW()),
(29,3,'OUT-BRO-001','Brouette renforcée','u',38.00,58.00,65,12,'active',NOW(),NOW()),
(30,3,'OUT-GRO-001','Groupe électrogène 5kVA','u',520.00,720.00,9,2,'active',NOW(),NOW()),
(31,4,'PCE-FIL-001','Filtre huile pickup','u',12.00,19.00,75,20,'active',NOW(),NOW()),
(32,4,'PCE-PNE-001','Pneu pickup 265/70R16','u',105.00,145.00,48,10,'active',NOW(),NOW()),
(33,4,'PCE-BAT-001','Batterie 100Ah','u',72.00,105.00,36,8,'active',NOW(),NOW()),
(34,4,'PCE-HUI-001','Huile moteur 15W40','bidon',18.00,27.00,110,25,'active',NOW(),NOW()),
(35,4,'PCE-ROU-001','Roulement industriel','u',28.00,43.00,80,15,'active',NOW(),NOW()),
(36,4,'PCE-COU-001','Courroie industrielle','u',22.00,35.00,70,15,'active',NOW(),NOW()),
(37,5,'EQP-POM-001','Pompe submersible','u',260.00,360.00,14,3,'active',NOW(),NOW()),
(38,5,'EQP-COM-001','Compresseur chantier','u',480.00,650.00,8,2,'active',NOW(),NOW()),
(39,5,'EQP-SOU-001','Poste à souder 300A','u',310.00,430.00,10,2,'active',NOW(),NOW()),
(40,5,'EQP-CAM-001','Caméra surveillance IP','u',42.00,75.00,65,15,'active',NOW(),NOW()),
(41,5,'EQP-OND-001','Onduleur 2kVA','u',150.00,225.00,20,5,'active',NOW(),NOW()),
(42,5,'EQP-PAN-001','Panneau solaire 450W','u',135.00,195.00,60,12,'active',NOW(),NOW()),
(43,5,'EQP-INV-001','Onduleur solaire 5kVA','u',650.00,890.00,8,2,'active',NOW(),NOW()),
(44,5,'EQP-BAT-002','Batterie solaire 200Ah','u',220.00,310.00,22,5,'active',NOW(),NOW()),
(45,2,'SEC-EXT-001','Extincteur CO2 5kg','u',38.00,62.00,55,10,'active',NOW(),NOW()),
(46,2,'SEC-TRS-001','Trousse premiers soins','u',18.00,32.00,75,15,'active',NOW(),NOW()),
(47,3,'OUT-MAR-001','Marteau piqueur','u',520.00,710.00,5,1,'active',NOW(),NOW()),
(48,3,'OUT-CAR-001','Carotteuse béton','u',680.00,920.00,4,1,'active',NOW(),NOW()),
(49,4,'PCE-GEN-001','Régulateur groupe électrogène','u',85.00,135.00,24,6,'active',NOW(),NOW()),
(50,5,'EQP-RES-001','Réservoir plastique 5000L','u',420.00,580.00,11,3,'active',NOW(),NOW());

INSERT INTO quotations (id, client_id, reference, quote_date, valid_until, status, subtotal, tax_amount, total_amount, estimated_margin, notes, created_by, created_at, updated_at) VALUES
(1,2,'DEV-2026-0001','2026-05-28','2026-06-27','Converted',9800,0,9800,2620,'Equipements sécurité site.',7,NOW(),NOW()),
(2,3,'DEV-2026-0002','2026-05-30','2026-06-29','Converted',15400,0,15400,4100,'Matériaux entrepôt.',7,NOW(),NOW()),
(3,8,'DEV-2026-0003','2026-06-01','2026-07-01','Converted',6200,0,6200,1850,'Maintenance City Mall.',7,NOW(),NOW()),
(4,6,'DEV-2026-0004','2026-06-03','2026-07-03','Converted',21800,0,21800,5900,'Pompes et pièces.',7,NOW(),NOW()),
(5,9,'DEV-2026-0005','2026-06-04','2026-07-04','Converted',7400,0,7400,2100,'Matériel hôtel.',7,NOW(),NOW()),
(6,10,'DEV-2026-0006','2026-06-06','2026-07-06','Converted',12600,0,12600,3300,'Local technique.',7,NOW(),NOW()),
(7,5,'DEV-2026-0007','2026-06-08','2026-07-08','Converted',4800,0,4800,1280,'Consommables agro.',7,NOW(),NOW()),
(8,4,'DEV-2026-0008','2026-06-09','2026-07-09','Converted',18900,0,18900,5200,'Outillage logistique.',7,NOW(),NOW()),
(9,1,'DEV-2026-0009','2026-06-11','2026-07-11','Validated',34200,0,34200,9100,'Lot matériaux projet.',7,NOW(),NOW()),
(10,7,'DEV-2026-0010','2026-06-12','2026-07-12','Draft',5600,0,5600,1500,'Devis complément école.',7,NOW(),NOW());

INSERT INTO quotation_items (quotation_id, product_id, description, quantity, unit_price, unit_cost, tax_rate, line_subtotal, line_tax, line_total, line_margin, created_at) VALUES
(1,17,'Casques et EPI site Mining Partner',300,9,5.5,0,2700,0,2700,1050,NOW()),(1,19,'Bottes sécurité',120,25,16,0,3000,0,3000,1080,NOW()),(1,45,'Extincteurs CO2',50,62,38,0,3100,0,3100,1200,NOW()),(1,46,'Trousses premiers soins',31.25,32,18,0,1000,0,1000,437.50,NOW()),
(2,1,'Ciment CPJ',500,13,10.5,0,6500,0,6500,1250,NOW()),(2,4,'Fer HA12',3000,1.95,1.5,0,5850,0,5850,1350,NOW()),(2,11,'Tôle bac alu',156.41,19.5,14.5,0,3050,0,3050,782.05,NOW()),
(3,40,'Caméras IP',40,75,42,0,3000,0,3000,1320,NOW()),(3,45,'Extincteurs',30,62,38,0,1860,0,1860,720,NOW()),(3,46,'Trousses secours',41.875,32,18,0,1340,0,1340,586.25,NOW()),
(4,37,'Pompes submersibles',25,360,260,0,9000,0,9000,2500,NOW()),(4,35,'Roulements industriels',120,43,28,0,5160,0,5160,1800,NOW()),(4,36,'Courroies industrielles',80,35,22,0,2800,0,2800,1040,NOW()),(4,33,'Batteries 100Ah',46.095,105,72,0,4840,0,4840,1521.15,NOW()),
(5,13,'Peinture intérieure',80,45,35,0,3600,0,3600,800,NOW()),(5,14,'Peinture extérieure',50,52,40,0,2600,0,2600,600,NOW()),(5,26,'Niveau laser',4.898,245,180,0,1200,0,1200,318.37,NOW()),
(6,20,'Câbles et consommables électriques',4200,0.95,0.62,0,3990,0,3990,1386,NOW()),(6,22,'Disjoncteurs',220,7.5,5.1,0,1650,0,1650,528,NOW()),(6,41,'Onduleurs 2kVA',20,225,150,0,4500,0,4500,1500,NOW()),(6,49,'Régulateurs groupe',18.22,135,85,0,2460,0,2460,911,NOW()),
(7,31,'Filtres huile pickup',120,19,12,0,2280,0,2280,840,NOW()),(7,34,'Huile moteur 15W40',80,27,18,0,2160,0,2160,720,NOW()),(7,20,'Gants manutention',90,4,2.2,0,360,0,360,162,NOW()),
(8,23,'Perceuses béton',30,112,75,0,3360,0,3360,1110,NOW()),(8,24,'Meuleuses 230mm',35,99,68,0,3465,0,3465,1085,NOW()),(8,30,'Groupes électrogènes',12,720,520,0,8640,0,8640,2400,NOW()),(8,32,'Pneus pickup',23.69,145,105,0,3435,0,3435,947.60,NOW()),
(9,1,'Ciment gros volume',1200,13,10.5,0,15600,0,15600,3000,NOW()),(9,4,'Fer HA12 projet',6000,1.95,1.5,0,11700,0,11700,2700,NOW()),(9,12,'Carrelage sol',460,15,10,0,6900,0,6900,2300,NOW()),
(10,17,'Casques chantier école',150,9,5.5,0,1350,0,1350,525,NOW()),(10,18,'Gilets réfléchissants',250,7,4,0,1750,0,1750,750,NOW()),(10,20,'Gants manutention',625,4,2.2,0,2500,0,2500,1125,NOW());

INSERT INTO sales_orders (id, client_id, quotation_id, reference, order_date, status, subtotal, tax_amount, total_amount, estimated_margin, notes, created_by, created_at, updated_at) VALUES
(1,2,1,'CMD-2026-0001','2026-06-01','Delivered',9800,0,9800,2620,'Commande EPI Mining Partner.',7,NOW(),NOW()),
(2,3,2,'CMD-2026-0002','2026-06-02','Partially Delivered',15400,0,15400,4100,'Matériaux BuildCo.',7,NOW(),NOW()),
(3,8,3,'CMD-2026-0003','2026-06-04','Delivered',6200,0,6200,1850,'Maintenance City Mall.',7,NOW(),NOW()),
(4,6,4,'CMD-2026-0004','2026-06-05','Invoiced',21800,0,21800,5900,'Equipements miniers.',7,NOW(),NOW()),
(5,9,5,'CMD-2026-0005','2026-06-06','Open',7400,0,7400,2100,'Matériel hôtel.',7,NOW(),NOW()),
(6,10,6,'CMD-2026-0006','2026-06-08','Partially Delivered',12600,0,12600,3300,'Local technique SNEL.',7,NOW(),NOW()),
(7,5,7,'CMD-2026-0007','2026-06-10','Delivered',4800,0,4800,1280,'Consommables agro.',7,NOW(),NOW()),
(8,4,8,'CMD-2026-0008','2026-06-11','Open',18900,0,18900,5200,'Outillage logistique.',7,NOW(),NOW());

INSERT INTO sales_order_items (id, sales_order_id, product_id, description, quantity, delivered_quantity, unit_price, unit_cost, tax_rate, line_subtotal, line_tax, line_total, line_margin, created_at)
SELECT id, quotation_id, product_id, description, quantity,
CASE WHEN quotation_id IN (1,3,7) THEN quantity WHEN quotation_id IN (2,6) THEN ROUND(quantity*0.55,2) ELSE 0 END,
unit_price, unit_cost, tax_rate, line_subtotal, line_tax, line_total, line_margin, NOW()
FROM quotation_items
WHERE quotation_id BETWEEN 1 AND 8;

INSERT INTO deliveries (id, sales_order_id, reference, delivery_date, status, notes, created_by, created_at, updated_at) VALUES
(1,1,'BL-2026-0001','2026-06-03','Delivered','Livraison complète EPI.',8,NOW(),NOW()),
(2,2,'BL-2026-0002','2026-06-04','Partial','Première livraison ciment.',8,NOW(),NOW()),
(3,2,'BL-2026-0003','2026-06-08','Partial','Fer HA12 partiel.',8,NOW(),NOW()),
(4,3,'BL-2026-0004','2026-06-06','Delivered','Livraison City Mall.',8,NOW(),NOW()),
(5,4,'BL-2026-0005','2026-06-10','Prepared','Préparation pompes.',8,NOW(),NOW()),
(6,5,'BL-2026-0006','2026-06-12','Prepared','En attente paiement acompte.',8,NOW(),NOW()),
(7,6,'BL-2026-0007','2026-06-13','Partial','Câbles livrés.',8,NOW(),NOW()),
(8,6,'BL-2026-0008','2026-06-15','Prepared','Onduleurs en attente.',8,NOW(),NOW()),
(9,7,'BL-2026-0009','2026-06-12','Delivered','Consommables Agro.',8,NOW(),NOW()),
(10,8,'BL-2026-0010','2026-06-16','Prepared','Outillage en préparation.',8,NOW(),NOW()),
(11,4,'BL-2026-0011','2026-06-16','Partial','Roulements et courroies livrés.',8,NOW(),NOW()),
(12,8,'BL-2026-0012','2026-06-17','Prepared','Groupes électrogènes à expédier.',8,NOW(),NOW());

INSERT INTO delivery_items (delivery_id, sales_order_item_id, product_id, quantity, created_at) VALUES
(1,1,17,300,NOW()),(1,2,19,120,NOW()),(1,3,45,50,NOW()),(1,4,46,31.25,NOW()),
(2,5,1,275,NOW()),(3,6,4,1650,NOW()),(3,7,11,86.03,NOW()),
(4,8,40,40,NOW()),(4,9,45,30,NOW()),(4,10,46,41.875,NOW()),
(7,18,20,2310,NOW()),(7,19,22,121,NOW()),(9,22,31,120,NOW()),(9,23,34,80,NOW()),(9,24,20,90,NOW()),
(11,12,35,60,NOW()),(11,13,36,40,NOW());

COMMIT;

-- Expected demo counts:
-- roles 8, users 8, clients 10, treasury_accounts 5, fund_requests 15,
-- construction_projects 5, construction_project_tasks 30, construction_materials 40,
-- construction_daily_reports 20, employees 25, placement_contracts 6,
-- products 50, quotations 10, sales_orders 8, deliveries 12,
-- invoices 15, payments 20.

START TRANSACTION;

INSERT INTO employees (id, employee_code, first_name, last_name, phone, email, job_title, base_salary, status, hired_at, notes, created_at, updated_at) VALUES
(1,'EMP-0001','Jean','Mukendi','+243 810 000 001','jean.mukendi@wake.local','Agent sécurité',420,'active','2026-01-15','Affecté site minier.',NOW(),NOW()),
(2,'EMP-0002','Grace','Kabongo','+243 810 000 002','grace.kabongo@wake.local','Assistante administrative',520,'active','2026-02-01','Placement bureau.',NOW(),NOW()),
(3,'EMP-0003','Patrick','Ilunga','+243 810 000 003','patrick.ilunga@wake.local','Technicien maintenance',680,'active','2026-02-20','Maintenance industrielle.',NOW(),NOW()),
(4,'EMP-0004','Mireille','Mbuyi','+243 810 000 004','mireille.mbuyi@wake.local','Réceptionniste',390,'active','2026-03-01','Front office.',NOW(),NOW()),
(5,'EMP-0005','Alain','Kasongo','+243 810 000 005','alain.kasongo@wake.local','Chauffeur',460,'active','2026-01-10','Permis CE.',NOW(),NOW()),
(6,'EMP-0006','Chantal','Tshibanda','+243 810 000 006','chantal.tshibanda@wake.local','Agent entretien',310,'active','2026-03-12','Site hôtelier.',NOW(),NOW()),
(7,'EMP-0007','Dieudonné','Kanku','+243 810 000 007','dieudonne.kanku@wake.local','Agent sécurité',430,'active','2026-02-08','Poste nuit.',NOW(),NOW()),
(8,'EMP-0008','Esther','Kalume','+243 810 000 008','esther.kalume@wake.local','Assistante RH',540,'active','2026-04-02','Support RH client.',NOW(),NOW()),
(9,'EMP-0009','Moïse','Kitenge','+243 810 000 009','moise.kitenge@wake.local','Magasinier',510,'active','2026-01-22','Gestion stock.',NOW(),NOW()),
(10,'EMP-0010','Béatrice','Lukusa','+243 810 000 010','beatrice.lukusa@wake.local','Caissière',450,'active','2026-03-18','Caisse client.',NOW(),NOW()),
(11,'EMP-0011','Arnaud','Mwamba','+243 810 000 011','arnaud.mwamba@wake.local','Opérateur engin',760,'active','2026-01-18','Engins légers.',NOW(),NOW()),
(12,'EMP-0012','Noella','Kabeya','+243 810 000 012','noella.kabeya@wake.local','Agent nettoyage',300,'active','2026-02-25','Equipe nettoyage.',NOW(),NOW()),
(13,'EMP-0013','Fabrice','Mulumba','+243 810 000 013','fabrice.mulumba@wake.local','Electricien',700,'active','2026-04-10','Maintenance bâtiment.',NOW(),NOW()),
(14,'EMP-0014','Sarah','Kabasele','+243 810 000 014','sarah.kabasele@wake.local','Secrétaire',480,'active','2026-01-28','Administration client.',NOW(),NOW()),
(15,'EMP-0015','Didier','Banza','+243 810 000 015','didier.banza@wake.local','Agent sécurité',425,'active','2026-03-05','Contrôle accès.',NOW(),NOW()),
(16,'EMP-0016','Ange','Ngoy','+243 810 000 016','ange.ngoy@wake.local','Manutentionnaire',360,'active','2026-02-14','Entrepôt.',NOW(),NOW()),
(17,'EMP-0017','Kevin','Mutombo','+243 810 000 017','kevin.mutombo@wake.local','Technicien IT',720,'active','2026-04-16','Support utilisateur.',NOW(),NOW()),
(18,'EMP-0018','Prisca','Kalonji','+243 810 000 018','prisca.kalonji@wake.local','Assistante comptable',560,'active','2026-03-22','Finance client.',NOW(),NOW()),
(19,'EMP-0019','Joël','Mpoyi','+243 810 000 019','joel.mpoyi@wake.local','Agent sécurité',415,'active','2026-01-19','Ronde site.',NOW(),NOW()),
(20,'EMP-0020','Lydia','Mbala','+243 810 000 020','lydia.mbala@wake.local','Agent accueil',380,'active','2026-05-02','Accueil visiteurs.',NOW(),NOW()),
(21,'EMP-0021','Christian','Tshomba','+243 810 000 021','christian.tshomba@wake.local','Soudeur',690,'active','2026-03-03','Atelier client.',NOW(),NOW()),
(22,'EMP-0022','Olive','Monga','+243 810 000 022','olive.monga@wake.local','Agent entretien',305,'active','2026-04-08','Entretien bureaux.',NOW(),NOW()),
(23,'EMP-0023','Héritier','Kabongo','+243 810 000 023','heritier.kabongo@wake.local','Chauffeur',470,'active','2026-02-19','Navette personnel.',NOW(),NOW()),
(24,'EMP-0024','Dorah','Ilunga','+243 810 000 024','dorah.ilunga@wake.local','Secrétaire',500,'active','2026-05-05','Direction client.',NOW(),NOW()),
(25,'EMP-0025','Samy','Mbuyi','+243 810 000 025','samy.mbuyi@wake.local','Agent sécurité',435,'active','2026-01-30','Site industriel.',NOW(),NOW());

INSERT INTO placement_contracts (id, reference, client_name, client_contact, client_phone, start_date, end_date, status, billing_day, notes, created_by, created_at, updated_at) VALUES
(1,'PLC-2026-001','Mining Partner SARL','Rachel Mutombo','+243 820 222 100','2026-06-01','2026-12-31','Active',30,'Agents sécurité et admin.',6,NOW(),NOW()),
(2,'PLC-2026-002','Katanga Logistics SA','Sarah Kyungu','+243 821 330 330','2026-05-15','2026-11-15','Active',30,'Chauffeurs et manutention.',6,NOW(),NOW()),
(3,'PLC-2026-003','City Mall Lubumbashi','Diane Kapinga','+243 821 444 119','2026-04-01','2026-06-30','Active',30,'Accueil et entretien.',6,NOW(),NOW()),
(4,'PLC-2026-004','Grand Karavia Resort','Hugues Mukendi','+243 999 888 310','2026-03-01','2026-09-30','Active',30,'Entretien et réception.',6,NOW(),NOW()),
(5,'PLC-2026-005','Lualaba Mining Services','Clarisse Ngoie','+243 999 221 447','2026-06-10','2027-06-09','Active',30,'Techniciens et sécurité.',6,NOW(),NOW()),
(6,'PLC-2026-006','Congo Agro Industries','Daniel Kazadi','+243 970 450 600','2026-02-01','2026-07-05','Active',30,'Support administratif.',6,NOW(),NOW());

INSERT INTO placement_contract_employees (placement_contract_id, employee_id, position_title, agent_cost, client_rate, margin_amount, start_date, end_date, status, created_at, updated_at) VALUES
(1,1,'Agent sécurité',420,590,170,'2026-06-01','2026-12-31','active',NOW(),NOW()),(1,2,'Assistante administrative',520,740,220,'2026-06-01','2026-12-31','active',NOW(),NOW()),(1,7,'Agent sécurité nuit',430,610,180,'2026-06-01','2026-12-31','active',NOW(),NOW()),(1,15,'Agent sécurité',425,600,175,'2026-06-01','2026-12-31','active',NOW(),NOW()),(1,19,'Agent sécurité ronde',415,585,170,'2026-06-01','2026-12-31','active',NOW(),NOW()),
(2,5,'Chauffeur',460,660,200,'2026-05-15','2026-11-15','active',NOW(),NOW()),(2,9,'Magasinier',510,720,210,'2026-05-15','2026-11-15','active',NOW(),NOW()),(2,16,'Manutentionnaire',360,510,150,'2026-05-15','2026-11-15','active',NOW(),NOW()),(2,23,'Chauffeur navette',470,675,205,'2026-05-15','2026-11-15','active',NOW(),NOW()),
(3,4,'Réceptionniste',390,550,160,'2026-04-01','2026-06-30','active',NOW(),NOW()),(3,6,'Agent entretien',310,450,140,'2026-04-01','2026-06-30','active',NOW(),NOW()),(3,10,'Caissière',450,640,190,'2026-04-01','2026-06-30','active',NOW(),NOW()),(3,12,'Agent nettoyage',300,440,140,'2026-04-01','2026-06-30','active',NOW(),NOW()),
(4,20,'Agent accueil',380,540,160,'2026-03-01','2026-09-30','active',NOW(),NOW()),(4,22,'Agent entretien',305,445,140,'2026-03-01','2026-09-30','active',NOW(),NOW()),(4,24,'Secrétaire direction',500,710,210,'2026-03-01','2026-09-30','active',NOW(),NOW()),
(5,3,'Technicien maintenance',680,950,270,'2026-06-10','2027-06-09','active',NOW(),NOW()),(5,11,'Opérateur engin',760,1060,300,'2026-06-10','2027-06-09','active',NOW(),NOW()),(5,13,'Electricien',700,980,280,'2026-06-10','2027-06-09','active',NOW(),NOW()),(5,17,'Technicien IT',720,1010,290,'2026-06-10','2027-06-09','active',NOW(),NOW()),(5,21,'Soudeur',690,970,280,'2026-06-10','2027-06-09','active',NOW(),NOW()),(5,25,'Agent sécurité industriel',435,620,185,'2026-06-10','2027-06-09','active',NOW(),NOW()),
(6,8,'Assistante RH',540,760,220,'2026-02-01','2026-07-05','active',NOW(),NOW()),(6,14,'Secrétaire',480,680,200,'2026-02-01','2026-07-05','active',NOW(),NOW()),(6,18,'Assistante comptable',560,790,230,'2026-02-01','2026-07-05','active',NOW(),NOW());

INSERT INTO placement_attendances (placement_contract_employee_id, attendance_month, days_present, days_absent, overtime_hours, notes, created_by, created_at, updated_at)
SELECT id, '2026-06', 24, 2, CASE WHEN id % 3 = 0 THEN 8 ELSE 0 END, 'Présence mensuelle de démonstration.', 6, NOW(), NOW()
FROM placement_contract_employees;

INSERT INTO placement_invoices (id, placement_contract_id, reference, invoice_month, invoice_date, due_date, subtotal, total_cost, margin_amount, status, created_by, created_at, updated_at) VALUES
(1,1,'PINV-202606-001','2026-06','2026-06-30','2026-07-15',2985,2210,775,'Issued',6,NOW(),NOW()),
(2,2,'PINV-202606-002','2026-06','2026-06-30','2026-07-15',2565,1800,765,'Issued',6,NOW(),NOW()),
(3,3,'PINV-202606-003','2026-06','2026-06-30','2026-07-15',2080,1450,630,'Issued',6,NOW(),NOW()),
(4,4,'PINV-202606-004','2026-06','2026-06-30','2026-07-15',1695,1185,510,'Issued',6,NOW(),NOW()),
(5,5,'PINV-202606-005','2026-06','2026-06-30','2026-07-15',5590,3985,1605,'Issued',6,NOW(),NOW()),
(6,6,'PINV-202606-006','2026-06','2026-06-30','2026-07-15',2230,1580,650,'Issued',6,NOW(),NOW());

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

INSERT INTO construction_materials (id, name, unit, unit_cost, is_active, created_at, updated_at) VALUES
(1, 'Ciment CPJ 42.5', 'sac', 12.00, 1, NOW(), NOW()),
(2, 'Fer à béton HA8', 'kg', 1.55, 1, NOW(), NOW()),
(3, 'Fer à béton HA10', 'kg', 1.65, 1, NOW(), NOW()),
(4, 'Fer à béton HA12', 'kg', 1.80, 1, NOW(), NOW()),
(5, 'Fer à béton HA16', 'kg', 1.95, 1, NOW(), NOW()),
(6, 'Sable fin', 'm3', 24.00, 1, NOW(), NOW()),
(7, 'Sable gros', 'm3', 22.00, 1, NOW(), NOW()),
(8, 'Gravier 5/15', 'm3', 32.00, 1, NOW(), NOW()),
(9, 'Gravier 15/25', 'm3', 35.00, 1, NOW(), NOW()),
(10, 'Moellons', 'm3', 28.00, 1, NOW(), NOW()),
(11, 'Briques cuites', 'u', 0.28, 1, NOW(), NOW()),
(12, 'Blocs béton 15', 'u', 0.75, 1, NOW(), NOW()),
(13, 'Blocs béton 20', 'u', 0.95, 1, NOW(), NOW()),
(14, 'Bois coffrage', 'm', 3.20, 1, NOW(), NOW()),
(15, 'Contreplaqué coffrage', 'feuille', 18.00, 1, NOW(), NOW()),
(16, 'Pointes 80mm', 'kg', 2.80, 1, NOW(), NOW()),
(17, 'Fil recuit', 'kg', 2.40, 1, NOW(), NOW()),
(18, 'Tube PVC 110', 'm', 5.50, 1, NOW(), NOW()),
(19, 'Tube PVC 50', 'm', 3.10, 1, NOW(), NOW()),
(20, 'Câble électrique 2.5mm', 'm', 0.95, 1, NOW(), NOW()),
(21, 'Câble électrique 4mm', 'm', 1.45, 1, NOW(), NOW()),
(22, 'Disjoncteur 20A', 'u', 7.50, 1, NOW(), NOW()),
(23, 'Peinture extérieure', 'seau', 48.00, 1, NOW(), NOW()),
(24, 'Peinture intérieure', 'seau', 42.00, 1, NOW(), NOW()),
(25, 'Enduit ciment', 'sac', 9.80, 1, NOW(), NOW()),
(26, 'Carrelage sol', 'm2', 14.00, 1, NOW(), NOW()),
(27, 'Colle carrelage', 'sac', 11.50, 1, NOW(), NOW()),
(28, 'Tôle bac alu', 'm2', 18.00, 1, NOW(), NOW()),
(29, 'Charpente métallique', 'kg', 2.10, 1, NOW(), NOW()),
(30, 'Gouttière PVC', 'm', 4.20, 1, NOW(), NOW()),
(31, 'Vitre claire 6mm', 'm2', 22.00, 1, NOW(), NOW()),
(32, 'Porte métallique', 'u', 145.00, 1, NOW(), NOW()),
(33, 'Fenêtre alu', 'u', 95.00, 1, NOW(), NOW()),
(34, 'Géotextile', 'm2', 1.20, 1, NOW(), NOW()),
(35, 'Grillage chantier', 'm', 6.50, 1, NOW(), NOW()),
(36, 'EPI casque', 'u', 8.00, 1, NOW(), NOW()),
(37, 'EPI gilet', 'u', 6.00, 1, NOW(), NOW()),
(38, 'Bottes sécurité', 'paire', 22.00, 1, NOW(), NOW()),
(39, 'Diesel groupe électrogène', 'litre', 1.55, 1, NOW(), NOW()),
(40, 'Eau chantier', 'm3', 4.00, 1, NOW(), NOW());

INSERT INTO construction_projects (id, project_manager_id, reference, name, client_name, contract_amount, forecast_cost, forecast_margin, start_date, end_date, location, status, notes, created_at, updated_at) VALUES
(1, 5, 'PRJ-2026-001', 'Résidence Kivu - Phase 1', 'Kivu Real Estate SARL', 185000.00, 136500.00, 48500.00, '2026-05-10', '2026-09-30', 'Lubumbashi - Golf', 'In Progress', 'Résidence de 12 appartements.', NOW(), NOW()),
(2, 5, 'PRJ-2026-002', 'Entrepôt BuildCo Kampemba', 'BuildCo RDC', 128000.00, 96500.00, 31500.00, '2026-04-20', '2026-08-15', 'Kampemba', 'In Progress', 'Entrepôt logistique.', NOW(), NOW()),
(3, 5, 'PRJ-2026-003', 'Réhabilitation Horizon Schools', 'Horizon Schools RDC', 74200.00, 53100.00, 21100.00, '2026-06-01', '2026-07-25', 'Bel-Air', 'In Progress', 'Réhabilitation salles et clôture.', NOW(), NOW()),
(4, 5, 'PRJ-2026-004', 'Extension Grand Karavia', 'Grand Karavia Resort', 212000.00, 171000.00, 41000.00, '2026-03-15', '2026-06-10', 'Lac Kipopo', 'On Hold', 'Retard suite approvisionnement importé.', NOW(), NOW()),
(5, 5, 'PRJ-2026-005', 'Local technique SNEL Sud', 'SNEL Sous-station Sud', 96500.00, 71500.00, 25000.00, '2026-06-05', '2026-09-05', 'Commune Annexe', 'Planning', 'Préparation chantier.', NOW(), NOW());

INSERT INTO construction_project_tasks (construction_project_id, name, unit, planned_quantity, planned_cost, planned_duration_days, progress_percent, sort_order, created_at, updated_at) VALUES
(1,'Installation chantier','lot',1,8500,7,100,1,NOW(),NOW()),(1,'Terrassement fondations','m3',420,18500,14,90,2,NOW(),NOW()),(1,'Béton armé fondations','m3',130,28500,18,78,3,NOW(),NOW()),(1,'Elévation murs','m2',860,42000,35,55,4,NOW(),NOW()),(1,'Charpente et toiture','m2',410,31500,21,18,5,NOW(),NOW()),(1,'Finitions intérieures','lot',1,7500,45,5,6,NOW(),NOW()),
(2,'Clôture provisoire','m',220,7200,5,100,1,NOW(),NOW()),(2,'Plateforme entrepôt','m2',1400,27000,20,72,2,NOW(),NOW()),(2,'Semelles et longrines','m3',95,24500,16,68,3,NOW(),NOW()),(2,'Structure métallique','kg',18500,23000,24,42,4,NOW(),NOW()),(2,'Couverture bac alu','m2',1320,10800,12,24,5,NOW(),NOW()),(2,'Dallage industriel','m2',1300,4000,16,0,6,NOW(),NOW()),
(3,'Démolition légère','lot',1,5200,5,100,1,NOW(),NOW()),(3,'Maçonnerie salles','m2',420,11800,12,75,2,NOW(),NOW()),(3,'Plomberie sanitaires','lot',1,7200,10,45,3,NOW(),NOW()),(3,'Electricité classes','lot',1,6400,8,40,4,NOW(),NOW()),(3,'Peinture bâtiments','m2',2100,12500,14,20,5,NOW(),NOW()),(3,'Clôture école','m',310,10000,15,18,6,NOW(),NOW()),
(4,'Fondations extension','m3',160,34000,21,100,1,NOW(),NOW()),(4,'Structure béton RDC','m3',210,52000,30,92,2,NOW(),NOW()),(4,'Murs et cloisons','m2',980,27500,25,88,3,NOW(),NOW()),(4,'Toiture terrasse','m2',520,21000,18,60,4,NOW(),NOW()),(4,'Menuiserie aluminium','u',48,18000,16,28,5,NOW(),NOW()),(4,'Finitions hôtel','lot',1,18500,40,8,6,NOW(),NOW()),
(5,'Implantation site','lot',1,4500,4,10,1,NOW(),NOW()),(5,'Fouilles local technique','m3',180,9800,10,0,2,NOW(),NOW()),(5,'Radier béton','m3',65,14800,12,0,3,NOW(),NOW()),(5,'Maçonnerie technique','m2',360,18500,18,0,4,NOW(),NOW()),(5,'Chemins câbles','m',420,12900,16,0,5,NOW(),NOW()),(5,'Finitions et réception','lot',1,11000,15,0,6,NOW(),NOW());

INSERT INTO construction_project_materials (construction_project_id, construction_material_id, planned_quantity, planned_cost, created_at, updated_at)
SELECT p.id, m.id,
CASE m.id WHEN 1 THEN 900 WHEN 4 THEN 6200 WHEN 6 THEN 160 WHEN 8 THEN 140 WHEN 11 THEN 11500 WHEN 14 THEN 900 WHEN 23 THEN 80 ELSE 250 END,
CASE m.id WHEN 1 THEN 10800 WHEN 4 THEN 11160 WHEN 6 THEN 3840 WHEN 8 THEN 4480 WHEN 11 THEN 3220 WHEN 14 THEN 2880 WHEN 23 THEN 3840 ELSE 1000 END,
NOW(), NOW()
FROM construction_projects p
JOIN construction_materials m ON m.id IN (1,4,6,8,11,14,23,39);

INSERT INTO construction_daily_reports (id, construction_project_id, report_date, weather, remarks, blockers, created_by, created_at, updated_at) VALUES
(1,1,'2026-06-01','Ensoleillé','Coulage partiel fondations bloc A.','Retard livraison gravier matin.',5,NOW(),NOW()),
(2,1,'2026-06-03','Nuageux','Ferraillage longrines et coffrage.','Aucun.',5,NOW(),NOW()),
(3,1,'2026-06-07','Ensoleillé','Elévation murs niveau 1.','Manque briques cuites.',5,NOW(),NOW()),
(4,1,'2026-06-12','Pluie légère','Travaux limités, préparation toiture.','Pluie intermittente.',5,NOW(),NOW()),
(5,2,'2026-05-25','Ensoleillé','Nivellement plateforme terminé.','Aucun.',5,NOW(),NOW()),
(6,2,'2026-05-30','Ensoleillé','Semelles axe A-B.','Groupe électrogène instable.',5,NOW(),NOW()),
(7,2,'2026-06-05','Nuageux','Début montage structure métallique.','Attente boulonnerie.',5,NOW(),NOW()),
(8,2,'2026-06-11','Ensoleillé','Pose fermes métalliques.','Aucun.',5,NOW(),NOW()),
(9,3,'2026-06-03','Ensoleillé','Démolition achevée et nettoyage.','Evacuation déchets.',5,NOW(),NOW()),
(10,3,'2026-06-06','Nuageux','Maçonnerie salles bloc B.','Aucun.',5,NOW(),NOW()),
(11,3,'2026-06-10','Ensoleillé','Plomberie sanitaires.','Raccords PVC manquants.',5,NOW(),NOW()),
(12,3,'2026-06-14','Ensoleillé','Préparation peinture intérieure.','Aucun.',5,NOW(),NOW()),
(13,4,'2026-05-18','Ensoleillé','Structure béton RDC avancée.','Aucun.',5,NOW(),NOW()),
(14,4,'2026-05-25','Pluie','Cloisons et maçonnerie.','Pluie forte après-midi.',5,NOW(),NOW()),
(15,4,'2026-06-02','Nuageux','Toiture terrasse partielle.','Approvisionnement membrane.',5,NOW(),NOW()),
(16,4,'2026-06-09','Ensoleillé','Menuiserie aluminium en attente.','Fournisseur retard.',5,NOW(),NOW()),
(17,5,'2026-06-06','Ensoleillé','Implantation et piquetage.','Validation client attendue.',5,NOW(),NOW()),
(18,5,'2026-06-08','Nuageux','Nettoyage site et stockage matériaux.','Aucun.',5,NOW(),NOW()),
(19,5,'2026-06-12','Ensoleillé','Clôture provisoire.','Aucun.',5,NOW(),NOW()),
(20,5,'2026-06-15','Ensoleillé','Préparation fouilles.','Engin disponible demain.',5,NOW(),NOW());

INSERT INTO construction_daily_progress (construction_daily_report_id, construction_project_task_id, executed_work, quantity_done, progress_percent, created_at)
SELECT r.id, t.id, CONCAT('Exécution ', t.name), ROUND(t.planned_quantity * 0.08, 2), LEAST(100, t.progress_percent + 2), NOW()
FROM construction_daily_reports r
JOIN construction_project_tasks t ON t.construction_project_id = r.construction_project_id
WHERE t.sort_order IN (2,3);

INSERT INTO construction_daily_consumptions (construction_daily_report_id, construction_material_id, quantity_used, unit_cost, total_cost, created_at) VALUES
(1,1,80,12,960,NOW()),(1,8,12,32,384,NOW()),(2,4,650,1.80,1170,NOW()),(3,11,1800,0.28,504,NOW()),(4,39,90,1.55,139.50,NOW()),
(5,6,35,24,840,NOW()),(6,1,95,12,1140,NOW()),(7,29,1250,2.10,2625,NOW()),(8,29,1480,2.10,3108,NOW()),
(9,35,80,6.50,520,NOW()),(10,12,950,0.75,712.50,NOW()),(11,18,120,5.50,660,NOW()),(12,24,36,42,1512,NOW()),
(13,1,140,12,1680,NOW()),(14,11,2200,0.28,616,NOW()),(15,25,160,9.80,1568,NOW()),(16,33,8,95,760,NOW()),
(17,35,60,6.50,390,NOW()),(18,39,55,1.55,85.25,NOW()),(19,35,95,6.50,617.50,NOW()),(20,40,18,4,72,NOW());

INSERT INTO construction_project_expenses (construction_project_id, construction_daily_report_id, expense_date, category, description, amount, created_by, created_at) VALUES
(1,1,'2026-06-01','Main d’oeuvre','Equipe bétonnage bloc A',780,5,NOW()),(1,3,'2026-06-07','Transport','Camion briques et sable',420,5,NOW()),
(2,7,'2026-06-05','Location engin','Nacelle montage structure',950,5,NOW()),(2,8,'2026-06-11','Main d’oeuvre','Equipe soudure',620,5,NOW()),
(3,10,'2026-06-06','Main d’oeuvre','Equipe maçonnerie école',520,5,NOW()),(3,11,'2026-06-10','Achat local','Raccords plomberie',310,5,NOW()),
(4,15,'2026-06-02','Transport','Transport membrane toiture',690,5,NOW()),(4,16,'2026-06-09','Main d’oeuvre','Pose menuiserie attente',430,5,NOW()),
(5,17,'2026-06-06','Implantation','Bornage et piquetage',260,5,NOW()),(5,20,'2026-06-15','Location engin','Mini pelle réservation',540,5,NOW());

COMMIT;
