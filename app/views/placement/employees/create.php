<section class="panel form-panel">
    <div class="panel-header">
        <div><span class="section-kicker">Placement de personnel</span><h2>Enregistrer un nouvel agent</h2><p class="ui-page-intro">Créez le profil administratif et définissez le coût de référence de l’agent.</p></div>
        <a class="btn btn-secondary" href="<?= url('placement/employees/index'); ?>"><i class="bi bi-arrow-left"></i> Retour aux agents</a>
    </div>
    <?php if ($errors): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($errors['required']); ?></div><?php endif; ?>
    <form class="form-grid" method="post" action="<?= url('placement/employees/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label><span class="field-label">Prénom <em>Obligatoire</em></span><input name="first_name" required value="<?= e($old['first_name'] ?? ''); ?>"></label>
        <label><span class="field-label">Nom <em>Obligatoire</em></span><input name="last_name" required value="<?= e($old['last_name'] ?? ''); ?>"></label>
        <label><span class="field-label">Téléphone</span><input name="phone" value="<?= e($old['phone'] ?? ''); ?>" placeholder="+243 …"></label>
        <label><span class="field-label">Adresse e-mail</span><input type="email" name="email" value="<?= e($old['email'] ?? ''); ?>"></label>
        <label><span class="field-label">Fonction principale <em>Obligatoire</em></span><input name="job_title" required value="<?= e($old['job_title'] ?? ''); ?>" placeholder="Ex. Agent de sécurité"></label>
        <label><span class="field-label">Coût mensuel de référence <em>Obligatoire</em></span><input type="number" step="0.01" min="0" name="base_salary" required value="<?= e((string) ($old['base_salary'] ?? 0)); ?>"></label>
        <label><span class="field-label">Date d’embauche</span><input type="date" name="hired_at" value="<?= e($old['hired_at'] ?? ''); ?>"></label>
        <label><span class="field-label">Statut</span><select name="status"><option value="active">Actif</option><option value="inactive">Inactif</option></select></label>
        <label class="span-2"><span class="field-label">Notes internes</span><textarea name="notes" rows="3" placeholder="Compétences, disponibilités ou informations administratives…"><?= e($old['notes'] ?? ''); ?></textarea></label>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('placement/employees/index'); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-person-check"></i> Créer l’agent</button></div>
    </form>
</section>
