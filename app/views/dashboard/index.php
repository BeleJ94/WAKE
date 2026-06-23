<?php
$chartPayload = json_encode($revenueExpenseChart);
$servicePayload = json_encode($serviceExpenses);
$kpisByKey = [];

foreach ($kpis as $kpi) {
    $kpisByKey[$kpi['key']] = $kpi;
}

$primaryKpiKeys = [
    'monthly_revenue',
    'global_margin',
    'monthly_expenses',
    'bank_accounts',
    'unpaid_invoices',
    'project_progress',
];

$primaryAlert = $importantAlerts[0] ?? [
    'level' => 'OK',
    'badge' => 'badge-success',
    'text' => 'Aucune alerte critique détectée sur les données actuelles.',
];

$alertDetail = [
    'Finance' => 'pending_requests',
    'Relance' => 'unpaid_invoices',
    'Logistique' => 'pending_deliveries',
    'Construction' => 'critical_projects',
    'OK' => 'global_margin',
][$primaryAlert['level']] ?? 'global_margin';

$monthNames = [
    1 => 'janvier',
    'février',
    'mars',
    'avril',
    'mai',
    'juin',
    'juillet',
    'août',
    'septembre',
    'octobre',
    'novembre',
    'décembre',
];
$periodLabel = ucfirst($monthNames[(int) date('n')]) . ' ' . date('Y');
?>

<section class="cfo-dashboard">
    <header class="cfo-header">
        <div>
            <h2>Tableau de bord financier</h2>
            <p><?= e($periodLabel); ?> · Consolidé · USD · WAKE SERVICES</p>
        </div>
        <span class="cfo-period-status">
            <i aria-hidden="true"></i>Période en cours
        </span>
    </header>

    <?php if ($message = Session::flash('success')): ?>
        <div class="alert alert-success"><?= e($message); ?></div>
    <?php endif; ?>

    <section class="cfo-kpi-grid" aria-label="Indicateurs financiers principaux">
        <?php foreach ($primaryKpiKeys as $index => $key): ?>
            <?php if (!isset($kpisByKey[$key])) continue; ?>
            <?php $kpi = $kpisByKey[$key]; ?>
            <button
                class="cfo-kpi cfo-kpi-tone-<?= ($index % 3) + 1; ?> dashboard-detail-trigger"
                type="button"
                data-dashboard-detail="<?= e($kpi['key']); ?>"
                aria-label="Afficher le détail : <?= e($kpi['label']); ?>"
            >
                <span><?= e($kpi['label']); ?></span>
                <strong><?= e($kpi['value']); ?></strong>
                <small><?= e($kpi['trend']); ?></small>
            </button>
        <?php endforeach; ?>
    </section>

    <button
        class="cfo-alert dashboard-detail-trigger"
        type="button"
        data-dashboard-detail="<?= e($alertDetail); ?>"
    >
        <span class="cfo-alert-label"><?= e($primaryAlert['level']); ?></span>
        <p><strong>Point d’attention —</strong> <?= e($primaryAlert['text']); ?></p>
        <span class="cfo-alert-action">
            Examiner
            <?php if (count($importantAlerts) > 1): ?>
                · <?= count($importantAlerts) - 1; ?> autre<?= count($importantAlerts) > 2 ? 's' : ''; ?>
            <?php endif; ?>
            <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
        </span>
    </button>

    <section class="cfo-analysis-grid">
        <article
            class="cfo-panel dashboard-detail-trigger"
            data-dashboard-detail="performance_chart"
            role="button"
            tabindex="0"
            aria-label="Explorer l’évolution des revenus et dépenses"
        >
            <header class="cfo-panel-header">
                <div>
                    <span>Performance mensuelle</span>
                    <h3>Revenus et dépenses</h3>
                </div>
                <span class="cfo-panel-link">6 mois <i class="bi bi-arrow-up-right"></i></span>
            </header>
            <div class="cfo-chart">
                <canvas
                    class="chart-canvas"
                    data-chart="revenue-expense"
                    data-payload="<?= e($chartPayload); ?>"
                    aria-label="Évolution mensuelle des revenus et dépenses"
                ></canvas>
            </div>
        </article>

        <article
            class="cfo-panel dashboard-detail-trigger"
            data-dashboard-detail="service_expenses"
            role="button"
            tabindex="0"
            aria-label="Explorer la répartition des dépenses"
        >
            <header class="cfo-panel-header">
                <div>
                    <span>Structure des coûts</span>
                    <h3>Dépenses par service</h3>
                </div>
                <span class="cfo-panel-link">Ce mois <i class="bi bi-arrow-up-right"></i></span>
            </header>
            <div class="cfo-mix-layout">
                <div class="cfo-donut">
                    <canvas
                        class="chart-canvas"
                        data-chart="service-expenses"
                        data-payload="<?= e($servicePayload); ?>"
                        aria-label="Répartition des dépenses par service"
                    ></canvas>
                </div>
                <div class="cfo-mix-list">
                    <?php
                    $serviceTotal = array_sum(array_map(static fn (array $item): float => (float) $item['value'], $serviceExpenses));
                    ?>
                    <?php foreach ($serviceExpenses as $item): ?>
                        <?php $share = $serviceTotal > 0 ? ((float) $item['value'] / $serviceTotal) * 100 : 0; ?>
                        <div>
                            <span class="cfo-mix-color" style="--mix-color: <?= e($item['color']); ?>"></span>
                            <span>
                                <strong><?= e($item['label']); ?></strong>
                                <small><?= money($item['value']); ?></small>
                            </span>
                            <em><?= number_format($share, 1, ',', ' '); ?>%</em>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <article
            class="cfo-panel dashboard-detail-trigger"
            data-dashboard-detail="global_margin"
            role="button"
            tabindex="0"
            aria-label="Explorer la synthèse financière"
        >
            <header class="cfo-panel-header">
                <div>
                    <span>Synthèse financière</span>
                    <h3>Résultat estimé</h3>
                </div>
                <span class="cfo-panel-link">USD <i class="bi bi-arrow-up-right"></i></span>
            </header>
            <div class="cfo-statement">
                <div>
                    <span>Revenus encaissés</span>
                    <strong><?= money($summary['revenues']); ?></strong>
                </div>
                <div>
                    <span>Dépenses payées</span>
                    <strong class="is-muted">(<?= money($summary['expenses']); ?>)</strong>
                </div>
                <div class="is-total">
                    <span>Résultat estimé</span>
                    <strong><?= money((float) $summary['revenues'] - (float) $summary['expenses']); ?></strong>
                </div>
                <div class="is-highlight">
                    <span>Marge estimée</span>
                    <strong><?= number_format((float) $summary['margin'], 1, ',', ' '); ?>%</strong>
                </div>
            </div>
        </article>

        <article
            class="cfo-panel dashboard-detail-trigger"
            data-dashboard-detail="overdue_invoices"
            role="button"
            tabindex="0"
            aria-label="Explorer les créances clients"
        >
            <header class="cfo-panel-header">
                <div>
                    <span>Recouvrement</span>
                    <h3>Créances prioritaires</h3>
                </div>
                <span class="cfo-panel-link"><?= count($overdueInvoices); ?> dossier<?= count($overdueInvoices) > 1 ? 's' : ''; ?> <i class="bi bi-arrow-up-right"></i></span>
            </header>
            <div class="cfo-record-list">
                <?php foreach ($overdueInvoices as $invoice): ?>
                    <div>
                        <span>
                            <strong><?= e($invoice['client']); ?></strong>
                            <small><?= e($invoice['invoice']); ?> · <?= e($invoice['days']); ?></small>
                        </span>
                        <em><?= e($invoice['amount']); ?></em>
                    </div>
                <?php endforeach; ?>
                <?php if ($overdueInvoices === []): ?>
                    <div class="cfo-panel-empty">Aucune créance prioritaire.</div>
                <?php endif; ?>
            </div>
        </article>

        <article
            class="cfo-panel dashboard-detail-trigger"
            data-dashboard-detail="recent_requests"
            role="button"
            tabindex="0"
            aria-label="Explorer les demandes financières récentes"
        >
            <header class="cfo-panel-header">
                <div>
                    <span>Décaissements</span>
                    <h3>Demandes récentes</h3>
                </div>
                <span class="cfo-panel-link"><?= count($financialRequests); ?> récentes <i class="bi bi-arrow-up-right"></i></span>
            </header>
            <div class="cfo-record-list">
                <?php foreach ($financialRequests as $request): ?>
                    <div>
                        <span>
                            <strong><?= e($request['ref']); ?></strong>
                            <small><?= e($request['service']); ?> · <?= e(status_label($request['status'])); ?></small>
                        </span>
                        <em><?= e($request['amount']); ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article
            class="cfo-panel dashboard-detail-trigger"
            data-dashboard-detail="critical_projects"
            role="button"
            tabindex="0"
            aria-label="Explorer les projets à surveiller"
        >
            <header class="cfo-panel-header">
                <div>
                    <span>Exécution opérationnelle</span>
                    <h3>Projets à surveiller</h3>
                </div>
                <span class="cfo-panel-link"><?= count($criticalProjects); ?> suivi<?= count($criticalProjects) > 1 ? 's' : ''; ?> <i class="bi bi-arrow-up-right"></i></span>
            </header>
            <div class="cfo-project-list">
                <?php foreach ($criticalProjects as $project): ?>
                    <div>
                        <span>
                            <strong><?= e($project['name']); ?></strong>
                            <small><?= e($project['risk']); ?> · <?= e($project['owner']); ?></small>
                        </span>
                        <span class="cfo-project-progress">
                            <em><?= (int) $project['progress']; ?>%</em>
                            <i><b style="width: <?= (int) $project['progress']; ?>%"></b></i>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if ($criticalProjects === []): ?>
                    <div class="cfo-panel-empty">Aucun projet critique.</div>
                <?php endif; ?>
            </div>
        </article>
    </section>
</section>

<div class="modal dashboard-detail-modal" id="dashboard-detail-modal" aria-hidden="true" data-dashboard-detail-modal>
    <div class="modal-card modal-dashboard" role="dialog" aria-modal="true" aria-labelledby="dashboard-detail-title" tabindex="-1">
        <header class="dashboard-detail-header">
            <div class="dashboard-detail-heading">
                <span class="dashboard-detail-eyebrow" data-dashboard-detail-eyebrow>Analyse financière</span>
                <h3 id="dashboard-detail-title" data-dashboard-detail-title>Détail</h3>
                <p data-dashboard-detail-description></p>
                <div class="dashboard-detail-context">
                    <span><i class="bi bi-calendar3"></i><?= e($periodLabel); ?></span>
                    <span><i class="bi bi-building"></i>WAKE SERVICES</span>
                    <span><i class="bi bi-currency-dollar"></i>Données consolidées</span>
                </div>
            </div>
            <button class="dashboard-detail-close" type="button" data-dashboard-detail-close aria-label="Fermer le détail">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="dashboard-detail-state" data-dashboard-detail-state>
            <div class="dashboard-detail-loader" aria-hidden="true"></div>
            <strong>Préparation de l’analyse</strong>
            <span>Chargement des données consolidées…</span>
        </div>

        <div class="dashboard-detail-content" data-dashboard-detail-content hidden>
            <section class="dashboard-detail-summary" data-dashboard-detail-summary aria-label="Synthèse du détail"></section>

            <div class="dashboard-detail-toolbar">
                <label class="dashboard-detail-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span class="sr-only">Rechercher dans les résultats</span>
                    <input type="search" placeholder="Rechercher dans les résultats…" data-dashboard-detail-search>
                </label>
                <div class="dashboard-detail-toolbar-meta">
                    <span data-dashboard-detail-count></span>
                    <span class="dashboard-detail-separator" aria-hidden="true"></span>
                    <span>Mise à jour à l’ouverture</span>
                </div>
                <div class="dashboard-detail-actions">
                    <a class="dashboard-detail-action" href="#" data-dashboard-export-excel>
                        <i class="bi bi-file-earmark-spreadsheet"></i><span>Excel</span>
                    </a>
                    <a class="dashboard-detail-action is-primary" href="#" data-dashboard-export-pdf>
                        <i class="bi bi-file-earmark-pdf"></i><span>PDF</span>
                    </a>
                </div>
            </div>
            <div class="table-responsive dashboard-detail-table-wrap" data-dashboard-detail-table-wrap>
                <table class="data-table dashboard-detail-table">
                    <thead data-dashboard-detail-head></thead>
                    <tbody data-dashboard-detail-body></tbody>
                </table>
            </div>
            <div class="dashboard-detail-no-results" data-dashboard-detail-no-results hidden>
                <i class="bi bi-search"></i>
                <strong>Aucun résultat</strong>
                <span>Essayez un terme de recherche différent.</span>
            </div>
        </div>
    </div>
</div>
