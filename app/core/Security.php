<?php

declare(strict_types=1);

class Security
{
    private const SESSION_TIMEOUT_SECONDS = 1800;

    public static function applyHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }

    public static function enforceSessionTimeout(): void
    {
        if (!Auth::check()) {
            $_SESSION['_last_activity_at'] = time();
            return;
        }

        $lastActivity = (int) ($_SESSION['_last_activity_at'] ?? time());
        if (time() - $lastActivity > self::SESSION_TIMEOUT_SECONDS) {
            AuditLog::record('session_timeout', 'user', Auth::id());
            Auth::logout();
            Session::start();
            Session::flash('error', 'Session expirée après inactivité. Veuillez vous reconnecter.');
            header('Location: ' . url('login'));
            exit;
        }

        $_SESSION['_last_activity_at'] = time();
        self::touchUserSession();
    }

    public static function validateUpload(array $file, array $allowedMimeTypes, array $allowedExtensions, int $maxBytes): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload invalide.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Extension de fichier non autorisée.');
        }

        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > $maxBytes) {
            throw new RuntimeException('Taille de fichier non autorisée.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Fichier uploadé introuvable.');
        }

        $mime = mime_content_type($tmp) ?: '';
        if (!in_array($mime, $allowedMimeTypes, true)) {
            throw new RuntimeException('Type MIME non autorisé.');
        }

        return ['extension' => $extension, 'mime_type' => $mime, 'size' => (int) $file['size']];
    }

    public static function safeUploadName(string $prefix, string $extension): string
    {
        return $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
    }

    public static function ensureUploadDirectory(string $relativeDirectory): string
    {
        $directory = PUBLIC_PATH . '/' . trim($relativeDirectory, '/');
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de préparer le dossier upload.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Le dossier de stockage des fichiers n’est pas accessible en écriture.');
        }

        $htaccess = $directory . '/.htaccess';
        if (!is_file($htaccess)) {
            $written = @file_put_contents(
                $htaccess,
                "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx|jsp|sh)$\">\n    Require all denied\n</FilesMatch>\n"
            );

            if ($written === false) {
                throw new RuntimeException('Impossible de sécuriser le dossier de stockage.');
            }
        }

        return $directory;
    }

    private static function touchUserSession(): void
    {
        try {
            $statement = Database::getConnection()->prepare(
                'UPDATE user_sessions
                 SET last_activity_at = NOW()
                 WHERE session_id = :session_id AND revoked_at IS NULL'
            );
            $statement->execute(['session_id' => session_id()]);
        } catch (Throwable $exception) {
            if (APP_DEBUG) {
                error_log($exception->getMessage());
            }
        }
    }
}
