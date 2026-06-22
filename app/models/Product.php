<?php

declare(strict_types=1);

class Product extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT products.*, product_categories.name AS category_name
             FROM products
             LEFT JOIN product_categories ON product_categories.id = products.product_category_id
             ORDER BY products.created_at DESC'
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query('SELECT * FROM products WHERE status = "active" ORDER BY name ASC')->fetchAll();
    }

    public function categories(): array
    {
        return $this->db->query('SELECT * FROM product_categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO products (product_category_id, sku, name, unit, cost_price, sale_price, stock_quantity, reorder_level, status, created_at, updated_at)
             VALUES (:category_id, :sku, :name, :unit, :cost_price, :sale_price, :stock_quantity, :reorder_level, :status, NOW(), NOW())'
        );
        $statement->execute([
            'category_id' => $data['product_category_id'] ?: null,
            'sku' => $data['sku'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'cost_price' => $data['cost_price'],
            'sale_price' => $data['sale_price'],
            'stock_quantity' => $data['stock_quantity'],
            'reorder_level' => $data['reorder_level'],
            'status' => $data['status'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function moveStock(int $productId, string $type, float $quantity, string $sourceType, int $sourceId, int $userId, string $notes): void
    {
        $product = $this->find($productId);
        if ($product === null) {
            throw new RuntimeException('Produit introuvable.');
        }
        $before = (float) $product['stock_quantity'];
        $after = $type === 'out' ? $before - $quantity : $before + $quantity;
        if ($after < 0) {
            throw new RuntimeException('Stock insuffisant pour ' . $product['name']);
        }
        $reference = 'STM-' . date('Ymd-His') . '-' . random_int(100, 999);
        $statement = $this->db->prepare(
            'INSERT INTO stock_movements (product_id, reference, movement_type, quantity, balance_before, balance_after, source_type, source_id, notes, created_by, created_at)
             VALUES (:product_id, :reference, :type, :quantity, :before, :after, :source_type, :source_id, :notes, :created_by, NOW())'
        );
        $statement->execute([
            'product_id' => $productId,
            'reference' => $reference,
            'type' => $type,
            'quantity' => $quantity,
            'before' => $before,
            'after' => $after,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
            'created_by' => $userId,
        ]);
        $update = $this->db->prepare('UPDATE products SET stock_quantity = :stock, updated_at = NOW() WHERE id = :id');
        $update->execute(['stock' => $after, 'id' => $productId]);
    }
}

