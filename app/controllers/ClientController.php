<?php

declare(strict_types=1);

class ClientController extends Controller
{
    public function index(): void
    {
        $this->view('clients.index', ['title' => 'Portefeuille clients', 'clients' => (new Client())->all()]);
    }

    public function create(): void
    {
        $this->view('clients.create', ['title' => 'Créer un client', 'old' => [], 'errors' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->payload();
        if ($data['name'] === '') {
            $this->view('clients.create', ['title' => 'Créer un client', 'old' => $data, 'errors' => ['name' => 'Le nom du client est obligatoire.']]);
            return;
        }
        $id = (new Client())->create($data);
        AuditLog::record('client_created', 'client', $id);
        Session::flash('success', 'Client créé.');
        $this->redirect('clients/index');
    }

    private function payload(): array
    {
        return [
            'name' => trim((string) $this->request('name', '')),
            'contact_name' => trim((string) $this->request('contact_name', '')),
            'phone' => trim((string) $this->request('phone', '')),
            'email' => trim((string) $this->request('email', '')),
            'address' => trim((string) $this->request('address', '')),
            'tax_number' => trim((string) $this->request('tax_number', '')),
            'status' => (string) $this->request('status', 'active'),
            'notes' => trim((string) $this->request('notes', '')),
        ];
    }
}
