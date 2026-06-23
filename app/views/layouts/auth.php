<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Connexion sécurisée à <?= e(APP_NAME); ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07111f">
    <title><?= e($title ?? 'Connexion'); ?> | <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css'); ?>">
</head>
<body class="auth-body">
    <main class="auth-page">
        <?= $content; ?>
    </main>
    <script src="<?= asset('js/app.js'); ?>"></script>
</body>
</html>
