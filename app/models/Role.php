<?php

declare(strict_types=1);

class Role extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT roles.*, COUNT(users.id) AS users_count
             FROM roles
             LEFT JOIN users ON users.role_id = roles.id
             GROUP BY roles.id
             ORDER BY roles.name ASC'
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query('SELECT * FROM roles WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $role = $statement->fetch();

        return $role ?: null;
    }

    public function permissions(int $roleId): array
    {
        $statement = $this->db->prepare(
            'SELECT permissions.name
             FROM role_permissions
             INNER JOIN permissions ON permissions.id = role_permissions.permission_id
             WHERE role_permissions.role_id = :role_id'
        );
        $statement->execute(['role_id' => $roleId]);

        return array_column($statement->fetchAll(), 'name');
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();

        try {
            $delete = $this->db->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
            $delete->execute(['role_id' => $roleId]);

            $insert = $this->db->prepare(
                'INSERT INTO role_permissions (role_id, permission_id, created_at)
                 VALUES (:role_id, :permission_id, NOW())'
            );

            foreach ($permissionIds as $permissionId) {
                $insert->execute([
                    'role_id' => $roleId,
                    'permission_id' => (int) $permissionId,
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}

