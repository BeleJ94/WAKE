<section class="dashboard-section">
    <div class="section-header"><div><span class="section-kicker">Logistique commerciale</span><h2>Bons de livraison</h2><p class="ui-page-intro">Consultez les livraisons préparées et leur état d’avancement.</p></div></div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
</section>
<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Suivi logistique</span><h3>Historique des livraisons</h3></div><span class="badge badge-neutral"><?= count($deliveries); ?> bons</span></div>
    <div class="filters-bar"><label>Recherche <input type="search" data-table-search data-target="#deliveries-table" placeholder="Bon, commande ou client"></label></div>
    <div class="table-responsive"><table class="data-table" id="deliveries-table"><thead><tr><th>Bon de livraison</th><th>Commande</th><th>Client</th><th>Date</th><th>Statut</th><th>Notes</th></tr></thead><tbody>
    <?php foreach ($deliveries as $delivery): ?><tr><td><strong><?= e($delivery['reference']); ?></strong></td><td><?= e($delivery['order_reference']); ?></td><td><?= e($delivery['client_name']); ?></td><td><?= e(date_fr($delivery['delivery_date'])); ?></td><td><span class="badge <?= sales_status_badge($delivery['status']); ?>"><?= e(status_label($delivery['status'])); ?></span></td><td><?= e($delivery['notes'] ?: '—'); ?></td></tr><?php endforeach; ?>
    <?php if ($deliveries === []): ?><tr><td colspan="6"><div class="empty-state compact"><i class="bi bi-truck"></i><p>Aucune livraison préparée pour le moment.</p></div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
