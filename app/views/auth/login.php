<section class="login-panel">
    <div class="login-brand">
        <span class="brand-mark">W</span>
        <div>
            <strong><?= e(APP_NAME); ?></strong>
            <small><?= e(APP_COMPANY); ?></small>
        </div>
    </div>

    <div class="login-copy">
        <span class="section-kicker">Accès sécurisé</span>
        <h1>Connexion à la suite de gestion</h1>
        <p>Accédez aux opérations WAKE SERVICES selon votre rôle et vos permissions.</p>
    </div>

    <?php if ($message = Session::flash('error')): ?>
        <div class="alert alert-danger"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($message = Session::flash('success')): ?>
        <div class="alert alert-success"><?= e($message); ?></div>
    <?php endif; ?>

    <form class="form-stack" method="post" action="<?= url('login'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label>
            Email professionnel
            <input type="email" name="email" required autocomplete="email" placeholder="admin@wake-services.local">
        </label>
        <label>
            Mot de passe
            <input type="password" name="password" required minlength="8" autocomplete="current-password" placeholder="Votre mot de passe">
        </label>
        <button class="btn btn-primary full-width" type="submit">Se connecter</button>
    </form>
</section>

