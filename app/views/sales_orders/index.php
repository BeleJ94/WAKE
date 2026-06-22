<section class="dashboard-section">
    <div class="section-header"><div><span class="section-kicker">Commercial & Ventes</span><h2>Commandes clients</h2><p class="ui-page-intro">Suivez les commandes validées, leur livraison et leur facturation.</p></div></div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
</section>
<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Cycle de vente</span><h3>Liste des commandes</h3></div><span class="badge badge-neutral"><?= count($orders); ?> commandes</span></div>
    <div class="filters-bar"><label>Recherche <input type="search" data-table-search data-target="#orders-table" placeholder="Commande, client ou statut"></label></div>
    <div class="table-responsive"><table class="data-table" id="orders-table"><thead><tr><th>Référence</th><th>Client</th><th>Date</th><th>Statut</th><th class="text-right">Total</th><th class="text-right">Marge estimée</th><th class="text-right">Action</th></tr></thead><tbody>
    <?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['reference']); ?></strong></td><td><?= e($order['client_name']); ?></td><td><?= e(date_fr($order['order_date'])); ?></td><td><span class="badge <?= sales_status_badge($order['status']); ?>"><?= e(status_label($order['status'])); ?></span></td><td class="text-right"><strong><?= money($order['total_amount']); ?></strong></td><td class="text-right"><?= money($order['estimated_margin']); ?></td><td class="text-right"><a class="btn btn-secondary btn-compact" href="<?= url('sales_orders/show?id=' . (int) $order['id']); ?>"><i class="bi bi-folder2-open"></i> Ouvrir le dossier</a></td></tr><?php endforeach; ?>
    <?php if ($orders === []): ?><tr><td colspan="7"><div class="empty-state compact"><i class="bi bi-cart-check"></i><p>Aucune commande client enregistrée.</p></div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
