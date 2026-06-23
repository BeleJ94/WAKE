<?php

declare(strict_types=1);

class PlacementEmployeeController extends Controller
{
    public function index(): void
    {
        $this->view('placement.employees.index', [
            'title' => 'Agents',
            'employees' => (new PlacementEmployee())->all(),
        ]);
    }

    public function create(): void
    {
        $this->view('placement.employees.create', ['title' => 'Créer un agent', 'errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = [
            'first_name' => trim((string) $this->request('first_name', '')),
            'last_name' => trim((string) $this->request('last_name', '')),
            'phone' => trim((string) $this->request('phone', '')),
            'email' => trim((string) $this->request('email', '')),
            'job_title' => trim((string) $this->request('job_title', '')),
            'base_salary' => max(0, (float) $this->request('base_salary', 0)),
            'status' => (string) $this->request('status', 'active'),
            'hired_at' => trim((string) $this->request('hired_at', '')),
            'notes' => trim((string) $this->request('notes', '')),
        ];
        if ($data['first_name'] === '' || $data['last_name'] === '' || $data['job_title'] === '') {
            $this->view('placement.employees.create', ['title' => 'Créer un agent', 'errors' => ['required' => 'Veuillez renseigner les champs obligatoires.'], 'old' => $data]);
            return;
        }
        $id = (new PlacementEmployee())->create($data);
        AuditLog::record('placement_employee_created', 'employee', $id);
        Session::flash('success', 'Agent créé.');
        $this->redirect('placement/employees/index');
    }
}
