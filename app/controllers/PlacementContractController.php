<?php

declare(strict_types=1);

class PlacementContractController extends Controller
{
    public function index(): void
    {
        $this->view('placement.contracts.index', ['title' => 'Contrats de placement', 'contracts' => (new PlacementContract())->all()]);
    }

    public function create(): void
    {
        $this->view('placement.contracts.create', ['title' => 'Créer un contrat de placement', 'employees' => (new PlacementEmployee())->active(), 'errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = [
            'client_name' => trim((string) $this->request('client_name', '')),
            'client_contact' => trim((string) $this->request('client_contact', '')),
            'client_phone' => trim((string) $this->request('client_phone', '')),
            'start_date' => trim((string) $this->request('start_date', '')),
            'end_date' => trim((string) $this->request('end_date', '')),
            'status' => (string) $this->request('status', 'Active'),
            'billing_day' => (int) $this->request('billing_day', 30),
            'notes' => trim((string) $this->request('notes', '')),
            'created_by' => (int) Auth::id(),
        ];
        $assignments = $this->assignmentsPayload();
        if ($data['client_name'] === '' || $data['start_date'] === '' || $assignments === []) {
            $this->view('placement.contracts.create', ['title' => 'Créer un contrat de placement', 'employees' => (new PlacementEmployee())->active(), 'errors' => ['required' => 'Le client, la date de début et au moins un agent sont obligatoires.'], 'old' => $data]);
            return;
        }
        $id = (new PlacementContract())->create($data, $assignments);
        AuditLog::record('placement_contract_created', 'placement_contract', $id);
        Session::flash('success', 'Contrat de placement créé.');
        $this->redirect('placement/contracts/show?id=' . $id);
    }

    public function show(): void
    {
        $contract = (new PlacementContract())->find((int) $this->request('id', 0));
        if ($contract === null) {
            Session::flash('error', 'Contrat introuvable.');
            $this->redirect('placement/contracts/index');
        }
        $this->view('placement.contracts.show', [
            'title' => $contract['reference'],
            'contract' => $contract,
            'assignments' => (new PlacementContract())->assignments((int) $contract['id']),
        ]);
    }

    private function assignmentsPayload(): array
    {
        $rows = [];
        $employeeIds = $_POST['assignments']['employee_id'] ?? [];
        foreach ($employeeIds as $index => $employeeId) {
            if ((int) $employeeId <= 0) {
                continue;
            }
            $rows[] = [
                'employee_id' => (int) $employeeId,
                'position_title' => trim((string) ($_POST['assignments']['position_title'][$index] ?? 'Agent')),
                'agent_cost' => max(0, (float) ($_POST['assignments']['agent_cost'][$index] ?? 0)),
                'client_rate' => max(0, (float) ($_POST['assignments']['client_rate'][$index] ?? 0)),
                'start_date' => trim((string) ($_POST['assignments']['start_date'][$index] ?? '')),
                'end_date' => trim((string) ($_POST['assignments']['end_date'][$index] ?? '')),
            ];
        }
        return $rows;
    }
}
