<?php

declare(strict_types=1);

class PlacementInvoiceController extends Controller
{
    public function index(): void
    {
        $this->view('placement.invoices', [
            'title' => 'Factures Placement',
            'contracts' => (new PlacementContract())->active(),
            'invoices' => (new PlacementInvoice())->all(),
        ]);
    }

    public function generate(): void
    {
        $this->requireCsrf();
        try {
            $id = (new SalesInvoice())->generateFromPlacementContract((int) $this->request('contract_id', 0), (string) $this->request('invoice_month', date('Y-m')), (int) Auth::id());
            AuditLog::record('placement_invoice_generated_unified', 'invoice', $id);
            Session::flash('success', 'Facture mensuelle générée dans la facturation centrale.');
            $this->redirect('invoices/show?id=' . $id);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('placement/invoices');
    }
}
