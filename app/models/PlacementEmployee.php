<?php

declare(strict_types=1);

class PlacementEmployee extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT employees.*,
                    active_contract.client_name,
                    active_contract.position_title,
                    active_contract.client_rate,
                    active_contract.margin_amount
             FROM employees
             LEFT JOIN (
                SELECT placement_contract_employees.*, placement_contracts.client_name
                FROM placement_contract_employees
                INNER JOIN placement_contracts ON placement_contracts.id = placement_contract_employees.placement_contract_id
                WHERE placement_contract_employees.status = "active"
             ) active_contract ON active_contract.employee_id = employees.id
             ORDER BY employees.created_at DESC'
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query('SELECT * FROM employees WHERE status = "active" ORDER BY last_name ASC, first_name ASC')->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO employees (employee_code, first_name, last_name, phone, email, job_title, base_salary, status, hired_at, notes, created_at, updated_at)
             VALUES (:code, :first_name, :last_name, :phone, :email, :job_title, :base_salary, :status, :hired_at, :notes, NOW(), NOW())'
        );
        $statement->execute([
            'code' => $this->nextCode(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'job_title' => $data['job_title'],
            'base_salary' => $data['base_salary'],
            'status' => $data['status'],
            'hired_at' => $data['hired_at'] ?: null,
            'notes' => $data['notes'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function nextCode(): string
    {
        return 'EMP-' . date('YmdHis') . random_int(10, 99);
    }
}

