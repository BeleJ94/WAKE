<?php
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
$basePath = trim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
if ($basePath !== '' && strpos($currentPath, $basePath) === 0) {
    $currentPath = trim(substr($currentPath, strlen($basePath)), '/');
}

function wake_nav_path(string $href): string
{
    $path = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');
    $base = trim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = trim(substr($path, strlen($base)), '/');
    }
    $path = preg_replace('#/index$#', '', $path) ?: $path;

    return $path === '' ? 'dashboard' : $path;
}

function wake_nav_active(string $href, string $currentPath): bool
{
    $path = wake_nav_path($href);
    $currentPath = $currentPath === '' ? 'dashboard' : $currentPath;

    return $currentPath === $path || strpos($currentPath, $path . '/') === 0;
}

function wake_nav_allowed(?string $permission): bool
{
    return $permission === null || Auth::can($permission);
}

function wake_visible_nav_items(array $items): array
{
    return array_values(array_filter($items, static function (array $item): bool {
        return wake_nav_allowed($item['permission'] ?? null);
    }));
}

function wake_active_nav_href(array $items, string $currentPath): ?string
{
    $activeHref = null;
    $activeLength = -1;

    foreach ($items as $item) {
        $path = wake_nav_path($item['href']);
        if (wake_nav_active($item['href'], $currentPath) && strlen($path) > $activeLength) {
            $activeHref = $item['href'];
            $activeLength = strlen($path);
        }
    }

    return $activeHref;
}

$navigationGroups = [
    [
        'id' => 'commercial',
        'label' => 'Commercial & Ventes',
        'description' => 'Du prospect à la livraison',
        'icon' => 'graph-up-arrow',
        'items' => [
            ['label' => 'Clients', 'href' => url('clients/index'), 'icon' => 'briefcase', 'permission' => 'clients.view'],
            ['label' => 'Produits & stock', 'href' => url('products/index'), 'icon' => 'boxes', 'permission' => 'products.view'],
            ['label' => 'Devis', 'href' => url('quotations/index'), 'icon' => 'file-earmark-text', 'permission' => 'quotations.view'],
            ['label' => 'Commandes', 'href' => url('sales_orders/index'), 'icon' => 'cart-check', 'permission' => 'sales_orders.view'],
            ['label' => 'Livraisons', 'href' => url('deliveries/index'), 'icon' => 'truck', 'permission' => 'deliveries.view'],
        ],
    ],
    [
        'id' => 'finance',
        'label' => 'Finance & Trésorerie',
        'description' => 'Contrôle des flux financiers',
        'icon' => 'cash-stack',
        'items' => [
            ['label' => 'Vue financière', 'href' => url('finance/reports'), 'icon' => 'pie-chart', 'permission' => 'finance.reports'],
            ['label' => 'Demandes de fonds', 'href' => url('fund_requests'), 'icon' => 'clipboard-check', 'permission' => 'fund_requests.view'],
            ['label' => 'Caisses & banques', 'href' => url('treasury_accounts'), 'icon' => 'bank', 'permission' => 'cashbanks.view'],
            ['label' => 'Transferts de fonds', 'href' => url('treasury_transfers'), 'icon' => 'arrow-left-right', 'permission' => 'treasury_transfers.view'],
            ['label' => 'Mouvements', 'href' => url('treasury_movements'), 'icon' => 'arrow-left-right', 'permission' => 'treasury_movements.view'],
            ['label' => 'Facturation', 'href' => url('invoices/index'), 'icon' => 'receipt-cutoff', 'permission' => 'sales_invoices.view'],
            ['label' => 'Paiements clients', 'href' => url('invoices/payment'), 'icon' => 'credit-card', 'permission' => 'invoices.payment'],
        ],
    ],
    [
        'id' => 'construction',
        'label' => 'Construction',
        'description' => 'Pilotage des projets et chantiers',
        'icon' => 'building-gear',
        'items' => [
            ['label' => 'Projets', 'href' => url('construction/projects'), 'icon' => 'buildings', 'permission' => 'projects.view'],
            ['label' => 'Cockpit chantier', 'href' => url('construction/projects/dashboard'), 'icon' => 'speedometer', 'permission' => 'projects.view'],
            ['label' => 'Rapports construction', 'href' => url('construction/reports'), 'icon' => 'bar-chart-line', 'permission' => 'construction.reports'],
        ],
    ],
    [
        'id' => 'placement',
        'label' => 'Placement',
        'description' => 'Personnel, contrats et présence',
        'icon' => 'people',
        'items' => [
            ['label' => 'Agents', 'href' => url('placement/employees/index'), 'icon' => 'person-vcard', 'permission' => 'placement.employees.view'],
            ['label' => 'Contrats clients', 'href' => url('placement/contracts/index'), 'icon' => 'file-earmark-check', 'permission' => 'placement.contracts.view'],
            ['label' => 'Présences', 'href' => url('placement/attendance'), 'icon' => 'calendar-check', 'permission' => 'placement.attendance.manage'],
            ['label' => 'Factures placement', 'href' => url('placement/invoices'), 'icon' => 'receipt', 'permission' => 'placement.invoices.manage'],
            ['label' => 'Rapports placement', 'href' => url('placement/reports'), 'icon' => 'graph-up', 'permission' => 'placement.reports'],
        ],
    ],
    [
        'id' => 'administration',
        'label' => 'Administration',
        'description' => 'Accès, sécurité et traçabilité',
        'icon' => 'shield-lock',
        'items' => [
            ['label' => 'Utilisateurs', 'href' => url('users'), 'icon' => 'person-gear', 'permission' => 'users.view'],
            ['label' => 'Rôles & permissions', 'href' => url('roles'), 'icon' => 'person-badge', 'permission' => 'roles.view'],
            ['label' => 'Journal d’audit', 'href' => url('audit_logs'), 'icon' => 'journal-text', 'permission' => 'audit_logs.view'],
        ],
    ],
];

$dashboardActive = wake_nav_active(url('dashboard'), $currentPath);
$reportsActive = wake_nav_active(url('reports'), $currentPath);
$notificationsActive = wake_nav_active(url('notifications'), $currentPath);
?>

<aside class="sidebar" data-sidebar aria-label="Navigation principale">
    <div class="sidebar-header">
        <a class="brand" href="<?= url(); ?>" aria-label="<?= e(APP_NAME); ?>">
            <span class="brand-mark">W</span>
            <span class="brand-copy">
                <strong><?= e(APP_NAME); ?></strong>
                <small><?= e(APP_COMPANY); ?></small>
            </span>
        </a>
        <button class="icon-button sidebar-close" type="button" data-sidebar-close aria-label="Fermer le menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sidebar-context">
        <span>Espace de travail</span>
        <strong>Gestion intégrée</strong>
    </div>

    <nav class="sidebar-nav" aria-label="Modules de l’application">
        <div class="nav-section">
            <span class="nav-section-label">Pilotage</span>
            <a class="nav-item nav-item-primary<?= $dashboardActive ? ' is-active' : ''; ?>" href="<?= url('dashboard'); ?>"<?= $dashboardActive ? ' aria-current="page"' : ''; ?>>
                <span class="nav-icon" aria-hidden="true"><i class="bi bi-grid-1x2"></i></span>
                <span class="nav-item-copy">
                    <strong>Vue d’ensemble</strong>
                    <small>Indicateurs et alertes clés</small>
                </span>
            </a>
        </div>

        <div class="nav-section nav-section-modules">
            <span class="nav-section-label">Modules métier</span>

            <?php foreach ($navigationGroups as $group): ?>
                <?php
                $visibleItems = wake_visible_nav_items($group['items']);
                if ($visibleItems === []) {
                    continue;
                }
                $activeItemHref = wake_active_nav_href($visibleItems, $currentPath);
                $groupActive = $activeItemHref !== null;
                $panelId = 'nav-group-' . $group['id'];
                ?>
                <div class="nav-group<?= $groupActive ? ' is-open has-active-item' : ''; ?>" data-nav-group>
                    <button
                        class="nav-group-toggle"
                        type="button"
                        data-nav-group-toggle
                        aria-expanded="<?= $groupActive ? 'true' : 'false'; ?>"
                        aria-controls="<?= e($panelId); ?>"
                    >
                        <span class="nav-icon" aria-hidden="true"><i class="bi bi-<?= e($group['icon']); ?>"></i></span>
                        <span class="nav-item-copy">
                            <strong><?= e($group['label']); ?></strong>
                            <small><?= e($group['description']); ?></small>
                        </span>
                        <i class="bi bi-chevron-down nav-group-chevron" aria-hidden="true"></i>
                    </button>

                    <div class="nav-group-panel" id="<?= e($panelId); ?>" data-nav-group-panel>
                        <div class="nav-group-items">
                            <?php foreach ($visibleItems as $item): ?>
                                <?php $itemActive = $item['href'] === $activeItemHref; ?>
                                <a class="nav-subitem<?= $itemActive ? ' is-active' : ''; ?>" href="<?= e($item['href']); ?>"<?= $itemActive ? ' aria-current="page"' : ''; ?>>
                                    <i class="bi bi-<?= e($item['icon']); ?>" aria-hidden="true"></i>
                                    <span><?= e($item['label']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (Auth::can('reports.view') || Auth::can('notifications.view')): ?>
            <div class="nav-section nav-section-tools">
                <span class="nav-section-label">Outils transversaux</span>
                <?php if (Auth::can('reports.view')): ?>
                    <a class="nav-item nav-item-compact<?= $reportsActive ? ' is-active' : ''; ?>" href="<?= url('reports'); ?>"<?= $reportsActive ? ' aria-current="page"' : ''; ?>>
                        <span class="nav-icon" aria-hidden="true"><i class="bi bi-bar-chart-line"></i></span>
                        <span>Rapports de gestion</span>
                    </a>
                <?php endif; ?>
                <?php if (Auth::can('notifications.view')): ?>
                    <a class="nav-item nav-item-compact<?= $notificationsActive ? ' is-active' : ''; ?>" href="<?= url('notifications'); ?>"<?= $notificationsActive ? ' aria-current="page"' : ''; ?>>
                        <span class="nav-icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                        <span>Notifications</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <span class="status-dot"><i class="bi bi-check2" aria-hidden="true"></i></span>
        <div>
            <strong>Système opérationnel</strong>
            <small>Environnement <?= e(APP_ENV); ?></small>
        </div>
    </div>
</aside>
