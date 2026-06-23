<section class="enterprise-login">
    <aside class="enterprise-login-intro" aria-label="Présentation de WAKE Business Suite">
        <a class="enterprise-login-brand" href="<?= url('login'); ?>" aria-label="<?= e(APP_NAME); ?>">
            <span class="enterprise-login-logo">W</span>
            <span>
                <strong><?= e(APP_NAME); ?></strong>
                <small><?= e(APP_COMPANY); ?></small>
            </span>
        </a>

        <div class="enterprise-login-message">
            <span class="enterprise-login-eyebrow">Plateforme de gestion intégrée</span>
            <h1>Pilotez vos opérations avec clarté.</h1>
            <p>Finance, ventes, construction et ressources humaines réunies dans un environnement sécurisé.</p>
        </div>

        <div class="enterprise-login-trust">
            <span><i class="bi bi-shield-check" aria-hidden="true"></i> Accès contrôlé par rôle</span>
            <span><i class="bi bi-clock-history" aria-hidden="true"></i> Activités tracées</span>
            <span><i class="bi bi-lock" aria-hidden="true"></i> Session sécurisée</span>
        </div>
    </aside>

    <div class="enterprise-login-access">
        <div class="enterprise-login-mobile-brand">
            <span class="enterprise-login-logo">W</span>
            <span>
                <strong><?= e(APP_NAME); ?></strong>
                <small><?= e(APP_COMPANY); ?></small>
            </span>
        </div>

        <div class="enterprise-login-card">
            <header>
                <span class="enterprise-login-kicker">Espace professionnel</span>
                <h2>Bienvenue</h2>
                <p>Connectez-vous pour accéder à votre espace de travail.</p>
            </header>

            <?php if ($message = Session::flash('error')): ?>
                <div class="enterprise-login-alert is-error" role="alert">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                    <span><?= e($message); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($message = Session::flash('success')): ?>
                <div class="enterprise-login-alert is-success" role="status">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span><?= e($message); ?></span>
                </div>
            <?php endif; ?>

            <form class="enterprise-login-form" method="post" action="<?= url('login'); ?>" data-validate>
                <?= Csrf::field(); ?>

                <label>
                    <span>Adresse e-mail professionnelle</span>
                    <span class="enterprise-login-field">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            inputmode="email"
                            placeholder="nom@entreprise.com"
                            autofocus
                        >
                    </span>
                </label>

                <label>
                    <span>Mot de passe</span>
                    <span class="enterprise-login-field">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input
                            type="password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="current-password"
                            placeholder="Saisissez votre mot de passe"
                            data-login-password
                        >
                        <button
                            class="enterprise-password-toggle"
                            type="button"
                            aria-label="Afficher le mot de passe"
                            aria-pressed="false"
                            data-login-password-toggle
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </span>
                </label>

                <button class="enterprise-login-submit" type="submit">
                    <span>Se connecter</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div class="enterprise-login-help">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <p>Problème d’accès ? Contactez l’administrateur de votre organisation.</p>
            </div>
        </div>

        <footer class="enterprise-login-footer">
            <span>© <?= date('Y'); ?> <?= e(APP_COMPANY); ?></span>
            <span>Système d’information interne</span>
        </footer>
    </div>
</section>
