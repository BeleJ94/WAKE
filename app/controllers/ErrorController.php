<?php

declare(strict_types=1);

class ErrorController extends Controller
{
    public function notFound(): void
    {
        $this->view('errors.404', [
            'title' => 'Page introuvable',
        ]);
    }

    public function forbidden(): void
    {
        $this->view('errors.403', [
            'title' => 'Accès refusé',
        ]);
    }
}
