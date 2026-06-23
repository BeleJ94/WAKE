<?php

declare(strict_types=1);

class PlacementReportController extends Controller
{
    public function index(): void
    {
        $this->view('placement.reports', [
            'title' => 'Rapports Placement',
            'metrics' => (new PlacementContract())->reportMetrics(),
            'employees' => (new PlacementEmployee())->all(),
        ]);
    }
}

