<?php

declare(strict_types=1);

class SalesOrder extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT sales_orders.*, clients.name AS client_name
             FROM sales_orders INNER JOIN clients ON clients.id = sales_orders.client_id
             ORDER BY sales_orders.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT sales_orders.*, clients.name AS client_name, clients.phone, clients.email
             FROM sales_orders INNER JOIN clients ON clients.id = sales_orders.client_id
             WHERE sales_orders.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function items(int $orderId): array
    {
        $statement = $this->db->prepare('SELECT * FROM sales_order_items WHERE sales_order_id = :id ORDER BY id ASC');
        $statement->execute(['id' => $orderId]);
        return $statement->fetchAll();
    }

    public function convertFromQuotation(int $quotationId, int $userId): int
    {
        $quotationModel = new Quotation();
        $quote = $quotationModel->find($quotationId);
        if ($quote === null || !in_array($quote['status'], ['Validated', 'Draft'], true)) {
            throw new RuntimeException('Devis non convertible.');
        }
        if ($quote['status'] === 'Draft') {
            $quotationModel->validateQuote($quotationId);
        }
        $items = $quotationModel->items($quotationId);
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO sales_orders (client_id, quotation_id, reference, order_date, status, subtotal, tax_amount, total_amount, estimated_margin, notes, created_by, created_at, updated_at)
                 VALUES (:client_id, :quotation_id, :reference, CURRENT_DATE(), "Open", :subtotal, :tax, :total, :margin, :notes, :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'client_id' => $quote['client_id'],
                'quotation_id' => $quote['id'],
                'reference' => 'CMD-' . date('Ymd-His') . '-' . random_int(100, 999),
                'subtotal' => $quote['subtotal'],
                'tax' => $quote['tax_amount'],
                'total' => $quote['total_amount'],
                'margin' => $quote['estimated_margin'],
                'notes' => $quote['notes'],
                'created_by' => $userId,
            ]);
            $orderId = (int) $this->db->lastInsertId();
            SalesCalculator::insertItems($this->db, 'sales_order_items', 'sales_order_id', $orderId, $items);
            $this->db->prepare('UPDATE quotations SET status = "Converted", updated_at = NOW() WHERE id = :id')->execute(['id' => $quotationId]);
            $this->db->commit();
            return $orderId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateDeliveryStatus(int $orderId): void
    {
        $items = $this->items($orderId);
        $total = 0;
        $delivered = 0;
        foreach ($items as $item) {
            $total += (float) $item['quantity'];
            $delivered += (float) $item['delivered_quantity'];
        }
        $status = $delivered <= 0 ? 'Open' : ($delivered >= $total ? 'Delivered' : 'Partially Delivered');
        $this->db->prepare('UPDATE sales_orders SET status = :status, updated_at = NOW() WHERE id = :id')->execute(['status' => $status, 'id' => $orderId]);
    }
}

