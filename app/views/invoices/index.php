<section class="dashboard-section">
    <div class="section-header">
        <div>
            <span class="section-kicker">Facturation centralisée</span>
            <h2>Factures WAKE SERVICES</h2>
        </div>
        <div class="toolbar-actions">
            <a class="btn btn-secondary" href="<?= url('invoices/payment'); ?>"><i class="bi bi-cash-coin"></i> Paiement</a>
            <a class="btn btn-primary" href="<?= url('invoices/create'); ?>"><i class="bi bi-plus-circle"></i> Nouvelle facture</a>
        </div>
    </div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
</section>

<section class="kpi-grid">
    <?php
    $total = array_sum(array_map(static fn($invoice) => (float) $invoice['total_amount'], $invoices));
    $paid = array_sum(array_map(static fn($invoice) => (float) $invoice['paid_amount'], $invoices));
    $remaining = $total - $paid;
    $overdue = count(array_filter($invoices, static fn($invoice) => $invoice['status'] === 'Overdue'));
    ?>
    <article class="kpi-card"><span>Total facturé</span><strong><?= money($total); ?></strong><small>Toutes sources confondues</small></article>
    <article class="kpi-card"><span>Montant encaissé</span><strong><?= money($paid); ?></strong><small>Paiements enregistrés</small></article>
    <article class="kpi-card"><span>Reste à payer</span><strong><?= money($remaining); ?></strong><small>Solde client ouvert</small></article>
    <article class="kpi-card"><span>En retard</span><strong><?= (int) $overdue; ?></strong><small>Factures échues</small></article>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Portefeuille client</span>
            <h3>Liste consolidée</h3>
        </div>
        <span class="badge badge-neutral"><?= count($invoices); ?> factures</span>
    </div>
    <div class="filters-bar">
        <label>Recherche <input data-table-search placeholder="Client, facture, statut"></label>
        <label>Statut
            <select data-table-filter="4">
                <option value="">Tous</option>
                <?php foreach (['Draft', 'Sent', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled'] as $status): ?>
                    <option value="<?= e($status); ?>"><?= e(status_label($status)); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="table-responsive">
        <table class="data-table" data-enhanced-table>
            <thead>
                <tr>
                    <th>Facture</th>
                    <th>Client</th>
                    <th>Source</th>
                    <th>Échéance</th>
                    <th>Statut</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Payé</th>
                    <th class="text-right">Reste</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><strong><?= e($invoice['reference']); ?></strong><small><?= e($invoice['invoice_date']); ?></small></td>
                        <td><?= e($invoice['client_name']); ?></td>
                        <td><?= e(invoice_source_label($invoice['source_type'] ?? 'manual')); ?></td>
                        <td><?= e($invoice['due_date'] ?? '-'); ?></td>
                        <td><span class="badge <?= sales_status_badge($invoice['status']); ?>"><?= e(status_label($invoice['status'])); ?></span></td>
                        <td class="text-right"><?= money($invoice['total_amount']); ?></td>
                        <td class="text-right"><?= money($invoice['paid_amount']); ?></td>
                        <td class="text-right"><strong><?= money($invoice['remaining_amount']); ?></strong></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= url('invoices/show?id=' . (int) $invoice['id']); ?>" title="Voir"><i class="bi bi-eye"></i></a>
                            <a class="btn-icon" href="<?= url('invoices/print?id=' . (int) $invoice['id']); ?>" title="Imprimer"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
