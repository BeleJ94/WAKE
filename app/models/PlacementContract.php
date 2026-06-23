<?php

declare(strict_types=1);

class PlacementContract extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT placement_contracts.*,
                    COUNT(placement_contract_employees.id) AS employees_count,
                    COALESCE(SUM(placement_contract_employees.agent_cost), 0) AS total_cost,
                    COALESCE(SUM(placement_contract_employees.client_rate), 0) AS total_revenue,
                    COALESCE(SUM(placement_contract_employees.margin_amount), 0) AS total_margin
             FROM placement_contracts
             LEFT JOIN placement_contract_employees ON placement_contract_employees.placement_contract_id = placement_contracts.id
             GROUP BY placement_contracts.id
             ORDER BY placement_contracts.created_at DESC'
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query('SELECT * FROM placement_contracts WHERE status = "Active" ORDER BY client_name ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM placement_contracts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $contract = $statement->fetch();

        return $contract ?: null;
    }

    public function assignments(int $contractId): array
    {
        $statement = $this->db->prepare(
            'SELECT placement_contract_employees.*, employees.employee_code, employees.first_name, employees.last_name, employees.phone
             FROM placement_contract_employees
             INNER JOIN employees ON employees.id = placement_contract_employees.employee_id
             WHERE placement_contract_employees.placement_contract_id = :id
             ORDER BY employees.last_name ASC'
        );
        $statement->execute(['id' => $contractId]);

        return $statement->fetchAll();
    }

    public function create(array $data, array $assignments): int
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'INSERT INTO placement_contracts
                 (reference, client_name, client_contact, client_phone, start_date, end_date, status, billing_day, notes, created_by, created_at, updated_at)
                 VALUES (:reference, :client_name, :client_contact, :client_phone, :start_date, :end_date, :status, :billing_day, :notes, :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'reference' => $this->nextReference(),
                'client_name' => $data['client_name'],
                'client_contact' => $data['client_contact'],
                'client_phone' => $data['client_phone'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?: null,
                'status' => $data['status'],
                'billing_day' => $data['billing_day'],
                'notes' => $data['notes'],
                'created_by' => $data['created_by'],
            ]);
            $contractId = (int) $this->db->lastInsertId();
            $this->insertAssignments($contractId, $data, $assignments);
            $this->db->commit();
            return $contractId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function expiringSoon(int $days = 30): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM placement_contracts
             WHERE status = "Active" AND end_date IS NOT NULL
               AND end_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL :days DAY)
             ORDER BY end_date ASC'
        );
        $statement->bindValue('days', $days, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function reportMetrics(): array
    {
        $contracts = $this->all();
        $activeAgents = $this->db->query('SELECT COUNT(*) AS total FROM placement_contract_employees WHERE status = "active"')->fetch();
        $invoices = $this->db->query('SELECT COUNT(*) AS count, COALESCE(SUM(subtotal),0) AS total, COALESCE(SUM(margin_amount),0) AS margin FROM placement_invoices')->fetch();
        return [
            'contracts' => $contracts,
            'active_agents' => (int) ($activeAgents['total'] ?? 0),
            'invoice_count' => (int) ($invoices['count'] ?? 0),
            'invoice_total' => (float) ($invoices['total'] ?? 0),
            'invoice_margin' => (float) ($invoices['margin'] ?? 0),
            'expiring' => $this->expiringSoon(),
        ];
    }

    private function insertAssignments(int $contractId, array $data, array $assignments): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO placement_contract_employees
             (placement_contract_id, employee_id, position_title, agent_cost, client_rate, margin_amount, start_date, end_date, status, created_at, updated_at)
             VALUES (:contract_id, :employee_id, :position_title, :agent_cost, :client_rate, :margin, :start_date, :end_date, "active", NOW(), NOW())'
        );
        foreach ($assignments as $assignment) {
            $statement->execute([
                'contract_id' => $contractId,
                'employee_id' => $assignment['employee_id'],
                'position_title' => $assignment['position_title'],
                'agent_cost' => $assignment['agent_cost'],
                'client_rate' => $assignment['client_rate'],
                'margin' => (float) $assignment['client_rate'] - (float) $assignment['agent_cost'],
                'start_date' => $assignment['start_date'] ?: $data['start_date'],
                'end_date' => $assignment['end_date'] ?: $data['end_date'] ?: null,
            ]);
        }
    }

    private function nextReference(): string
    {
        return 'PLC-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}

