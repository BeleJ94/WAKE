<?php

declare(strict_types=1);

class TreasuryAccount extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT treasury_accounts.*, users.name AS responsible_name
             FROM treasury_accounts
             LEFT JOIN users ON users.id = treasury_accounts.responsible_user_id
             ORDER BY treasury_accounts.created_at DESC'
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query(
            'SELECT treasury_accounts.*, users.name AS responsible_name
             FROM treasury_accounts
             LEFT JOIN users ON users.id = treasury_accounts.responsible_user_id
             WHERE treasury_accounts.status = "active"
             ORDER BY treasury_accounts.name ASC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT treasury_accounts.*, users.name AS responsible_name
             FROM treasury_accounts
             LEFT JOIN users ON users.id = treasury_accounts.responsible_user_id
             WHERE treasury_accounts.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $account = $statement->fetch();

        return $account ?: null;
    }

    public function movements(int $id, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $statement = $this->db->prepare(
            'SELECT treasury_movements.*, users.name AS created_by_name, fund_requests.reference AS request_reference
             FROM treasury_movements
             INNER JOIN users ON users.id = treasury_movements.created_by
             LEFT JOIN fund_requests ON fund_requests.id = treasury_movements.fund_request_id
             WHERE treasury_movements.treasury_account_id = :id
             ORDER BY treasury_movements.created_at DESC
             LIMIT ' . $limit
        );
        $statement->execute(['id' => $id]);

        return $statement->fetchAll();
    }

    public function movementSummary(int $id): array
    {
        $statement = $this->db->prepare(
            'SELECT
                COUNT(*) AS movement_count,
                COALESCE(SUM(CASE WHEN movement_type = "inflow" THEN amount ELSE 0 END), 0) AS total_inflow,
                COALESCE(SUM(CASE WHEN movement_type = "outflow" THEN amount ELSE 0 END), 0) AS total_outflow,
                MAX(created_at) AS last_movement_at
             FROM treasury_movements
             WHERE treasury_account_id = :id'
        );
        $statement->execute(['id' => $id]);
        $summary = $statement->fetch() ?: [];

        return [
            'movement_count' => (int) ($summary['movement_count'] ?? 0),
            'total_inflow' => (float) ($summary['total_inflow'] ?? 0),
            'total_outflow' => (float) ($summary['total_outflow'] ?? 0),
            'last_movement_at' => $summary['last_movement_at'] ?? null,
        ];
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO treasury_accounts (responsible_user_id, name, type, currency, opening_balance, current_balance, status, notes, created_at, updated_at)
             VALUES (:responsible_user_id, :name, :type, :currency, :opening_balance, :current_balance, :status, :notes, NOW(), NOW())'
        );
        $statement->execute([
            'responsible_user_id' => $data['responsible_user_id'] ?: null,
            'name' => $data['name'],
            'type' => $data['type'],
            'currency' => $data['currency'],
            'opening_balance' => $data['opening_balance'],
            'current_balance' => $data['opening_balance'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $account = $this->find($id);
        if ($account === null) {
            throw new RuntimeException('Compte de trésorerie introuvable.');
        }

        if ($data['currency'] !== $account['currency'] && $this->hasFinancialHistory($id)) {
            throw new RuntimeException('La monnaie ne peut plus être modifiée car ce compte possède déjà un historique financier.');
        }

        if ($data['status'] === 'inactive' && $this->hasPendingPayments($id)) {
            throw new RuntimeException('Ce compte ne peut pas être désactivé : des demandes approuvées attendent encore leur paiement.');
        }

        $statement = $this->db->prepare(
            'UPDATE treasury_accounts
             SET responsible_user_id = :responsible_user_id,
                 name = :name,
                 type = :type,
                 currency = :currency,
                 status = :status,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'responsible_user_id' => $data['responsible_user_id'] ?: null,
            'name' => $data['name'],
            'type' => $data['type'],
            'currency' => $data['currency'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);
    }

    public function totals(): array
    {
        $statement = $this->db->query(
            'SELECT type, SUM(current_balance) AS total
             FROM treasury_accounts
             WHERE status = "active"
             GROUP BY type'
        );

        $totals = ['Caisse' => 0.0, 'Banque' => 0.0, 'Mobile Money' => 0.0, 'Autre' => 0.0];

        foreach ($statement->fetchAll() as $row) {
            $totals[$row['type']] = (float) $row['total'];
        }

        return $totals;
    }

    private function hasFinancialHistory(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM treasury_movements WHERE treasury_account_id = :movement_account
                UNION ALL
                SELECT 1 FROM fund_requests WHERE treasury_account_id = :request_account
             )'
        );
        $statement->execute([
            'movement_account' => $id,
            'request_account' => $id,
        ]);

        return (bool) $statement->fetchColumn();
    }

    private function hasPendingPayments(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(
                SELECT 1
                FROM fund_requests
                WHERE treasury_account_id = :id AND status = "Approved"
             )'
        );
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetchColumn();
    }
}
