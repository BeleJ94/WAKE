<?php

declare(strict_types=1);

class QuotationController extends Controller
{
    public function index(): void
    {
        $this->view('quotations.index', ['title' => 'Devis clients', 'quotations' => (new Quotation())->all()]);
    }

    public function create(): void
    {
        $this->view('quotations.create', ['title' => 'Créer un devis', 'clients' => (new Client())->active(), 'products' => (new Product())->active(), 'old' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = [
            'client_id' => (int) $this->request('client_id', 0),
            'quote_date' => (string) $this->request('quote_date', date('Y-m-d')),
            'valid_until' => (string) $this->request('valid_until', ''),
            'notes' => trim((string) $this->request('notes', '')),
            'created_by' => (int) Auth::id(),
        ];
        $items = $this->itemsPayload();
        if ($data['client_id'] <= 0 || $items === []) {
            Session::flash('error', 'Client et lignes de devis obligatoires.');
            $this->redirect('quotations/create');
        }
        $id = (new Quotation())->create($data, $items);
        AuditLog::record('quotation_created', 'quotation', $id);
        Session::flash('success', 'Devis créé.');
        $this->redirect('quotations/index');
    }

    public function validateQuote(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);
        (new Quotation())->validateQuote($id);
        AuditLog::record('quotation_validated', 'quotation', $id);
        Session::flash('success', 'Devis validé.');
        $this->redirect('quotations/index');
    }

    public function convert(): void
    {
        $this->requireCsrf();
        try {
            $id = (new SalesOrder())->convertFromQuotation((int) $this->request('id', 0), (int) Auth::id());
            AuditLog::record('quotation_converted_to_order', 'sales_order', $id);
            Session::flash('success', 'Devis transformé en commande.');
            $this->redirect('sales_orders/show?id=' . $id);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('quotations/index');
        }
    }

    private function itemsPayload(): array
    {
        $items = [];
        $descriptions = $_POST['items']['description'] ?? [];
        foreach ($descriptions as $index => $description) {
            $description = trim((string) $description);
            if ($description === '') {
                continue;
            }
            $items[] = [
                'product_id' => (int) ($_POST['items']['product_id'][$index] ?? 0),
                'description' => $description,
                'quantity' => max(0, (float) ($_POST['items']['quantity'][$index] ?? 0)),
                'unit_price' => max(0, (float) ($_POST['items']['unit_price'][$index] ?? 0)),
                'unit_cost' => max(0, (float) ($_POST['items']['unit_cost'][$index] ?? 0)),
                'tax_rate' => max(0, (float) ($_POST['items']['tax_rate'][$index] ?? 0)),
            ];
        }
        return $items;
    }
}
