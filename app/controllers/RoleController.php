<?php

declare(strict_types=1);

class RoleController extends Controller
{
    public function index(): void
    {
        $this->view('roles.index', [
            'title' => 'Rôles',
            'roles' => (new Role())->all(),
        ]);
    }

    public function permissions(): void
    {
        $roleId = (int) $this->request('role_id', 0);
        $roleModel = new Role();
        $roles = $roleModel->active();
        $role = $roleId > 0 ? $roleModel->find($roleId) : ($roles[0] ?? null);

        if ($role === null) {
            Session::flash('error', 'Aucun rôle disponible.');
            $this->redirect('roles');
        }

        $this->view('roles.permissions', [
            'title' => 'Permissions des rôles',
            'roles' => $roles,
            'role' => $role,
            'permissions' => (new Permission())->grouped(),
            'rolePermissions' => $roleModel->permissions((int) $role['id']),
        ]);
    }

    public function updatePermissions(): void
    {
        $this->requireCsrf();

        $roleId = (int) $this->request('role_id', 0);
        $role = (new Role())->find($roleId);

        if ($role === null) {
            Session::flash('error', 'Rôle introuvable.');
            $this->redirect('roles');
        }

        if ($role['slug'] === 'super-admin') {
            Session::flash('error', 'Les permissions Super Admin restent globales.');
            $this->redirect('roles/permissions?role_id=' . $roleId);
        }

        $permissionIds = $_POST['permissions'] ?? [];

        if (!is_array($permissionIds)) {
            $permissionIds = [];
        }

        (new Role())->syncPermissions($roleId, $permissionIds);
        AuditLog::record('role_permissions_updated', 'role', $roleId);
        Session::flash('success', 'Permissions mises à jour.');

        $this->redirect('roles/permissions?role_id=' . $roleId);
    }
}

