<section class="panel form-panel wide-form">
    <div class="panel-header"><div><span class="section-kicker">Construction</span><h2>Créer un projet de construction</h2><p class="ui-page-intro">Définissez le périmètre, le budget, les travaux et les consommables prévisionnels.</p></div><a class="btn btn-secondary" href="<?= url('construction/projects'); ?>"><i class="bi bi-arrow-left"></i> Retour aux projets</a></div>
    <?php if ($errors): ?><div class="alert alert-danger">Veuillez corriger les champs obligatoires.</div><?php endif; ?>
    <form method="post" action="<?= url('construction/projects/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <?php require VIEW_PATH . '/construction/projects/form.php'; ?>
        <div class="line-items">
            <div class="line-items-header"><h3>Travaux à réaliser</h3></div>
            <?php $taskRows = $oldTasks ?? [['name' => '', 'unit' => 'm2', 'planned_quantity' => 0, 'planned_cost' => 0, 'planned_duration_days' => 0]]; ?>
            <?php foreach ($taskRows as $row): ?><?php require VIEW_PATH . '/construction/projects/task_row.php'; ?><?php endforeach; ?>
        </div>
        <div class="line-items">
            <div class="line-items-header"><h3>Consommables prévisionnels</h3></div>
            <?php $materialRows = $oldMaterials ?? [['construction_material_id' => '', 'planned_quantity' => 0, 'planned_cost' => 0]]; ?>
            <?php foreach ($materialRows as $row): ?><?php require VIEW_PATH . '/construction/projects/material_row.php'; ?><?php endforeach; ?>
        </div>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('construction/projects'); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-building-add"></i> Créer le projet</button></div>
    </form>
</section>
