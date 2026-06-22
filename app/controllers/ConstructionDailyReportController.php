<?php

declare(strict_types=1);

class ConstructionDailyReportController extends Controller
{
    public function create(): void
    {
        $project = (new ConstructionProject())->find((int) $this->request('project_id', 0));
        if ($project === null) {
            Session::flash('error', 'Projet introuvable.');
            $this->redirect('construction/projects');
        }
        $this->view('construction.daily_reports.create', [
            'title' => 'Rapport journalier',
            'project' => $project,
            'tasks' => (new ConstructionProject())->tasks((int) $project['id']),
            'materials' => (new ConstructionMaterial())->active(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $projectId = (int) $this->request('construction_project_id', 0);
        $data = [
            'construction_project_id' => $projectId,
            'report_date' => trim((string) $this->request('report_date', '')),
            'weather' => trim((string) $this->request('weather', '')),
            'remarks' => trim((string) $this->request('remarks', '')),
            'blockers' => trim((string) $this->request('blockers', '')),
            'created_by' => (int) Auth::id(),
        ];

        try {
            if ($data['report_date'] === '') {
                throw new RuntimeException('La date du rapport est obligatoire.');
            }
            $reportId = (new ConstructionDailyReport())->create(
                $data,
                $this->progressPayload(),
                $this->consumptionPayload(),
                $this->expensePayload(),
                $this->photosPayload()
            );
            AuditLog::record('construction_daily_report_created', 'construction_daily_report', $reportId, ['project_id' => $projectId]);
            (new Notification())->scanSystemAlerts();
            Session::flash('success', 'Rapport journalier enregistré.');
            $this->redirect('construction/daily_reports/show?id=' . $reportId);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('construction/daily_reports/create?project_id=' . $projectId);
        }
    }

    public function show(): void
    {
        $model = new ConstructionDailyReport();
        $report = $model->find((int) $this->request('id', 0));
        if ($report === null) {
            Session::flash('error', 'Rapport introuvable.');
            $this->redirect('construction/projects');
        }
        $this->view('construction.daily_reports.show', [
            'title' => 'Rapport ' . $report['report_date'],
            'report' => $report,
            'progress' => $model->progress((int) $report['id']),
            'consumptions' => $model->consumptions((int) $report['id']),
            'expenses' => $model->expenses((int) $report['id']),
            'photos' => $model->photos((int) $report['id']),
        ]);
    }

    private function progressPayload(): array
    {
        $rows = [];
        $taskIds = $_POST['progress']['task_id'] ?? [];
        foreach ($taskIds as $index => $taskId) {
            if ((int) $taskId <= 0 || trim((string) ($_POST['progress']['executed_work'][$index] ?? '')) === '') {
                continue;
            }
            $rows[] = [
                'task_id' => (int) $taskId,
                'executed_work' => trim((string) $_POST['progress']['executed_work'][$index]),
                'quantity_done' => max(0, (float) ($_POST['progress']['quantity_done'][$index] ?? 0)),
                'progress_percent' => min(100, max(0, (float) ($_POST['progress']['progress_percent'][$index] ?? 0))),
            ];
        }
        return $rows;
    }

    private function consumptionPayload(): array
    {
        $rows = [];
        $ids = $_POST['consumptions']['material_id'] ?? [];
        foreach ($ids as $index => $id) {
            if ((int) $id <= 0 || (float) ($_POST['consumptions']['quantity_used'][$index] ?? 0) <= 0) {
                continue;
            }
            $rows[] = [
                'material_id' => (int) $id,
                'quantity_used' => (float) $_POST['consumptions']['quantity_used'][$index],
                'unit_cost' => max(0, (float) ($_POST['consumptions']['unit_cost'][$index] ?? 0)),
            ];
        }
        return $rows;
    }

    private function expensePayload(): array
    {
        $rows = [];
        $descriptions = $_POST['expenses']['description'] ?? [];
        foreach ($descriptions as $index => $description) {
            $description = trim((string) $description);
            if ($description === '') {
                continue;
            }
            $rows[] = [
                'category' => trim((string) ($_POST['expenses']['category'][$index] ?? 'Divers')) ?: 'Divers',
                'description' => $description,
                'amount' => max(0, (float) ($_POST['expenses']['amount'][$index] ?? 0)),
            ];
        }
        return $rows;
    }

    private function photosPayload(): array
    {
        $photos = [];
        if (empty($_FILES['photos']['name'][0])) {
            return $photos;
        }
        $allowed = ['image/jpeg', 'image/png'];
        foreach ($_FILES['photos']['name'] as $index => $name) {
            if ($_FILES['photos']['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }
            $file = [
                'name' => $name,
                'type' => $_FILES['photos']['type'][$index] ?? '',
                'tmp_name' => $_FILES['photos']['tmp_name'][$index],
                'error' => $_FILES['photos']['error'][$index],
                'size' => $_FILES['photos']['size'][$index],
            ];
            try {
                $validated = Security::validateUpload($file, $allowed, ['jpg', 'jpeg', 'png'], 5 * 1024 * 1024);
            } catch (Throwable $exception) {
                continue;
            }
            $relativeDirectory = 'uploads/construction_photos';
            $filename = Security::safeUploadName('construction', $validated['extension']);
            $relative = $relativeDirectory . '/' . $filename;
            if (@move_uploaded_file($file['tmp_name'], Security::ensureUploadDirectory($relativeDirectory) . '/' . $filename)) {
                $photos[] = [
                    'caption' => trim((string) ($_POST['photo_caption'] ?? '')),
                    'original_name' => $name,
                    'file_path' => $relative,
                    'mime_type' => $validated['mime_type'],
                    'file_size' => $validated['size'],
                ];
            }
        }
        return $photos;
    }
}
