<section class="executive-hero">
    <div>
        <span class="section-kicker">Construction</span>
        <h2>Cockpit du portefeuille projets</h2>
        <p>Suivi consolidé des chantiers, marges, coûts consommés et risques opérationnels.</p>
    </div>
    <div class="executive-summary"><span>Avancement moyen</span><strong><?= number_format($dashboard['average_progress'], 1, ',', ' '); ?>%</strong><small><?= (int) $dashboard['active_count']; ?> projets actifs</small></div>
</section>

<section class="kpi-grid executive-kpis">
    <article class="kpi-card"><div class="kpi-meta"><span>Contrats signés</span><span class="badge badge-success">CA</span></div><strong><?= money($dashboard['contract_total']); ?></strong><small>Portefeuille construction</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Coût prévisionnel</span><span class="badge badge-neutral">Budget</span></div><strong><?= money($dashboard['forecast_total']); ?></strong><small>Base de contrôle</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Coût réel</span><span class="badge badge-warning">Consommé</span></div><strong><?= money($dashboard['actual_total']); ?></strong><small>Dépenses + consommations</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Projets critiques</span><span class="badge badge-danger">Alertes</span></div><strong><?= (int) $dashboard['critical_count']; ?></strong><small>Retard ou écart coût</small></article>
</section>

<section class="decision-grid">
    <?php foreach ($dashboard['projects'] as $project): ?>
        <article class="panel">
            <div class="panel-header"><div><span class="section-kicker"><?= e($project['reference']); ?></span><h3><?= e($project['name']); ?></h3></div><span class="badge <?= project_status_badge($project['status']); ?>"><?= e(status_label($project['status'])); ?></span></div>
            <div class="stack-list">
                <div class="project-row"><div class="project-copy"><strong>Avancement physique</strong><small><?= e($project['client_name']); ?></small></div><div class="progress-box"><span><?= number_format($project['metrics']['physical_progress'], 1, ',', ' '); ?>%</span><div class="progress-track"><i style="width: <?= (int) $project['metrics']['physical_progress']; ?>%"></i></div></div></div>
                <div class="stack-item"><div><strong>Marge réelle estimée</strong><small>Marge prévue <?= money($project['metrics']['forecast_margin']); ?></small></div><span class="amount-text"><?= money($project['metrics']['actual_margin']); ?></span></div>
                <a class="btn btn-secondary full-width" href="<?= url('construction/projects/show?id=' . (int) $project['id']); ?>">Ouvrir cockpit</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
