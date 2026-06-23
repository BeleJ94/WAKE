<?php

declare(strict_types=1);

class TreasuryMovement extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT treasury_movements.*, treasury_accounts.name AS account_name, users.name AS created_by_name
             FROM treasury_movements
             INNER JOIN treasury_accounts ON treasury_accounts.id = treasury_movements.treasury_account_id
             INNER JOIN users ON users.id = treasury_movements.created_by
             ORDER BY treasury_movements.created_at DESC'
        )->fetchAll();
    }

    public function createOutflowForFundRequest(array $request, array $account, int $userId, string $description): int
    {
        $amount = (float) $request['total_amount'];
        $balanceBefore = (float) $account['current_balance'];

        if ($balanceBefore < $amount) {
            throw new RuntimeException('Solde insuffisant pour effectuer le paiement.');
        }

        $balanceAfter = $balanceBefore - $amount;
        $reference = $this->nextReference();

        $statement = $this->db->prepare(
            'INSERT INTO treasury_movements
             (treasury_account_id, fund_request_id, reference, movement_type, amount, balance_before, balance_after, description, created_by, created_at)
             VALUES (:treasury_account_id, :fund_request_id, :reference, "outflow", :amount, :balance_before, :balance_after, :description, :created_by, NOW())'
        );
        $statement->execute([
            'treasury_account_id' => $account['id'],
            'fund_request_id' => $request['id'],
            'reference' => $reference,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'created_by' => $userId,
        ]);

        $update = $this->db->prepare('UPDATE treasury_accounts SET current_balance = :balance, updated_at = NOW() WHERE id = :id');
        $update->execute(['balance' => $balanceAfter, 'id' => $account['id']]);

        return (int) $this->db->lastInsertId();
    }

    private function nextReference(): string
    {
        return 'TRM-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}

