<?php

declare(strict_types=1);

class Client extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT clients.*,
                    COUNT(DISTINCT quotations.id) AS quotations_count,
                    COUNT(DISTINCT sales_orders.id) AS orders_count,
                    COALESCE(SUM(invoices.total_amount), 0) AS invoiced_total,
                    COALESCE(SUM(invoices.paid_amount), 0) AS paid_total
             FROM clients
             LEFT JOIN quotations ON quotations.client_id = clients.id
             LEFT JOIN sales_orders ON sales_orders.client_id = clients.id
             LEFT JOIN invoices ON invoices.client_id = clients.id
             GROUP BY clients.id
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

