<?php

declare(strict_types=1);

class FinanceReportController extends Controller
{
    public function index(): void
    {
        $this->view('finance.reports', [
            'title' => 'Rapports Finance',
            'totals' => (new TreasuryAccount())->totals(),
            'metrics' => (new FundRequest())->metrics(),
        ]);
    }
}

