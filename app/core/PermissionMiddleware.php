<?php

declare(strict_types=1);

class PermissionMiddleware
{
    public static function handle(string $permission): void
    {
        if (!Auth::can($permission)) {
            http_response_code(403);
            AuditLog::record('permission_denied', 'security', null, ['permission' => $permission]);
            (new ErrorController())->forbidden();
            exit;
        }
    }
}

