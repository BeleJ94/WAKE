<?php

declare(strict_types=1);

class PlacementAttendance extends Model
{
    public function assignments(): array
    {
        return $this->db->query(
            'SELECT placement_contract_employees.*, employees.first_name, employees.last_name, placement_contracts.client_name
             FROM placement_contract_employees
             INNER JOIN employees ON employees.id = placement_contract_employees.employee_id
             INNER JOIN placement_contracts ON placement_contracts.id = placement_contract_employees.placement_contract_id
             WHERE placement_contract_employees.status = "active"
             ORDER BY placement_contracts.client_name, employees.last_name'
        )->fetchAll();
    }

    public function forMonth(string $month): array
    {
        $statement = $this->db->prepare(
            'SELECT placement_attendances.*, employees.first_name, employees.last_name, placement_contracts.client_name
             FROM placement_attendances
             INNER JOIN placement_contract_employees ON placement_contract_employees.id = placement_attendances.placement_contract_employee_id
             INNER JOIN employees ON employees.id = placement_contract_employees.employee_id
             INNER JOIN placement_contracts ON placement_contracts.id = placement_contract_employees.placement_contract_id
             WHERE placement_attendances.attendance_month = :month
             ORDER BY placement_contracts.client_name, employees.last_name'
        );
        $statement->execute(['month' => $month]);
        return $statement->fetchAll();
    }

    public function save(array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO placement_attendances
             (placement_contract_employee_id, attendance_month, days_present, days_absent, overtime_hours, notes, created_by, created_at, updated_at)
             VALUES (:assignment_id, :month, :present, :absent, :overtime, :notes, :created_by, NOW(), NOW())
             ON DUPLICATE KEY UPDATE days_present = VALUES(days_present), days_absent = VALUES(days_absent),
                overtime_hours = VALUES(overtime_hours), notes = VALUES(notes), updated_at = NOW()'
        );
        $statement->execute([
            'assignment_id' => $data['assignment_id'],
            'month' => $data['month'],
            'present' => $data['days_present'],
            'absent' => $data['days_absent'],
            'overtime' => $data['overtime_hours'],
            'notes' => $data['notes'],
            'created_by' => $data['created_by'],
        ]);
    }
}

