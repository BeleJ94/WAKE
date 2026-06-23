<?php

declare(strict_types=1);

class Client extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT clients.*,
                    (SELECT COUNT(*) FROM quotations WHERE quotations.client_id = clients.id) AS quotations_count,
                    (SELECT COUNT(*) FROM sales_orders WHERE sales_orders.client_id = clients.id) AS orders_count,
                    (SELECT COALESCE(SUM(invoices.total_amount), 0) FROM invoices WHERE invoices.client_id = clients.id) AS invoiced_total,
                    (SELECT COALESCE(SUM(invoices.paid_amount), 0) FROM invoices WHERE invoices.client_id = clients.id) AS paid_total
             FROM clients
             ORDER BY clients.created_at DESC'
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query('SELECT * FROM clients WHERE status = "active" ORDER BY name ASC')->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO clients (client_code, name, contact_name, phone, email, address, tax_number, status, notes, created_at, updated_at)
             VALUES (:code, :name, :contact_name, :phone, :email, :address, :tax_number, :status, :notes, NOW(), NOW())'
        );
        $statement->execute([
            'code' => 'CLI-' . date('YmdHis') . random_int(10, 99),
            'name' => $data['name'],
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address' => $data['address'],
            'tax_number' => $data['tax_number'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);
        return (int) $this->db->lastInsertId();
    }
}
