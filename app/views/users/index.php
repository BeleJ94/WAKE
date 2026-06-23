<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Administration</span>
            <h2>Utilisateurs et accès</h2>
            <p class="ui-page-intro">Gérez les comptes, les rôles et l’état des accès à la plateforme.</p>
        </div>
        <?php if (Auth::can('users.create')): ?>
            <a class="btn btn-primary" href="<?= url('users/create'); ?>">Créer un utilisateur</a>
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
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['name']); ?></td>
                        <td><?= e($user['email']); ?></td>
                        <td><?= e($user['role_name'] ?? 'Non assigné'); ?></td>
                        <td><span class="badge <?= $user['status'] === 'active' ? 'badge-success' : 'badge-neutral'; ?>"><?= e(status_label($user['status'])); ?></span></td>
                        <td><?= e(date_fr($user['last_login_at'], true)); ?></td>
                        <td class="text-right">
                            <?php if (Auth::can('users.edit')): ?>
                                <a class="btn btn-secondary btn-compact" href="<?= url('users/edit?id=' . (int) $user['id']); ?>"><i class="bi bi-pencil-square"></i> Modifier</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
