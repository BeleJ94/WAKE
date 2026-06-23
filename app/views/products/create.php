<section class="panel form-panel">
    <div class="panel-header">
        <div><span class="section-kicker">Catalogue & Stock</span><h2>Ajouter un produit</h2><p class="ui-page-intro">Renseignez l’identité, les prix et les paramètres de suivi du stock.</p></div>
        <a class="btn btn-secondary" href="<?= url('products/index'); ?>"><i class="bi bi-arrow-left"></i> Retour au catalogue</a>
    </div>
    <?php if ($errors): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($errors['required']); ?></div><?php endif; ?>
    <form class="form-grid" method="post" action="<?= url('products/store'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label><span class="field-label">Référence SKU <em>Obligatoire</em></span><input name="sku" required value="<?= e($old['sku'] ?? ''); ?>" placeholder="Ex. MAT-0001"></label>
        <label><span class="field-label">Nom du produit <em>Obligatoire</em></span><input name="name" required value="<?= e($old['name'] ?? ''); ?>" placeholder="Désignation commerciale"></label>
        <label><span class="field-label">Catégorie</span><select name="product_category_id"><option value="">Sans catégorie</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id']; ?>"><?= e($category['name']); ?></option><?php endforeach; ?></select></label>
        <label><span class="field-label">Unité de gestion <em>Obligatoire</em></span><input name="unit" required value="<?= e($old['unit'] ?? 'u'); ?>" placeholder="u, kg, m²…"></label>
        <label><span class="field-label">Coût d’achat <em>Obligatoire</em></span><input type="number" min="0" step="0.01" name="cost_price" required value="<?= e((string) ($old['cost_price'] ?? 0)); ?>"></label>
        <label><span class="field-label">Prix de vente <em>Obligatoire</em></span><input type="number" min="0" step="0.01" name="sale_price" required value="<?= e((string) ($old['sale_price'] ?? 0)); ?>"></label>
        <label><span class="field-label">Stock initial</span><input type="number" min="0" step="0.01" name="stock_quantity" value="<?= e((string) ($old['stock_quantity'] ?? 0)); ?>"></label>
        <label><span class="field-label">Seuil d’alerte</span><input type="number" min="0" step="0.01" name="reorder_level" value="<?= e((string) ($old['reorder_level'] ?? 0)); ?>"><small class="field-hint">Une alerte sera affichée lorsque le stock atteint ce niveau.</small></label>
        <label><span class="field-label">Statut</span><select name="status"><option value="active">Actif</option><option value="inactive">Inactif</option></select></label>
        <div class="form-actions"><a class="btn btn-secondary" href="<?= url('products/index'); ?>">Annuler</a><button class="btn btn-primary" type="submit"><i class="bi bi-box-seam"></i> Ajouter le produit</button></div>
    </form>
</section>
