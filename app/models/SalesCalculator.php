<?php

declare(strict_types=1);

class SalesCalculator
{
    public static function totals(array $items): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $margin = 0.0;
        foreach ($items as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $cost = (float) $item['unit_cost'];
            $rate = (float) $item['tax_rate'];
            $lineSubtotal = $qty * $price;
            $lineTax = $lineSubtotal * ($rate / 100);
            $subtotal += $lineSubtotal;
            $tax += $lineTax;
            $margin += ($price - $cost) * $qty;
        }
        return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax, 'margin' => $margin];
    }

    public static function insertItems(PDO $db, string $table, string $foreignKey, int $documentId, array $items): void
    {
        $statement = $db->prepare(
            "INSERT INTO {$table}
             ({$foreignKey}, product_id, description, quantity, unit_price, unit_cost, tax_rate, line_subtotal, line_tax, line_total, line_margin, created_at)
             VALUES (:document_id, :product_id, :description, :quantity, :unit_price, :unit_cost, :tax_rate, :subtotal, :tax, :total, :margin, NOW())"
        );
        foreach ($items as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $cost = (float) $item['unit_cost'];
            $taxRate = (float) $item['tax_rate'];
            $subtotal = $qty * $price;
            $tax = $subtotal * ($taxRate / 100);
            $statement->execute([
                'document_id' => $documentId,
                'product_id' => $item['product_id'] ?: null,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit_price' => $price,
                'unit_cost' => $cost,
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'margin' => ($price - $cost) * $qty,
            ]);
        }
    }
}

