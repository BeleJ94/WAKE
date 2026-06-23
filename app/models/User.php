<?php

declare(strict_types=1);

class User extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT users.*, roles.name AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             ORDER BY users.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findWithRole(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT users.*, roles.name AS role_name, roles.slug AS role_slug
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (role_id, name, email, password, status, created_at, updated_at)
             VALUES (:role_id, :name, :email, :password, :status, NOW(), NOW())'
        );
        $statement->execute([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => $data['status'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'role_id = :role_id',
            'name = :name',
            'email = :email',
            'status = :status',
            'updated_at = NOW()',
        ];

        $params = [
            'id' => $id,
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $statement = $this->db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $statement->execute($params);
    }

    public function updateLastLogin(int $id): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}

