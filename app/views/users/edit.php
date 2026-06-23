<section class="panel form-panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Administration</span>
            <h2>Modifier l’utilisateur</h2>
            <p class="ui-page-intro">Mettez à jour son rôle, son statut ou ses informations de connexion.</p>
        </div>
        <a class="btn btn-secondary" href="<?= url('users'); ?>"><i class="bi bi-arrow-left"></i> Retour aux utilisateurs</a>
    </div>

    <form class="form-grid" method="post" action="<?= url('users/update'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <input type="hidden" name="id" value="<?= (int) $user['id']; ?>">
        <?php require VIEW_PATH . '/users/form.php'; ?>
        <div class="form-actions">
            <a class="btn btn-secondary" href="<?= url('users'); ?>">Annuler</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Enregistrer les modifications</button>
        </div>
    </form>
</section>
