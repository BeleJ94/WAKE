<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Placement</span><h2>Factures mensuelles</h2></div></div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
    <form class="form-grid" method="post" action="<?= url('placement/invoices/generate'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label>Contrat <select name="contract_id" required><?php foreach ($contracts as $contract): ?><option value="<?= (int) $contract['id']; ?>"><?= e($contract['reference']); ?> · <?= e($contract['client_name']); ?></option><?php endforeach; ?></select></label>
        <label>Mois à facturer <input type="month" name="invoice_month" required value="<?= date('Y-m'); ?>"></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-lightning-charge"></i> Générer la facture mensuelle</button></div>
    </form>
</section>
<section class="panel"><div class="panel-header"><div><span class="section-kicker">Historique</span><h3>Factures de placement générées</h3></div><span class="badge badge-neutral"><?= count($invoices); ?> factures</span></div><div class="table-responsive"><table class="data-table"><thead><tr><th>Référence</th><th>Client</th><th>Période</th><th>Statut</th><th class="text-right">Total facturé</th><th class="text-right">Coût</th><th class="text-right">Marge</th></tr></thead><tbody><?php foreach ($invoices as $invoice): ?><tr><td><strong><?= e($invoice['reference']); ?></strong></td><td><?= e($invoice['client_name']); ?></td><td><?= e($invoice['invoice_month']); ?></td><td><span class="badge <?= placement_status_badge($invoice['status']); ?>"><?= e(status_label($invoice['status'])); ?></span></td><td class="text-right"><?= money($invoice['subtotal']); ?></td><td class="text-right"><?= money($invoice['total_cost']); ?></td><td class="text-right"><?= money($invoice['margin_amount']); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
