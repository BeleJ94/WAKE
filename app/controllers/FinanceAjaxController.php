<?php

declare(strict_types=1);

class FinanceAjaxController extends Controller
{
    public function accountBalance(): void
    {
        $account = (new TreasuryAccount())->find((int) $this->request('id', 0));
        $amount = (float) $this->request('amount', 0);

        $this->json([
            'found' => $account !== null,
            'balance' => $account ? (float) $account['current_balance'] : 0,
            'currency' => $account['currency'] ?? 'USD',
            'sufficient' => $account !== null && (float) $account['current_balance'] >= $amount,
        ]);
    }

    public function fundRequestDetails(): void
    {
        $request = (new FundRequest())->find((int) $this->request('id', 0));

        if ($request === null) {
            $this->json(['found' => false]);
            return;
        }

        $this->json([
            'found' => true,
            'request' => $request,
        ]);
    }

    public function status(): void
    {
        $this->requireCsrf();
        $request = (new FundRequest())->find((int) $this->request('id', 0));
        $this->json([
            'found' => $request !== null,
            'status' => $request['status'] ?? null,
            'badge' => $request ? status_badge_class($request['status']) : null,
        ]);
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
