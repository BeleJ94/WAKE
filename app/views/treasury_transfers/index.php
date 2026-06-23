<section class="transfer-hero">
    <div>
        <span class="section-kicker">Finance & Trésorerie</span>
        <h2>Transferts entre comptes</h2>
        <p>Orchestrez les mouvements internes avec séparation des responsabilités, validation et traçabilité intégrale.</p>
    </div>
    <?php if (Auth::can('treasury_transfers.create')): ?>
        <a class="btn btn-primary" href="<?= url('treasury_transfers/create'); ?>"><i class="bi bi-plus-lg"></i> Nouveau transfert</a>
    <?php endif; ?>
</section>

<?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>

<section class="transfer-kpi-grid">
    <article><span class="transfer-kpi-icon is-pending"><i class="bi bi-hourglass-split"></i></span><div><small>À approuver</small><strong><?= (int) $metrics['Pending']; ?></strong><span>Décision Direction requise</span></div></article>
    <article><span class="transfer-kpi-icon is-approved"><i class="bi bi-patch-check"></i></span><div><small>À exécuter</small><strong><?= (int) $metrics['Approved']; ?></strong><span>Autorisation obtenue</span></div></article>
    <article><span class="transfer-kpi-icon is-executed"><i class="bi bi-arrow-left-right"></i></span><div><small>Exécutés</small><strong><?= (int) $metrics['Executed']; ?></strong><span>Écritures comptabilisées</span></div></article>
    <article><span class="transfer-kpi-icon is-draft"><i class="bi bi-file-earmark"></i></span><div><small>Brouillons</small><strong><?= (int) $metrics['Draft']; ?></strong><span>En préparation</span></div></article>
</section>

<section class="panel transfer-list-panel">
    <div class="transfer-list-head">
        <div><span class="section-kicker">Registre sécurisé</span><h3>Historique des transferts</h3><p><?= count($transfers); ?> opération(s) enregistrée(s)</p></div>
        <div class="transfer-list-tools">
            <label class="transfer-search"><i class="bi bi-search"></i><input type="search" data-table-search data-target="#transfers-table" placeholder="Référence, compte, motif…"></label>
            <select data-table-status data-target="#transfers-table" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                <?php foreach (['Draft', 'Pending', 'Approved', 'Executed', 'Rejected', 'Cancelled'] as $status): ?><option value="<?= e($status); ?>"><?= e(status_label($status)); ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="transfer-security-note"><i class="bi bi-shield-lock"></i><span><strong>Double écriture garantie</strong> Chaque exécution débite la source et crédite la destination dans une transaction unique.</span></div>
    <div class="table-responsive transfer-table-wrap">
        <table class="data-table transfer-table" id="transfers-table">
            <thead><tr><th>Transfert</th><th>Comptes</th><th>Montants</th><th>Demandeur</th><th>Statut</th><th>Date</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            <?php foreach ($transfers as $transfer): ?>
                <tr data-status="<?= e($transfer['status']); ?>">
                    <td><strong><?= e($transfer['reference']); ?></strong><small><?= e($transfer['purpose']); ?></small></td>
                    <td>
                        <div class="transfer-route-mini"><span><?= e($transfer['source_name']); ?></span><i class="bi bi-arrow-right"></i><span><?= e($transfer['destination_name']); ?></span></div>
                    </td>
                    <td><strong><?= money($transfer['source_amount'], $transfer['source_currency']); ?></strong><?php if ($transfer['source_currency'] !== $transfer['destination_currency']): ?><small>Reçu : <?= money($transfer['destination_amount'], $transfer['destination_currency']); ?></small><?php endif; ?></td>
                    <td><?= e($transfer['requester_name']); ?></td>
                    <td><span class="badge <?= status_badge_class($transfer['status']); ?>"><?= e(status_label($transfer['status'])); ?></span></td>
                    <td><?= e(date_fr($transfer['created_at'])); ?></td>
                    <td class="text-right"><a class="btn btn-secondary btn-compact" href="<?= url('treasury_transfers/show?id=' . (int) $transfer['id']); ?>"><i class="bi bi-eye"></i> Ouvrir</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($transfers === []): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-arrow-left-right"></i><p>Aucun transfert enregistré.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
