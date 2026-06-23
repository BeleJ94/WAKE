<section class="dashboard-section">
    <div class="section-header">
        <div><span class="section-kicker">Commercial & Ventes</span><h2>Devis clients</h2><p class="ui-page-intro">Préparez, validez et transformez les propositions commerciales en commandes.</p></div>
        <a class="btn btn-primary" href="<?= url('quotations/create'); ?>"><i class="bi bi-plus-circle"></i> Créer un devis</a>
    </div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Portefeuille commercial</span><h3>Liste des devis</h3></div><span class="badge badge-neutral"><?= count($quotations); ?> devis</span></div>
    <div class="filters-bar"><label>Recherche <input type="search" data-table-search data-target="#quotations-table" placeholder="Référence ou client"></label></div>
    <div class="table-responsive">
        <table class="data-table" id="quotations-table">
            <thead><tr><th>Référence</th><th>Client</th><th>Date</th><th>Statut</th><th class="text-right">Montant HT</th><th class="text-right">Taxes</th><th class="text-right">Total</th><th class="text-right">Marge estimée</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($quotations as $quotation): ?>
                <tr>
                    <td><strong><?= e($quotation['reference']); ?></strong></td>
                    <td><?= e($quotation['client_name']); ?></td>
                    <td><?= e(date_fr($quotation['quote_date'])); ?></td>
                    <td><span class="badge <?= sales_status_badge($quotation['status']); ?>"><?= e(status_label($quotation['status'])); ?></span></td>
                    <td class="text-right"><?= money($quotation['subtotal']); ?></td>
                    <td class="text-right"><?= money($quotation['tax_amount']); ?></td>
                    <td class="text-right"><strong><?= money($quotation['total_amount']); ?></strong></td>
                    <td class="text-right"><?= money($quotation['estimated_margin']); ?></td>
                    <td class="action-cell">
                        <?php if ($quotation['status'] === 'Draft'): ?><form method="post" action="<?= url('quotations/validate'); ?>"><?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $quotation['id']; ?>"><button class="btn btn-secondary btn-compact" type="submit"><i class="bi bi-check2"></i> Valider</button></form><?php endif; ?>
                        <?php if (in_array($quotation['status'], ['Draft', 'Validated'], true)): ?><form method="post" action="<?= url('quotations/convert'); ?>"><?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $quotation['id']; ?>"><button class="btn btn-primary btn-compact" type="submit"><i class="bi bi-cart-check"></i> Créer la commande</button></form><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($quotations === []): ?><tr><td colspan="9"><div class="empty-state compact"><i class="bi bi-file-earmark-text"></i><h4>Aucun devis enregistré</h4><p>Créez votre première proposition commerciale pour démarrer le cycle de vente.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
