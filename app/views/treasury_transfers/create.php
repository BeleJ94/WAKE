<section class="transfer-create-hero">
    <a class="transfer-back" href="<?= url('treasury_transfers'); ?>"><i class="bi bi-arrow-left"></i></a>
    <div><span class="section-kicker">Nouvelle opération interne</span><h2>Préparer un transfert de fonds</h2><p>Définissez les comptes, le montant et le motif. L’opération ne modifiera aucun solde avant approbation et exécution.</p></div>
</section>

<?php if (isset($errors['transfer'])): ?><div class="alert alert-danger"><?= e($errors['transfer']); ?></div><?php endif; ?>

<form class="transfer-create-layout" method="post" action="<?= url('treasury_transfers/store'); ?>" enctype="multipart/form-data" data-transfer-form data-validate>
    <?= Csrf::field(); ?>
    <div class="transfer-form-main">
        <section class="panel transfer-form-card">
            <div class="transfer-step-head"><span>01</span><div><h3>Trajet financier</h3><p>Sélectionnez deux comptes actifs et distincts.</p></div></div>
            <div class="transfer-account-grid">
                <label class="transfer-account-field">
                    <span class="field-label">Compte source <em>Obligatoire</em></span>
                    <select name="source_account_id" required data-transfer-source>
                        <option value="">Sélectionner le compte à débiter</option>
                        <?php foreach ($accounts as $account): ?><option value="<?= (int) $account['id']; ?>" data-currency="<?= e($account['currency']); ?>" data-balance="<?= e((string) $account['current_balance']); ?>" <?= (int) ($old['source_account_id'] ?? 0) === (int) $account['id'] ? 'selected' : ''; ?>><?= e($account['name']); ?> · <?= money($account['current_balance'], $account['currency']); ?></option><?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['source_account_id'])): ?><small class="field-error"><?= e($errors['source_account_id']); ?></small><?php endif; ?>
                    <small class="transfer-account-balance" data-transfer-source-balance>Sélectionnez un compte source.</small>
                </label>
                <div class="transfer-direction"><span><i class="bi bi-arrow-right"></i></span></div>
                <label class="transfer-account-field">
                    <span class="field-label">Compte destinataire <em>Obligatoire</em></span>
                    <select name="destination_account_id" required data-transfer-destination>
                        <option value="">Sélectionner le compte à créditer</option>
                        <?php foreach ($accounts as $account): ?><option value="<?= (int) $account['id']; ?>" data-currency="<?= e($account['currency']); ?>" data-balance="<?= e((string) $account['current_balance']); ?>" <?= (int) ($old['destination_account_id'] ?? 0) === (int) $account['id'] ? 'selected' : ''; ?>><?= e($account['name']); ?> · <?= money($account['current_balance'], $account['currency']); ?></option><?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['destination_account_id'])): ?><small class="field-error"><?= e($errors['destination_account_id']); ?></small><?php endif; ?>
                    <small class="transfer-account-balance" data-transfer-destination-balance>Sélectionnez un compte destinataire.</small>
                </label>
            </div>
        </section>

        <section class="panel transfer-form-card">
            <div class="transfer-step-head"><span>02</span><div><h3>Montant et conversion</h3><p>Le taux représente la quantité reçue pour une unité débitée.</p></div></div>
            <div class="transfer-money-grid">
                <label><span class="field-label">Montant à débiter <em>Obligatoire</em></span><div class="transfer-money-input"><input type="number" name="source_amount" min="0.01" step="0.01" required value="<?= e((string) ($old['source_amount'] ?? '')); ?>" data-transfer-amount><span data-transfer-source-currency>—</span></div><?php if (isset($errors['source_amount'])): ?><small class="field-error"><?= e($errors['source_amount']); ?></small><?php endif; ?></label>
                <label><span class="field-label">Taux de change</span><div class="transfer-money-input"><input type="number" name="exchange_rate" min="0.000001" step="0.000001" required value="<?= e((string) ($old['exchange_rate'] ?? '1')); ?>" data-transfer-rate><span><i class="bi bi-calculator"></i></span></div><?php if (isset($errors['exchange_rate'])): ?><small class="field-error"><?= e($errors['exchange_rate']); ?></small><?php endif; ?></label>
                <div class="transfer-received"><span>Montant à créditer</span><strong data-transfer-received>0,00 —</strong><small data-transfer-rate-hint>Le calcul apparaîtra ici.</small></div>
            </div>
        </section>

        <section class="panel transfer-form-card">
            <div class="transfer-step-head"><span>03</span><div><h3>Justification et traçabilité</h3><p>Documentez clairement la raison opérationnelle.</p></div></div>
            <div class="form-grid transfer-purpose-grid">
                <label class="span-2"><span class="field-label">Motif du transfert <em>Obligatoire</em></span><input name="purpose" required minlength="5" maxlength="255" value="<?= e($old['purpose'] ?? ''); ?>" placeholder="Ex. Approvisionnement de la caisse opérationnelle"><?php if (isset($errors['purpose'])): ?><small class="field-error"><?= e($errors['purpose']); ?></small><?php endif; ?></label>
                <label class="span-2"><span class="field-label">Notes complémentaires</span><textarea name="notes" rows="3" placeholder="Contexte, instructions ou référence interne…"><?= e($old['notes'] ?? ''); ?></textarea></label>
                <label class="span-2 transfer-upload"><span class="field-label">Pièce justificative <em>Optionnel</em></span><div><i class="bi bi-cloud-arrow-up"></i><span><strong>Ajouter un PDF ou une image</strong><small>PDF, JPG ou PNG · 5 Mo maximum</small></span><input type="file" name="supporting_document" accept=".pdf,.jpg,.jpeg,.png"></div></label>
            </div>
        </section>
    </div>

    <aside class="transfer-summary-card">
        <div class="transfer-summary-head"><span><i class="bi bi-shield-check"></i></span><div><small>Contrôle avant soumission</small><h3>Résumé du transfert</h3></div></div>
        <div class="transfer-summary-route"><strong data-transfer-summary-source>Compte source</strong><span><i class="bi bi-arrow-down"></i></span><strong data-transfer-summary-destination>Compte destinataire</strong></div>
        <div class="transfer-summary-values"><div><span>Montant débité</span><strong data-transfer-summary-debit>0,00 —</strong></div><div><span>Montant crédité</span><strong data-transfer-summary-credit>0,00 —</strong></div></div>
        <ul><li><i class="bi bi-check2"></i> Validation du solde disponible</li><li><i class="bi bi-check2"></i> Approbation indépendante</li><li><i class="bi bi-check2"></i> Débit et crédit atomiques</li><li><i class="bi bi-check2"></i> Journal d’audit complet</li></ul>
        <div class="transfer-summary-actions"><button class="btn btn-secondary" type="submit" name="action" value="draft"><i class="bi bi-file-earmark"></i> Enregistrer brouillon</button><button class="btn btn-primary" type="submit" name="action" value="submit"><i class="bi bi-send"></i> Soumettre à validation</button></div>
    </aside>
</form>
