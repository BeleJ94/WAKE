<section class="dashboard-section">
    <div class="section-header">
        <div>
            <span class="section-kicker">Sécurité</span>
            <h2>Journal d’audit</h2>
        </div>
        <span class="badge badge-neutral"><?= count($logs); ?> événements</span>
    </div>
</section>

<section class="panel">
    <form class="report-filters" method="get" action="<?= url('audit_logs/index'); ?>">
        <label>Type d’action <input name="action" value="<?= e($filters['action']); ?>" placeholder="Connexion, paiement, approbation…"></label>
        <label>Entité
            <select name="entity_type">
                <option value="">Toutes</option>
                <?php foreach ($entityTypes as $type): ?>
                    <option value="<?= e($type['entity_type']); ?>" <?= $filters['entity_type'] === $type['entity_type'] ? 'selected' : ''; ?>><?= e($type['entity_type']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Début <input type="date" name="start_date" value="<?= e($filters['start_date']); ?>"></label>
        <label>Fin <input type="date" name="end_date" value="<?= e($filters['end_date']); ?>"></label>
        <div class="report-filter-actions">
            <button class="btn btn-primary" type="submit">Filtrer</button>
            <a class="btn btn-secondary" href="<?= url('audit_logs/index'); ?>">Réinitialiser</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Traçabilité</span>
            <h3>Actions critiques et connexions</h3>
        </div>
    </div>
    <div class="filters-bar">
        <label>Recherche <input data-table-search placeholder="Utilisateur, action, IP"></label>
    </div>
    <div class="table-responsive">
        <table class="data-table" data-enhanced-table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Entité</th>
                    <th>Adresse IP</th>
                    <th>Informations techniques</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e($log['created_at']); ?></td>
                        <td>
                            <strong><?= e($log['user_name'] ?? 'Système'); ?></strong>
                            <small><?= e($log['user_email'] ?? ''); ?></small>
                        </td>
                        <td><span class="badge badge-neutral"><?= e(audit_action_label($log['action'])); ?></span></td>
                        <td><?= e(entity_type_label($log['entity_type'])); ?> #<?= e((string) ($log['entity_id'] ?? '—')); ?></td>
                        <td><?= e($log['ip_address'] ?? '-'); ?></td>
                        <td><code><?= e($log['metadata'] ?? '{}'); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($logs === []): ?>
                    <tr><td colspan="6"><div class="empty-state compact"><p>Aucun événement d’audit pour ces filtres.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
