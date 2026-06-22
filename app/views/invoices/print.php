<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($invoice['reference']); ?> - WAKE SERVICES</title>
    <link rel="stylesheet" href="<?= asset('css/app.css'); ?>">
</head>
<body class="print-body">
    <main class="invoice-document">
        <header class="invoice-document-header">
            <div class="brand-block">
                <div class="brand-mark">WAKE</div>
                <div>
                    <h1>WAKE SERVICES</h1>
                    <p>Business Suite · Facturation centralisée</p>
                </div>
            </div>
            <div class="invoice-meta">
                <span>FACTURE</span>
                <strong><?= e($invoice['reference']); ?></strong>
                <small>Statut : <?= e($invoice['status']); ?></small>
            </div>
        </header>

        <section class="invoice-document-grid">
            <div>
                <span class="section-kicker">Facturé à</span>
                <h2><?= e($invoice['client_name']); ?></h2>
                <p><?= e($invoice['address'] ?? 'Adresse non renseignée'); ?></p>
                <?php if (!empty($invoice['phone']) || !empty($invoice['email'])): ?>
                    <p><?= e($invoice['phone'] ?? ''); ?> <?= !empty($invoice['email']) ? ' · ' . e($invoice['email']) : ''; ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-dates">
                <p><span>Date</span><strong><?= e($invoice['invoice_date']); ?></strong></p>
                <p><span>Échéance</span><strong><?= e($invoice['due_date'] ?? '-'); ?></strong></p>
                <p><span>Source</span><strong><?= e(invoice_source_label($invoice['source_type'] ?? 'manual')); ?></strong></p>
            </div>
        </section>

        <table class="invoice-print-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qté</th>
                    <th>PU</th>
                    <th>Taxe</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['description']); ?></td>
                        <td><?= e($item['quantity']); ?></td>
                        <td><?= money($item['unit_price']); ?></td>
                        <td><?= money($item['line_tax']); ?></td>
                        <td><?= money($item['line_total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <section class="invoice-document-bottom">
            <div class="payment-terms">
                <span class="section-kicker">Conditions de paiement</span>
                <p><?= e($invoice['payment_terms'] ?? 'Paiement à 15 jours sauf accord contractuel contraire.'); ?></p>
                <?php if (!empty($invoice['notes'])): ?><p><?= e($invoice['notes']); ?></p><?php endif; ?>
            </div>
            <div class="invoice-print-totals">
                <p><span>Sous-total</span><strong><?= money($invoice['subtotal']); ?></strong></p>
                <p><span>Taxes</span><strong><?= money($invoice['tax_amount']); ?></strong></p>
                <p><span>Total</span><strong><?= money($invoice['total_amount']); ?></strong></p>
                <p><span>Montant payé</span><strong><?= money($invoice['paid_amount']); ?></strong></p>
                <p class="due"><span>Reste à payer</span><strong><?= money($invoice['remaining_amount']); ?></strong></p>
            </div>
        </section>

        <footer class="invoice-document-footer">
            <p>Merci pour votre confiance.</p>
            <button class="btn btn-primary no-print" type="button" onclick="window.print()">Exporter / Imprimer PDF</button>
        </footer>
    </main>
</body>
</html>
