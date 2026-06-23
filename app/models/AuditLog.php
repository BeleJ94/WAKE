<?php

declare(strict_types=1);

class AuditLog extends Model
{
    public function latest(array $filters = [], int $limit = 100): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = 'audit_logs.action LIKE :action';
            $params['action'] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'audit_logs.entity_type = :entity_type';
            $params['entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(audit_logs.created_at) >= :start_date';
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(audit_logs.created_at) <= :end_date';
            $params['end_date'] = $filters['end_date'];
        }

        $statement = $this->db->prepare(
            'SELECT audit_logs.*, users.name AS user_name, users.email AS user_email
             FROM audit_logs
             LEFT JOIN users ON users.id = audit_logs.user_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY audit_logs.created_at DESC
             LIMIT :limit'
        );
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function entityTypes(): array
    {
        return $this->db->query('SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type ASC')->fetchAll();
    }

    public static function record(string $action, string $entityType, ?int $entityId = null, array $metadata = []): void
    {
        try {
            $db = Database::getConnection();
            $statement = $db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata, created_at)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent, :metadata, NOW())'
            );
            $statement->execute([
                'user_id' => Auth::id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'metadata' => json_encode($metadata),
            ]);
        } catch (Throwable $exception) {
            if (APP_DEBUG) {
                error_log($exception->getMessage());
            }
        }
    }
}
