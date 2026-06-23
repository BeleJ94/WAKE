<section class="dashboard-section">
    <div class="section-header">
        <div>
            <span class="section-kicker">Centre d’alertes</span>
            <h2>Notifications internes</h2>
        </div>
        <form method="post" action="<?= url('notifications/read-all'); ?>" data-ajax-action>
            <?= Csrf::field(); ?>
            <button class="btn btn-secondary" type="submit"><i class="bi bi-check2-all"></i> Tout marquer comme lu</button>
        </form>
    </div>
    <?php if ($unreadCount > 0): ?>
        <div class="alert alert-info"><?= (int) $unreadCount; ?> notification<?= $unreadCount > 1 ? 's' : ''; ?> non lue<?= $unreadCount > 1 ? 's' : ''; ?>.</div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <span class="section-kicker">Activité à traiter</span>
            <h3>Dernières notifications</h3>
        </div>
        <span class="badge badge-neutral"><?= count($notifications); ?> éléments</span>
    </div>

    <div class="notification-list">
        <?php foreach ($notifications as $notification): ?>
            <article class="notification-item <?= $notification['read_at'] === null ? 'is-unread' : ''; ?>" data-notification-id="<?= (int) $notification['id']; ?>">
                <span class="notification-severity is-<?= e($notification['severity']); ?>"></span>
                <div>
                    <div class="notification-title">
                        <strong><?= e($notification['title']); ?></strong>
                        <small><?= e(date_fr($notification['created_at'], true)); ?></small>
                    </div>
                    <p><?= e($notification['message']); ?></p>
                    <div class="notification-actions">
                        <?php if (!empty($notification['link_url'])): ?>
                            <a class="btn btn-secondary btn-compact" href="<?= e($notification['link_url']); ?>"><i class="bi bi-folder2-open"></i> Ouvrir le dossier</a>
                        <?php endif; ?>
                        <?php if ($notification['read_at'] === null): ?>
                            <form method="post" action="<?= url('notifications/read'); ?>" data-ajax-action>
                                <?= Csrf::field(); ?>
                                <input type="hidden" name="id" value="<?= (int) $notification['id']; ?>">
                                <button class="btn btn-primary" type="submit">Marquer comme lu</button>
                            </form>
                        <?php else: ?>
                            <span class="badge badge-success">Lu</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if ($notifications === []): ?>
            <div class="empty-state compact">
                <i class="bi bi-bell"></i>
                <p>Aucune notification pour le moment.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
