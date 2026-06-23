<div class="fund-item-row construction-row">
    <label>Consommable
        <select name="materials[construction_material_id][]">
            <option value="">Sélectionner</option>
            <?php foreach ($materials as $material): ?><option value="<?= (int) $material['id']; ?>" <?= (int) ($row['construction_material_id'] ?? 0) === (int) $material['id'] ? 'selected' : ''; ?>><?= e($material['name']); ?> / <?= e($material['unit']); ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Qté prévue <input type="number" step="0.01" min="0" name="materials[planned_quantity][]" value="<?= e((string) ($row['planned_quantity'] ?? 0)); ?>"></label>
    <label>Coût prévu <input type="number" step="0.01" min="0" name="materials[planned_cost][]" value="<?= e((string) ($row['planned_cost'] ?? 0)); ?>"></label>
</div>

