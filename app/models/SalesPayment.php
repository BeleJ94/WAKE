<?php

declare(strict_types=1);

class SalesPayment extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT payments.*, invoices.reference AS invoice_reference, clients.name AS client_name
             FROM payments
             INNER JOIN invoices ON invoices.id = payments.invoice_id
             INNER JOIN clients ON clients.id = invoices.client_id
             ORDER BY payments.created_at DESC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $invoiceModel = new SalesInvoice();
        $invoice = $invoiceModel->find((int) $data['invoice_id']);
        if ($invoice === null) {
            throw new RuntimeException('Facture introuvable.');
        }
        $remaining = (float) $invoice['total_amount'] - (float) $invoice['paid_amount'];
        if (in_array($invoice['status'], ['Draft', 'Cancelled'], true)) {
            throw new RuntimeException('Cette facture ne peut pas recevoir de paiement.');
        }
        if ((float) $data['amount'] <= 0 || (float) $data['amount'] > $remaining) {
            throw new RuntimeException('Montant paiement invalide.');
        }
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO payments (invoice_id, reference, payment_date, amount, method, notes, created_by, created_at)
                 VALUES (:invoice_id, :reference, :payment_date, :amount, :method, :notes, :created_by, NOW())'
            );
            $statement->execute([
                'invoice_id' => $data['invoice_id'],
                'reference' => 'PAY-' . date('Ymd-His') . '-' . random_int(100, 999),
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'notes' => $data['notes'],
                'created_by' => $data['created_by'],
            ]);
            $paymentId = (int) $this->db->lastInsertId();
            $newPaid = (float) $invoice['paid_amount'] + (float) $data['amount'];
            $status = $newPaid >= (float) $invoice['total_amount'] ? 'Paid' : 'Partially Paid';
            $this->db->prepare('UPDATE invoices SET paid_amount = :paid, status = :status, updated_at = NOW() WHERE id = :id')->execute(['paid' => $newPaid, 'status' => $status, 'id' => $invoice['id']]);
            if ($invoice['source_type'] === 'sales_order' && $invoice['source_id'] && $status === 'Paid') {
                $this->db->prepare('UPDATE sales_orders SET status = "Closed", updated_at = NOW() WHERE id = :id')->execute(['id' => $invoice['source_id']]);
            }
            $this->db->commit();
            return $paymentId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
