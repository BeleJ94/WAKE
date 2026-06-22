<?php

declare(strict_types=1);

class FundRequest extends Model
{
    public function all(): array
    {
        return $this->db->query(
            'SELECT fund_requests.*, requester.name AS requester_name, treasury_accounts.name AS account_name
             FROM fund_requests
             INNER JOIN users requester ON requester.id = fund_requests.requested_by
             LEFT JOIN treasury_accounts ON treasury_accounts.id = fund_requests.treasury_account_id
             ORDER BY fund_requests.created_at DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT fund_requests.*, requester.name AS requester_name, approver.name AS approver_name,
                    payer.name AS payer_name, treasury_accounts.name AS account_name,
                    treasury_accounts.responsible_user_id AS account_responsible_user_id
             FROM fund_requests
             INNER JOIN users requester ON requester.id = fund_requests.requested_by
             LEFT JOIN users approver ON approver.id = fund_requests.approved_by
             LEFT JOIN users payer ON payer.id = fund_requests.paid_by
             LEFT JOIN treasury_accounts ON treasury_accounts.id = fund_requests.treasury_account_id
             WHERE fund_requests.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $request = $statement->fetch();

        return $request ?: null;
    }

    public function approvals(int $requestId): array
    {
        $statement = $this->db->prepare(
            'SELECT fund_request_approvals.*, users.name AS user_name
             FROM fund_request_approvals
             INNER JOIN users ON users.id = fund_request_approvals.user_id
             WHERE fund_request_approvals.fund_request_id = :id
             ORDER BY fund_request_approvals.created_at ASC'
        );
        $statement->execute(['id' => $requestId]);

        return $statement->fetchAll();
    }

    public function attachments(int $requestId): array
    {
        $statement = $this->db->prepare(
            'SELECT fund_request_attachments.*, users.name AS uploaded_by_name
             FROM fund_request_attachments
             INNER JOIN users ON users.id = fund_request_attachments.uploaded_by
             WHERE fund_request_attachments.fund_request_id = :id
             ORDER BY fund_request_attachments.created_at DESC'
        );
        $statement->execute(['id' => $requestId]);

        return $statement->fetchAll();
    }

    public function paymentProofs(int $requestId): array
    {
        $statement = $this->db->prepare(
            'SELECT payment_proofs.*, users.name AS uploaded_by_name
             FROM payment_proofs
             INNER JOIN users ON users.id = payment_proofs.uploaded_by
             WHERE payment_proofs.fund_request_id = :id
             ORDER BY payment_proofs.created_at DESC'
        );
        $statement->execute(['id' => $requestId]);

        return $statement->fetchAll();
    }

    public function create(array $data, bool $submit, ?array $attachment = null): int
    {
        $this->db->beginTransaction();

        try {
            $reference = $this->nextReference();
            $status = $submit ? 'Pending' : 'Draft';
            $statement = $this->db->prepare(
                'INSERT INTO fund_requests
                 (requested_by, reference, title, department, purpose, status, total_amount, currency, needed_at, created_at, updated_at)
                 VALUES (:requested_by, :reference, :title, :department, :purpose, :status, :total_amount, :currency, :needed_at, NOW(), NOW())'
            );
            $statement->execute([
                'requested_by' => $data['requested_by'],
                'reference' => $reference,
                'title' => $data['title'],
                'department' => $data['department'],
                'purpose' => $data['purpose'],
                'status' => $status,
                'total_amount' => $data['total_amount'],
                'currency' => $data['currency'],
                'needed_at' => $data['needed_at'] ?: null,
            ]);

            $requestId = (int) $this->db->lastInsertId();

            if ($attachment !== null) {
                $this->saveAttachment($requestId, $attachment);
            }

            if ($submit) {
                $this->track($requestId, (int) $data['requested_by'], 'Submitted', 'Demande soumise pour approbation.');
            }

            $this->db->commit();

            return $requestId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function submit(int $id, int $userId): void
    {
        $request = $this->find($id);

        if ($request === null || $request['status'] !== 'Draft') {
            throw new RuntimeException('Seule une demande Draft peut être soumise.');
        }

        $statement = $this->db->prepare('UPDATE fund_requests SET status = "Pending", updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
        $this->track($id, $userId, 'Submitted', 'Demande soumise pour approbation.');
    }

    public function approve(int $id, int $accountId, int $userId, ?string $comment): void
    {
        $request = $this->find($id);

        if ($request === null || $request['status'] !== 'Pending') {
            throw new RuntimeException('Seule une demande Pending peut être approuvée.');
        }

        $account = (new TreasuryAccount())->find($accountId);
        if ($account === null || ($account['status'] ?? '') !== 'active') {
            throw new RuntimeException('Sélectionnez un compte de trésorerie actif.');
        }
        if (($account['currency'] ?? '') !== ($request['currency'] ?? '')) {
            throw new RuntimeException('Le compte sélectionné doit utiliser la même monnaie que la demande.');
        }

        $statement = $this->db->prepare(
            'UPDATE fund_requests
             SET status = "Approved", treasury_account_id = :account_id, approved_by = :approved_by,
                 approved_at = NOW(), rejected_reason = NULL, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'account_id' => $accountId,
            'approved_by' => $userId,
            'id' => $id,
        ]);
        $this->track($id, $userId, 'Approved', $comment);
    }

    public function reject(int $id, int $userId, string $reason): void
    {
        $request = $this->find($id);

        if ($request === null || $request['status'] !== 'Pending') {
            throw new RuntimeException('Seule une demande Pending peut être rejetée.');
        }

        $statement = $this->db->prepare(
            'UPDATE fund_requests
             SET status = "Rejected", approved_by = :approved_by, approved_at = NOW(),
                 rejected_reason = :reason, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['approved_by' => $userId, 'reason' => $reason, 'id' => $id]);
        $this->track($id, $userId, 'Rejected', $reason);
    }

    public function pay(int $id, int $userId, string $description, ?array $proof): int
    {
        $this->db->beginTransaction();

        try {
            $request = $this->find($id);

            if ($request === null || $request['status'] !== 'Approved') {
                throw new RuntimeException('Un paiement ne peut être fait que sur une demande approuvée.');
            }

            if ((int) $request['account_responsible_user_id'] !== $userId) {
                throw new RuntimeException('Seul le responsable du compte peut effectuer le paiement.');
            }

            $account = (new TreasuryAccount())->find((int) $request['treasury_account_id']);

            if ($account === null) {
                throw new RuntimeException('Compte de trésorerie introuvable.');
            }

            $movementId = (new TreasuryMovement())->createOutflowForFundRequest($request, $account, $userId, $description);

            $statement = $this->db->prepare(
                'UPDATE fund_requests
                 SET status = "Paid", paid_by = :paid_by, paid_at = NOW(), updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute(['paid_by' => $userId, 'id' => $id]);

            if ($proof !== null) {
                $this->saveProofRecord($id, $movementId, $userId, $proof);
            }

            $this->db->commit();

            return $movementId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function metrics(): array
    {
        $statusRows = $this->db->query('SELECT status, COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS amount FROM fund_requests GROUP BY status')->fetchAll();
        $metrics = [
            'pending_count' => 0,
            'approved_count' => 0,
            'paid_month' => 0.0,
            'pending_amount' => 0.0,
            'pending_by_currency' => ['USD' => 0.0, 'CDF' => 0.0],
            'paid_month_by_currency' => ['USD' => 0.0, 'CDF' => 0.0],
        ];

        foreach ($statusRows as $row) {
            if ($row['status'] === 'Pending') {
                $metrics['pending_count'] = (int) $row['count'];
                $metrics['pending_amount'] = (float) $row['amount'];
            }

            if ($row['status'] === 'Approved') {
                $metrics['approved_count'] = (int) $row['count'];
            }
        }

        $month = $this->db->query(
            'SELECT COALESCE(SUM(amount), 0) AS total
             FROM treasury_movements
             WHERE movement_type = "outflow"
               AND MONTH(created_at) = MONTH(CURRENT_DATE())
               AND YEAR(created_at) = YEAR(CURRENT_DATE())'
        )->fetch();
        $metrics['paid_month'] = (float) ($month['total'] ?? 0);

        $pendingCurrencies = $this->db->query(
            'SELECT currency, COALESCE(SUM(total_amount), 0) AS total
             FROM fund_requests
             WHERE status = "Pending"
             GROUP BY currency'
        )->fetchAll();
        foreach ($pendingCurrencies as $row) {
            $metrics['pending_by_currency'][$row['currency']] = (float) $row['total'];
        }

        $paidCurrencies = $this->db->query(
            'SELECT currency, COALESCE(SUM(total_amount), 0) AS total
             FROM fund_requests
             WHERE status = "Paid"
               AND MONTH(paid_at) = MONTH(CURRENT_DATE())
               AND YEAR(paid_at) = YEAR(CURRENT_DATE())
             GROUP BY currency'
        )->fetchAll();
        foreach ($paidCurrencies as $row) {
            $metrics['paid_month_by_currency'][$row['currency']] = (float) $row['total'];
        }

        return $metrics;
    }

    public function detailRows(string $type): array
    {
        $where = '';
        if ($type === 'pending') {
            $where = 'fund_requests.status = "Pending"';
        } elseif ($type === 'approved') {
            $where = 'fund_requests.status = "Approved"';
        } elseif ($type === 'paid_month') {
            $where = 'fund_requests.status = "Paid"
                      AND MONTH(fund_requests.paid_at) = MONTH(CURRENT_DATE())
                      AND YEAR(fund_requests.paid_at) = YEAR(CURRENT_DATE())';
        } else {
            throw new InvalidArgumentException('Type de détail inconnu.');
        }

        return $this->db->query(
            'SELECT fund_requests.reference, fund_requests.title, fund_requests.department,
                    requester.name AS requester_name, fund_requests.total_amount,
                    fund_requests.currency, fund_requests.needed_at, fund_requests.status
             FROM fund_requests
             INNER JOIN users requester ON requester.id = fund_requests.requested_by
             WHERE ' . $where . '
             ORDER BY fund_requests.created_at DESC'
        )->fetchAll();
    }

    private function saveProofRecord(int $requestId, int $movementId, int $userId, array $proof): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO payment_proofs
             (fund_request_id, treasury_movement_id, uploaded_by, original_name, file_path, mime_type, file_size, created_at)
             VALUES (:fund_request_id, :treasury_movement_id, :uploaded_by, :original_name, :file_path, :mime_type, :file_size, NOW())'
        );
        $statement->execute([
            'fund_request_id' => $requestId,
            'treasury_movement_id' => $movementId,
            'uploaded_by' => $userId,
            'original_name' => $proof['original_name'],
            'file_path' => $proof['file_path'],
            'mime_type' => $proof['mime_type'],
            'file_size' => $proof['file_size'],
        ]);
    }

    private function saveAttachment(int $requestId, array $attachment): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO fund_request_attachments
             (fund_request_id, uploaded_by, original_name, file_path, mime_type, file_size, created_at)
             VALUES (:fund_request_id, :uploaded_by, :original_name, :file_path, :mime_type, :file_size, NOW())'
        );
        $statement->execute([
            'fund_request_id' => $requestId,
            'uploaded_by' => $attachment['uploaded_by'],
            'original_name' => $attachment['original_name'],
            'file_path' => $attachment['file_path'],
            'mime_type' => $attachment['mime_type'],
            'file_size' => $attachment['file_size'],
        ]);
    }

    private function track(int $requestId, int $userId, string $action, ?string $comment): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO fund_request_approvals (fund_request_id, user_id, action, comment, created_at)
             VALUES (:fund_request_id, :user_id, :action, :comment, NOW())'
        );
        $statement->execute([
            'fund_request_id' => $requestId,
            'user_id' => $userId,
            'action' => $action,
            'comment' => $comment,
        ]);
    }

    private function nextReference(): string
    {
        return 'DF-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
