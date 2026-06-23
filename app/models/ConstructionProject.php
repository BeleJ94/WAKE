<?php

declare(strict_types=1);

class ConstructionProject extends Model
{
    public function all(): array
    {
        $projects = $this->db->query(
            'SELECT construction_projects.*, users.name AS manager_name
             FROM construction_projects
             LEFT JOIN users ON users.id = construction_projects.project_manager_id
             ORDER BY construction_projects.created_at DESC'
        )->fetchAll();

        foreach ($projects as &$project) {
            $project['metrics'] = $this->metrics((int) $project['id']);
        }

        return $projects;
    }

    public function dashboard(): array
    {
        $projects = $this->all();
        $totalContract = 0.0;
        $totalForecast = 0.0;
        $totalActual = 0.0;
        $progress = 0.0;
        $critical = 0;

        foreach ($projects as $project) {
            $totalContract += (float) $project['contract_amount'];
            $totalForecast += (float) $project['forecast_cost'];
            $totalActual += (float) $project['metrics']['actual_cost'];
            $progress += (float) $project['metrics']['physical_progress'];
            if ($project['metrics']['delay_days'] > 0 || $project['metrics']['cost_variance'] < 0) {
                $critical++;
            }
        }

        $count = count($projects);

        return [
            'projects' => $projects,
            'active_count' => count(array_filter($projects, static fn ($project) => $project['status'] === 'In Progress')),
            'contract_total' => $totalContract,
            'forecast_total' => $totalForecast,
            'actual_total' => $totalActual,
            'average_progress' => $count > 0 ? $progress / $count : 0,
            'critical_count' => $critical,
        ];
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT construction_projects.*, users.name AS manager_name
             FROM construction_projects
             LEFT JOIN users ON users.id = construction_projects.project_manager_id
             WHERE construction_projects.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $project = $statement->fetch();

        if (!$project) {
            return null;
        }

        $project['metrics'] = $this->metrics($id);

        return $project;
    }

    public function tasks(int $projectId): array
    {
        $statement = $this->db->prepare('SELECT * FROM construction_project_tasks WHERE construction_project_id = :id ORDER BY sort_order ASC, id ASC');
        $statement->execute(['id' => $projectId]);

        return $statement->fetchAll();
    }

    public function materials(int $projectId): array
    {
        $statement = $this->db->prepare(
            'SELECT construction_project_materials.*, construction_materials.name, construction_materials.unit,
                    construction_materials.unit_cost,
                    COALESCE(SUM(construction_daily_consumptions.quantity_used), 0) AS actual_quantity,
                    COALESCE(SUM(construction_daily_consumptions.total_cost), 0) AS actual_cost
             FROM construction_project_materials
             INNER JOIN construction_materials ON construction_materials.id = construction_project_materials.construction_material_id
             LEFT JOIN construction_daily_reports ON construction_daily_reports.construction_project_id = construction_project_materials.construction_project_id
             LEFT JOIN construction_daily_consumptions ON construction_daily_consumptions.construction_daily_report_id = construction_daily_reports.id
                AND construction_daily_consumptions.construction_material_id = construction_materials.id
             WHERE construction_project_materials.construction_project_id = :id
             GROUP BY construction_project_materials.id
             ORDER BY construction_materials.name ASC'
        );
        $statement->execute(['id' => $projectId]);

        return $statement->fetchAll();
    }

    public function reports(int $projectId): array
    {
        $statement = $this->db->prepare(
            'SELECT construction_daily_reports.*, users.name AS created_by_name
             FROM construction_daily_reports
             INNER JOIN users ON users.id = construction_daily_reports.created_by
             WHERE construction_daily_reports.construction_project_id = :id
             ORDER BY construction_daily_reports.report_date DESC'
        );
        $statement->execute(['id' => $projectId]);

        return $statement->fetchAll();
    }

    public function expenses(int $projectId): array
    {
        $statement = $this->db->prepare('SELECT * FROM construction_project_expenses WHERE construction_project_id = :id ORDER BY expense_date DESC, id DESC');
        $statement->execute(['id' => $projectId]);

        return $statement->fetchAll();
    }

    public function photos(int $projectId): array
    {
        $statement = $this->db->prepare('SELECT * FROM construction_project_photos WHERE construction_project_id = :id ORDER BY created_at DESC LIMIT 12');
        $statement->execute(['id' => $projectId]);

        return $statement->fetchAll();
    }

    public function create(array $data, array $tasks, array $materials): int
    {
        $this->db->beginTransaction();

        try {
            $reference = $this->nextReference();
            $forecastMargin = (float) $data['contract_amount'] - (float) $data['forecast_cost'];
            $statement = $this->db->prepare(
                'INSERT INTO construction_projects
                 (project_manager_id, reference, name, client_name, contract_amount, forecast_cost, forecast_margin, start_date, end_date, location, status, notes, created_at, updated_at)
                 VALUES (:project_manager_id, :reference, :name, :client_name, :contract_amount, :forecast_cost, :forecast_margin, :start_date, :end_date, :location, :status, :notes, NOW(), NOW())'
            );
            $statement->execute([
                'project_manager_id' => $data['project_manager_id'] ?: null,
                'reference' => $reference,
                'name' => $data['name'],
                'client_name' => $data['client_name'],
                'contract_amount' => $data['contract_amount'],
                'forecast_cost' => $data['forecast_cost'],
                'forecast_margin' => $forecastMargin,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'location' => $data['location'],
                'status' => $data['status'],
                'notes' => $data['notes'],
            ]);
            $projectId = (int) $this->db->lastInsertId();
            $this->insertTasks($projectId, $tasks);
            $this->insertMaterials($projectId, $materials);
            $this->db->commit();

            return $projectId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $data): void
    {
        $forecastMargin = (float) $data['contract_amount'] - (float) $data['forecast_cost'];
        $statement = $this->db->prepare(
            'UPDATE construction_projects
             SET project_manager_id = :project_manager_id, name = :name, client_name = :client_name,
                 contract_amount = :contract_amount, forecast_cost = :forecast_cost, forecast_margin = :forecast_margin,
                 start_date = :start_date, end_date = :end_date, location = :location, status = :status,
                 notes = :notes, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'project_manager_id' => $data['project_manager_id'] ?: null,
            'name' => $data['name'],
            'client_name' => $data['client_name'],
            'contract_amount' => $data['contract_amount'],
            'forecast_cost' => $data['forecast_cost'],
            'forecast_margin' => $forecastMargin,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'location' => $data['location'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);
    }

    public function updateTaskProgress(int $taskId, float $progress): void
    {
        $statement = $this->db->prepare('UPDATE construction_project_tasks SET progress_percent = LEAST(100, GREATEST(progress_percent, :progress)), updated_at = NOW() WHERE id = :id');
        $statement->execute(['progress' => $progress, 'id' => $taskId]);
    }

    public function metrics(int $projectId): array
    {
        $project = $this->baseProject($projectId);
        $tasks = $this->tasks($projectId);
        $physical = 0.0;
        $plannedCost = 0.0;
        $weighted = 0.0;

        foreach ($tasks as $task) {
            $cost = (float) $task['planned_cost'];
            $plannedCost += $cost;
            $weighted += $cost * ((float) $task['progress_percent'] / 100);
        }

        if ($plannedCost > 0) {
            $physical = ($weighted / $plannedCost) * 100;
        }

        $actualCost = $this->actualCost($projectId);
        $forecastCost = (float) ($project['forecast_cost'] ?? 0);
        $contractAmount = (float) ($project['contract_amount'] ?? 0);
        $financial = $forecastCost > 0 ? ($actualCost / $forecastCost) * 100 : 0;
        $forecastMargin = $contractAmount - $forecastCost;
        $actualMargin = $contractAmount - $actualCost;
        $delayDays = $this->delayDays($project ?: []);
        $plannedConsumption = $this->plannedMaterialCost($projectId);
        $actualConsumption = $this->actualMaterialCost($projectId);

        return [
            'physical_progress' => min(100, $physical),
            'financial_progress' => min(999, $financial),
            'actual_cost' => $actualCost,
            'forecast_margin' => $forecastMargin,
            'actual_margin' => $actualMargin,
            'margin_variance' => $actualMargin - $forecastMargin,
            'planned_consumption' => $plannedConsumption,
            'actual_consumption' => $actualConsumption,
            'consumption_variance' => $plannedConsumption - $actualConsumption,
            'cost_variance' => $forecastCost - $actualCost,
            'delay_days' => $delayDays,
        ];
    }

    private function baseProject(int $projectId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM construction_projects WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $projectId]);
        $project = $statement->fetch();

        return $project ?: null;
    }

    private function actualCost(int $projectId): float
    {
        $statement = $this->db->prepare(
            'SELECT
                (SELECT COALESCE(SUM(amount), 0) FROM construction_project_expenses WHERE construction_project_id = :project_expenses) +
                (SELECT COALESCE(SUM(construction_daily_consumptions.total_cost), 0)
                 FROM construction_daily_consumptions
                 INNER JOIN construction_daily_reports ON construction_daily_reports.id = construction_daily_consumptions.construction_daily_report_id
                 WHERE construction_daily_reports.construction_project_id = :project_consumptions) AS total'
        );
        $statement->execute(['project_expenses' => $projectId, 'project_consumptions' => $projectId]);
        $row = $statement->fetch();

        return (float) ($row['total'] ?? 0);
    }

    private function plannedMaterialCost(int $projectId): float
    {
        $statement = $this->db->prepare('SELECT COALESCE(SUM(planned_cost), 0) AS total FROM construction_project_materials WHERE construction_project_id = :id');
        $statement->execute(['id' => $projectId]);
        $row = $statement->fetch();

        return (float) ($row['total'] ?? 0);
    }

    private function actualMaterialCost(int $projectId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(construction_daily_consumptions.total_cost), 0) AS total
             FROM construction_daily_consumptions
             INNER JOIN construction_daily_reports ON construction_daily_reports.id = construction_daily_consumptions.construction_daily_report_id
             WHERE construction_daily_reports.construction_project_id = :id'
        );
        $statement->execute(['id' => $projectId]);
        $row = $statement->fetch();

        return (float) ($row['total'] ?? 0);
    }

    private function delayDays(array $project): int
    {
        if (($project['status'] ?? '') === 'Completed') {
            return 0;
        }

        $end = strtotime($project['end_date'] ?? 'now');
        $today = strtotime(date('Y-m-d'));

        return $today > $end ? (int) floor(($today - $end) / 86400) : 0;
    }

    private function insertTasks(int $projectId, array $tasks): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO construction_project_tasks
             (construction_project_id, name, unit, planned_quantity, planned_cost, planned_duration_days, progress_percent, sort_order, created_at, updated_at)
             VALUES (:project_id, :name, :unit, :quantity, :cost, :duration, 0, :sort_order, NOW(), NOW())'
        );
        foreach ($tasks as $index => $task) {
            $statement->execute([
                'project_id' => $projectId,
                'name' => $task['name'],
                'unit' => $task['unit'],
                'quantity' => $task['planned_quantity'],
                'cost' => $task['planned_cost'],
                'duration' => $task['planned_duration_days'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function insertMaterials(int $projectId, array $materials): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO construction_project_materials
             (construction_project_id, construction_material_id, planned_quantity, planned_cost, created_at, updated_at)
             VALUES (:project_id, :material_id, :quantity, :cost, NOW(), NOW())'
        );
        foreach ($materials as $material) {
            $statement->execute([
                'project_id' => $projectId,
                'material_id' => $material['construction_material_id'],
                'quantity' => $material['planned_quantity'],
                'cost' => $material['planned_cost'],
            ]);
        }
    }

    private function nextReference(): string
    {
        return 'PRJ-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}

