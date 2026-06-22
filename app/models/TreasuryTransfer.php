<?php

declare(strict_types=1);

class TreasuryTransfer extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT transfers.*, source.name AS source_name, destination.name AS destination_name,
                    requester.name AS requester_name, approver.name AS approver_name, executor.name AS executor_name
             FROM treasury_transfers transfers
             INNER JOIN treasury_accounts source ON source.id = transfers.source_account_id
             INNER JOIN treasury_accounts destination ON destination.id = transfers.destination_account_id
             INNER JOIN users requester ON requester.id = transfers.requested_by
             LEFT JOIN users approver ON approver.id = transfers.approved_by
             LEFT JOIN users executor ON executor.id = transfers.executed_by
             ORDER BY transfers.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT transfers.*, source.name AS source_name, source.current_balance AS source_current_balance,
                    source.responsible_user_id AS source_responsible_user_id,
                    destination.name AS destination_name, destination.current_balance AS destination_current_balance,
                    requester.name AS requester_name, approver.name AS approver_name, executor.name AS executor_name
             FROM treasury_transfers transfers
             INNER JOIN treasury_accounts source ON source.id = transfers.source_account_id
             INNER JOIN treasury_accounts destination ON destination.id = transfers.destination_account_id
             INNER JOIN users requester ON requester.id = transfers.requested_by
             LEFT JOIN users approver ON approver.id = transfers.approved_by
             LEFT JOIN users executor ON executor.id = transfers.executed_by
             WHERE transfers.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function metrics(): array
    {
        $rows = $this->db->query(
            'SELECT status, COUNT(*) AS count FROM treasury_transfers GROUP BY status'
        )->fetchAll();
        $metrics = ['Draft' => 0, 'Pending' => 0, 'Approved' => 0, 'Executed' => 0, 'Rejected' => 0, 'Cancelled' => 0];
        foreach ($rows as $row) {
            $metrics[$row['status']] = (int) $row['count'];
        }
        return $metrics;
    }

    public function events(int $id): array
    {
        $statement = $this->db->prepare(
            'SELECT events.*, users.name AS user_name
             FROM treasury_transfer_events events
             INNER JOIN users ON users.id = events.user_id
             WHERE events.treasury_transfer_id = :id ORDER BY events.created_at ASC'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll();
    }

    public function attachments(int $id): array
    {
        $statement = $this->db->prepare(
            'SELECT attachments.*, users.name AS uploaded_by_name
             FROM treasury_transfer_attachments attachments
             INNER JOIN users ON users.id = attachments.uploaded_by
             WHERE attachments.treasury_transfer_id = :id ORDER BY attachments.created_at DESC'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetchAll();
    }

    public function create(array $data, bool $submit, ?array $attachment): int
    {
        $this->db->beginTransaction();
        try {
            $reference = $this->nextReference();
            $status = $submit ? 'Pending' : 'Draft';
            $statement = $this->db->prepare(
                'INSERT INTO treasury_transfers
                 (reference, source_account_id, destination_account_id, requested_by, status, source_amount,
                  source_currency, exchange_rate, destination_amount, destination_currency, purpose, notes,
                  requested_at, created_at, updated_at)
                 VALUES (:reference, :source_account_id, :destination_account_id, :requested_by, :status, :source_amount,
                  :source_currency, :exchange_rate, :destination_amount, :destination_currency, :purpose, :notes,
                  :requested_at, NOW(), NOW())'
            );
            $statement->execute([
                'reference' => $reference,
                'source_account_id' => $data['source_account_id'],
                'destination_account_id' => $data['destination_account_id'],
                'requested_by' => $data['requested_by'],
                'status' => $status,
                'source_amount' => $data['source_amount'],
                'source_currency' => $data['source_currency'],
                'exchange_rate' => $data['exchange_rate'],
                'destination_amount' => $data['destination_amount'],
                'destination_currency' => $data['destination_currency'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?: null,
                'requested_at' => $submit ? date('Y-m-d H:i:s') : null,
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->track($id, $data['requested_by'], 'Created', 'Transfert créé.');
            if ($submit) {
                $this->track($id, $data['requested_by'], 'Submitted', 'Transfert soumis pour approbation.');
            }
            if ($attachment !== null) {
                $this->saveAttachment($id, $data['requested_by'], $attachment);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function submit(int $id, int $userId): void
    {
        $transfer = $this->find($id);
        if ($transfer === null || $transfer['status'] !== 'Draft') {
            throw new RuntimeException('Seul un transfert en brouillon peut être soumis.');
        }
        if ((int) $transfer['requested_by'] !== $userId && !Auth::can('treasury_transfers.approve')) {
            throw new RuntimeException('Vous ne pouvez pas soumettre ce transfert.');
        }
        $statement = $this->db->prepare('UPDATE treasury_transfers SET status = "Pending", requested_at = NOW(), updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
        $this->track($id, $userId, 'Submitted', 'Transfert soumis pour approbation.');
    }

    public function approve(int $id, int $userId, string $comment = ''): void
    {
        $transfer = $this->find($id);
        if ($transfer === null || $transfer['status'] !== 'Pending') {
            throw new RuntimeException('Seul un transfert en attente peut être approuvé.');
        }
        if ((int) $transfer['requested_by'] === $userId) {
            throw new RuntimeException('Le demandeur ne peut pas approuver son propre transfert.');
        }
        $statement = $this->db->prepare(
            'UPDATE treasury_transfers SET status = "Approved", approved_by = :user_id, approved_at = NOW(),
             rejection_reason = NULL, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute(['user_id' => $userId, 'id' => $id]);
        $this->track($id, $userId, 'Approved', $comment ?: 'Transfert approuvé.');
    }

    public function reject(int $id, int $userId, string $reason): void
    {
        $transfer = $this->find($id);
        if ($transfer === null || $transfer['status'] !== 'Pending') {
            throw new RuntimeException('Seul un transfert en attente peut être rejeté.');
        }
        if ($reason === '') {
            throw new RuntimeException('Le motif du rejet est obligatoire.');
        }
        $statement = $this->db->prepare(
            'UPDATE treasury_transfers SET status = "Rejected", approved_by = :user_id, approved_at = NOW(),
             rejection_reason = :reason, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute(['user_id' => $userId, 'reason' => $reason, 'id' => $id]);
        $this->track($id, $userId, 'Rejected', $reason);
    }

    public function cancel(int $id, int $userId, string $reason): void
    {
        $transfer = $this->find($id);
        if ($transfer === null || !in_array($transfer['status'], ['Draft', 'Pending', 'Approved'], true)) {
            throw new RuntimeException('Ce transfert ne peut plus être annulé.');
        }
        if ((int) $transfer['requested_by'] !== $userId && !Auth::can('treasury_transfers.approve')) {
            throw new RuntimeException('Vous ne pouvez pas annuler ce transfert.');
        }
        $statement = $this->db->prepare('UPDATE treasury_transfers SET status = "Cancelled", updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
        $this->track($id, $userId, 'Cancelled', $reason ?: 'Transfert annulé.');
    }

    public function execute(int $id, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT * FROM treasury_transfers WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            $transfer = $statement->fetch();
            if (!$transfer || $transfer['status'] !== 'Approved') {
                throw new RuntimeException('Seul un transfert approuvé peut être exécuté.');
            }

            $accounts = $this->db->prepare(
                'SELECT * FROM treasury_accounts WHERE id IN (:source_id, :destination_id) ORDER BY id FOR UPDATE'
            );
            $accounts->execute(['source_id' => $transfer['source_account_id'], 'destination_id' => $transfer['destination_account_id']]);
            $accountRows = $accounts->fetchAll();
            $byId = [];
            foreach ($accountRows as $account) {
                $byId[(int) $account['id']] = $account;
            }
            $source = $byId[(int) $transfer['source_account_id']] ?? null;
            $destination = $byId[(int) $transfer['destination_account_id']] ?? null;
            if ($source === null || $destination === null || $source['status'] !== 'active' || $destination['status'] !== 'active') {
                throw new RuntimeException('Les deux comptes doivent être actifs.');
            }
            if ((int) $source['responsible_user_id'] !== $userId && !Auth::can('treasury_transfers.approve')) {
                throw new RuntimeException('Seul le responsable du compte source peut exécuter ce transfert.');
            }
            if ((float) $source['current_balance'] < (float) $transfer['source_amount']) {
                throw new RuntimeException('Solde insuffisant sur le compte source.');
            }

            $sourceBefore = (float) $source['current_balance'];
            $sourceAfter = $sourceBefore - (float) $transfer['source_amount'];
            $destinationBefore = (float) $destination['current_balance'];
            $destinationAfter = $destinationBefore + (float) $transfer['destination_amount'];
            $movementReference = 'TRF-' . date('Ymd-His') . '-' . random_int(100, 999);

            $movement = $this->db->prepare(
                'INSERT INTO treasury_movements
                 (treasury_account_id, treasury_transfer_id, reference, movement_type, amount, balance_before,
                  balance_after, description, created_by, created_at)
                 VALUES (:account_id, :transfer_id, :reference, :movement_type, :amount, :balance_before,
                  :balance_after, :description, :created_by, NOW())'
            );
            $movement->execute([
                'account_id' => $source['id'], 'transfer_id' => $id, 'reference' => $movementReference . '-OUT',
                'movement_type' => 'outflow', 'amount' => $transfer['source_amount'], 'balance_before' => $sourceBefore,
                'balance_after' => $sourceAfter, 'description' => 'Transfert vers ' . $destination['name'] . ' · ' . $transfer['reference'],
                'created_by' => $userId,
            ]);
            $sourceMovementId = (int) $this->db->lastInsertId();
            $movement->execute([
                'account_id' => $destination['id'], 'transfer_id' => $id, 'reference' => $movementReference . '-IN',
                'movement_type' => 'inflow', 'amount' => $transfer['destination_amount'], 'balance_before' => $destinationBefore,
                'balance_after' => $destinationAfter, 'description' => 'Transfert reçu de ' . $source['name'] . ' · ' . $transfer['reference'],
                'created_by' => $userId,
            ]);
            $destinationMovementId = (int) $this->db->lastInsertId();

            $update = $this->db->prepare('UPDATE treasury_accounts SET current_balance = :balance, updated_at = NOW() WHERE id = :id');
            $update->execute(['balance' => $sourceAfter, 'id' => $source['id']]);
            $update->execute(['balance' => $destinationAfter, 'id' => $destination['id']]);
            $this->db->prepare(
                'UPDATE treasury_transfers SET status = "Executed", executed_by = :user_id, executed_at = NOW(), updated_at = NOW() WHERE id = :id'
            )->execute(['user_id' => $userId, 'id' => $id]);
            $this->track($id, $userId, 'Executed', 'Débit et crédit exécutés simultanément.');
            $this->db->commit();
            return ['source_movement_id' => $sourceMovementId, 'destination_movement_id' => $destinationMovementId];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function saveAttachment(int $id, int $userId, array $file): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO treasury_transfer_attachments
             (treasury_transfer_id, uploaded_by, original_name, file_path, mime_type, file_size, created_at)
             VALUES (:transfer_id, :uploaded_by, :original_name, :file_path, :mime_type, :file_size, NOW())'
        );
        $statement->execute([
            'transfer_id' => $id, 'uploaded_by' => $userId, 'original_name' => $file['original_name'],
            'file_path' => $file['file_path'], 'mime_type' => $file['mime_type'], 'file_size' => $file['file_size'],
        ]);
    }

    private function track(int $id, int $userId, string $action, string $comment): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO treasury_transfer_events (treasury_transfer_id, user_id, action, comment, created_at)
             VALUES (:transfer_id, :user_id, :action, :comment, NOW())'
        );
        $statement->execute(['transfer_id' => $id, 'user_id' => $userId, 'action' => $action, 'comment' => $comment]);
    }

    private function nextReference(): string
    {
        return 'TRF-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
