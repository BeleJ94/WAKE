<?php

declare(strict_types=1);

class Quotation extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT quotations.*, clients.name AS client_name
             FROM quotations
             INNER JOIN clients ON clients.id = quotations.client_id
             ORDER BY quotations.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT quotations.*, clients.name AS client_name
             FROM quotations INNER JOIN clients ON clients.id = quotations.client_id
             WHERE quotations.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function items(int $quotationId): array
    {
        $statement = $this->db->prepare('SELECT * FROM quotation_items WHERE quotation_id = :id ORDER BY id ASC');
        $statement->execute(['id' => $quotationId]);
        return $statement->fetchAll();
    }

    public function create(array $data, array $items): int
    {
        $totals = SalesCalculator::totals($items);
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO quotations (client_id, reference, quote_date, valid_until, status, subtotal, tax_amount, total_amount, estimated_margin, notes, created_by, created_at, updated_at)
                 VALUES (:client_id, :reference, :quote_date, :valid_until, "Draft", :subtotal, :tax, :total, :margin, :notes, :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'client_id' => $data['client_id'],
                'reference' => 'DEV-' . date('Ymd-His') . '-' . random_int(100, 999),
                'quote_date' => $data['quote_date'],
                'valid_until' => $data['valid_until'] ?: null,
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'margin' => $totals['margin'],
                'notes' => $data['notes'],
                'created_by' => $data['created_by'],
            ]);
            $id = (int) $this->db->lastInsertId();
            SalesCalculator::insertItems($this->db, 'quotation_items', 'quotation_id', $id, $items);
            $this->db->commit();
            return $id;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function validateQuote(int $id): void
    {
        $statement = $this->db->prepare('UPDATE quotations SET status = "Validated", updated_at = NOW() WHERE id = :id AND status = "Draft"');
        $statement->execute(['id' => $id]);
    }
}

