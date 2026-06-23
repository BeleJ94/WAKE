<section class="dashboard-section">
    <div class="section-header"><div><span class="section-kicker">Encaissements clients</span><h2>Enregistrer un paiement</h2><p class="ui-page-intro">Affectez un encaissement à une facture ouverte et conservez sa référence de règlement.</p></div></div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
</section>

<section class="panel">
    <form class="form-grid" method="post" action="<?= url('payments/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label class="span-2"><span class="field-label">Facture à régler <em>Obligatoire</em></span><select name="invoice_id" required><option value="">Sélectionner une facture ouverte</option><?php foreach ($invoices as $invoice): if ($invoice['status'] === 'Paid') continue; ?><option value="<?= (int) $invoice['id']; ?>"><?= e($invoice['reference']); ?> · <?= e($invoice['client_name']); ?> · Reste <?= money((float) $invoice['total_amount'] - (float) $invoice['paid_amount']); ?></option><?php endforeach; ?></select></label>
        <label><span class="field-label">Date du paiement</span><input type="date" name="payment_date" value="<?= date('Y-m-d'); ?>" required></label>
        <label><span class="field-label">Montant reçu <em>Obligatoire</em></span><input type="number" step="0.01" min="0.01" name="amount" required></label>
        <label><span class="field-label">Mode de paiement</span><select name="method"><option value="Cash">Espèces</option><option value="Bank transfer">Virement bancaire</option><option value="Mobile Money">Mobile Money</option><option value="Cheque">Chèque</option></select></label>
        <label><span class="field-label">Référence ou notes</span><input name="notes" placeholder="Référence bancaire, numéro de transaction…"></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Enregistrer le paiement</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Historique</span><h3>Derniers paiements enregistrés</h3></div><span class="badge badge-neutral"><?= count($payments); ?> paiements</span></div>
    <div class="table-responsive"><table class="data-table"><thead><tr><th>Référence</th><th>Facture</th><th>Client</th><th>Date</th><th>Mode de paiement</th><th class="text-right">Montant</th></tr></thead><tbody><?php foreach ($payments as $payment): ?><tr><td><strong><?= e($payment['reference']); ?></strong></td><td><?= e($payment['invoice_reference']); ?></td><td><?= e($payment['client_name']); ?></td><td><?= e(date_fr($payment['payment_date'])); ?></td><td><?= e(status_label($payment['method'])); ?></td><td class="text-right"><strong><?= money($payment['amount']); ?></strong></td></tr><?php endforeach; ?><?php if ($payments === []): ?><tr><td colspan="6"><div class="empty-state compact"><i class="bi bi-credit-card"></i><p>Aucun paiement client enregistré.</p></div></td></tr><?php endif; ?></tbody></table></div>
</section>
