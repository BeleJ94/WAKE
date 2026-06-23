<?php

declare(strict_types=1);

class SalesPaymentController extends Controller
{
    public function index(): void
    {
        $this->redirect('invoices/payment');
    }

    public function store(): void
    {
        $this->requireCsrf();
        try {
            $id = (new SalesPayment())->create([
                'invoice_id' => (int) $this->request('invoice_id', 0),
                'payment_date' => (string) $this->request('payment_date', date('Y-m-d')),
                'amount' => max(0, (float) $this->request('amount', 0)),
                'method' => trim((string) $this->request('method', 'Cash')),
                'notes' => trim((string) $this->request('notes', '')),
                'created_by' => (int) Auth::id(),
            ]);
            AuditLog::record('payment_created', 'payment', $id);
            Session::flash('success', 'Paiement enregistré.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('invoices/payment');
    }
}
