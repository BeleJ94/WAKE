<?php

declare(strict_types=1);

class PlacementInvoice extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT placement_invoices.*, placement_contracts.client_name
             FROM placement_invoices
             INNER JOIN placement_contracts ON placement_contracts.id = placement_invoices.placement_contract_id
             ORDER BY placement_invoices.created_at DESC'
        )->fetchAll();
    }

    public function generate(int $contractId, string $month, int $userId): int
    {
        $contract = (new PlacementContract())->find($contractId);
        if ($contract === null) {
            throw new RuntimeException('Contrat introuvable.');
        }
        $assignments = (new PlacementContract())->assignments($contractId);
        if ($assignments === []) {
            throw new RuntimeException('Aucun agent affecté à facturer.');
        }

        $this->db->beginTransaction();
        try {
            $reference = 'PINV-' . date('Ymd-His') . '-' . random_int(100, 999);
            $statement = $this->db->prepare(
                'INSERT INTO placement_invoices
                 (placement_contract_id, reference, invoice_month, invoice_date, due_date, subtotal, total_cost, margin_amount, status, created_by, created_at, updated_at)
                 VALUES (:contract_id, :reference, :month, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY), 0, 0, 0, "Issued", :created_by, NOW(), NOW())'
            );
            $statement->execute(['contract_id' => $contractId, 'reference' => $reference, 'month' => $month, 'created_by' => $userId]);
            $invoiceId = (int) $this->db->lastInsertId();

            $subtotal = 0.0;
            $totalCost = 0.0;
            $item = $this->db->prepare(
                'INSERT INTO placement_invoice_items
                 (placement_invoice_id, placement_contract_employee_id, description, quantity, unit_rate, agent_cost, line_total, margin_amount, created_at)
                 VALUES (:invoice_id, :assignment_id, :description, 1, :rate, :cost, :line_total, :margin, NOW())'
            );
            foreach ($assignments as $assignment) {
                if ($assignment['status'] !== 'active') {
                    continue;
                }
                $line = (float) $assignment['client_rate'];
                $cost = (float) $assignment['agent_cost'];
                $subtotal += $line;
                $totalCost += $cost;
                $item->execute([
                    'invoice_id' => $invoiceId,
                    'assignment_id' => $assignment['id'],
                    'description' => $assignment['first_name'] . ' ' . $assignment['last_name'] . ' - ' . $assignment['position_title'],
                    'rate' => $line,
                    'cost' => $cost,
                    'line_total' => $line,
                    'margin' => $line - $cost,
                ]);
            }
            $update = $this->db->prepare('UPDATE placement_invoices SET subtotal = :subtotal, total_cost = :cost, margin_amount = :margin WHERE id = :id');
            $update->execute(['subtotal' => $subtotal, 'cost' => $totalCost, 'margin' => $subtotal - $totalCost, 'id' => $invoiceId]);
            $this->db->commit();
            return $invoiceId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}

