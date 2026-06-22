<div class="form-grid">
    <label>Nom projet <input name="name" required value="<?= e($old['name'] ?? ''); ?>"></label>
    <label>Client <input name="client_name" required value="<?= e($old['client_name'] ?? ''); ?>"></label>
    <label>Montant contrat <input type="number" min="0" step="0.01" name="contract_amount" required value="<?= e((string) ($old['contract_amount'] ?? 0)); ?>"></label>
    <label>Coût prévisionnel <input type="number" min="0" step="0.01" name="forecast_cost" required value="<?= e((string) ($old['forecast_cost'] ?? 0)); ?>"></label>
    <label>Date début <input type="date" name="start_date" required value="<?= e($old['start_date'] ?? ''); ?>"></label>
    <label>Date fin <input type="date" name="end_date" required value="<?= e($old['end_date'] ?? ''); ?>"></label>
    <label>Chef de projet
        <select name="project_manager_id"><option value="">Sélectionner</option><?php foreach ($users as $user): ?><option value="<?= (int) $user['id']; ?>" <?= (int) ($old['project_manager_id'] ?? 0) === (int) $user['id'] ? 'selected' : ''; ?>><?= e($user['name']); ?></option><?php endforeach; ?></select>
    </label>
    <label>Statut
        <select name="status"><?php foreach (['Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled'] as $status): ?><option value="<?= e($status); ?>" <?= ($old['status'] ?? 'Planning') === $status ? 'selected' : ''; ?>><?= e(status_label($status)); ?></option><?php endforeach; ?></select>
    </label>
    <label class="span-2">Localisation <input name="location" required value="<?= e($old['location'] ?? ''); ?>"></label>
    <label class="span-2">Notes <textarea name="notes" rows="3"><?= e($old['notes'] ?? ''); ?></textarea></label>
</div>
