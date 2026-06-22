<?php

declare(strict_types=1);

class Notification extends Model
{
    public function recent(?int $userId, int $limit = 50): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE user_id IS NULL OR user_id = :user_id
             ORDER BY read_at IS NULL DESC, created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('user_id', $userId ?? 0, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function unreadCount(?int $userId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications
             WHERE read_at IS NULL AND (user_id IS NULL OR user_id = :user_id)'
        );
        $statement->execute(['user_id' => $userId ?? 0]);

        return (int) $statement->fetchColumn();
    }

    public function markAsRead(int $id, ?int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE id = :id AND (user_id IS NULL OR user_id = :user_id)'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId ?? 0]);
    }

    public function markAllAsRead(?int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE read_at IS NULL AND (user_id IS NULL OR user_id = :user_id)'
        );
        $statement->execute(['user_id' => $userId ?? 0]);
    }

    public function create(array $data): int
    {
        $hash = $data['unique_hash'] ?? $this->hash($data);
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO notifications
             (user_id, type, title, message, link_url, severity, entity_type, entity_id, unique_hash, created_at)
             VALUES (:user_id, :type, :title, :message, :link_url, :severity, :entity_type, :entity_id, :unique_hash, NOW())'
        );
        $statement->execute([
            'user_id' => $data['user_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'link_url' => $data['link_url'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'unique_hash' => $hash,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public static function push(string $type, string $title, string $message, ?string $linkUrl = null, string $severity = 'info', ?string $entityType = null, ?int $entityId = null, ?int $userId = null): void
    {
        try {
            (new self())->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link_url' => $linkUrl,
                'severity' => $severity,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
        } catch (Throwable $exception) {
            error_log('Notification error: ' . $exception->getMessage());
        }
    }

    public function scanSystemAlerts(): void
    {
        $this->scanProjectAlerts();
        $this->scanOverdueInvoices();
        $this->scanPendingDeliveries();
        $this->scanExpiringPlacementContracts();
    }

    private function scanProjectAlerts(): void
    {
        foreach ((new ConstructionProject())->all() as $project) {
            if ((int) $project['metrics']['delay_days'] > 0) {
                $this->create([
                    'type' => 'project_delay',
                    'title' => 'Projet en retard',
                    'message' => $project['reference'] . ' accuse ' . (int) $project['metrics']['delay_days'] . ' jour(s) de retard potentiel.',
                    'link_url' => url('construction/projects/show?id=' . (int) $project['id']),
                    'severity' => 'danger',
                    'entity_type' => 'construction_project',
                    'entity_id' => (int) $project['id'],
                    'unique_hash' => 'project_delay_' . (int) $project['id'],
                ]);
            }
            if ((float) $project['metrics']['cost_variance'] < 0) {
                $this->create([
                    'type' => 'project_budget_overrun',
                    'title' => 'Dépassement budget projet',
                    'message' => $project['reference'] . ' dépasse le budget prévisionnel.',
                    'link_url' => url('construction/projects/show?id=' . (int) $project['id']),
                    'severity' => 'danger',
                    'entity_type' => 'construction_project',
                    'entity_id' => (int) $project['id'],
                    'unique_hash' => 'project_budget_overrun_' . (int) $project['id'],
                ]);
            }
            if ((float) $project['metrics']['consumption_variance'] < 0) {
                $this->create([
                    'type' => 'consumption_over_forecast',
                    'title' => 'Consommation supérieure aux prévisions',
                    'message' => $project['reference'] . ' consomme plus que prévu.',
                    'link_url' => url('construction/projects/show?id=' . (int) $project['id']),
                    'severity' => 'warning',
                    'entity_type' => 'construction_project',
                    'entity_id' => (int) $project['id'],
                    'unique_hash' => 'consumption_over_forecast_' . (int) $project['id'],
                ]);
            }
        }
    }

    private function scanOverdueInvoices(): void
    {
        (new SalesInvoice())->markOverdue();
        $rows = $this->db->query(
            'SELECT id, reference, total_amount, paid_amount
             FROM invoices
             WHERE status = "Overdue" AND total_amount > paid_amount'
        )->fetchAll();

        foreach ($rows as $invoice) {
            $this->create([
                'type' => 'invoice_overdue',
                'title' => 'Facture en retard',
                'message' => $invoice['reference'] . ' reste impayée.',
                'link_url' => url('invoices/show?id=' . (int) $invoice['id']),
                'severity' => 'danger',
                'entity_type' => 'invoice',
                'entity_id' => (int) $invoice['id'],
                'unique_hash' => 'invoice_overdue_' . (int) $invoice['id'],
            ]);
        }
    }

    private function scanPendingDeliveries(): void
    {
        $rows = $this->db->query(
            'SELECT id, reference, status
             FROM deliveries
             WHERE status IN ("Prepared", "Partial")'
        )->fetchAll();

        foreach ($rows as $delivery) {
            $this->create([
                'type' => 'delivery_pending',
                'title' => 'Livraison en attente',
                'message' => $delivery['reference'] . ' est encore au statut ' . $delivery['status'] . '.',
                'link_url' => url('deliveries/index'),
                'severity' => 'warning',
                'entity_type' => 'delivery',
                'entity_id' => (int) $delivery['id'],
                'unique_hash' => 'delivery_pending_' . (int) $delivery['id'],
            ]);
        }
    }

    private function scanExpiringPlacementContracts(): void
    {
        foreach ((new PlacementContract())->expiringSoon(30) as $contract) {
            $this->create([
                'type' => 'placement_contract_expiring',
                'title' => 'Contrat placement bientôt expiré',
                'message' => $contract['reference'] . ' expire le ' . $contract['end_date'] . '.',
                'link_url' => url('placement/contracts/show?id=' . (int) $contract['id']),
                'severity' => 'warning',
                'entity_type' => 'placement_contract',
                'entity_id' => (int) $contract['id'],
                'unique_hash' => 'placement_contract_expiring_' . (int) $contract['id'],
            ]);
        }
    }

    private function hash(array $data): string
    {
        return hash('sha256', implode('|', [
            $data['type'] ?? '',
            $data['entity_type'] ?? '',
            (string) ($data['entity_id'] ?? ''),
            (string) ($data['user_id'] ?? ''),
            date('Y-m-d'),
        ]));
    }
}
