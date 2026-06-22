<?php

declare(strict_types=1);

class Permission extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM permissions ORDER BY module ASC, label ASC')->fetchAll();
    }

    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->all() as $permission) {
            $grouped[$permission['module']][] = $permission;
        }

        return $grouped;
    }

    public function forUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT DISTINCT permissions.name
             FROM users
             INNER JOIN role_permissions ON role_permissions.role_id = users.role_id
             INNER JOIN permissions ON permissions.id = role_permissions.permission_id
             WHERE users.id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return array_column($statement->fetchAll(), 'name');
    }
}

