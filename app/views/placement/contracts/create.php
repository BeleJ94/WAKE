<section class="panel form-panel wide-form">
    <div class="panel-header"><div><span class="section-kicker">Placement de personnel</span><h2>Créer un contrat de placement</h2><p class="ui-page-intro">Définissez le client, la période contractuelle et les agents affectés.</p></div><a class="btn btn-secondary" href="<?= url('placement/contracts/index'); ?>"><i class="bi bi-arrow-left"></i> Retour aux contrats</a></div>
    <?php if ($errors): ?><div class="alert alert-danger"><?= e($errors['required']); ?></div><?php endif; ?>
    <form method="post" action="<?= url('placement/contracts/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <div class="form-grid">
            <label>Client <input name="client_name" required value="<?= e($old['client_name'] ?? ''); ?>" placeholder="Raison sociale du client"></label>
            <label>Contact principal <input name="client_contact" value="<?= e($old['client_contact'] ?? ''); ?>"></label>
            <label>Téléphone du client <input name="client_phone" value="<?= e($old['client_phone'] ?? ''); ?>"></label>
            <label>Jour de facturation <input type="number" min="1" max="31" name="billing_day" value="<?= e((string) ($old['billing_day'] ?? 30)); ?>"></label>
            <label>Date de début <input type="date" name="start_date" required value="<?= e($old['start_date'] ?? ''); ?>"></label>
            <label>Date de fin <input type="date" name="end_date" value="<?= e($old['end_date'] ?? ''); ?>"></label>
            <label>Statut <select name="status"><option value="Active">Actif</option><option value="Draft">Brouillon</option><option value="Suspended">Suspendu</option><option value="Expired">Expiré</option><option value="Closed">Clôturé</option></select></label>
            <label class="span-2">Notes contractuelles <textarea name="notes" rows="3" placeholder="Conditions particulières ou informations de suivi…"><?= e($old['notes'] ?? ''); ?></textarea></label>
        </div>
        <div class="line-items"><div class="line-items-header"><h3>Affectation des agents</h3></div>
            <div class="fund-item-row construction-row">
                <label>Agent <select name="assignments[employee_id][]" required><option value="">Sélectionner</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id']; ?>"><?= e($employee['first_name'] . ' ' . $employee['last_name']); ?> · <?= e($employee['job_title']); ?></option><?php endforeach; ?></select></label>
                <label>Poste occupé <input name="assignments[position_title][]" required></label>
                <label>Coût agent <input type="number" step="0.01" min="0" name="assignments[agent_cost][]" required></label>
                <label>Tarif client <input type="number" step="0.01" min="0" name="assignments[client_rate][]" required></label>
                <label>Date de début <input type="date" name="assignments[start_date][]"></label>
            </div>
        </div>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('placement/contracts/index'); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-check"></i> Créer le contrat</button></div>
    </form>
</section>
