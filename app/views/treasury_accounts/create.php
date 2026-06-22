<section class="panel form-panel">
    <div class="panel-header">
        <div><span class="section-kicker">Trésorerie</span><h2>Nouveau compte</h2></div>
        <a class="btn btn-secondary" href="<?= url('treasury_accounts'); ?>">Retour</a>
    </div>
    <form class="form-grid" method="post" action="<?= url('treasury_accounts/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label>Nom du compte <input class="<?= isset($errors['name']) ? 'is-invalid' : ''; ?>" name="name" required value="<?= e($old['name'] ?? ''); ?>"></label>
        <label>Type
            <select name="type" required class="<?= isset($errors['type']) ? 'is-invalid' : ''; ?>">
                <?php foreach (['Caisse', 'Banque', 'Mobile Money', 'Autre'] as $type): ?>
                    <option value="<?= e($type); ?>" <?= ($old['type'] ?? '') === $type ? 'selected' : ''; ?>><?= e($type); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Devise <input name="currency" required value="<?= e($old['currency'] ?? 'USD'); ?>"></label>
        <label>Solde initial <input type="number" min="0" step="0.01" name="opening_balance" required value="<?= e((string) ($old['opening_balance'] ?? 0)); ?>"></label>
        <label>Responsable
            <select name="responsible_user_id">
                <option value="">Aucun</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id']; ?>" <?= (int) ($old['responsible_user_id'] ?? 0) === (int) $user['id'] ? 'selected' : ''; ?>><?= e($user['name']); ?> · <?= e($user['email']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Statut
            <select name="status"><option value="active">Actif</option><option value="inactive">Inactif</option></select>
        </label>
        <label class="span-2">Notes <textarea name="notes" rows="3"><?= e($old['notes'] ?? ''); ?></textarea></label>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('treasury_accounts'); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Créer le compte</button></div>
    </form>
</section>
