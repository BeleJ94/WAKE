<section class="executive-hero construction-cockpit">
    <div>
        <span class="section-kicker"><?= e($project['reference']); ?></span>
        <h2><?= e($project['name']); ?></h2>
        <p><?= e($project['client_name']); ?> · <?= e($project['location']); ?> · Chef projet <?= e($project['manager_name'] ?? '-'); ?></p>
    </div>
    <div class="executive-summary"><span>Marge réelle estimée</span><strong><?= money($project['metrics']['actual_margin']); ?></strong><small>Prévue <?= money($project['metrics']['forecast_margin']); ?></small></div>
</section>

<?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>

<section class="kpi-grid executive-kpis">
    <article class="kpi-card"><div class="kpi-meta"><span>Avancement global</span><span class="badge badge-success">Physique</span></div><strong><?= number_format($project['metrics']['physical_progress'], 1, ',', ' '); ?>%</strong><small>Travaux pondérés par coût</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Budget consommé</span><span class="badge badge-warning">Financier</span></div><strong><?= number_format($project['metrics']['financial_progress'], 1, ',', ' '); ?>%</strong><small><?= money($project['metrics']['actual_cost']); ?> consommé</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Écart consommation</span><span class="badge <?= $project['metrics']['consumption_variance'] >= 0 ? 'badge-success' : 'badge-danger'; ?>">Prévu vs réel</span></div><strong><?= money($project['metrics']['consumption_variance']); ?></strong><small>Prévu <?= money($project['metrics']['planned_consumption']); ?></small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Retard éventuel</span><span class="badge <?= $project['metrics']['delay_days'] > 0 ? 'badge-danger' : 'badge-success'; ?>">Planning</span></div><strong><?= (int) $project['metrics']['delay_days']; ?> j</strong><small>Fin prévue <?= e($project['end_date']); ?></small></article>
</section>

<section class="panel alerts-panel">
    <div class="panel-header">
        <div><span class="section-kicker">Alertes cockpit</span><h3>Dépassements et risques</h3></div>
        <div class="action-group">
            <?php if (Auth::can('sites.reports.create')): ?><a class="btn btn-primary" href="<?= url('construction/daily_reports/create?project_id=' . (int) $project['id']); ?>">Nouveau rapport journalier</a><?php endif; ?>
            <?php if (Auth::can('projects.edit')): ?><a class="btn btn-secondary" href="<?= url('construction/projects/edit?id=' . (int) $project['id']); ?>">Modifier</a><?php endif; ?>
        </div>
    </div>
    <div class="alert-grid">
        <?php foreach ($alerts as $alert): ?><div class="alert-card"><span class="badge <?= e($alert['badge']); ?>"><?= e($alert['label']); ?></span><p><?= e($alert['text']); ?></p></div><?php endforeach; ?>
    </div>
</section>

<section class="dashboard-grid executive-grid">
    <div class="panel panel-wide">
        <div class="panel-header"><div><span class="section-kicker">Travaux</span><h3>Travaux en cours</h3></div></div>
        <div class="stack-list">
            <?php foreach ($tasks as $task): ?>
                <div class="project-row">
                    <div class="project-copy"><strong><?= e($task['name']); ?></strong><small><?= e($task['planned_quantity']); ?> <?= e($task['unit']); ?> · coût prévu <?= money($task['planned_cost']); ?> · durée <?= (int) $task['planned_duration_days']; ?> j</small></div>
                    <div class="progress-box"><span><?= number_format((float) $task['progress_percent'], 1, ',', ' '); ?>%</span><div class="progress-track"><i style="width: <?= (int) $task['progress_percent']; ?>%"></i></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <aside class="panel">
        <div class="panel-header"><div><span class="section-kicker">Consommables</span><h3>Prévu vs réel</h3></div></div>
        <div class="stack-list">
            <?php foreach ($materials as $material): ?>
                <div class="stack-item"><div><strong><?= e($material['name']); ?></strong><small>Prévu <?= e($material['planned_quantity']); ?> <?= e($material['unit']); ?> · réel <?= e($material['actual_quantity']); ?></small></div><span class="amount-text"><?= money($material['actual_cost']); ?></span></div>
            <?php endforeach; ?>
        </div>
    </aside>
</section>

<section class="decision-grid">
    <article class="panel">
        <div class="panel-header"><div><span class="section-kicker">Dépenses</span><h3>Dépenses projet</h3></div></div>
        <div class="stack-list">
            <?php foreach (array_slice($expenses, 0, 6) as $expense): ?><div class="stack-item"><div><strong><?= e($expense['category']); ?></strong><small><?= e($expense['description']); ?> · <?= e($expense['expense_date']); ?></small></div><span class="amount-text"><?= money($expense['amount']); ?></span></div><?php endforeach; ?>
            <?php if ($expenses === []): ?><div class="empty-state compact"><p>Aucune dépense enregistrée.</p></div><?php endif; ?>
        </div>
    </article>
    <article class="panel">
        <div class="panel-header"><div><span class="section-kicker">Rapports</span><h3>Rapports journaliers</h3></div></div>
        <div class="stack-list">
            <?php foreach (array_slice($reports, 0, 6) as $report): ?><div class="stack-item"><div><strong><?= e($report['report_date']); ?></strong><small><?= e($report['weather'] ?? ''); ?> · <?= e($report['created_by_name']); ?></small></div><a class="btn btn-secondary" href="<?= url('construction/daily_reports/show?id=' . (int) $report['id']); ?>">Voir</a></div><?php endforeach; ?>
            <?php if ($reports === []): ?><div class="empty-state compact"><p>Aucun rapport journalier.</p></div><?php endif; ?>
        </div>
    </article>
    <article class="panel">
        <div class="panel-header"><div><span class="section-kicker">Photos</span><h3>Galerie chantier</h3></div></div>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?><a href="<?= rtrim(PUBLIC_URL, '/') . '/' . e($photo['file_path']); ?>" target="_blank"><img src="<?= rtrim(PUBLIC_URL, '/') . '/' . e($photo['file_path']); ?>" alt="<?= e($photo['caption'] ?? 'Photo chantier'); ?>"></a><?php endforeach; ?>
            <?php if ($photos === []): ?><div class="empty-state compact"><p>Aucune photo chantier.</p></div><?php endif; ?>
        </div>
    </article>
</section>

