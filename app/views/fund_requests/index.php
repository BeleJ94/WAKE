<section class="funds-cockpit-hero">
    <div class="funds-cockpit-copy">
        <span class="section-kicker">Finance & Trésorerie</span>
        <h2>Piloter les demandes de fonds</h2>
        <p>Suivez les décisions, les paiements et les priorités opérationnelles depuis un espace unique.</p>
    </div>
    <?php if (Auth::can('fund_requests.create')): ?>
        <a class="btn btn-primary" href="<?= url('fund_requests/create'); ?>">
            <i class="bi bi-plus-lg"></i> Nouvelle demande
        </a>
    <?php endif; ?>
</section>

<?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>

<section class="funds-kpi-grid">
    <article
        class="funds-kpi-card funds-kpi-pending dashboard-detail-trigger"
        data-dashboard-detail="pending"
        data-detail-url="<?= url('fund_requests/details'); ?>"
        role="button"
        tabindex="0"
        aria-label="Afficher les demandes en attente"
    >
        <div class="funds-kpi-top">
            <span class="funds-kpi-icon"><i class="bi bi-hourglass-split"></i></span>
            <span class="badge badge-warning">À décider</span>
        </div>
        <span class="funds-kpi-label">Demandes en attente</span>
        <strong><?= (int) $metrics['pending_count']; ?></strong>
        <div class="funds-currency-breakdown">
            <span><?= money($metrics['pending_by_currency']['USD'], 'USD'); ?></span>
            <span><?= money($metrics['pending_by_currency']['CDF'], 'CDF'); ?></span>
        </div>
        <em>Voir le détail <i class="bi bi-arrow-up-right"></i></em>
    </article>

    <article
        class="funds-kpi-card funds-kpi-approved dashboard-detail-trigger"
        data-dashboard-detail="approved"
        data-detail-url="<?= url('fund_requests/details'); ?>"
        role="button"
        tabindex="0"
        aria-label="Afficher les demandes approuvées à payer"
    >
        <div class="funds-kpi-top">
            <span class="funds-kpi-icon"><i class="bi bi-check2-circle"></i></span>
            <span class="badge badge-success">À payer</span>
        </div>
        <span class="funds-kpi-label">Demandes approuvées</span>
        <strong><?= (int) $metrics['approved_count']; ?></strong>
        <p>En attente du responsable du compte</p>
        <em>Voir le détail <i class="bi bi-arrow-up-right"></i></em>
    </article>

    <article
        class="funds-kpi-card funds-kpi-paid dashboard-detail-trigger"
        data-dashboard-detail="paid_month"
        data-detail-url="<?= url('fund_requests/details'); ?>"
        role="button"
        tabindex="0"
        aria-label="Afficher les dépenses payées ce mois"
    >
        <div class="funds-kpi-top">
            <span class="funds-kpi-icon"><i class="bi bi-wallet2"></i></span>
            <span class="badge badge-neutral">Ce mois</span>
        </div>
        <span class="funds-kpi-label">Dépenses payées</span>
        <div class="funds-paid-values">
            <strong><?= money($metrics['paid_month_by_currency']['USD'], 'USD'); ?></strong>
            <strong><?= money($metrics['paid_month_by_currency']['CDF'], 'CDF'); ?></strong>
        </div>
        <em>Voir le détail <i class="bi bi-arrow-up-right"></i></em>
    </article>
</section>

<section class="panel funds-list-panel">
    <div class="funds-list-header">
        <div>
            <span class="section-kicker">Workflow financier</span>
            <h3>Demandes de fonds</h3>
            <p><?= count($requests); ?> demande(s) enregistrée(s)</p>
        </div>
        <div class="funds-list-tools">
            <label class="funds-search">
                <i class="bi bi-search"></i>
                <input type="search" data-table-search data-target="#fund-requests-table" placeholder="Rechercher une demande…">
            </label>
            <select data-table-status data-target="#fund-requests-table" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                <?php foreach (['Draft', 'Pending', 'Approved', 'Rejected', 'Paid', 'Cancelled'] as $status): ?><option value="<?= e($status); ?>"><?= e(status_label($status)); ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive funds-table-wrap">
        <table class="data-table funds-table" id="fund-requests-table">
            <thead>
                <tr><th>Demande</th><th>Service & demandeur</th><th>Échéance</th><th>Statut</th><th class="text-right">Montant</th><th>Compte</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr data-status="<?= e($request['status']); ?>">
                        <td>
                            <div class="fund-request-identity">
                                <span class="fund-request-status-dot <?= e(strtolower(str_replace(' ', '-', $request['status']))); ?>"></span>
                                <div>
                                    <a href="<?= url('fund_requests/show?id=' . (int) $request['id']); ?>"><?= e($request['reference']); ?></a>
                                    <small><?= e($request['title']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><strong><?= e($request['department']); ?></strong><small class="table-subtext"><?= e($request['requester_name']); ?></small></td>
                        <td><?= e($request['needed_at'] ?: '-'); ?></td>
                        <td><span class="badge <?= status_badge_class($request['status']); ?>" data-status-badge="<?= (int) $request['id']; ?>"><?= e(status_label($request['status'])); ?></span></td>
                        <td class="text-right"><strong class="fund-request-amount"><?= money($request['total_amount'], $request['currency']); ?></strong></td>
                        <td><?= e($request['account_name'] ?? 'Non affecté'); ?></td>
                        <td class="text-right">
                            <div class="funds-row-actions">
                                <a class="icon-button" href="<?= url('fund_requests/show?id=' . (int) $request['id']); ?>" title="Voir le dossier"><i class="bi bi-eye"></i></a>
                                <?php if ($request['status'] === 'Pending' && Auth::can('fund_requests.approve')): ?>
                                    <a class="btn btn-primary btn-compact" href="<?= url('fund_requests/approve?id=' . (int) $request['id']); ?>">Décider</a>
                                <?php endif; ?>
                                <?php if ($request['status'] === 'Approved' && Auth::can('fund_requests.pay')): ?>
                                    <a class="btn btn-primary btn-compact" href="<?= url('fund_requests/payment?id=' . (int) $request['id']); ?>">Payer</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($requests === []): ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p>Aucune demande de fonds enregistrée.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal dashboard-detail-modal" id="fund-requests-kpi-modal" aria-hidden="true" data-dashboard-detail-modal>
    <div class="modal-card modal-dashboard" role="dialog" aria-modal="true" aria-labelledby="fund-request-detail-title">
        <div class="modal-header">
            <div>
                <span class="section-kicker">Analyse des demandes</span>
                <h3 id="fund-request-detail-title" data-dashboard-detail-title>Détail</h3>
                <p data-dashboard-detail-description></p>
            </div>
            <button class="icon-button" type="button" data-dashboard-detail-close aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="dashboard-detail-state" data-dashboard-detail-state>
            <i class="bi bi-arrow-repeat"></i><span>Chargement des données…</span>
        </div>
        <div class="dashboard-detail-content" data-dashboard-detail-content hidden>
            <div class="dashboard-detail-toolbar">
                <span data-dashboard-detail-count></span>
                <div class="toolbar-actions">
                    <a class="btn btn-secondary" href="#" data-dashboard-export-excel><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    <a class="btn btn-primary" href="#" data-dashboard-export-pdf><i class="bi bi-file-earmark-pdf"></i> PDF</a>
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
