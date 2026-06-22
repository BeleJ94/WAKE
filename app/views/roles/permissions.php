<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Contrôle des accès</span>
            <h2>Permissions par rôle</h2>
            <p class="ui-page-intro">Définissez précisément les fonctionnalités accessibles à chaque profil utilisateur.</p>
        </div>
        <a class="btn btn-secondary" href="<?= url('roles'); ?>">Retour</a>
    </div>

    <?php if ($message = Session::flash('success')): ?>
        <div class="alert alert-success"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($message = Session::flash('error')): ?>
        <div class="alert alert-danger"><?= e($message); ?></div>
    <?php endif; ?>

    <form class="role-switcher" method="get" action="<?= url('roles/permissions'); ?>">
        <label>
            Rôle à configurer
            <select name="role_id" onchange="this.form.submit()">
                <?php foreach ($roles as $item): ?>
                    <option value="<?= (int) $item['id']; ?>" <?= (int) $item['id'] === (int) $role['id'] ? 'selected' : ''; ?>>
                        <?= e($item['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <form method="post" action="<?= url('roles/permissions'); ?>">
        <?= Csrf::field(); ?>
        <input type="hidden" name="role_id" value="<?= (int) $role['id']; ?>">

        <div class="permissions-grid">
            <?php foreach ($permissions as $module => $items): ?>
                <fieldset class="permission-group">
                    <legend><?= e($module); ?></legend>
                    <?php foreach ($items as $permission): ?>
                        <label class="check-row">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="<?= (int) $permission['id']; ?>"
                                <?= in_array($permission['name'], $rolePermissions, true) ? 'checked' : ''; ?>
                                <?= $role['slug'] === 'super-admin' ? 'disabled' : ''; ?>
                            >
                            <span>
                                <strong><?= e($permission['label']); ?></strong>
                                <small>Autorisation système : <?= e($permission['name']); ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" <?= $role['slug'] === 'super-admin' ? 'disabled' : ''; ?>>Enregistrer les permissions</button>
        </div>
    </form>
</section>
