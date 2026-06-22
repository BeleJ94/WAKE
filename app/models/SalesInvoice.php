<?php

declare(strict_types=1);

class SalesInvoice extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT invoices.*,
                    clients.name AS client_name,
                    clients.address,
                    sales_orders.reference AS order_reference,
                    (invoices.total_amount - invoices.paid_amount) AS remaining_amount
             FROM invoices
             INNER JOIN clients ON clients.id = invoices.client_id
             LEFT JOIN sales_orders ON sales_orders.id = invoices.sales_order_id
             ORDER BY invoices.created_at DESC'
        )->fetchAll();
    }

    public function payable(): array
    {
        return $this->db->query(
            'SELECT invoices.*,
                    COALESCE(invoices.client_name_snapshot, clients.name) AS client_name,
                    (invoices.total_amount - invoices.paid_amount) AS remaining_amount
             FROM invoices
             INNER JOIN clients ON clients.id = invoices.client_id
             WHERE invoices.status NOT IN ("Paid", "Cancelled")
               AND invoices.total_amount > invoices.paid_amount
             ORDER BY invoices.due_date ASC, invoices.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT invoices.*,
                    COALESCE(invoices.client_name_snapshot, clients.name) AS client_name,
                    COALESCE(invoices.client_address_snapshot, clients.address) AS address,
                    clients.email,
                    clients.phone,
                    sales_orders.reference AS order_reference,
                    (invoices.total_amount - invoices.paid_amount) AS remaining_amount
             FROM invoices INNER JOIN clients ON clients.id = invoices.client_id
             LEFT JOIN sales_orders ON sales_orders.id = invoices.sales_order_id
             WHERE invoices.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function items(int $invoiceId): array
    {
        $statement = $this->db->prepare('SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY id ASC');
        $statement->execute(['id' => $invoiceId]);
        return $statement->fetchAll();
    }

    public function createManual(array $data, array $items): int
    {
        $client = $this->findClient((int) $data['client_id']);
        if ($client === null) {
            throw new RuntimeException('Client introuvable.');
        }
        $totals = $this->calculateTotals($items);
        if ($totals['total'] <= 0) {
            throw new RuntimeException('La facture doit contenir au moins une ligne valide.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO invoices
                 (client_id, client_name_snapshot, client_address_snapshot, sales_order_id, source_type, source_id, reference, invoice_date, due_date, status, subtotal, tax_amount, total_amount, paid_amount, estimated_margin, notes, payment_terms, sent_at, created_by, created_at, updated_at)
                 VALUES (:client_id, :client_name, :client_address, NULL, :source_type, NULL, :reference, :invoice_date, :due_date, :status, :subtotal, :tax, :total, 0, :margin, :notes, :payment_terms, :sent_at, :created_by, NOW(), NOW())'
            );
            $status = $data['status'] === 'Draft' ? 'Draft' : 'Sent';
            $statement->execute([
                'client_id' => $client['id'],
                'client_name' => $client['name'],
                'client_address' => $client['address'],
                'source_type' => $data['source_type'],
                'reference' => $this->nextReference(),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?: null,
                'status' => $status,
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'margin' => $totals['margin'],
                'notes' => $data['notes'],
                'payment_terms' => $data['payment_terms'],
                'sent_at' => $status === 'Sent' ? date('Y-m-d H:i:s') : null,
                'created_by' => $data['created_by'],
            ]);
            $invoiceId = (int) $this->db->lastInsertId();
            $this->insertItems($invoiceId, $items);
            $this->db->commit();
            return $invoiceId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function generateFromOrder(int $orderId, int $userId): int
    {
        $orderModel = new SalesOrder();
        $order = $orderModel->find($orderId);
        if ($order === null) {
            throw new RuntimeException('Commande introuvable.');
        }
        $items = $orderModel->items($orderId);
        $this->db->beginTransaction();
        try {
            $client = $this->findClient((int) $order['client_id']);
            $statement = $this->db->prepare(
                'INSERT INTO invoices
                 (client_id, client_name_snapshot, client_address_snapshot, sales_order_id, source_type, source_id, reference, invoice_date, due_date, status, subtotal, tax_amount, total_amount, paid_amount, estimated_margin, notes, payment_terms, sent_at, created_by, created_at, updated_at)
                 VALUES (:client_id, :client_name, :client_address, :order_id, "sales_order", :order_id_source, :reference, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY), "Sent", :subtotal, :tax, :total, 0, :margin, :notes, :payment_terms, NOW(), :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'client_id' => $order['client_id'],
                'client_name' => $client['name'] ?? $order['client_name'],
                'client_address' => $client['address'] ?? null,
                'order_id' => $orderId,
                'order_id_source' => $orderId,
                'reference' => 'FAC-' . date('Ymd-His') . '-' . random_int(100, 999),
                'subtotal' => $order['subtotal'],
                'tax' => $order['tax_amount'],
                'total' => $order['total_amount'],
                'margin' => $order['estimated_margin'],
                'notes' => $order['notes'],
                'payment_terms' => 'Paiement à 15 jours sauf accord contractuel contraire.',
                'created_by' => $userId,
            ]);
            $invoiceId = (int) $this->db->lastInsertId();
            SalesCalculator::insertItems($this->db, 'invoice_items', 'invoice_id', $invoiceId, $items);
            $this->db->prepare('UPDATE sales_orders SET status = "Invoiced", updated_at = NOW() WHERE id = :id')->execute(['id' => $orderId]);
            $this->db->commit();
            return $invoiceId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function generateFromPlacementContract(int $contractId, string $month, int $userId): int
    {
        $contractModel = new PlacementContract();
        $contract = $contractModel->find($contractId);
        if ($contract === null) {
            throw new RuntimeException('Contrat de placement introuvable.');
        }
        $assignments = $contractModel->assignments($contractId);
        if ($assignments === []) {
            throw new RuntimeException('Aucun agent affecté à facturer.');
        }

        $items = [];
        foreach ($assignments as $assignment) {
            if ($assignment['status'] !== 'active') {
                continue;
            }
            $items[] = [
                'description' => 'Placement ' . $month . ' - ' . $assignment['first_name'] . ' ' . $assignment['last_name'] . ' / ' . $assignment['position_title'],
                'quantity' => 1,
                'unit_price' => (float) $assignment['client_rate'],
                'unit_cost' => (float) $assignment['agent_cost'],
                'tax_rate' => 0,
            ];
        }
        if ($items === []) {
            throw new RuntimeException('Aucun agent actif à facturer.');
        }

        $client = $this->findOrCreateClientFromPlacement($contract, $userId);
        $totals = $this->calculateTotals($items);

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO invoices
                 (client_id, client_name_snapshot, client_address_snapshot, sales_order_id, source_type, source_id, reference, invoice_date, due_date, status, subtotal, tax_amount, total_amount, paid_amount, estimated_margin, notes, payment_terms, sent_at, created_by, created_at, updated_at)
                 VALUES (:client_id, :client_name, :client_address, NULL, "placement_contract", :source_id, :reference, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY), "Sent", :subtotal, :tax, :total, 0, :margin, :notes, :payment_terms, NOW(), :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'client_id' => $client['id'],
                'client_name' => $client['name'],
                'client_address' => $client['address'],
                'source_id' => $contractId,
                'reference' => $this->nextReference('FPL'),
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'margin' => $totals['margin'],
                'notes' => 'Facturation mensuelle du contrat ' . $contract['reference'] . ' - période ' . $month,
                'payment_terms' => 'Paiement à 15 jours à compter de la date de facture.',
                'created_by' => $userId,
            ]);
            $invoiceId = (int) $this->db->lastInsertId();
            $this->insertItems($invoiceId, $items);
            $this->db->commit();
            return $invoiceId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function markOverdue(): void
    {
        $this->db->exec(
            'UPDATE invoices
             SET status = "Overdue", updated_at = NOW()
             WHERE status IN ("Sent", "Partially Paid")
               AND due_date IS NOT NULL
               AND due_date < CURRENT_DATE()
               AND total_amount > paid_amount'
        );
    }

    private function insertItems(int $invoiceId, array $items): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO invoice_items
             (invoice_id, product_id, description, quantity, unit_price, unit_cost, tax_rate, line_subtotal, line_tax, line_total, line_margin, created_at)
             VALUES (:invoice_id, NULL, :description, :quantity, :unit_price, :unit_cost, :tax_rate, :subtotal, :tax, :total, :margin, NOW())'
        );
        foreach ($items as $item) {
            $quantity = max(0, (float) ($item['quantity'] ?? 0));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            if ($quantity <= 0 || $unitPrice <= 0 || trim((string) ($item['description'] ?? '')) === '') {
                continue;
            }
            $unitCost = max(0, (float) ($item['unit_cost'] ?? 0));
            $taxRate = max(0, (float) ($item['tax_rate'] ?? 0));
            $subtotal = $quantity * $unitPrice;
            $tax = $subtotal * ($taxRate / 100);
            $statement->execute([
                'invoice_id' => $invoiceId,
                'description' => trim((string) $item['description']),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'margin' => $subtotal - ($quantity * $unitCost),
            ]);
        }
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $margin = 0.0;
        foreach ($items as $item) {
            $quantity = max(0, (float) ($item['quantity'] ?? 0));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            if ($quantity <= 0 || $unitPrice <= 0 || trim((string) ($item['description'] ?? '')) === '') {
                continue;
            }
            $unitCost = max(0, (float) ($item['unit_cost'] ?? 0));
            $lineSubtotal = $quantity * $unitPrice;
            $lineTax = $lineSubtotal * (max(0, (float) ($item['tax_rate'] ?? 0)) / 100);
            $subtotal += $lineSubtotal;
            $tax += $lineTax;
            $margin += $lineSubtotal - ($quantity * $unitCost);
        }
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'margin' => $margin,
        ];
    }

    private function findClient(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $client = $statement->fetch();
        return $client ?: null;
    }

    private function findOrCreateClientFromPlacement(array $contract, int $userId): array
    {
        $statement = $this->db->prepare('SELECT * FROM clients WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $contract['client_name']]);
        $client = $statement->fetch();
        if ($client) {
            return $client;
        }

        $insert = $this->db->prepare(
            'INSERT INTO clients
             (client_code, name, contact_name, phone, email, address, tax_number, status, notes, created_at, updated_at)
             VALUES (:code, :name, :contact, :phone, NULL, NULL, NULL, "active", :notes, NOW(), NOW())'
        );
        $insert->execute([
            'code' => 'CLT-PLC-' . date('Ymd-His') . '-' . random_int(100, 999),
            'name' => $contract['client_name'],
            'contact' => $contract['client_contact'],
            'phone' => $contract['client_phone'],
            'notes' => 'Client créé automatiquement depuis le contrat de placement ' . $contract['reference'] . ' par utilisateur #' . $userId . '.',
        ]);

        return $this->findClient((int) $this->db->lastInsertId());
    }

    private function nextReference(string $prefix = 'FAC'): string
    {
        return $prefix . '-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
