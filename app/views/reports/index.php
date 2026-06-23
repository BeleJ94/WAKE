<?php
$query = array_merge($_GET, ['type' => $currentType]);
$exportUrl = url('reports/export?' . http_build_query($query));
$chartPayload = json_encode($report['chart'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>

<section class="executive-hero reports-hero">
    <div>
        <span class="section-kicker">Centre de rapports</span>
        <h2><?= e($report['title']); ?></h2>
        <p><?= e($report['description']); ?></p>
    </div>
    <div class="executive-summary">
        <span>Total indicateur</span>
        <strong><?= money($report['summary']['total']); ?></strong>
        <small><?= (int) $report['summary']['rows']; ?> lignes analysées</small>
    </div>
</section>

<section class="panel report-filters-panel">
    <form class="report-filters" method="get" action="<?= url('reports'); ?>">
        <label>Rapport
            <select name="type" onchange="this.form.submit()">
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key); ?>" <?= $key === $currentType ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Début <input type="date" name="start_date" value="<?= e($filters['start_date']); ?>"></label>
        <label>Fin <input type="date" name="end_date" value="<?= e($filters['end_date']); ?>"></label>
        <label>Client
            <select name="client_id">
                <option value="0">Tous</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id']; ?>" <?= (int) $filters['client_id'] === (int) $client['id'] ? 'selected' : ''; ?>><?= e($client['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Service
            <select name="service">
                <option value="">Tous</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?= e($service['name']); ?>" <?= $filters['service'] === $service['name'] ? 'selected' : ''; ?>><?= e($service['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Statut
            <select name="status">
                <option value="">Tous</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status); ?>" <?= $filters['status'] === $status ? 'selected' : ''; ?>><?= e(status_label($status)); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="report-filter-actions">
            <button class="btn btn-secondary" type="submit"><i class="bi bi-funnel"></i> Filtrer</button>
            <a class="btn btn-primary" href="<?= e($exportUrl); ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Export Excel</a>
            <button class="btn btn-secondary no-print" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </form>
</section>

<section class="kpi-grid executive-kpis">
    <article class="kpi-card"><div class="kpi-meta"><span>Lignes</span><span class="badge badge-neutral">Données</span></div><strong><?= (int) $report['summary']['rows']; ?></strong><small>Résultats filtrés</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Total</span><span class="badge badge-success">Période</span></div><strong><?= money($report['summary']['total']); ?></strong><small>Indicateur principal</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Moyenne</span><span class="badge badge-warning">Analyse</span></div><strong><?= money($report['summary']['average']); ?></strong><small>Par ligne</small></article>
    <article class="kpi-card"><div class="kpi-meta"><span>Export</span><span class="badge badge-success">Excel</span></div><strong>Prêt</strong><small>Données exportables</small></article>
</section>

<section class="report-layout">
    <article class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Graphique</span>
                <h3>Top indicateurs</h3>
            </div>
        </div>
        <div class="chart-wrap report-chart">
            <canvas data-chart="report-bars" data-payload='<?= e($chartPayload); ?>'></canvas>
        </div>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Catalogue</span>
                <h3>Rapports disponibles</h3>
            </div>
        </div>
        <div class="report-menu">
            <?php foreach ($types as $key => $label): ?>
                <?php $itemQuery = array_merge($_GET, ['type' => $key]); ?>
                <a class="<?= $key === $currentType ? 'is-active' : ''; ?>" href="<?= url('reports?' . http_build_query($itemQuery)); ?>">
                    <span><?= e($label); ?></span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
</section>

<section class="panel report-table-panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Analyse tabulaire</span>
            <h3>Données détaillées</h3>
        </div>
        <span class="badge badge-neutral"><?= (int) $report['summary']['rows']; ?> lignes</span>
    </div>
    <div class="filters-bar">
        <label>Recherche <input data-table-search placeholder="Filtrer le tableau"></label>
    </div>
    <div class="table-responsive">
        <table class="data-table" data-enhanced-table>
            <thead>
                <tr>
                    <?php foreach ($report['columns'] as $column): ?>
                        <th><?= e($column); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['rows'] as $row): ?>
                    <tr>
                        <?php foreach ($row as $key => $value): ?>
                            <td class="<?= in_array($key, $report['amount_keys'], true) ? 'text-right' : ''; ?>">
                                <?php if (in_array($key, $report['amount_keys'], true)): ?>
                                    <?= money($value); ?>
                                <?php elseif ($key === 'status'): ?>
                                    <span class="badge <?= sales_status_badge((string) $value); ?>"><?= e(status_label((string) $value)); ?></span>
                                <?php elseif ($key === 'source_type'): ?>
                                    <?= e(invoice_source_label((string) $value)); ?>
                                <?php elseif (is_numeric($value) && stripos((string) $key, 'percent') !== false): ?>
                                    <?= number_format((float) $value, 1, ',', ' '); ?>%
                                <?php else: ?>
                                    <?= e((string) $value); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if ($report['rows'] === []): ?>
                    <tr><td colspan="<?= count($report['columns']); ?>"><div class="empty-state compact"><i class="bi bi-inbox"></i><p>Aucune donnée disponible pour ces filtres.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
