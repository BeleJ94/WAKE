<?php
$statusLabels = [
    'Draft' => 'Brouillon',
    'Pending' => 'En attente',
    'Approved' => 'Approuvée',
    'Rejected' => 'Rejetée',
    'Paid' => 'Payée',
    'Cancelled' => 'Annulée',
];
$actionLabels = [
    'Submitted' => 'Demande soumise',
    'Approved' => 'Demande approuvée',
    'Rejected' => 'Demande rejetée',
    'Cancelled' => 'Demande annulée',
];
$statusLabel = $statusLabels[$request['status']] ?? $request['status'];
$workflowOrder = ['Draft', 'Pending', 'Approved', 'Paid'];
$currentStep = array_search($request['status'], $workflowOrder, true);
if ($currentStep === false) {
    $currentStep = $request['status'] === 'Rejected' ? 1 : 0;
}
$formatDate = static function (?string $value, bool $withTime = false): string {
    if ($value === null || $value === '') {
        return 'Non précisée';
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date($withTime ? 'd/m/Y à H:i' : 'd/m/Y', $timestamp);
};
?>

<section class="request-record-hero">
    <div class="request-record-heading">
        <a class="request-record-back" href="<?= url('fund_requests'); ?>">
            <i class="bi bi-arrow-left"></i> Demandes de fonds
        </a>
        <div class="request-record-title">
            <div>
                <span class="section-kicker"><?= e($request['reference']); ?></span>
                <h2><?= e($request['title']); ?></h2>
                <p>Créée par <?= e($request['requester_name']); ?> · <?= e($formatDate($request['created_at'], true)); ?></p>
            </div>
            <span class="request-record-status <?= status_badge_class($request['status']); ?>">
                <i class="bi bi-<?= $request['status'] === 'Paid' ? 'check2-circle' : ($request['status'] === 'Rejected' ? 'x-circle' : 'clock-history'); ?>"></i>
                <?= e($statusLabel); ?>
            </span>
        </div>
    </div>

    <div class="request-record-amount">
        <span>Montant demandé</span>
        <strong><?= money($request['total_amount'], $request['currency']); ?></strong>
        <small><?= e($request['currency']); ?> · <?= e($request['department']); ?></small>
    </div>
</section>

<?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>

<section class="panel request-workflow-card">
    <div class="request-section-heading">
        <div>
            <span class="section-kicker">Progression</span>
            <h3>Cycle de traitement</h3>
        </div>
        <span class="request-next-step">
            <?php if ($request['status'] === 'Draft'): ?>Prochaine étape : soumission
            <?php elseif ($request['status'] === 'Pending'): ?>Prochaine étape : décision Direction
            <?php elseif ($request['status'] === 'Approved'): ?>Prochaine étape : paiement
            <?php elseif ($request['status'] === 'Paid'): ?>Traitement terminé
            <?php elseif ($request['status'] === 'Rejected'): ?>Traitement interrompu
            <?php else: ?>Dossier clôturé<?php endif; ?>
        </span>
    </div>

    <div class="request-workflow<?= $request['status'] === 'Rejected' ? ' is-rejected' : ''; ?>">
        <?php
        $steps = [
            ['status' => 'Draft', 'label' => 'Création', 'icon' => 'file-earmark-plus'],
            ['status' => 'Pending', 'label' => 'Soumission', 'icon' => 'send'],
            ['status' => 'Approved', 'label' => 'Approbation', 'icon' => 'shield-check'],
            ['status' => 'Paid', 'label' => 'Paiement', 'icon' => 'wallet2'],
        ];
        ?>
        <?php foreach ($steps as $index => $step): ?>
            <?php
            $isComplete = $index <= $currentStep && $request['status'] !== 'Rejected';
            $isCurrent = $index === $currentStep && !in_array($request['status'], ['Paid', 'Rejected'], true);
            if ($request['status'] === 'Rejected') {
                $isComplete = $index <= 1;
                $isCurrent = $index === 1;
            }
            ?>
            <div class="request-workflow-step<?= $isComplete ? ' is-complete' : ''; ?><?= $isCurrent ? ' is-current' : ''; ?>">
                <span><i class="bi bi-<?= e($step['icon']); ?>"></i></span>
                <strong><?= e($step['label']); ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($request['status'] === 'Rejected' && !empty($request['rejected_reason'])): ?>
        <div class="request-rejection-note">
            <i class="bi bi-exclamation-octagon"></i>
            <div><strong>Motif du rejet</strong><p><?= e($request['rejected_reason']); ?></p></div>
        </div>
    <?php endif; ?>
</section>

<div class="request-record-layout">
    <main class="request-record-main">
        <section class="panel request-information-card">
            <div class="request-section-heading">
                <div><span class="section-kicker">Informations</span><h3>Détails de la demande</h3></div>
            </div>

            <div class="request-information-grid">
                <article><span><i class="bi bi-person"></i> Demandeur</span><strong><?= e($request['requester_name']); ?></strong></article>
                <article><span><i class="bi bi-diagram-3"></i> Service</span><strong><?= e($request['department']); ?></strong></article>
                <article><span><i class="bi bi-calendar3"></i> Date souhaitée</span><strong><?= e($formatDate($request['needed_at'])); ?></strong></article>
                <article><span><i class="bi bi-bank"></i> Compte affecté</span><strong><?= e($request['account_name'] ?? 'Non affecté'); ?></strong></article>
            </div>

            <div class="request-purpose-block">
                <span>Objet et justification</span>
                <p><?= nl2br(e($request['purpose'])); ?></p>
            </div>
        </section>

        <section class="panel request-documents-card">
            <div class="request-section-heading">
                <div><span class="section-kicker">Documents</span><h3>Pièce justificative</h3></div>
                <span class="badge <?= $attachments !== [] ? 'badge-success' : 'badge-neutral'; ?>">
                    <?= $attachments !== [] ? count($attachments) . ' fichier' : 'Aucun fichier'; ?>
                </span>
            </div>

            <?php if ($attachments !== []): ?>
                <div class="request-document-list">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php
                        $attachmentUrl = rtrim(PUBLIC_URL, '/') . '/' . $attachment['file_path'];
                        $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                        ?>
                        <article class="request-document-item">
                            <a class="request-document-preview" href="<?= e($attachmentUrl); ?>" target="_blank" rel="noopener">
                                <?php if ($isImage): ?>
                                    <img src="<?= e($attachmentUrl); ?>" alt="Aperçu de <?= e($attachment['original_name']); ?>">
                                <?php else: ?>
                                    <i class="bi bi-file-earmark-pdf"></i>
                                <?php endif; ?>
                                <span><i class="bi bi-arrows-angle-expand"></i></span>
                            </a>
                            <div class="request-document-meta">
                                <strong><?= e($attachment['original_name']); ?></strong>
                                <small><?= e(file_size_label((int) $attachment['file_size'])); ?> · ajouté par <?= e($attachment['uploaded_by_name']); ?></small>
                            </div>
                            <a class="btn btn-secondary btn-compact" href="<?= e($attachmentUrl); ?>" target="_blank" rel="noopener">
                                <i class="bi bi-eye"></i> Ouvrir
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="request-record-empty">
                    <i class="bi bi-file-earmark"></i>
                    <div><strong>Aucun justificatif joint</strong><p>Cette demande a été créée sans pièce justificative.</p></div>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel request-history-card">
            <div class="request-section-heading">
                <div><span class="section-kicker">Traçabilité</span><h3>Historique du workflow</h3></div>
                <span class="request-history-count"><?= count($approvals); ?> événement(s)</span>
            </div>

            <?php if ($approvals !== []): ?>
                <div class="request-timeline">
                    <?php foreach (array_reverse($approvals) as $approval): ?>
                        <article class="request-timeline-item">
                            <span class="request-timeline-icon"><i class="bi bi-<?= $approval['action'] === 'Rejected' ? 'x-lg' : ($approval['action'] === 'Approved' ? 'check2' : 'send'); ?>"></i></span>
                            <div>
                                <strong><?= e($actionLabels[$approval['action']] ?? $approval['action']); ?></strong>
                                <small><?= e($approval['user_name']); ?> · <?= e($formatDate($approval['created_at'], true)); ?></small>
                                <?php if (!empty($approval['comment'])): ?><p><?= e($approval['comment']); ?></p><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="request-record-empty compact">
                    <i class="bi bi-clock-history"></i>
                    <div><strong>Aucun événement enregistré</strong><p>Le suivi apparaîtra après la soumission.</p></div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="request-record-sidebar">
        <section class="panel request-action-card">
            <span class="section-kicker">Action disponible</span>
            <?php if ($request['status'] === 'Draft' && Auth::can('fund_requests.create')): ?>
                <span class="request-action-icon"><i class="bi bi-send"></i></span>
                <h3>Soumettre la demande</h3>
                <p>Transmettez le dossier à la Direction pour décision.</p>
                <form method="post" action="<?= url('fund_requests/submit'); ?>">
                    <?= Csrf::field(); ?><input type="hidden" name="id" value="<?= (int) $request['id']; ?>">
                    <button class="btn btn-primary full-width" type="submit">Soumettre à la Direction</button>
                </form>
            <?php elseif ($request['status'] === 'Pending' && Auth::can('fund_requests.approve')): ?>
                <span class="request-action-icon"><i class="bi bi-shield-check"></i></span>
                <h3>Prendre une décision</h3>
                <p>Examinez le dossier avant de l’approuver ou de le rejeter.</p>
                <a class="btn btn-primary full-width" href="<?= url('fund_requests/approve?id=' . (int) $request['id']); ?>">Examiner la demande</a>
            <?php elseif ($request['status'] === 'Approved' && Auth::can('fund_requests.pay')): ?>
                <span class="request-action-icon"><i class="bi bi-wallet2"></i></span>
                <h3>Effectuer le paiement</h3>
                <p>Le dossier est approuvé et prêt pour le décaissement.</p>
                <a class="btn btn-primary full-width" href="<?= url('fund_requests/payment?id=' . (int) $request['id']); ?>">Procéder au paiement</a>
            <?php else: ?>
                <span class="request-action-icon is-muted"><i class="bi bi-check2-circle"></i></span>
                <h3><?= $request['status'] === 'Paid' ? 'Dossier traité' : 'Aucune action requise'; ?></h3>
                <p><?= $request['status'] === 'Paid' ? 'Le paiement de cette demande a été finalisé.' : 'Le statut actuel ne nécessite aucune action de votre part.'; ?></p>
            <?php endif; ?>
        </section>

        <section class="panel request-payment-card">
            <div class="request-section-heading">
                <div><span class="section-kicker">Paiement</span><h3>Preuves</h3></div>
            </div>
            <?php if ($proofs !== []): ?>
                <div class="request-proof-list">
                    <?php foreach ($proofs as $proof): ?>
                        <a href="<?= rtrim(PUBLIC_URL, '/') . '/' . e($proof['file_path']); ?>" target="_blank" rel="noopener">
                            <span><i class="bi bi-receipt"></i></span>
                            <div><strong><?= e($proof['original_name']); ?></strong><small><?= e($proof['uploaded_by_name']); ?> · <?= e($formatDate($proof['created_at'], true)); ?></small></div>
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="request-record-empty compact">
                    <i class="bi bi-receipt"></i>
                    <div><strong>Aucune preuve</strong><p>Disponible après paiement.</p></div>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>
