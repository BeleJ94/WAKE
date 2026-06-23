<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Plateforme de gestion intégrée pour WAKE SERVICES">
    <meta name="csrf-token" content="<?= e(Csrf::token()); ?>">
    <title><?= e($title ?? APP_NAME); ?> | <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css'); ?>">
</head>
<body>
    <div class="app-shell" data-app-shell>
        <?php require VIEW_PATH . '/partials/sidebar.php'; ?>

        <div class="workspace">
            <?php require VIEW_PATH . '/partials/topbar.php'; ?>

            <main class="main-content" id="main-content">
                <?= $content; ?>
            </main>

            <?php require VIEW_PATH . '/partials/footer.php'; ?>
        </div>
    </div>

    <div class="overlay" data-sidebar-overlay aria-hidden="true"></div>

    <script>window.BASE_URL = "<?= e(rtrim(BASE_URL, '/')); ?>";</script>
    <script src="<?= asset('js/app.js'); ?>"></script>
</body>
</html>
