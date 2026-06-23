<?php

declare(strict_types=1);

class ConstructionProjectController extends Controller
{
    public function index(): void
    {
        $this->view('construction.projects.index', [
            'title' => 'Projets de construction',
            'projects' => (new ConstructionProject())->all(),
        ]);
    }

    public function dashboard(): void
    {
        $this->view('construction.projects.dashboard', [
            'title' => 'Cockpit Construction',
            'dashboard' => (new ConstructionProject())->dashboard(),
        ]);
    }

    public function create(): void
    {
        $this->view('construction.projects.create', $this->formData('Créer un projet de construction'));
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->payload();
        $tasks = $this->tasksPayload();
        $materials = $this->materialsPayload();
        $errors = $this->validatePayload($data, $tasks);

        if ($errors !== []) {
            $viewData = $this->formData('Créer un projet de construction');
            $viewData['errors'] = $errors;
            $viewData['old'] = $data;
            $viewData['oldTasks'] = $tasks;
            $viewData['oldMaterials'] = $materials;
            $this->view('construction.projects.create', $viewData);
            return;
        }

        $id = (new ConstructionProject())->create($data, $tasks, $materials);
        AuditLog::record('construction_project_created', 'construction_project', $id);
        (new Notification())->scanSystemAlerts();
        Session::flash('success', 'Projet construction créé.');
        $this->redirect('construction/projects/show?id=' . $id);
    }

    public function show(): void
    {
        $project = $this->loadProject();
        $model = new ConstructionProject();

        $this->view('construction.projects.show', [
            'title' => $project['reference'],
            'project' => $project,
            'tasks' => $model->tasks((int) $project['id']),
            'materials' => $model->materials((int) $project['id']),
            'expenses' => $model->expenses((int) $project['id']),
            'reports' => $model->reports((int) $project['id']),
            'photos' => $model->photos((int) $project['id']),
            'alerts' => $this->alerts($project),
        ]);
    }

    public function edit(): void
    {
        $project = $this->loadProject();
        $this->view('construction.projects.edit', [
            'title' => 'Modifier ' . $project['reference'],
            'project' => $project,
            'users' => (new User())->all(),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);
        $data = $this->payload();
        $errors = $this->validatePayload($data, [['name' => 'skip']]);

        if ($errors !== []) {
            $project = array_merge((new ConstructionProject())->find($id) ?? [], $data);
            $this->view('construction.projects.edit', [
                'title' => 'Modifier le projet',
                'project' => $project,
                'users' => (new User())->all(),
                'errors' => $errors,
            ]);
            return;
        }

        (new ConstructionProject())->update($id, $data);
        AuditLog::record('construction_project_updated', 'construction_project', $id);
        (new Notification())->scanSystemAlerts();
        Session::flash('success', 'Projet mis à jour.');
        $this->redirect('construction/projects/show?id=' . $id);
    }

    private function formData(string $title): array
    {
        return [
            'title' => $title,
            'users' => (new User())->all(),
            'materials' => (new ConstructionMaterial())->active(),
            'errors' => [],
            'old' => [],
        ];
    }

    private function payload(): array
    {
        return [
            'project_manager_id' => (int) $this->request('project_manager_id', 0),
            'name' => trim((string) $this->request('name', '')),
            'client_name' => trim((string) $this->request('client_name', '')),
            'contract_amount' => max(0, (float) $this->request('contract_amount', 0)),
            'forecast_cost' => max(0, (float) $this->request('forecast_cost', 0)),
            'start_date' => trim((string) $this->request('start_date', '')),
            'end_date' => trim((string) $this->request('end_date', '')),
            'location' => trim((string) $this->request('location', '')),
            'status' => (string) $this->request('status', 'Planning'),
            'notes' => trim((string) $this->request('notes', '')),
        ];
    }

    private function tasksPayload(): array
    {
        $rows = [];
        $names = $_POST['tasks']['name'] ?? [];
        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $rows[] = [
                'name' => $name,
                'unit' => trim((string) ($_POST['tasks']['unit'][$index] ?? 'u')),
                'planned_quantity' => max(0, (float) ($_POST['tasks']['planned_quantity'][$index] ?? 0)),
                'planned_cost' => max(0, (float) ($_POST['tasks']['planned_cost'][$index] ?? 0)),
                'planned_duration_days' => max(0, (int) ($_POST['tasks']['planned_duration_days'][$index] ?? 0)),
            ];
        }
        return $rows;
    }

    private function materialsPayload(): array
    {
        $rows = [];
        $ids = $_POST['materials']['construction_material_id'] ?? [];
        foreach ($ids as $index => $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $rows[] = [
                'construction_material_id' => $id,
                'planned_quantity' => max(0, (float) ($_POST['materials']['planned_quantity'][$index] ?? 0)),
                'planned_cost' => max(0, (float) ($_POST['materials']['planned_cost'][$index] ?? 0)),
            ];
        }
        return $rows;
    }

    private function validatePayload(array $data, array $tasks): array
    {
        $errors = [];
        foreach (['name', 'client_name', 'start_date', 'end_date', 'location'] as $field) {
            if ((string) $data[$field] === '') {
                $errors[$field] = 'Champ obligatoire.';
            }
        }
        if ($data['contract_amount'] <= 0 || $data['forecast_cost'] <= 0) {
            $errors['budget'] = 'Montant contrat et coût prévisionnel doivent être supérieurs à zéro.';
        }
        if (!in_array($data['status'], ['Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled'], true)) {
            $errors['status'] = 'Statut invalide.';
        }
        if ($tasks === []) {
            $errors['tasks'] = 'Ajoutez au moins un travail à réaliser.';
        }
        return $errors;
    }

    private function loadProject(): array
    {
        $project = (new ConstructionProject())->find((int) $this->request('id', 0));
        if ($project === null) {
            Session::flash('error', 'Projet introuvable.');
            $this->redirect('construction/projects');
        }
        return $project;
    }

    private function alerts(array $project): array
    {
        $metrics = $project['metrics'];
        $alerts = [];
        if ($metrics['financial_progress'] > $metrics['physical_progress'] + 15) {
            $alerts[] = ['badge' => 'badge-danger', 'label' => 'Budget', 'text' => 'Avancement financier supérieur à l’avancement physique.'];
        }
        if ($metrics['consumption_variance'] < 0) {
            $alerts[] = ['badge' => 'badge-warning', 'label' => 'Consommation', 'text' => 'Consommation réelle supérieure au prévisionnel.'];
        }
        if ($metrics['delay_days'] > 0) {
            $alerts[] = ['badge' => 'badge-danger', 'label' => 'Retard', 'text' => $metrics['delay_days'] . ' jour(s) de retard potentiel.'];
        }
        if ($alerts === []) {
            $alerts[] = ['badge' => 'badge-success', 'label' => 'Contrôle', 'text' => 'Aucun dépassement critique détecté.'];
        }
        return $alerts;
    }
}
