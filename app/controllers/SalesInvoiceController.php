<?php

declare(strict_types=1);

class SalesInvoiceController extends Controller
{
    public function index(): void
    {
        $invoiceModel = new SalesInvoice();
        $invoiceModel->markOverdue();
        $this->view('invoices.index', ['title' => 'Facturation centralisée', 'invoices' => $invoiceModel->all()]);
    }

    public function create(): void
    {
        $this->view('invoices.create', [
            'title' => 'Nouvelle facture',
            'clients' => (new Client())->active(),
            'placementContracts' => (new PlacementContract())->active(),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        try {
            $sourceType = (string) $this->request('source_type', 'manual');
            if (!in_array($sourceType, ['manual', 'construction_project', 'other_service'], true)) {
                $sourceType = 'manual';
            }

            $id = (new SalesInvoice())->createManual([
                'client_id' => (int) $this->request('client_id', 0),
                'source_type' => $sourceType,
                'invoice_date' => (string) $this->request('invoice_date', date('Y-m-d')),
                'due_date' => (string) $this->request('due_date', ''),
                'status' => (string) $this->request('status', 'Sent'),
                'notes' => trim((string) $this->request('notes', '')),
                'payment_terms' => trim((string) $this->request('payment_terms', 'Paiement à 15 jours sauf accord contractuel contraire.')),
                'created_by' => (int) Auth::id(),
            ], $this->invoiceItemsFromRequest());
            AuditLog::record('invoice_manual_created', 'invoice', $id);
            Notification::push('invoice_created', 'Nouvelle facture', 'Une facture a été créée.', url('invoices/show?id=' . $id), 'info', 'invoice', $id);
            Session::flash('success', 'Facture créée.');
            $this->redirect('invoices/show?id=' . $id);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('invoices/create');
        }
    }

    public function generatePlacement(): void
    {
        $this->requireCsrf();
        try {
            $id = (new SalesInvoice())->generateFromPlacementContract(
                (int) $this->request('contract_id', 0),
                (string) $this->request('invoice_month', date('Y-m')),
                (int) Auth::id()
            );
            AuditLog::record('placement_invoice_generated_unified', 'invoice', $id);
            Notification::push('invoice_created', 'Facture placement générée', 'Une facture de placement a été générée.', url('invoices/show?id=' . $id), 'info', 'invoice', $id);
            Session::flash('success', 'Facture de placement générée dans la facturation centrale.');
            $this->redirect('invoices/show?id=' . $id);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('invoices/create');
        }
    }

    public function show(): void
    {
        $invoice = (new SalesInvoice())->find((int) $this->request('id', 0));
        if ($invoice === null) {
            Session::flash('error', 'Facture introuvable.');
            $this->redirect('invoices/index');
        }
        $this->view('invoices.show', ['title' => $invoice['reference'], 'invoice' => $invoice, 'items' => (new SalesInvoice())->items((int) $invoice['id'])]);
    }

    public function payment(): void
    {
        $invoiceModel = new SalesInvoice();
        $this->view('invoices.payment', [
            'title' => 'Paiements factures',
            'payments' => (new SalesPayment())->all(),
            'invoices' => $invoiceModel->payable(),
            'selectedInvoiceId' => (int) $this->request('invoice_id', 0),
        ]);
    }

    public function storePayment(): void
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
            AuditLog::record('invoice_payment_created', 'payment', $id);
            Notification::push('invoice_payment_created', 'Paiement facture enregistré', 'Un paiement client a été enregistré.', url('invoices/payment'), 'success', 'payment', $id);
            Session::flash('success', 'Paiement enregistré.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('invoices/payment');
    }

    public function print(): void
    {
        $invoice = (new SalesInvoice())->find((int) $this->request('id', 0));
        if ($invoice === null) {
            Session::flash('error', 'Facture introuvable.');
            $this->redirect('invoices/index');
        }
        $this->view('invoices.print', [
            'title' => 'Impression ' . $invoice['reference'],
            'invoice' => $invoice,
            'items' => (new SalesInvoice())->items((int) $invoice['id']),
        ], '');
    }

    private function invoiceItemsFromRequest(): array
    {
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['unit_price'] ?? [];
        $costs = $_POST['unit_cost'] ?? [];
        $taxes = $_POST['tax_rate'] ?? [];
        $items = [];

        foreach ($descriptions as $index => $description) {
            $items[] = [
                'description' => trim((string) $description),
                'quantity' => (float) ($quantities[$index] ?? 0),
                'unit_price' => (float) ($prices[$index] ?? 0),
                'unit_cost' => (float) ($costs[$index] ?? 0),
                'tax_rate' => (float) ($taxes[$index] ?? 0),
            ];
        }

        return $items;
    }
}
