<?php

declare(strict_types=1);

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $this->view('auth.login', [
            'title' => 'Connexion',
        ], 'auth');
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email = trim((string) $this->request('email', ''));
        $password = (string) $this->request('password', '');

        if ($email === '' || $password === '') {
            Session::flash('error', 'Email et mot de passe sont obligatoires.');
            $this->redirect('login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            AuditLog::record('login_failed', 'user', null, ['email' => $email]);
            Session::flash('error', 'Identifiants incorrects.');
            $this->redirect('login');
        }

        if ($user['status'] !== 'active') {
            AuditLog::record('login_blocked', 'user', (int) $user['id']);
            Session::flash('error', 'Ce compte utilisateur est désactivé.');
            $this->redirect('login');
        }

        Auth::login($user);
        $userModel->updateLastLogin((int) $user['id']);
        $this->storeSession((int) $user['id']);
        AuditLog::record('login_success', 'user', (int) $user['id']);

        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        $this->requireCsrf();

        AuditLog::record('logout', 'user', Auth::id());
        $this->revokeSession();
        Auth::logout();

        Session::start();
        Session::flash('success', 'Vous êtes déconnecté.');
        $this->redirect('login');
    }

    private function storeSession(int $userId): void
    {
        $statement = Database::getConnection()->prepare(
            'INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity_at, created_at)
             VALUES (:user_id, :session_id, :ip_address, :user_agent, NOW(), NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    private function revokeSession(): void
    {
        $statement = Database::getConnection()->prepare(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE session_id = :session_id AND revoked_at IS NULL'
        );
        $statement->execute(['session_id' => session_id()]);
    }
}
