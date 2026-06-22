<?php

declare(strict_types=1);

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Veuillez vous connecter pour accéder à cette page.');
            header('Location: ' . url('login'));
            exit;
        }
    }
}

