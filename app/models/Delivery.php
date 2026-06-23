<?php

declare(strict_types=1);

class Delivery extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT deliveries.*, sales_orders.reference AS order_reference, clients.name AS client_name
             FROM deliveries
             INNER JOIN sales_orders ON sales_orders.id = deliveries.sales_order_id
             INNER JOIN clients ON clients.id = sales_orders.client_id
             ORDER BY deliveries.created_at DESC'
        )->fetchAll();
    }

    public function create(int $orderId, array $quantities, string $notes, int $userId): int
    {
        $orderModel = new SalesOrder();
        $order = $orderModel->find($orderId);
        if ($order === null || in_array($order['status'], ['Closed', 'Cancelled'], true)) {
            throw new RuntimeException('Commande invalide.');
        }
        $items = $orderModel->items($orderId);
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO deliveries (sales_order_id, reference, delivery_date, status, notes, created_by, created_at, updated_at)
                 VALUES (:order_id, :reference, CURRENT_DATE(), "Prepared", :notes, :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'order_id' => $orderId,
                'reference' => 'BL-' . date('Ymd-His') . '-' . random_int(100, 999),
                'notes' => $notes,
                'created_by' => $userId,
            ]);
            $deliveryId = (int) $this->db->lastInsertId();
            $insert = $this->db->prepare(
                'INSERT INTO delivery_items (delivery_id, sales_order_item_id, product_id, quantity, created_at)
                 VALUES (:delivery_id, :item_id, :product_id, :quantity, NOW())'
            );
            $deliveredAny = false;
            foreach ($items as $item) {
                $qty = max(0, (float) ($quantities[$item['id']] ?? 0));
                if ($qty <= 0) {
                    continue;
                }
                $remaining = (float) $item['quantity'] - (float) $item['delivered_quantity'];
                if ($qty > $remaining) {
                    throw new RuntimeException('Quantité livrée supérieure au reste à livrer.');
                }
                if ($item['product_id']) {
                    (new Product())->moveStock((int) $item['product_id'], 'out', $qty, 'delivery', $deliveryId, $userId, 'Livraison commande ' . $order['reference']);
                }
                $insert->execute(['delivery_id' => $deliveryId, 'item_id' => $item['id'], 'product_id' => $item['product_id'], 'quantity' => $qty]);
                $this->db->prepare('UPDATE sales_order_items SET delivered_quantity = delivered_quantity + :qty WHERE id = :id')->execute(['qty' => $qty, 'id' => $item['id']]);
                $deliveredAny = true;
            }
            if (!$deliveredAny) {
                throw new RuntimeException('Aucune quantité à livrer.');
            }
            $orderModel->updateDeliveryStatus($orderId);
            $freshOrder = $orderModel->find($orderId);
            $status = $freshOrder['status'] === 'Delivered' ? 'Delivered' : 'Partial';
            $this->db->prepare('UPDATE deliveries SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $deliveryId]);
            $this->db->commit();
            return $deliveryId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}

