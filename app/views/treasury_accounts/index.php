<?php
$kpis = [
    'Caisse' => ['label' => 'Caisses', 'icon' => 'cash-stack', 'tone' => 'cash', 'hint' => 'Liquidités disponibles'],
    'Banque' => ['label' => 'Banques', 'icon' => 'bank', 'tone' => 'bank', 'hint' => 'Soldes bancaires actifs'],
    'Mobile Money' => ['label' => 'Mobile Money', 'icon' => 'phone', 'tone' => 'mobile', 'hint' => 'Portefeuilles numériques'],
    'all' => ['label' => 'Tous les comptes', 'icon' => 'wallet2', 'tone' => 'all', 'hint' => 'Vue consolidée'],
];
?>

<section class="treasury-hero">
    <div class="treasury-hero-copy">
        <span class="section-kicker">Finance & Trésorerie</span>
        <h2>Caisses & Banques</h2>
        <p>Visualisez vos disponibilités, identifiez rapidement chaque responsable et pilotez tous les comptes depuis un espace clair.</p>
    </div>
    <?php if (Auth::can('cashbanks.create')): ?>
        <a class="btn btn-primary" href="<?= url('treasury_accounts/create'); ?>">
            <i class="bi bi-plus-lg"></i> Nouveau compte
        </a>
    <?php endif; ?>
</section>

<?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>

<section class="treasury-kpi-grid" aria-label="Indicateurs des comptes de trésorerie">
    <?php foreach ($kpis as $type => $config): $metric = $accountMetrics[$type]; ?>
        <article
            class="treasury-kpi treasury-kpi-<?= e($config['tone']); ?> dashboard-detail-trigger"
            data-dashboard-detail="<?= e($type); ?>"
            data-detail-url="<?= url('treasury_accounts/details'); ?>"
            role="button"
            tabindex="0"
            aria-label="Afficher le détail : <?= e($config['label']); ?>"
        >
            <div class="treasury-kpi-head">
                <span class="treasury-kpi-icon"><i class="bi bi-<?= e($config['icon']); ?>"></i></span>
                <span class="treasury-kpi-count"><?= (int) $metric['active']; ?> actif<?= (int) $metric['active'] > 1 ? 's' : ''; ?></span>
            </div>
            <span class="treasury-kpi-label"><?= e($config['label']); ?></span>
            <div class="treasury-kpi-values">
                <strong><?= money($metric['balances']['USD'], 'USD'); ?></strong>
                <span><?= money($metric['balances']['CDF'], 'CDF'); ?></span>
            </div>
            <div class="treasury-kpi-foot">
                <small><?= e($config['hint']); ?></small>
                <em>Voir le détail <i class="bi bi-arrow-up-right"></i></em>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel treasury-list-panel" data-treasury-datatable>
    <div class="treasury-list-heading">
        <div>
            <span class="section-kicker">Référentiel des comptes</span>
            <h3>Comptes de trésorerie</h3>
            <p><span data-treasury-result-count><?= count($accounts); ?></span> compte(s) affiché(s)</p>
        </div>
        <div class="treasury-list-summary">
            <span><i class="bi bi-shield-check"></i> <?= (int) $accountMetrics['all']['active']; ?> actifs</span>
            <span><i class="bi bi-archive"></i> <?= count($accounts) - (int) $accountMetrics['all']['active']; ?> inactifs</span>
        </div>
    </div>

    <div class="treasury-filter-panel">
        <div class="treasury-filter-title">
            <div><i class="bi bi-funnel"></i><span><strong>Filtres rapides</strong><small>Affinez la liste selon vos besoins</small></span></div>
            <button class="btn btn-secondary btn-compact" type="button" data-treasury-reset>
                <i class="bi bi-arrow-clockwise"></i> Réinitialiser
            </button>
        </div>
        <div class="treasury-filter-grid">
            <label class="treasury-search-field">
                <span>Rechercher</span>
                <div><i class="bi bi-search"></i><input type="search" placeholder="Compte, responsable, note…" data-treasury-search></div>
            </label>
            <label>
                <span>Type de compte</span>
                <select data-treasury-filter="type">
                    <option value="">Tous les types</option>
                    <option value="Caisse">Caisse</option>
                    <option value="Banque">Banque</option>
                    <option value="Mobile Money">Mobile Money</option>
                    <option value="Autre">Autre</option>
                </select>
            </label>
            <label>
                <span>Monnaie</span>
                <select data-treasury-filter="currency">
                    <option value="">Toutes les monnaies</option>
                    <option value="USD">USD</option>
                    <option value="CDF">CDF</option>
                </select>
            </label>
            <label>
                <span>Statut</span>
                <select data-treasury-filter="status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
            </label>
            <label>
                <span>Responsable</span>
                <select data-treasury-filter="responsible">
                    <option value="">Tous les responsables</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id']; ?>"><?= e($user['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="0">Non affecté</option>
                </select>
            </label>
        </div>
    </div>

    <div class="treasury-table-toolbar">
        <span data-treasury-range>Affichage de tous les comptes</span>
        <label>Afficher
            <select data-treasury-page-size>
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            lignes
        </label>
    </div>

    <div class="table-responsive treasury-table-wrap">
        <table class="data-table treasury-table" id="accounts-table">
            <thead>
                <tr>
                    <th><button type="button" data-treasury-sort="name">Compte <i class="bi bi-arrow-down-up"></i></button></th>
                    <th><button type="button" data-treasury-sort="type">Type <i class="bi bi-arrow-down-up"></i></button></th>
                    <th><button type="button" data-treasury-sort="responsibleName">Responsable <i class="bi bi-arrow-down-up"></i></button></th>
                    <th><button type="button" data-treasury-sort="status">Statut <i class="bi bi-arrow-down-up"></i></button></th>
                    <th class="text-right"><button type="button" data-treasury-sort="balance">Solde <i class="bi bi-arrow-down-up"></i></button></th>
                    <th>Notes</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr
                        data-name="<?= e(strtolower($account['name'])); ?>"
                        data-type="<?= e($account['type']); ?>"
                        data-currency="<?= e($account['currency']); ?>"
                        data-status="<?= e($account['status']); ?>"
                        data-responsible="<?= (int) ($account['responsible_user_id'] ?? 0); ?>"
                        data-responsible-name="<?= e(strtolower($account['responsible_name'] ?? '')); ?>"
                        data-balance="<?= e((string) $account['current_balance']); ?>"
                    >
                        <td data-label="Compte">
                            <div class="treasury-account-name">
                                <span class="treasury-account-icon"><i class="bi bi-<?= $account['type'] === 'Banque' ? 'bank' : ($account['type'] === 'Mobile Money' ? 'phone' : 'cash-stack'); ?>"></i></span>
                                <div><strong><?= e($account['name']); ?></strong><small><?= e($account['currency']); ?></small></div>
                            </div>
                        </td>
                        <td data-label="Type"><span class="treasury-type-pill"><?= e($account['type']); ?></span></td>
                        <td data-label="Responsable"><?= e($account['responsible_name'] ?? 'Non affecté'); ?></td>
                        <td data-label="Statut"><span class="badge <?= status_badge_class($account['status']); ?>"><?= $account['status'] === 'active' ? 'Actif' : 'Inactif'; ?></span></td>
                        <td class="text-right" data-label="Solde"><strong class="treasury-balance-value"><?= money($account['current_balance'], $account['currency']); ?></strong></td>
                        <td data-label="Notes"><span class="treasury-note"><?= e($account['notes'] ?: '—'); ?></span></td>
                        <td class="text-right" data-label="Actions">
                            <div class="treasury-row-actions">
                                <button
                                    class="icon-button treasury-view-button"
                                    type="button"
                                    title="Voir les détails"
                                    aria-label="Voir les détails de <?= e($account['name']); ?>"
                                    data-treasury-account-detail
                                    data-account-id="<?= (int) $account['id']; ?>"
                                ><i class="bi bi-eye"></i></button>
                                <?php if (Auth::can('cashbanks.manage')): ?>
                                <button
                                    class="icon-button treasury-edit-button"
                                    type="button"
                                    title="Modifier le compte"
                                    aria-label="Modifier <?= e($account['name']); ?>"
                                    data-treasury-edit
                                    data-account-id="<?= (int) $account['id']; ?>"
                                    data-account-name="<?= e($account['name']); ?>"
                                    data-account-type="<?= e($account['type']); ?>"
                                    data-account-currency="<?= e($account['currency']); ?>"
                                    data-account-opening="<?= e((string) $account['opening_balance']); ?>"
                                    data-account-balance="<?= e((string) $account['current_balance']); ?>"
                                    data-account-responsible="<?= (int) ($account['responsible_user_id'] ?? 0); ?>"
                                    data-account-status="<?= e($account['status']); ?>"
                                    data-account-notes="<?= e($account['notes'] ?? ''); ?>"
                                ><i class="bi bi-pencil-square"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="treasury-table-empty" data-treasury-empty hidden>
            <i class="bi bi-search"></i>
            <strong>Aucun compte trouvé</strong>
            <p>Modifiez ou réinitialisez les filtres pour élargir les résultats.</p>
        </div>
    </div>

    <div class="treasury-pagination" data-treasury-pagination></div>
</section>

<div class="modal dashboard-detail-modal" id="treasury-kpi-modal" aria-hidden="true" data-dashboard-detail-modal>
    <div class="modal-card modal-dashboard" role="dialog" aria-modal="true" aria-labelledby="treasury-detail-title">
        <div class="modal-header">
            <div>
                <span class="section-kicker">Analyse de trésorerie</span>
                <h3 id="treasury-detail-title" data-dashboard-detail-title>Détail</h3>
                <p data-dashboard-detail-description></p>
            </div>
            <button class="icon-button" type="button" data-dashboard-detail-close aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="dashboard-detail-state" data-dashboard-detail-state><i class="bi bi-arrow-repeat"></i><span>Chargement des données…</span></div>
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

<div
    class="modal treasury-account-detail-modal"
    id="treasury-account-detail-modal"
    aria-hidden="true"
    data-account-detail-url="<?= url('treasury_accounts/account-details'); ?>"
>
    <div class="modal-card treasury-account-detail-card" role="dialog" aria-modal="true" aria-labelledby="account-detail-name">
        <div class="account-detail-loading" data-account-detail-loading>
            <i class="bi bi-arrow-repeat"></i>
            <strong>Préparation de la fiche du compte…</strong>
        </div>

        <div class="account-detail-content" data-account-detail-content hidden>
            <header class="account-detail-hero">
                <div class="account-detail-identity">
                    <span class="account-detail-icon" data-account-detail-icon><i class="bi bi-bank"></i></span>
                    <div>
                        <span class="section-kicker">Fiche financière du compte</span>
                        <h3 id="account-detail-name" data-account-detail-name>Compte</h3>
                        <div class="account-detail-tags">
                            <span data-account-detail-type></span>
                            <span data-account-detail-currency></span>
                            <span data-account-detail-status></span>
                        </div>
                    </div>
                </div>
                <button class="icon-button account-detail-close" type="button" data-account-detail-close aria-label="Fermer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </header>

            <div class="account-detail-body">
                <section class="account-detail-balance">
                    <div>
                        <span>Solde disponible</span>
                        <strong data-account-detail-balance>0,00 USD</strong>
                        <small data-account-detail-variation></small>
                    </div>
                    <span class="account-detail-balance-mark"><i class="bi bi-shield-check"></i></span>
                </section>

                <section class="account-detail-metrics">
                    <article><span>Solde initial</span><strong data-account-detail-opening>0,00</strong><small>À la création</small></article>
                    <article><span>Total des entrées</span><strong class="is-positive" data-account-detail-inflow>0,00</strong><small>Mouvements créditeurs</small></article>
                    <article><span>Total des sorties</span><strong class="is-negative" data-account-detail-outflow>0,00</strong><small>Mouvements débiteurs</small></article>
                    <article><span>Mouvements</span><strong data-account-detail-count>0</strong><small data-account-detail-last>Aucune opération</small></article>
                </section>

                <section class="account-detail-info-grid">
                    <article>
                        <span class="account-detail-info-icon"><i class="bi bi-person-check"></i></span>
                        <div><small>Responsable du compte</small><strong data-account-detail-responsible>Non affecté</strong></div>
                    </article>
                    <article>
                        <span class="account-detail-info-icon"><i class="bi bi-calendar3"></i></span>
                        <div><small>Créé le</small><strong data-account-detail-created>—</strong></div>
                    </article>
                    <article class="account-detail-notes">
                        <span class="account-detail-info-icon"><i class="bi bi-card-text"></i></span>
                        <div><small>Notes opérationnelles</small><p data-account-detail-notes></p></div>
                    </article>
                </section>

                <section class="account-detail-history">
                    <div class="account-detail-section-head">
                        <div><span class="section-kicker">Traçabilité</span><h4>Derniers mouvements</h4></div>
                        <span data-account-detail-history-count></span>
                    </div>
                    <div class="table-responsive account-detail-table-wrap">
                        <table class="data-table account-detail-table">
                            <thead><tr><th>Opération</th><th>Date</th><th>Type</th><th class="text-right">Montant</th><th class="text-right">Solde après</th><th>Créé par</th></tr></thead>
                            <tbody data-account-detail-movements></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <footer class="account-detail-footer">
                <span><i class="bi bi-lock"></i> Données financières sécurisées</span>
                <div class="toolbar-actions">
                    <a class="btn btn-secondary" href="#" data-account-detail-excel><i class="bi bi-file-earmark-excel"></i> Exporter Excel</a>
                    <a class="btn btn-primary" href="#" data-account-detail-pdf><i class="bi bi-file-earmark-pdf"></i> Exporter PDF</a>
                </div>
            </footer>
        </div>
    </div>
</div>

<?php if (Auth::can('cashbanks.manage')): ?>
    <div class="modal treasury-edit-modal" id="treasury-edit-modal" aria-hidden="true">
        <div class="modal-card treasury-edit-card" role="dialog" aria-modal="true" aria-labelledby="treasury-edit-title">
            <div class="treasury-edit-header">
                <div class="treasury-edit-heading">
                    <span class="treasury-edit-mark" data-edit-account-icon><i class="bi bi-bank"></i></span>
                    <div>
                        <span class="section-kicker">Gestion du compte</span>
                        <h3 id="treasury-edit-title">Modifier le compte</h3>
                        <p>Actualisez les informations administratives tout en préservant l’intégrité financière.</p>
                    </div>
                </div>
                <button class="icon-button treasury-edit-close" type="button" data-modal-close aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="post" action="<?= url('treasury_accounts/update'); ?>" data-treasury-edit-form>
                <?= Csrf::field(); ?><input type="hidden" name="id" data-edit-account-id>

                <div class="treasury-edit-body">
                    <div class="treasury-balance-banner">
                        <div class="treasury-edit-balance-main">
                            <span>Solde actuel protégé</span>
                            <strong data-edit-account-balance>0,00 USD</strong>
                            <small><i class="bi bi-shield-lock"></i> Donnée comptable non modifiable</small>
                        </div>
                        <div class="treasury-edit-balance-opening">
                            <span>Solde initial</span>
                            <strong data-edit-account-opening>0,00 USD</strong>
                            <small>Valeur enregistrée à l’ouverture</small>
                        </div>
                        <span class="treasury-edit-balance-mark"><i class="bi bi-shield-check"></i></span>
                    </div>

                    <div class="treasury-edit-sections">
                        <section class="treasury-edit-section">
                            <div class="treasury-edit-section-title">
                                <span><i class="bi bi-wallet2"></i></span>
                                <div><strong>Identification du compte</strong><small>Informations principales et classification.</small></div>
                            </div>
                            <div class="form-grid treasury-edit-grid">
                                <label class="span-2"><span class="field-label">Nom du compte <em>Obligatoire</em></span><input name="name" required minlength="3" maxlength="150" data-edit-account-name></label>
                                <label><span class="field-label">Type <em>Obligatoire</em></span><select name="type" required data-edit-account-type><?php foreach (['Caisse', 'Banque', 'Mobile Money', 'Autre'] as $type): ?><option value="<?= e($type); ?>"><?= e($type); ?></option><?php endforeach; ?></select></label>
                                <label><span class="field-label">Monnaie <em>Obligatoire</em></span><select name="currency" required data-edit-account-currency><option value="USD">USD — Dollar américain</option><option value="CDF">CDF — Franc congolais</option></select><small class="field-hint"><i class="bi bi-info-circle"></i> Verrouillée si le compte possède un historique.</small></label>
                            </div>
                        </section>

                        <section class="treasury-edit-section">
                            <div class="treasury-edit-section-title">
                                <span><i class="bi bi-person-gear"></i></span>
                                <div><strong>Gestion et responsabilité</strong><small>Responsable, disponibilité et informations utiles.</small></div>
                            </div>
                            <div class="form-grid treasury-edit-grid">
                                <label><span class="field-label">Statut opérationnel</span><select name="status" required data-edit-account-status><option value="active">Actif — Disponible</option><option value="inactive">Inactif — Indisponible</option></select></label>
                                <label><span class="field-label">Responsable du compte</span><select name="responsible_user_id" data-edit-account-responsible><option value="">Aucun responsable</option><?php foreach ($users as $user): ?><option value="<?= (int) $user['id']; ?>"><?= e($user['name']); ?> · <?= e($user['email']); ?></option><?php endforeach; ?></select></label>
                                <label class="span-2"><span class="field-label">Notes opérationnelles</span><textarea name="notes" rows="4" maxlength="255" placeholder="Usage du compte, restrictions ou informations de gestion…" data-edit-account-notes></textarea></label>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="treasury-edit-actions">
                    <span><i class="bi bi-lock"></i> Les modifications sont tracées dans le journal d’audit.</span>
                    <div>
                        <button class="btn btn-secondary" type="button" data-modal-close>Annuler</button>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Enregistrer les modifications</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
