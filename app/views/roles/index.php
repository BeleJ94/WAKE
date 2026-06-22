<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Sécurité</span>
            <h2>Rôles</h2>
        </div>
        <?php if (Auth::can('roles.permissions')): ?>
            <a class="btn btn-primary" href="<?= url('roles/permissions'); ?>">Gérer les permissions</a>
        <?php endif; ?>
    </div>

    <?php if ($message = Session::flash('success')): ?>
        <div class="alert alert-success"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($message = Session::flash('error')): ?>
        <div class="alert alert-danger"><?= e($message); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rôle</th>
                    <th>Description</th>
                    <th>Utilisateurs</th>
                    <th>Statut</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= e($role['name']); ?></td>
                        <td><?= e($role['description']); ?></td>
                        <td><?= (int) $role['users_count']; ?></td>
                        <td><span class="badge <?= (int) $role['is_active'] === 1 ? 'badge-success' : 'badge-neutral'; ?>"><?= (int) $role['is_active'] === 1 ? 'Actif' : 'Inactif'; ?></span></td>
                        <td class="text-right">
                            <?php if (Auth::can('roles.permissions')): ?>
                                <a class="btn btn-secondary" href="<?= url('roles/permissions?role_id=' . (int) $role['id']); ?>">Permissions</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

