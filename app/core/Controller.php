<?php

declare(strict_types=1);

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!is_readable($viewFile)) {
            throw new RuntimeException("Vue {$view} introuvable.");
        }

        $content = $this->renderViewFile($viewFile, $data);
        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';

        if ($layout !== '' && is_readable($layoutFile)) {
            extract($data, EXTR_SKIP);
            require $layoutFile;
            return;
        }

        echo $content;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    protected function request(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requireCsrf(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect('login');
        }
    }

    protected function back(string $fallback = ''): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        header('Location: ' . ($referer !== '' ? $referer : $this->url($fallback)));
        exit;
    }

    protected function url(string $path = ''): string
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    protected function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function renderViewFile(string $viewFile, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;

        return (string) ob_get_clean();
    }
}
