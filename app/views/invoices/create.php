<section class="dashboard-section">
    <div class="section-header">
        <div>
            <span class="section-kicker">Nouvelle facture</span>
            <h2>Créer une facture centralisée</h2>
        </div>
        <a class="btn btn-secondary" href="<?= url('invoices/index'); ?>"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
</section>

<section class="two-column-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Facture libre</span>
                <h3>Client, source et lignes</h3>
            </div>
            <span class="badge badge-neutral">Manuel</span>
        </div>
        <form method="post" action="<?= url('invoices/store'); ?>" data-validate>
            <?= Csrf::field(); ?>
            <div class="form-grid">
                <label>Client
                    <select name="client_id" required>
                        <option value="">Sélectionner</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id']; ?>"><?= e($client['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Source
                    <select name="source_type" required>
                        <option value="manual">Autre service</option>
                        <option value="construction_project">Projet construction</option>
                        <option value="other_service">Prestation diverse</option>
                    </select>
                </label>
                <label>Date de facturation <input type="date" name="invoice_date" value="<?= date('Y-m-d'); ?>" required></label>
                <label>Date d’échéance <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+15 days')); ?>"></label>
                <label>Statut
                    <select name="status">
                        <option value="Sent">Envoyée</option>
                        <option value="Draft">Brouillon</option>
                    </select>
                </label>
                <label class="span-2">Conditions de paiement
                    <input name="payment_terms" value="Paiement à 15 jours sauf accord contractuel contraire.">
                </label>
                <label class="span-2">Notes
                    <textarea name="notes" rows="2" placeholder="Référence contrat, chantier, période ou observation client"></textarea>
                </label>
            </div>

            <div class="invoice-lines" data-invoice-lines>
                <div class="invoice-line invoice-line-head">
                    <span>Description</span><span>Qté</span><span>PU</span><span>Coût</span><span>Taxe %</span><span></span>
                </div>
                <?php for ($index = 0; $index < 3; $index++): ?>
                    <div class="invoice-line">
                        <input name="description[]" placeholder="Ligne facture <?= $index + 1; ?>" <?= $index === 0 ? 'required' : ''; ?>>
                        <input type="number" step="0.01" min="0" name="quantity[]" value="<?= $index === 0 ? '1' : ''; ?>" <?= $index === 0 ? 'required' : ''; ?>>
                        <input type="number" step="0.01" min="0" name="unit_price[]" <?= $index === 0 ? 'required' : ''; ?>>
                        <input type="number" step="0.01" min="0" name="unit_cost[]">
                        <input type="number" step="0.01" min="0" name="tax_rate[]" value="0">
                        <button class="btn-icon" type="button" data-remove-line title="Retirer"><i class="bi bi-x-lg"></i></button>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="form-actions split-actions">
                <button class="btn btn-secondary" type="button" data-add-invoice-line><i class="bi bi-plus"></i> Ajouter une ligne</button>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Créer la facture</button>
            </div>
        </form>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Génération métier</span>
                <h3>Placement de personnel</h3>
            </div>
            <span class="badge badge-success">Centralisé</span>
        </div>
        <form method="post" action="<?= url('invoices/generate-placement'); ?>" data-validate>
            <?= Csrf::field(); ?>
            <div class="form-grid">
                <label class="span-2">Contrat actif
                    <select name="contract_id" required>
                        <option value="">Sélectionner un contrat</option>
                        <?php foreach ($placementContracts as $contract): ?>
                            <option value="<?= (int) $contract['id']; ?>"><?= e($contract['reference']); ?> · <?= e($contract['client_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">Période
                    <input type="month" name="invoice_month" value="<?= date('Y-m'); ?>" required>
                </label>
            </div>
            <div class="empty-state compact">
                <i class="bi bi-receipt"></i>
                <p>Les agents actifs du contrat deviennent automatiquement des lignes de facture dans le module unique.</p>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><i class="bi bi-lightning-charge"></i> Générer</button>
            </div>
        </form>
    </aside>
</section>
