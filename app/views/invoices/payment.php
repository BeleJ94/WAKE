<section class="dashboard-section">
    <div class="section-header">
        <div>
            <span class="section-kicker">Encaissements</span>
            <h2>Paiements des factures</h2>
        </div>
        <a class="btn btn-secondary" href="<?= url('invoices/index'); ?>"><i class="bi bi-receipt"></i> Factures</a>
    </div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
</section>

<section class="two-column-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Paiement partiel ou total</span>
                <h3>Enregistrer un encaissement</h3>
            </div>
        </div>
        <form class="form-grid" method="post" action="<?= url('invoices/payment'); ?>" data-validate>
            <?= Csrf::field(); ?>
            <label class="span-2">Facture
                <select name="invoice_id" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($invoices as $invoice): ?>
                        <option value="<?= (int) $invoice['id']; ?>" <?= (int) $selectedInvoiceId === (int) $invoice['id'] ? 'selected' : ''; ?>>
                            <?= e($invoice['reference']); ?> · <?= e($invoice['client_name']); ?> · reste <?= money($invoice['remaining_amount']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date paiement <input type="date" name="payment_date" value="<?= date('Y-m-d'); ?>" required></label>
            <label>Montant <input type="number" step="0.01" min="0.01" name="amount" required></label>
            <label>Méthode
                <select name="method">
                    <option>Cash</option>
                    <option>Banque</option>
                    <option>Mobile Money</option>
                    <option>Chèque</option>
                    <option>Autre</option>
                </select>
            </label>
            <label class="span-2">Notes <input name="notes" placeholder="Référence transaction, banque, observation"></label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Enregistrer paiement</button>
            </div>
        </form>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Factures ouvertes</span>
                <h3>À encaisser</h3>
            </div>
            <span class="badge badge-warning"><?= count($invoices); ?> ouvertes</span>
        </div>
        <div class="mini-list">
            <?php foreach (array_slice($invoices, 0, 6) as $invoice): ?>
                <a href="<?= url('invoices/show?id=' . (int) $invoice['id']); ?>">
                    <span><?= e($invoice['reference']); ?> · <?= e($invoice['client_name']); ?></span>
                    <strong><?= money($invoice['remaining_amount']); ?></strong>
                </a>
            <?php endforeach; ?>
            <?php if ($invoices === []): ?>
                <div class="empty-state compact"><i class="bi bi-check2-circle"></i><p>Aucune facture en attente de paiement.</p></div>
            <?php endif; ?>
        </div>
    </aside>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Historique</span>
            <h3>Derniers paiements</h3>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" data-enhanced-table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Facture</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Méthode</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e($payment['reference']); ?></td>
                        <td><?= e($payment['invoice_reference']); ?></td>
                        <td><?= e($payment['client_name']); ?></td>
                        <td><?= e($payment['payment_date']); ?></td>
                        <td><?= e($payment['method']); ?></td>
                        <td class="text-right"><?= money($payment['amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
