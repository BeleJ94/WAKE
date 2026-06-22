USE wake_business_suite;

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_code VARCHAR(60) NOT NULL,
    name VARCHAR(180) NOT NULL,
    contact_name VARCHAR(160) NULL,
    phone VARCHAR(80) NULL,
    email VARCHAR(160) NULL,
    address VARCHAR(255) NULL,
    tax_number VARCHAR(120) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY clients_code_unique (client_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY product_categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_category_id INT UNSIGNED NULL,
    sku VARCHAR(80) NOT NULL,
    name VARCHAR(180) NOT NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'u',
    cost_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    stock_quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    reorder_level DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY products_sku_unique (sku),
    CONSTRAINT products_category_fk
        FOREIGN KEY (product_category_id) REFERENCES product_categories (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    reference VARCHAR(80) NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    source_type VARCHAR(80) NULL,
    source_id BIGINT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY stock_movements_reference_unique (reference),
    KEY stock_movements_product_index (product_id),
    CONSTRAINT stock_movements_product_fk
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE RESTRICT,
    CONSTRAINT stock_movements_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    reference VARCHAR(80) NOT NULL,
    quote_date DATE NOT NULL,
    valid_until DATE NULL,
    status ENUM('Draft', 'Validated', 'Converted', 'Cancelled') NOT NULL DEFAULT 'Draft',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estimated_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY quotations_reference_unique (reference),
    CONSTRAINT quotations_client_fk FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE RESTRICT,
    CONSTRAINT quotations_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    line_subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT quotation_items_quotation_fk FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE CASCADE,
    CONSTRAINT quotation_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    quotation_id INT UNSIGNED NULL,
    reference VARCHAR(80) NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('Open', 'Partially Delivered', 'Delivered', 'Invoiced', 'Paid', 'Closed', 'Cancelled') NOT NULL DEFAULT 'Open',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estimated_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY sales_orders_reference_unique (reference),
    CONSTRAINT sales_orders_client_fk FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE RESTRICT,
    CONSTRAINT sales_orders_quotation_fk FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE SET NULL,
    CONSTRAINT sales_orders_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1.00,
    delivered_quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    line_subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT sales_order_items_order_fk FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE CASCADE,
    CONSTRAINT sales_order_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT UNSIGNED NOT NULL,
    reference VARCHAR(80) NOT NULL,
    delivery_date DATE NOT NULL,
    status ENUM('Prepared', 'Partial', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Prepared',
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY deliveries_reference_unique (reference),
    CONSTRAINT deliveries_order_fk FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE RESTRICT,
    CONSTRAINT deliveries_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT UNSIGNED NOT NULL,
    sales_order_item_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT delivery_items_delivery_fk FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE,
    CONSTRAINT delivery_items_order_item_fk FOREIGN KEY (sales_order_item_id) REFERENCES sales_order_items (id) ON DELETE RESTRICT,
    CONSTRAINT delivery_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    sales_order_id INT UNSIGNED NULL,
    reference VARCHAR(80) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    status ENUM('Draft', 'Issued', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Issued',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estimated_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY invoices_reference_unique (reference),
    CONSTRAINT invoices_client_fk FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE RESTRICT,
    CONSTRAINT invoices_order_fk FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE SET NULL,
    CONSTRAINT invoices_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    line_subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_margin DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    CONSTRAINT invoice_items_invoice_fk FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE,
    CONSTRAINT invoice_items_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    reference VARCHAR(80) NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    method VARCHAR(80) NOT NULL DEFAULT 'Cash',
    notes VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY payments_reference_unique (reference),
    CONSTRAINT payments_invoice_fk FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE RESTRICT,
    CONSTRAINT payments_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (module, name, label, created_at) VALUES
('Clients', 'clients.create', 'Créer des clients', NOW()),
('Clients', 'products.view', 'Voir le catalogue produits', NOW()),
('Clients', 'products.create', 'Créer des produits', NOW()),
('Ventes', 'quotations.view', 'Voir les devis', NOW()),
('Ventes', 'quotations.create', 'Créer des devis', NOW()),
('Ventes', 'quotations.validate', 'Valider et convertir les devis', NOW()),
('Commandes', 'sales_orders.view', 'Voir les commandes', NOW()),
('Livraisons', 'deliveries.create', 'Créer les livraisons', NOW()),
('Facturation', 'sales_invoices.view', 'Voir les factures commerciales', NOW()),
('Facturation', 'payments.create', 'Enregistrer les paiements', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles CROSS JOIN permissions
WHERE roles.slug = 'super-admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT roles.id, permissions.id, NOW()
FROM roles
INNER JOIN permissions ON permissions.name IN (
    'clients.view','clients.create','products.view','products.create','quotations.view','quotations.create',
    'quotations.validate','sales_orders.view','orders.view','orders.manage','deliveries.view','deliveries.create',
    'invoices.view','sales_invoices.view','payments.create','reports.view'
)
WHERE roles.slug IN ('direction', 'commercial', 'logistique', 'finance');

INSERT IGNORE INTO product_categories (name, description, is_active, created_at) VALUES
('Matériaux', 'Matériaux de construction et consommables.', 1, NOW()),
('Equipements', 'Equipements et matériels.', 1, NOW()),
('Pièces', 'Pièces de rechange.', 1, NOW());

INSERT IGNORE INTO clients (client_code, name, contact_name, phone, email, address, tax_number, status, notes, created_at, updated_at) VALUES
('CLI-0001', 'Mining Partner SARL', 'Mme Rachel', '+243 820 222 100', 'contact@mining-partner.local', 'Lubumbashi', 'RCCM-DEMO-001', 'active', 'Client démonstration.', NOW(), NOW()),
('CLI-0002', 'BuildCo RDC', 'M. Alain', '+243 820 333 200', 'sales@buildco.local', 'Kolwezi', 'RCCM-DEMO-002', 'active', 'Client démonstration.', NOW(), NOW());

INSERT IGNORE INTO products (product_category_id, sku, name, unit, cost_price, sale_price, stock_quantity, reorder_level, status, created_at, updated_at)
SELECT c.id, 'MAT-CIM-001', 'Ciment CPJ 50kg', 'sac', 9.50, 13.00, 500.00, 80.00, 'active', NOW(), NOW()
FROM product_categories c WHERE c.name = 'Matériaux';

INSERT IGNORE INTO products (product_category_id, sku, name, unit, cost_price, sale_price, stock_quantity, reorder_level, status, created_at, updated_at)
SELECT c.id, 'EQP-PMP-001', 'Pompe chantier 3 pouces', 'u', 420.00, 620.00, 12.00, 2.00, 'active', NOW(), NOW()
FROM product_categories c WHERE c.name = 'Equipements';

