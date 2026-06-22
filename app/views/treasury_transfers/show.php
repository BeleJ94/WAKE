<?php
$statusSteps = ['Draft', 'Pending', 'Approved', 'Executed'];
$currentIndex = array_search($transfer['status'], $statusSteps, true);
$currentIndex = $currentIndex === false ? -1 : $currentIndex;
?>
<section class="transfer-detail-hero">
    <div class="transfer-detail-top">
        <a class="transfer-back" href="<?= url('treasury_transfers'); ?>"><i class="bi bi-arrow-left"></i></a>
        <div><span class="section-kicker">Dossier de transfert</span><h2><?= e($transfer['reference']); ?></h2><p><?= e($transfer['purpose']); ?></p></div>
        <span class="badge <?= status_badge_class($transfer['status']); ?>"><?= e(status_label($transfer['status'])); ?></span>
    </div>
    <div class="transfer-detail-route">
        <article><small>Compte source</small><strong><?= e($transfer['source_name']); ?></strong><span><?= money($transfer['source_amount'], $transfer['source_currency']); ?></span><em>Solde actuel : <?= money($transfer['source_current_balance'], $transfer['source_currency']); ?></em></article>
        <div><span><i class="bi bi-arrow-right"></i></span><small><?= number_format((float) $transfer['exchange_rate'], 6, ',', ' '); ?></small></div>
        <article><small>Compte destinataire</small><strong><?= e($transfer['destination_name']); ?></strong><span><?= money($transfer['destination_amount'], $transfer['destination_currency']); ?></span><em>Solde actuel : <?= money($transfer['destination_current_balance'], $transfer['destination_currency']); ?></em></article>
    </div>
</section>

<?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>

<section class="transfer-workflow">
    <?php foreach ($statusSteps as $index => $step): ?><div class="<?= $index < $currentIndex ? 'is-complete' : ($index === $currentIndex ? 'is-current' : ''); ?>"><span><?= $index < $currentIndex ? '<i class="bi bi-check2"></i>' : $index + 1; ?></span><strong><?= e(status_label($step)); ?></strong></div><?php endforeach; ?>
</section>

<div class="transfer-detail-grid">
    <div>
        <section class="panel transfer-detail-card">
            <div class="panel-header"><div><span class="section-kicker">Informations</span><h3>Détails de l’opération</h3></div><i class="bi bi-receipt"></i></div>
            <div class="transfer-info-grid">
                <div><small>Demandeur</small><strong><?= e($transfer['requester_name']); ?></strong></div>
                <div><small>Créé le</small><strong><?= e(date('d/m/Y H:i', strtotime($transfer['created_at']))); ?></strong></div>
                <div><small>Approbateur</small><strong><?= e($transfer['approver_name'] ?: 'En attente'); ?></strong></div>
                <div><small>Exécuté par</small><strong><?= e($transfer['executor_name'] ?: 'Non exécuté'); ?></strong></div>
                <div class="span-2"><small>Notes</small><p><?= e($transfer['notes'] ?: 'Aucune note complémentaire.'); ?></p></div>
                <?php if ($transfer['rejection_reason']): ?><div class="span-2 transfer-rejection"><small>Motif du rejet</small><p><?= e($transfer['rejection_reason']); ?></p></div><?php endif; ?>
            </div>
        </section>

        <section class="panel transfer-detail-card">
            <div class="panel-header"><div><span class="section-kicker">Traçabilité</span><h3>Journal de l’opération</h3></div><i class="bi bi-clock-history"></i></div>
            <div class="transfer-timeline">
                <?php foreach ($events as $event): ?><article><span><i class="bi bi-check2"></i></span><div><strong><?= e(status_label($event['action'])); ?></strong><p><?= e($event['comment'] ?: 'Aucun commentaire.'); ?></p><small><?= e($event['user_name']); ?> · <?= e(date_fr($event['created_at'], true)); ?></small></div></article><?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside>
        <section class="panel transfer-action-card">
            <div class="transfer-action-head"><span><i class="bi bi-shield-lock"></i></span><div><small>Centre de décision</small><h3>Actions disponibles</h3></div></div>
            <?php if ($transfer['status'] === 'Draft' && Auth::can('treasury_transfers.create')): ?>
                <form method="post" action="<?= url('treasury_transfers/submit'); ?>"><?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $transfer['id']; ?>"><button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Soumettre à approbation</button></form>
            <?php endif; ?>
            <?php if ($transfer['status'] === 'Pending' && Auth::can('treasury_transfers.approve')): ?>
                <form method="post" action="<?= url('treasury_transfers/decision'); ?>" class="transfer-decision-form"><?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $transfer['id']; ?>"><label>Commentaire <textarea name="comment" rows="2" placeholder="Commentaire d’approbation…"></textarea></label><button class="btn btn-primary" type="submit" name="decision" value="approve"><i class="bi bi-check2-circle"></i> Approuver</button><label>Motif de rejet <textarea name="reason" rows="2" placeholder="Obligatoire en cas de rejet"></textarea></label><button class="btn btn-danger" type="submit" name="decision" value="reject"><i class="bi bi-x-circle"></i> Rejeter</button></form>
            <?php endif; ?>
            <?php if ($transfer['status'] === 'Approved' && Auth::can('treasury_transfers.execute')): ?>
                <div class="transfer-execute-warning"><i class="bi bi-exclamation-triangle"></i><p>Cette action débitera et créditera immédiatement les deux comptes.</p></div>
                <form method="post" action="<?= url('treasury_transfers/execute'); ?>"><?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $transfer['id']; ?>"><button class="btn btn-primary" type="submit"><i class="bi bi-lightning-charge"></i> Exécuter le transfert</button></form>
            <?php endif; ?>
            <?php if (in_array($transfer['status'], ['Draft', 'Pending', 'Approved'], true) && Auth::can('treasury_transfers.create')): ?>
                <form method="post" action="<?= url('treasury_transfers/cancel'); ?>" class="transfer-cancel-form"><?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $transfer['id']; ?>"><input name="reason" placeholder="Motif d’annulation"><button class="btn btn-secondary" type="submit"><i class="bi bi-slash-circle"></i> Annuler le transfert</button></form>
            <?php endif; ?>
            <?php if ($transfer['status'] === 'Executed'): ?><div class="transfer-complete-state"><i class="bi bi-patch-check-fill"></i><strong>Transfert exécuté</strong><span>Les deux écritures ont été comptabilisées.</span></div><?php endif; ?>
        </section>

        <section class="panel transfer-attachments-card">
            <div class="panel-header"><div><span class="section-kicker">Documents</span><h3>Justificatifs</h3></div></div>
            <?php foreach ($attachments as $attachment): ?><a href="<?= e(rtrim(PUBLIC_URL, '/') . '/' . ltrim($attachment['file_path'], '/')); ?>" target="_blank"><i class="bi bi-file-earmark-check"></i><span><strong><?= e($attachment['original_name']); ?></strong><small><?= file_size_label((int) $attachment['file_size']); ?> · <?= e($attachment['uploaded_by_name']); ?></small></span><i class="bi bi-box-arrow-up-right"></i></a><?php endforeach; ?>
            <?php if ($attachments === []): ?><div class="transfer-no-document"><i class="bi bi-paperclip"></i><span>Aucun justificatif joint.</span></div><?php endif; ?>
        </section>
    </aside>
</div>
