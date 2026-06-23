<?php
$chartPayload = json_encode($revenueExpenseChart);
$servicePayload = json_encode($serviceExpenses);
?>

<section class="executive-hero">
    <div>
        <span class="section-kicker">Direction générale</span>
        <h2>Vue instantanée de WAKE SERVICES</h2>
        <p>Indicateurs clés, risques opérationnels et priorités financières pour décider vite, même en déplacement.</p>
    </div>
    <button class="executive-summary dashboard-detail-trigger" type="button" data-dashboard-detail="global_margin">
        <span>Marge estimée</span>
        <strong><?= number_format((float) $summary['margin'], 1, ',', ' '); ?>%</strong>
        <small>Revenus <?= money($summary['revenues']); ?> / dépenses <?= money($summary['expenses']); ?></small>
        <em><i class="bi bi-arrows-angle-expand"></i> Voir le détail</em>
    </button>
</section>

<?php if ($message = Session::flash('success')): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<section class="kpi-grid executive-kpis">
    <?php foreach ($kpis as $kpi): ?>
        <article
            class="kpi-card decision-card dashboard-detail-trigger"
            data-dashboard-detail="<?= e($kpi['key']); ?>"
            role="button"
            tabindex="0"
            aria-label="Afficher le détail : <?= e($kpi['label']); ?>"
        >
            <div class="kpi-meta">
                <span class="kpi-label">
                    <span class="kpi-icon" aria-hidden="true"><i class="bi bi-<?= e($kpi['icon'] ?? 'activity'); ?>"></i></span>
                    <?= e($kpi['label']); ?>
                </span>
                <span class="badge <?= e($kpi['badge']); ?>"><?= e($kpi['status']); ?></span>
            </div>
            <strong><?= e($kpi['value']); ?></strong>
            <small><?= e($kpi['trend']); ?></small>
            <span class="detail-hint"><i class="bi bi-arrows-angle-expand"></i> Détails</span>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid executive-grid">
    <div class="panel panel-wide dashboard-detail-trigger" data-dashboard-detail="performance_chart" role="button" tabindex="0" aria-label="Afficher le détail du graphique revenus et dépenses">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Performance mensuelle</span>
                <h3>Revenus vs dépenses</h3>
            </div>
            <span class="detail-hint"><i class="bi bi-arrows-angle-expand"></i> Explorer</span>
        </div>
        <div class="chart-wrap">
            <canvas
                class="chart-canvas"
                data-chart="revenue-expense"
                data-payload="<?= e($chartPayload); ?>"
                aria-label="Graphique revenus versus dépenses"
            ></canvas>
        </div>
    </div>

    <aside class="panel dashboard-detail-trigger" data-dashboard-detail="service_expenses" role="button" tabindex="0" aria-label="Afficher le détail des dépenses par service">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Structure des coûts</span>
                <h3>Dépenses par service</h3>
            </div>
            <span class="detail-hint"><i class="bi bi-arrows-angle-expand"></i> Explorer</span>
        </div>
        <div class="chart-wrap compact-chart">
            <canvas
                class="chart-canvas"
                data-chart="service-expenses"
                data-payload="<?= e($servicePayload); ?>"
                aria-label="Graphique dépenses par service"
            ></canvas>
        </div>
        <div class="legend-list">
            <?php foreach ($serviceExpenses as $item): ?>
                <div class="legend-item">
                    <span style="--legend-color: <?= e($item['color']); ?>"></span>
                    <strong><?= e($item['label']); ?></strong>
                    <em><?= number_format((float) $item['value'], 0, ',', ' '); ?> USD</em>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
</section>

<section class="decision-grid">
    <article class="panel dashboard-detail-trigger" data-dashboard-detail="recent_requests" role="button" tabindex="0" aria-label="Afficher toutes les demandes financières récentes">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Finance</span>
                <h3>Dernières demandes financières</h3>
            </div>
            <span class="detail-hint"><i class="bi bi-arrows-angle-expand"></i> <?= count($financialRequests); ?> récentes</span>
        </div>
        <div class="stack-list">
            <?php foreach ($financialRequests as $request): ?>
                <div class="stack-item">
                    <div>
                        <strong><?= e($request['ref']); ?></strong>
                        <small><?= e($request['service']); ?> · <?= e($request['amount']); ?></small>
                    </div>
                    <span class="badge <?= e($request['badge']); ?>"><?= e(status_label($request['status'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel dashboard-detail-trigger" data-dashboard-detail="critical_projects" role="button" tabindex="0" aria-label="Afficher les projets critiques">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Construction</span>
                <h3>Projets critiques</h3>
            </div>
            <span class="detail-hint"><i class="bi bi-arrows-angle-expand"></i> <?= count($criticalProjects); ?> suivis</span>
        </div>
        <div class="stack-list">
            <?php foreach ($criticalProjects as $project): ?>
                <div class="project-row">
                    <div class="project-copy">
                        <strong><?= e($project['name']); ?></strong>
                        <small><?= e($project['risk']); ?> · <?= e($project['owner']); ?></small>
                    </div>
                    <div class="progress-box">
                        <span><?= (int) $project['progress']; ?>%</span>
                        <div class="progress-track"><i style="width: <?= (int) $project['progress']; ?>%"></i></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel dashboard-detail-trigger" data-dashboard-detail="overdue_invoices" role="button" tabindex="0" aria-label="Afficher les factures en retard">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Recouvrement</span>
                <h3>Factures en retard</h3>
            </div>
            <span class="detail-hint"><i class="bi bi-arrows-angle-expand"></i> <?= count($overdueInvoices); ?> ouvertes</span>
        </div>
        <div class="stack-list">
            <?php foreach ($overdueInvoices as $invoice): ?>
                <div class="stack-item">
                    <div>
                        <strong><?= e($invoice['client']); ?></strong>
                        <small><?= e($invoice['invoice']); ?> · <?= e($invoice['days']); ?></small>
                    </div>
                    <span class="amount-text"><?= e($invoice['amount']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel alerts-panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Priorités dirigeant</span>
            <h3>Alertes importantes</h3>
        </div>
    </div>
    <div class="alert-grid">
        <?php foreach ($importantAlerts as $alert): ?>
            <?php
            $alertDetail = [
                'Finance' => 'pending_requests',
                'Relance' => 'unpaid_invoices',
                'Logistique' => 'pending_deliveries',
                'Construction' => 'critical_projects',
                'OK' => 'global_margin',
            ][$alert['level']] ?? 'global_margin';
            ?>
            <div
                class="alert-card dashboard-detail-trigger"
                data-dashboard-detail="<?= e($alertDetail); ?>"
                role="button"
                tabindex="0"
                aria-label="Afficher le détail de l’alerte <?= e($alert['level']); ?>"
            >
                <span class="badge <?= e($alert['badge']); ?>"><?= e($alert['level']); ?></span>
                <p><?= e($alert['text']); ?></p>
                <span class="detail-hint"><i class="bi bi-arrow-up-right"></i> Examiner</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal dashboard-detail-modal" id="dashboard-detail-modal" aria-hidden="true" data-dashboard-detail-modal>
    <div class="modal-card modal-dashboard" role="dialog" aria-modal="true" aria-labelledby="dashboard-detail-title">
        <div class="modal-header">
            <div>
                <span class="section-kicker">Analyse détaillée</span>
                <h3 id="dashboard-detail-title" data-dashboard-detail-title>Détail</h3>
                <p data-dashboard-detail-description></p>
            </div>
            <button class="icon-button" type="button" data-dashboard-detail-close aria-label="Fermer">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="dashboard-detail-state" data-dashboard-detail-state>
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            <span>Chargement des données...</span>
        </div>

        <div class="dashboard-detail-content" data-dashboard-detail-content hidden>
            <div class="dashboard-detail-toolbar">
                <span data-dashboard-detail-count></span>
                <div class="toolbar-actions">
                    <a class="btn btn-secondary" href="#" data-dashboard-export-excel>
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                    <a class="btn btn-primary" href="#" data-dashboard-export-pdf>
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="table-responsive dashboard-detail-table-wrap">
                <table class="data-table dashboard-detail-table">
                    <thead data-dashboard-detail-head></thead>
                    <tbody data-dashboard-detail-body></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
