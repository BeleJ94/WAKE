<section class="panel form-panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Administration</span>
            <h2>Créer un utilisateur</h2>
            <p class="ui-page-intro">Attribuez un rôle et définissez les informations de connexion du nouvel utilisateur.</p>
        </div>
        <a class="btn btn-secondary" href="<?= url('users'); ?>"><i class="bi bi-arrow-left"></i> Retour aux utilisateurs</a>
    </div>

    <form class="form-grid" method="post" action="<?= url('users/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <?php require VIEW_PATH . '/users/form.php'; ?>
        <div class="form-actions">
            <a class="btn btn-secondary" href="<?= url('users'); ?>">Annuler</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Créer l’utilisateur</button>
        </div>
    </form>
</section>
