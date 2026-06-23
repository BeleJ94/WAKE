<section class="panel form-panel">
    <div class="panel-header">
        <div><span class="section-kicker">Paiement</span><h2><?= e($request['reference']); ?></h2></div>
        <a class="btn btn-secondary" href="<?= url('fund_requests/show?id=' . (int) $request['id']); ?>">Retour</a>
    </div>
    <div class="detail-grid">
        <article><span>Montant à payer</span><strong><?= money($request['total_amount'], $request['currency']); ?></strong></article>
        <article><span>Compte</span><strong><?= e($account['name'] ?? '-'); ?></strong></article>
        <article><span>Solde actuel</span><strong><?= $account ? money($account['current_balance'], $account['currency']) : '-'; ?></strong></article>
        <article><span>Responsable</span><strong><?= e($account['responsible_name'] ?? '-'); ?></strong></article>
    </div>
    <form method="post" action="<?= url('fund_requests/payment'); ?>" enctype="multipart/form-data" data-validate>
        <?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $request['id']; ?>">
        <div class="form-grid">
            <label class="span-2">Description paiement <input type="text" name="description" required value="Paiement <?= e($request['reference']); ?>"></label>
            <label class="span-2">Preuve de paiement <input type="file" name="payment_proof" accept="application/pdf,image/png,image/jpeg"></label>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Confirmer le paiement</button></div>
    </form>
</section>

