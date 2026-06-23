<?php

declare(strict_types=1);

class UserController extends Controller
{
    public function index(): void
    {
        $this->view('users.index', [
            'title' => 'Utilisateurs',
            'users' => (new User())->all(),
        ]);
    }

    public function create(): void
    {
        $this->view('users.create', [
            'title' => 'Nouvel utilisateur',
            'roles' => (new Role())->active(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->userPayload();
        $errors = $this->validateUser($data, true);

        if ($errors !== []) {
            $this->view('users.create', [
                'title' => 'Nouvel utilisateur',
                'roles' => (new Role())->active(),
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $id = (new User())->create($data);
        AuditLog::record('user_created', 'user', $id, ['email' => $data['email']]);
        Session::flash('success', 'Utilisateur créé avec succès.');
        $this->redirect('users');
    }

    public function edit(): void
    {
        $id = (int) $this->request('id', 0);
        $user = (new User())->find($id);

        if ($user === null) {
            Session::flash('error', 'Utilisateur introuvable.');
            $this->redirect('users');
        }

        $this->view('users.edit', [
            'title' => 'Modifier utilisateur',
            'roles' => (new Role())->active(),
            'user' => $user,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireCsrf();

        $id = (int) $this->request('id', 0);
        $userModel = new User();
        $user = $userModel->find($id);

        if ($user === null) {
            Session::flash('error', 'Utilisateur introuvable.');
            $this->redirect('users');
        }

        $data = $this->userPayload();
        $errors = $this->validateUser($data, false, $id);

        if ($errors !== []) {
            $this->view('users.edit', [
                'title' => 'Modifier utilisateur',
                'roles' => (new Role())->active(),
                'user' => array_merge($user, $data),
                'errors' => $errors,
            ]);
            return;
        }

        $userModel->update($id, $data);
        AuditLog::record('user_updated', 'user', $id, ['email' => $data['email']]);
        Session::flash('success', 'Utilisateur mis à jour.');
        $this->redirect('users');
    }

    private function userPayload(): array
    {
        return [
            'name' => trim((string) $this->request('name', '')),
            'email' => trim((string) $this->request('email', '')),
            'role_id' => (int) $this->request('role_id', 0),
            'status' => (string) $this->request('status', 'active'),
            'password' => (string) $this->request('password', ''),
        ];
    }

    private function validateUser(array $data, bool $passwordRequired, ?int $ignoreUserId = null): array
    {
        $errors = [];

        if (strlen($data['name']) < 2) {
            $errors['name'] = 'Le nom doit contenir au moins 2 caractères.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        } else {
            $existing = (new User())->findByEmail($data['email']);
            if ($existing !== null && (int) $existing['id'] !== (int) $ignoreUserId) {
                $errors['email'] = 'Cette adresse email est déjà utilisée.';
            }
        }

        if ($data['role_id'] <= 0 || (new Role())->find($data['role_id']) === null) {
            $errors['role_id'] = 'Rôle invalide.';
        }

        if (!in_array($data['status'], ['active', 'inactive'], true)) {
            $errors['status'] = 'Statut invalide.';
        }

        if ($passwordRequired && strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if (!$passwordRequired && $data['password'] !== '' && strlen($data['password']) < 8) {
            $errors['password'] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        }

        return $errors;
    }
}

