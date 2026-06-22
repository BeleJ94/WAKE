<section class="panel form-panel wide-form">
    <div class="panel-header"><div><span class="section-kicker">Construction</span><h2>Modifier le projet</h2><p class="ui-page-intro">Actualisez les informations contractuelles, le planning et les paramètres financiers.</p></div><a class="btn btn-secondary" href="<?= url('construction/projects/show?id=' . (int) $project['id']); ?>"><i class="bi bi-arrow-left"></i> Retour au cockpit</a></div>
    <form method="post" action="<?= url('construction/projects/update'); ?>" data-validate>
        <?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $project['id']; ?>">
        <?php $old = $project; require VIEW_PATH . '/construction/projects/form.php'; ?>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('construction/projects/show?id=' . (int) $project['id']); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Enregistrer les modifications</button></div>
    </form>
</section>
