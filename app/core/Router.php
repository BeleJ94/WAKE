<?php

declare(strict_types=1);

class Router
{
    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $uri, string $action, array $middleware = []): void
    {
        $this->add('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, string $action, array $middleware = []): void
    {
        $this->add('POST', $uri, $action, $middleware);
    }

    public function dispatch(string $method, string $requestUri): void
    {
        $method = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
        $uri = $this->normalizeUri($requestUri);
        $route = $this->routes[$method][$uri] ?? null;

        if ($route === null) {
            $this->renderNotFound();
            return;
        }

        $this->runMiddleware($route['middleware']);
        $this->executeAction($route['action']);
    }

    private function add(string $method, string $uri, string $action, array $middleware = []): void
    {
        $this->routes[$method][$this->trimUri($uri)] = [
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $rule) {
            if ($rule === 'auth') {
                AuthMiddleware::handle();
                continue;
            }

            if (strpos($rule, 'permission:') === 0) {
                PermissionMiddleware::handle(substr($rule, strlen('permission:')));
            }
        }
    }

    private function executeAction(string $action): void
    {
        [$controllerName, $methodName] = explode('@', $action, 2);

        if (!class_exists($controllerName)) {
            throw new RuntimeException("Controller {$controllerName} introuvable.");
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            throw new RuntimeException("Action {$controllerName}@{$methodName} introuvable.");
        }

        $controller->{$methodName}();
    }

    private function renderNotFound(): void
    {
        http_response_code(404);

        if (class_exists('ErrorController')) {
            (new ErrorController())->notFound();
            return;
        }

        echo '404 - Page introuvable';
    }

    private function normalizeUri(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '';
        $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';

        if ($basePath !== '' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        if (strpos($path, '/public') === 0) {
            $path = substr($path, strlen('/public'));
        }

        return $this->trimUri($path);
    }

    private function trimUri(string $uri): string
    {
        return trim($uri, '/');
    }
}
