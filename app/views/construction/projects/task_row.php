<div class="fund-item-row construction-row">
    <label>Travail <input name="tasks[name][]" required value="<?= e($row['name'] ?? ''); ?>"></label>
    <label>Unité <input name="tasks[unit][]" required value="<?= e($row['unit'] ?? 'u'); ?>"></label>
    <label>Qté prévue <input type="number" step="0.01" min="0" name="tasks[planned_quantity][]" required value="<?= e((string) ($row['planned_quantity'] ?? 0)); ?>"></label>
    <label>Coût prévu <input type="number" step="0.01" min="0" name="tasks[planned_cost][]" required value="<?= e((string) ($row['planned_cost'] ?? 0)); ?>"></label>
    <label>Durée <input type="number" min="0" name="tasks[planned_duration_days][]" required value="<?= e((string) ($row['planned_duration_days'] ?? 0)); ?>"></label>
</div>

