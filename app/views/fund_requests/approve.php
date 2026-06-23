<section class="approval-hero">
    <div>
        <span class="section-kicker">Décision Direction</span>
        <h2>Examiner la demande <?= e($request['reference']); ?></h2>
        <p>Consultez le contexte, vérifiez le justificatif et formalisez votre décision.</p>
    </div>
    <a class="btn btn-secondary" href="<?= url('fund_requests/show?id=' . (int) $request['id']); ?>">
        <i class="bi bi-arrow-left"></i> Retour au dossier
    </a>
</section>

<?php if ($message = Session::flash('error')): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($message); ?></div>
<?php endif; ?>

<div class="approval-layout">
    <main class="approval-main">
        <section class="panel approval-request-card">
            <div class="approval-card-heading">
                <div>
                    <span class="section-kicker">Demande à examiner</span>
                    <h3><?= e($request['title']); ?></h3>
                </div>
                <span class="badge badge-warning"><?= e(status_label($request['status'])); ?></span>
            </div>

            <div class="approval-key-data">
                <article class="approval-amount">
                    <span>Montant sollicité</span>
                    <strong><?= money($request['total_amount'], $request['currency']); ?></strong>
                    <small>Monnaie : <?= e($request['currency']); ?></small>
                </article>
                <article>
                    <span>Demandeur</span>
                    <strong><?= e($request['requester_name']); ?></strong>
                </article>
                <article>
                    <span>Service</span>
                    <strong><?= e($request['department']); ?></strong>
                </article>
                <article>
                    <span>Date souhaitée</span>
                    <strong><?= e($request['needed_at'] ?: 'Non précisée'); ?></strong>
                </article>
            </div>

            <div class="approval-purpose">
                <span>Objet et justification</span>
                <p><?= nl2br(e($request['purpose'])); ?></p>
            </div>
        </section>

        <section class="panel approval-document-card">
            <div class="approval-card-heading">
                <div>
                    <span class="section-kicker">Contrôle documentaire</span>
                    <h3>Pièce justificative</h3>
                </div>
                <?php if ($attachments !== []): ?>
                    <span class="badge badge-success"><i class="bi bi-check2-circle"></i> Disponible</span>
                <?php else: ?>
                    <span class="badge badge-neutral">Non jointe</span>
                <?php endif; ?>
            </div>

            <?php if ($attachments !== []): ?>
                <?php foreach ($attachments as $attachment): ?>
                    <?php
                    $attachmentUrl = rtrim(PUBLIC_URL, '/') . '/' . $attachment['file_path'];
                    $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                    ?>
                    <article class="approval-document">
                        <div class="approval-document-preview">
                            <?php if ($isImage): ?>
                                <img src="<?= e($attachmentUrl); ?>" alt="Aperçu de <?= e($attachment['original_name']); ?>">
                            <?php else: ?>
                                <iframe src="<?= e($attachmentUrl); ?>#toolbar=0" title="Aperçu du justificatif <?= e($attachment['original_name']); ?>"></iframe>
                            <?php endif; ?>
                        </div>
                        <div class="approval-document-footer">
                            <span class="file-type-icon"><i class="bi bi-<?= $isImage ? 'file-earmark-image' : 'file-earmark-pdf'; ?>"></i></span>
                            <div>
                                <strong><?= e($attachment['original_name']); ?></strong>
                                <small><?= e(file_size_label((int) $attachment['file_size'])); ?> · ajouté par <?= e($attachment['uploaded_by_name']); ?></small>
                            </div>
                            <a class="btn btn-secondary" href="<?= e($attachmentUrl); ?>" target="_blank" rel="noopener">
                                <i class="bi bi-arrows-angle-expand"></i> Ouvrir
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="approval-empty-document">
                    <i class="bi bi-file-earmark-x"></i>
                    <div><strong>Aucune pièce justificative</strong><span>La décision peut être prise sur la base des informations fournies.</span></div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="approval-sidebar">
        <form class="panel approval-decision-card" method="post" action="<?= url('fund_requests/approve'); ?>" data-validate data-balance-form data-approval-form>
            <?= Csrf::field(); ?>
            <input type="hidden" name="id" value="<?= (int) $request['id']; ?>">

            <div class="approval-card-heading">
                <div>
                    <span class="section-kicker">Votre décision</span>
                    <h3>Statuer sur la demande</h3>
                </div>
            </div>

            <p class="approval-help">Choisissez d’abord une décision. Les informations nécessaires s’afficheront ensuite.</p>

            <div class="decision-choice-grid">
                <label class="decision-choice decision-choice-approve">
                    <input type="radio" name="decision" value="approve" data-decision-choice>
                    <span><i class="bi bi-check2-circle"></i></span>
                    <strong>Approuver</strong>
                    <small>Autoriser le paiement depuis un compte.</small>
                </label>
                <label class="decision-choice decision-choice-reject">
                    <input type="radio" name="decision" value="reject" data-decision-choice>
                    <span><i class="bi bi-x-circle"></i></span>
                    <strong>Rejeter</strong>
                    <small>Refuser la demande avec un motif.</small>
                </label>
            </div>

            <div class="decision-panel decision-panel-approve" data-decision-panel="approve" hidden>
                <label>
                    <span class="field-label">Compte à utiliser <em>Obligatoire</em></span>
                    <select name="treasury_account_id" data-balance-account data-amount="<?= e((string) $request['total_amount']); ?>">
                        <option value="">Sélectionner un compte en <?= e($request['currency']); ?></option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= (int) $account['id']; ?>">
                                <?= e($account['name']); ?> · <?= money($account['current_balance'], $account['currency']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($accounts === []): ?>
                        <small class="balance-result is-danger">Aucun compte actif en <?= e($request['currency']); ?> n’est disponible.</small>
                    <?php else: ?>
                        <small data-balance-result class="balance-result">Sélectionnez un compte pour contrôler son solde.</small>
                    <?php endif; ?>
                </label>
                <label>
                    <span class="field-label">Commentaire d’approbation</span>
                    <textarea name="comment" rows="3" placeholder="Ajoutez une instruction ou une observation facultative."></textarea>
                </label>
            </div>

            <div class="decision-panel decision-panel-reject" data-decision-panel="reject" hidden>
                <label>
                    <span class="field-label">Motif de rejet <em>Obligatoire</em></span>
                    <textarea name="rejected_reason" rows="4" placeholder="Expliquez clairement pourquoi la demande est rejetée."></textarea>
                    <small class="field-hint">Ce motif sera conservé dans l’historique et visible par le demandeur.</small>
                </label>
            </div>

            <button class="btn btn-primary approval-submit" type="submit" data-approval-submit disabled>
                <i class="bi bi-shield-check"></i> Confirmer la décision
            </button>
        </form>
    </aside>
</div>
