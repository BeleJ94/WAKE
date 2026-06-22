<?php

declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home.index', [
            'title' => 'Tableau de bord',
        ]);
    }
}

