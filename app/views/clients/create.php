<section class="panel form-panel">
    <div class="panel-header">
        <div><span class="section-kicker">Parcours client</span><h2>Créer un nouveau client</h2><p class="ui-page-intro">Centralisez les coordonnées, informations fiscales et données de suivi du client.</p></div>
        <a class="btn btn-secondary" href="<?= url('clients/index'); ?>"><i class="bi bi-arrow-left"></i> Retour aux clients</a>
    </div>
    <form class="form-grid" method="post" action="<?= url('clients/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label><span class="field-label">Raison sociale <em>Obligatoire</em></span><input name="name" required value="<?= e($old['name'] ?? ''); ?>" placeholder="Ex. Horizon Mining SARL"></label>
        <label><span class="field-label">Personne de contact</span><input name="contact_name" value="<?= e($old['contact_name'] ?? ''); ?>" placeholder="Nom du contact principal"></label>
        <label><span class="field-label">Téléphone</span><input name="phone" value="<?= e($old['phone'] ?? ''); ?>" placeholder="+243 …"></label>
        <label><span class="field-label">Adresse e-mail</span><input type="email" name="email" value="<?= e($old['email'] ?? ''); ?>" placeholder="contact@entreprise.cd"></label>
        <label class="span-2"><span class="field-label">Adresse</span><input name="address" value="<?= e($old['address'] ?? ''); ?>" placeholder="Ville, commune et adresse complète"></label>
        <label><span class="field-label">Numéro fiscal</span><input name="tax_number" value="<?= e($old['tax_number'] ?? ''); ?>" placeholder="Identifiant fiscal du client"></label>
        <label><span class="field-label">Statut du client</span><select name="status"><option value="active">Actif</option><option value="inactive">Inactif</option></select></label>
        <label class="span-2"><span class="field-label">Notes internes</span><textarea name="notes" rows="3" placeholder="Préférences, conditions particulières ou informations utiles…"><?= e($old['notes'] ?? ''); ?></textarea></label>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('clients/index'); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Créer le client</button></div>
    </form>
</section>
