<section class="fund-request-hero">
    <div class="fund-request-hero-copy">
        <span class="section-kicker">Finance & Trésorerie</span>
        <h2>Nouvelle demande de fonds</h2>
        <p>Présentez votre besoin clairement, indiquez le montant souhaité et joignez un justificatif pour accélérer la décision.</p>
    </div>
    <a class="btn btn-secondary" href="<?= url('fund_requests'); ?>">
        <i class="bi bi-arrow-left"></i> Retour aux demandes
    </a>
</section>

<?php if (isset($errors['attachment'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($errors['attachment']); ?></div>
<?php endif; ?>

<form
    class="fund-request-form"
    method="post"
    action="<?= url('fund_requests/store'); ?>"
    enctype="multipart/form-data"
    data-validate
    data-fund-request-form
>
    <?= Csrf::field(); ?>

    <div class="fund-request-layout">
        <div class="fund-request-main">
            <section class="panel request-form-card">
                <div class="request-card-heading">
                    <span class="request-step">1</span>
                    <div>
                        <h3>Informations de la demande</h3>
                        <p>Décrivez le besoin de manière concise et exploitable.</p>
                    </div>
                </div>

                <div class="form-grid request-fields">
                    <label>
                        <span class="field-label">Titre de la demande <em>Obligatoire</em></span>
                        <input class="<?= isset($errors['title']) ? 'is-invalid' : ''; ?>" type="text" name="title" required maxlength="180" placeholder="Ex. Achat de matériel pour le chantier" value="<?= e($old['title'] ?? ''); ?>">
                        <?php if (isset($errors['title'])): ?><small class="field-error"><?= e($errors['title']); ?></small><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label">Service demandeur <em>Obligatoire</em></span>
                        <input class="<?= isset($errors['department']) ? 'is-invalid' : ''; ?>" type="text" name="department" required maxlength="120" placeholder="Ex. Construction" value="<?= e($old['department'] ?? ''); ?>">
                        <?php if (isset($errors['department'])): ?><small class="field-error"><?= e($errors['department']); ?></small><?php endif; ?>
                    </label>

                    <label>
                        <span class="field-label">Date souhaitée</span>
                        <input type="date" name="needed_at" value="<?= e($old['needed_at'] ?? ''); ?>">
                        <small class="field-hint">Date à laquelle les fonds sont nécessaires.</small>
                    </label>

                    <label class="span-2">
                        <span class="field-label">Objet et justification <em>Obligatoire</em></span>
                        <textarea class="<?= isset($errors['purpose']) ? 'is-invalid' : ''; ?>" name="purpose" required rows="5" maxlength="2000" placeholder="Expliquez le contexte, l’utilisation prévue des fonds et le résultat attendu."><?= e($old['purpose'] ?? ''); ?></textarea>
                        <?php if (isset($errors['purpose'])): ?><small class="field-error"><?= e($errors['purpose']); ?></small><?php else: ?><small class="field-hint">Une justification précise facilite l’approbation.</small><?php endif; ?>
                    </label>
                </div>
            </section>

            <section class="panel request-form-card">
                <div class="request-card-heading">
                    <span class="request-step">2</span>
                    <div>
                        <h3>Montant demandé</h3>
                        <p>Saisissez le montant exact et choisissez sa monnaie.</p>
                    </div>
                </div>

                <div class="money-entry">
                    <label class="money-amount-field">
                        <span class="field-label">Montant <em>Obligatoire</em></span>
                        <span class="money-input-shell">
                            <i class="bi bi-cash-stack"></i>
                            <input class="<?= isset($errors['total_amount']) ? 'is-invalid' : ''; ?>" type="number" name="total_amount" min="0.01" step="0.01" required placeholder="0,00" value="<?= e((string) ($old['total_amount'] ?? '')); ?>" data-request-amount>
                        </span>
                        <?php if (isset($errors['total_amount'])): ?><small class="field-error"><?= e($errors['total_amount']); ?></small><?php endif; ?>
                    </label>

                    <label class="money-currency-field">
                        <span class="field-label">Monnaie <em>Obligatoire</em></span>
                        <select class="<?= isset($errors['currency']) ? 'is-invalid' : ''; ?>" name="currency" required data-request-currency>
                            <option value="USD" <?= ($old['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD — Dollar américain</option>
                            <option value="CDF" <?= ($old['currency'] ?? '') === 'CDF' ? 'selected' : ''; ?>>CDF — Franc congolais</option>
                        </select>
                        <?php if (isset($errors['currency'])): ?><small class="field-error"><?= e($errors['currency']); ?></small><?php endif; ?>
                    </label>
                </div>
            </section>

            <section class="panel request-form-card">
                <div class="request-card-heading">
                    <span class="request-step">3</span>
                    <div>
                        <h3>Pièce justificative</h3>
                        <p>Ajoutez un document permettant à la Direction d’évaluer la demande.</p>
                    </div>
                    <span class="optional-badge">Optionnel</span>
                </div>

                <div class="supporting-upload" data-supporting-upload>
                    <input class="supporting-upload-input" id="supporting-document" type="file" name="supporting_document" accept="application/pdf,image/png,image/jpeg" data-supporting-input>
                    <label class="supporting-dropzone" for="supporting-document" data-supporting-dropzone>
                        <span class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                        <strong>Choisir une pièce justificative</strong>
                        <span>ou glissez-déposez le fichier ici</span>
                        <small>PDF, JPG ou PNG · 5 Mo maximum</small>
                    </label>

                    <div class="supporting-preview" data-supporting-preview hidden>
                        <div class="supporting-preview-frame" data-supporting-preview-frame></div>
                        <div class="supporting-preview-meta">
                            <span class="file-type-icon" data-supporting-file-icon><i class="bi bi-file-earmark-check"></i></span>
                            <div>
                                <strong data-supporting-file-name></strong>
                                <small data-supporting-file-size></small>
                            </div>
                            <button class="icon-button" type="button" data-supporting-remove aria-label="Retirer la pièce justificative">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="fund-request-aside">
            <div class="request-summary-card">
                <span class="section-kicker">Récapitulatif</span>
                <h3>Votre demande</h3>
                <div class="request-summary-amount">
                    <span>Montant sollicité</span>
                    <strong data-request-summary-amount>0,00 USD</strong>
                </div>
                <div class="request-summary-row">
                    <i class="bi bi-shield-check"></i>
                    <div><strong>Traitement sécurisé</strong><small>La demande suivra le circuit d’approbation.</small></div>
                </div>
                <div class="request-summary-row">
                    <i class="bi bi-paperclip"></i>
                    <div><strong>Justificatif recommandé</strong><small>Un document clair accélère la décision.</small></div>
                </div>
                <div class="request-summary-row">
                    <i class="bi bi-clock-history"></i>
                    <div><strong>Traçabilité complète</strong><small>Chaque étape sera enregistrée dans l’historique.</small></div>
                </div>
            </div>
        </aside>
    </div>

    <div class="fund-request-actions">
        <div>
            <strong>Prêt à enregistrer ?</strong>
            <span>Vous pouvez conserver un brouillon ou transmettre directement la demande.</span>
        </div>
        <div class="form-actions">
            <button class="btn btn-secondary" name="action" value="draft" type="submit">
                <i class="bi bi-save"></i> Enregistrer comme brouillon
            </button>
            <button class="btn btn-primary" name="action" value="submit" type="submit">
                Soumettre à la Direction <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
</form>
