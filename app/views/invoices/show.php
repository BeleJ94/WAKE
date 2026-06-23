<section class="executive-hero">
    <div>
        <span class="section-kicker"><?= e($invoice['reference']); ?> · <?= e(invoice_source_label($invoice['source_type'] ?? 'manual')); ?></span>
        <h2><?= e($invoice['client_name']); ?></h2>
        <p><?= e($invoice['address'] ?? 'Adresse non renseignée'); ?> · échéance <?= e($invoice['due_date'] ?? '-'); ?></p>
    </div>
    <div class="executive-summary">
        <span>Reste à payer</span>
        <strong><?= money($invoice['remaining_amount']); ?></strong>
        <small>Total <?= money($invoice['total_amount']); ?> · payé <?= money($invoice['paid_amount']); ?></small>
    </div>
</section>

<section class="kpi-grid">
    <article class="kpi-card"><span>Sous-total</span><strong><?= money($invoice['subtotal']); ?></strong><small>Hors taxes</small></article>
    <article class="kpi-card"><span>Taxes</span><strong><?= money($invoice['tax_amount']); ?></strong><small>Montant fiscal</small></article>
    <article class="kpi-card"><span>Marge estimée</span><strong><?= money($invoice['estimated_margin']); ?></strong><small>Avant frais indirects</small></article>
    <article class="kpi-card"><span>Statut</span><strong><span class="badge <?= sales_status_badge($invoice['status']); ?>"><?= e($invoice['status']); ?></span></strong><small><?= e($invoice['payment_terms'] ?? ''); ?></small></article>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Document client</span>
            <h3>Lignes de facture</h3>
        </div>
        <div class="toolbar-actions">
            <?php if (!in_array($invoice['status'], ['Paid', 'Cancelled', 'Draft'], true)): ?>
                <a class="btn btn-secondary" href="<?= url('invoices/payment?invoice_id=' . (int) $invoice['id']); ?>"><i class="bi bi-cash-coin"></i> Encaisser</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="<?= url('invoices/print?id=' . (int) $invoice['id']); ?>"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qté</th>
                    <th class="text-right">PU</th>
                    <th class="text-right">Taxe</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['description']); ?></td>
                        <td class="text-right"><?= e($item['quantity']); ?></td>
                        <td class="text-right"><?= money($item['unit_price']); ?></td>
                        <td class="text-right"><?= money($item['line_tax']); ?></td>
                        <td class="text-right"><?= money($item['line_total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="invoice-totals">
        <div><span>Total</span><strong><?= money($invoice['total_amount']); ?></strong></div>
        <div><span>Montant payé</span><strong><?= money($invoice['paid_amount']); ?></strong></div>
        <div class="due"><span>Reste à payer</span><strong><?= money($invoice['remaining_amount']); ?></strong></div>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Traçabilité</span>
            <h3>Origine et conditions</h3>
        </div>
    </div>
    <div class="detail-grid">
        <div><span>Source</span><strong><?= e(invoice_source_label($invoice['source_type'] ?? 'manual')); ?></strong></div>
        <div><span>Référence commande</span><strong><?= e($invoice['order_reference'] ?? '-'); ?></strong></div>
        <div><span>Date de facturation</span><strong><?= e(date_fr($invoice['invoice_date'])); ?></strong></div>
        <div><span>Conditions</span><strong><?= e($invoice['payment_terms'] ?? '-'); ?></strong></div>
    </div>
    <?php if (!empty($invoice['notes'])): ?>
        <div class="alert alert-info"><?= e($invoice['notes']); ?></div>
    <?php endif; ?>
</section>
