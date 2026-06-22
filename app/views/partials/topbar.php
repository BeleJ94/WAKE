<?php
$notificationCount = 0;
$hasQuickActions = Auth::can('fund_requests.create')
    || Auth::can('treasury_transfers.create')
    || Auth::can('invoices.create')
    || Auth::can('clients.create')
    || Auth::can('reports.view');
if (Auth::check()) {
    try {
        $notificationModel = new Notification();
        $notificationModel->scanSystemAlerts();
        $notificationCount = $notificationModel->unreadCount((int) Auth::id());
    } catch (Throwable $exception) {
        $notificationCount = 0;
    }
}
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="icon-button menu-button" type="button" data-sidebar-toggle aria-label="Ouvrir le menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="page-heading">
            <nav class="breadcrumbs" aria-label="Fil d'Ariane">
                <a href="<?= url(); ?>">Accueil</a>
                <span aria-hidden="true">/</span>
                <span><?= e($title ?? 'Dashboard'); ?></span>
            </nav>
            <h1><?= e($title ?? 'Dashboard'); ?></h1>
        </div>
    </div>

    <div class="topbar-actions">
        <form class="search-box" role="search" data-global-search>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" placeholder="Rechercher un client, projet, facture..." aria-label="Recherche globale">
            <div class="global-search-results" data-global-search-results></div>
        </form>

        <?php if ($hasQuickActions): ?><div class="quick-actions">
            <button class="btn btn-primary" type="button" data-dropdown-toggle="quick-actions-menu">
                <span class="btn-glyph"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
                Actions rapides
            </button>
            <div class="dropdown-menu" id="quick-actions-menu" data-dropdown-menu>
                <?php if (Auth::can('fund_requests.create')): ?><a href="<?= url('fund_requests/create'); ?>"><i class="bi bi-clipboard-plus"></i> Nouvelle demande de fonds</a><?php endif; ?>
                <?php if (Auth::can('treasury_transfers.create')): ?><a href="<?= url('treasury_transfers/create'); ?>"><i class="bi bi-arrow-left-right"></i> Nouveau transfert</a><?php endif; ?>
                <?php if (Auth::can('invoices.create')): ?><a href="<?= url('invoices/create'); ?>"><i class="bi bi-receipt"></i> Créer une facture</a><?php endif; ?>
                <?php if (Auth::can('clients.create')): ?><a href="<?= url('clients/create'); ?>"><i class="bi bi-briefcase"></i> Créer un client</a><?php endif; ?>
                <?php if (Auth::can('reports.view')): ?><a href="<?= url('reports'); ?>"><i class="bi bi-bar-chart-line"></i> Consulter les rapports</a><?php endif; ?>
            </div>
        </div><?php endif; ?>

        <a class="icon-button notification-button" href="<?= url('notifications'); ?>" aria-label="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <?php if ($notificationCount > 0): ?>
                <span class="notification-count" data-notification-count><?= $notificationCount > 99 ? '99+' : (int) $notificationCount; ?></span>
            <?php endif; ?>
        </a>

        <div class="profile-dropdown">
            <button class="profile-menu" type="button" aria-label="Menu utilisateur" data-dropdown-toggle="profile-menu">
                <span class="avatar" aria-hidden="true"><?= e(strtoupper(substr(Auth::user()['name'] ?? 'AD', 0, 2))); ?></span>
                <span>
                    <strong><?= e(Auth::user()['name'] ?? 'Utilisateur'); ?></strong>
                    <small><?= e(Auth::user()['role_name'] ?? 'Rôle'); ?></small>
                </span>
            </button>
            <div class="dropdown-menu profile-dropdown-menu" id="profile-menu" data-dropdown-menu>
                <div class="profile-card">
                    <span class="avatar" aria-hidden="true"><?= e(strtoupper(substr(Auth::user()['name'] ?? 'AD', 0, 2))); ?></span>
                    <div>
                        <strong><?= e(Auth::user()['name'] ?? 'Utilisateur'); ?></strong>
                        <small><?= e(Auth::user()['email'] ?? ''); ?></small>
                    </div>
                </div>
                <a href="<?= url('notifications'); ?>"><i class="bi bi-bell"></i> Notifications</a>
                <a href="<?= url('reports'); ?>"><i class="bi bi-graph-up-arrow"></i> Rapports Direction</a>
                <form method="post" action="<?= url('logout'); ?>">
                    <?= Csrf::field(); ?>
                    <button type="submit"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
                </form>
            </div>
        </div>
    </div>
</header>
