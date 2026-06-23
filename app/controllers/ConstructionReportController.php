<?php

declare(strict_types=1);

class ConstructionReportController extends Controller
{
    public function index(): void
    {
        $this->view('construction.reports', [
            'title' => 'Rapports Construction',
            'dashboard' => (new ConstructionProject())->dashboard(),
        ]);
    }
}

