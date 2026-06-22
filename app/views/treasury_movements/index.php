<section class="panel">
    <div class="panel-header">
        <div><span class="section-kicker">Trésorerie</span><h2>Mouvements de trésorerie</h2><p class="ui-page-intro">Consultez toutes les entrées et sorties comptabilisées sur les comptes.</p></div>
        <div class="filters"><input type="search" data-table-search data-target="#movements-table" placeholder="Filtrer les mouvements"></div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="movements-table">
            <thead><tr><th>Référence</th><th>Compte</th><th>Type</th><th>Description</th><th class="text-right">Montant</th><th class="text-right">Avant</th><th class="text-right">Après</th><th>Créé par</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($movements as $movement): ?>
                    <tr data-status="<?= e($movement['movement_type']); ?>">
                        <td><?= e($movement['reference']); ?></td>
                        <td><?= e($movement['account_name']); ?></td>
                        <td><span class="badge <?= $movement['movement_type'] === 'outflow' ? 'badge-warning' : 'badge-success'; ?>"><?= e(status_label($movement['movement_type'])); ?></span></td>
                        <td><?= e($movement['description']); ?></td>
                        <td class="text-right"><?= money($movement['amount']); ?></td>
                        <td class="text-right"><?= money($movement['balance_before']); ?></td>
                        <td class="text-right"><?= money($movement['balance_after']); ?></td>
                        <td><?= e($movement['created_by_name']); ?></td>
                        <td><?= e(date_fr($movement['created_at'], true)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
