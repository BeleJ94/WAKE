<?php

declare(strict_types=1);

class ConstructionDailyReport extends Model
{
    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT construction_daily_reports.*, construction_projects.name AS project_name,
                    construction_projects.reference AS project_reference, users.name AS created_by_name
             FROM construction_daily_reports
             INNER JOIN construction_projects ON construction_projects.id = construction_daily_reports.construction_project_id
             INNER JOIN users ON users.id = construction_daily_reports.created_by
             WHERE construction_daily_reports.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $report = $statement->fetch();

        return $report ?: null;
    }

    public function progress(int $reportId): array
    {
        $statement = $this->db->prepare(
            'SELECT construction_daily_progress.*, construction_project_tasks.name AS task_name, construction_project_tasks.unit
             FROM construction_daily_progress
             INNER JOIN construction_project_tasks ON construction_project_tasks.id = construction_daily_progress.construction_project_task_id
             WHERE construction_daily_progress.construction_daily_report_id = :id'
        );
        $statement->execute(['id' => $reportId]);

        return $statement->fetchAll();
    }

    public function consumptions(int $reportId): array
    {
        $statement = $this->db->prepare(
            'SELECT construction_daily_consumptions.*, construction_materials.name AS material_name, construction_materials.unit
             FROM construction_daily_consumptions
             INNER JOIN construction_materials ON construction_materials.id = construction_daily_consumptions.construction_material_id
             WHERE construction_daily_consumptions.construction_daily_report_id = :id'
        );
        $statement->execute(['id' => $reportId]);

        return $statement->fetchAll();
    }

    public function expenses(int $reportId): array
    {
        $statement = $this->db->prepare('SELECT * FROM construction_project_expenses WHERE construction_daily_report_id = :id ORDER BY id ASC');
        $statement->execute(['id' => $reportId]);

        return $statement->fetchAll();
    }

    public function photos(int $reportId): array
    {
        $statement = $this->db->prepare('SELECT * FROM construction_project_photos WHERE construction_daily_report_id = :id ORDER BY created_at DESC');
        $statement->execute(['id' => $reportId]);

        return $statement->fetchAll();
    }

    public function create(array $data, array $progressRows, array $consumptionRows, array $expenseRows, array $photos): int
    {
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare(
                'INSERT INTO construction_daily_reports
                 (construction_project_id, report_date, weather, remarks, blockers, created_by, created_at, updated_at)
                 VALUES (:project_id, :report_date, :weather, :remarks, :blockers, :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'project_id' => $data['construction_project_id'],
                'report_date' => $data['report_date'],
                'weather' => $data['weather'],
                'remarks' => $data['remarks'],
                'blockers' => $data['blockers'],
                'created_by' => $data['created_by'],
            ]);
            $reportId = (int) $this->db->lastInsertId();

            $this->insertProgress($reportId, $progressRows);
            $this->insertConsumptions($reportId, $consumptionRows);
            $this->insertExpenses($reportId, (int) $data['construction_project_id'], (int) $data['created_by'], (string) $data['report_date'], $expenseRows);
            $this->insertPhotos($reportId, (int) $data['construction_project_id'], (int) $data['created_by'], $photos);

            $this->db->commit();

            return $reportId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function insertProgress(int $reportId, array $rows): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO construction_daily_progress
             (construction_daily_report_id, construction_project_task_id, executed_work, quantity_done, progress_percent, created_at)
             VALUES (:report_id, :task_id, :work, :quantity, :progress, NOW())'
        );

        foreach ($rows as $row) {
            $statement->execute([
                'report_id' => $reportId,
                'task_id' => $row['task_id'],
                'work' => $row['executed_work'],
                'quantity' => $row['quantity_done'],
                'progress' => $row['progress_percent'],
            ]);
            (new ConstructionProject())->updateTaskProgress((int) $row['task_id'], (float) $row['progress_percent']);
        }
    }

    private function insertConsumptions(int $reportId, array $rows): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO construction_daily_consumptions
             (construction_daily_report_id, construction_material_id, quantity_used, unit_cost, total_cost, created_at)
             VALUES (:report_id, :material_id, :quantity, :unit_cost, :total, NOW())'
        );

        foreach ($rows as $row) {
            $quantity = (float) $row['quantity_used'];
            $unitCost = (float) $row['unit_cost'];
            $statement->execute([
                'report_id' => $reportId,
                'material_id' => $row['material_id'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total' => $quantity * $unitCost,
            ]);
        }
    }

    private function insertExpenses(int $reportId, int $projectId, int $userId, string $date, array $rows): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO construction_project_expenses
             (construction_project_id, construction_daily_report_id, expense_date, category, description, amount, created_by, created_at)
             VALUES (:project_id, :report_id, :expense_date, :category, :description, :amount, :created_by, NOW())'
        );

        foreach ($rows as $row) {
            $statement->execute([
                'project_id' => $projectId,
                'report_id' => $reportId,
                'expense_date' => $date,
                'category' => $row['category'],
                'description' => $row['description'],
                'amount' => $row['amount'],
                'created_by' => $userId,
            ]);
        }
    }

    private function insertPhotos(int $reportId, int $projectId, int $userId, array $photos): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO construction_project_photos
             (construction_project_id, construction_daily_report_id, uploaded_by, caption, original_name, file_path, mime_type, file_size, created_at)
             VALUES (:project_id, :report_id, :uploaded_by, :caption, :original_name, :file_path, :mime_type, :file_size, NOW())'
        );

        foreach ($photos as $photo) {
            $statement->execute([
                'project_id' => $projectId,
                'report_id' => $reportId,
                'uploaded_by' => $userId,
                'caption' => $photo['caption'],
                'original_name' => $photo['original_name'],
                'file_path' => $photo['file_path'],
                'mime_type' => $photo['mime_type'],
                'file_size' => $photo['file_size'],
            ]);
        }
    }
}

