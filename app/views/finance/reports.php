<section class="executive-hero">
    <div>
        <span class="section-kicker">Rapports Finance</span>
        <h2>Synthèse Finance & Trésorerie</h2>
        <p>Vue consolidée des soldes, demandes en attente et paiements du mois.</p>
    </div>
    <div class="executive-summary"><span>Demandes en attente</span><strong><?= (int) $metrics['pending_count']; ?></strong><small><?= money($metrics['pending_amount']); ?> à arbitrer</small></div>
</section>

<section class="kpi-grid executive-kpis">
    <article class="kpi-card"><div class="kpi-meta"><span>Caisses</span><span class="badge badge-success">Disponible</span></div><strong><?= money($totals['Caisse']); ?></strong><small>Solde total caisses</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Banques</span><span class="badge badge-success">Disponible</span></div><strong><?= money($totals['Banque']); ?></strong><small>Solde total banques</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Mobile Money</span><span class="badge badge-neutral">Portefeuilles</span></div><strong><?= money($totals['Mobile Money']); ?></strong><small>Solde total des portefeuilles</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Dépenses payées</span><span class="badge badge-warning">Mois</span></div><strong><?= money($metrics['paid_month']); ?></strong><small>Mouvements sortants</small></article>
</section>

<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Analyse complémentaire</span><h3>Rapports détaillés</h3></div><a class="btn btn-primary" href="<?= url('reports'); ?>"><i class="bi bi-bar-chart-line"></i> Consulter les rapports</a></div>
    <div class="empty-state compact"><i class="bi bi-graph-up-arrow"></i><h4>Approfondir l’analyse financière</h4><p>Utilisez le centre de rapports pour filtrer les périodes, analyser les données et générer vos exports.</p></div>
</section>
