<?php

declare(strict_types=1);

class TreasuryTransferController extends Controller
{
    public function index(): void
    {
        $model = new TreasuryTransfer();
        $this->view('treasury_transfers.index', [
            'title' => 'Transferts de fonds',
            'transfers' => $model->all(),
            'metrics' => $model->metrics(),
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([], []);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->payload();
        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->renderCreate($data, $errors);
            return;
        }

        $attachment = null;
        try {
            $attachment = $this->handleAttachment();
            $submit = $this->request('action') === 'submit';
            $id = (new TreasuryTransfer())->create($data, $submit, $attachment);
            AuditLog::record('treasury_transfer_created', 'treasury_transfer', $id, ['status' => $submit ? 'Pending' : 'Draft']);
            if ($submit) {
                Notification::push('treasury_transfer_submitted', 'Transfert à approuver', 'Un nouveau transfert de fonds attend une décision.', url('treasury_transfers/show?id=' . $id), 'warning', 'treasury_transfer', $id);
            }
            Session::flash('success', $submit ? 'Transfert soumis pour approbation.' : 'Transfert enregistré en brouillon.');
            $this->redirect('treasury_transfers/show?id=' . $id);
        } catch (Throwable $exception) {
            if ($attachment !== null) {
                $this->removeUploadedFile($attachment['file_path']);
            }
            $this->renderCreate($data, ['transfer' => $exception->getMessage()]);
        }
    }

    public function show(): void
    {
        $transfer = $this->loadTransfer();
        $model = new TreasuryTransfer();
        $this->view('treasury_transfers.show', [
            'title' => $transfer['reference'],
            'transfer' => $transfer,
            'events' => $model->events((int) $transfer['id']),
            'attachments' => $model->attachments((int) $transfer['id']),
        ]);
    }

    public function submit(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);
        try {
            (new TreasuryTransfer())->submit($id, (int) Auth::id());
            AuditLog::record('treasury_transfer_submitted', 'treasury_transfer', $id);
            Notification::push('treasury_transfer_submitted', 'Transfert à approuver', 'Le transfert #' . $id . ' attend une décision.', url('treasury_transfers/show?id=' . $id), 'warning', 'treasury_transfer', $id);
            Session::flash('success', 'Transfert soumis pour approbation.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('treasury_transfers/show?id=' . $id);
    }

    public function decision(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);
        $decision = (string) $this->request('decision', '');
        try {
            if ($decision === 'approve') {
                (new TreasuryTransfer())->approve($id, (int) Auth::id(), trim((string) $this->request('comment', '')));
                AuditLog::record('treasury_transfer_approved', 'treasury_transfer', $id);
                Notification::push('treasury_transfer_approved', 'Transfert approuvé', 'Le transfert #' . $id . ' peut maintenant être exécuté.', url('treasury_transfers/show?id=' . $id), 'success', 'treasury_transfer', $id);
                Session::flash('success', 'Transfert approuvé.');
            } elseif ($decision === 'reject') {
                $reason = trim((string) $this->request('reason', ''));
                (new TreasuryTransfer())->reject($id, (int) Auth::id(), $reason);
                AuditLog::record('treasury_transfer_rejected', 'treasury_transfer', $id, ['reason' => $reason]);
                Session::flash('success', 'Transfert rejeté.');
            } else {
                throw new RuntimeException('Décision invalide.');
            }
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('treasury_transfers/show?id=' . $id);
    }

    public function execute(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);
        try {
            $movements = (new TreasuryTransfer())->execute($id, (int) Auth::id());
            AuditLog::record('treasury_transfer_executed', 'treasury_transfer', $id, $movements);
            Notification::push('treasury_transfer_executed', 'Transfert exécuté', 'Le transfert #' . $id . ' a été débité et crédité avec succès.', url('treasury_transfers/show?id=' . $id), 'success', 'treasury_transfer', $id);
            Session::flash('success', 'Transfert exécuté. Les deux comptes ont été mis à jour simultanément.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('treasury_transfers/show?id=' . $id);
    }

    public function cancel(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);
        try {
            (new TreasuryTransfer())->cancel($id, (int) Auth::id(), trim((string) $this->request('reason', '')));
            AuditLog::record('treasury_transfer_cancelled', 'treasury_transfer', $id);
            Session::flash('success', 'Transfert annulé.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        $this->redirect('treasury_transfers/show?id=' . $id);
    }

    private function payload(): array
    {
        $sourceId = (int) $this->request('source_account_id', 0);
        $destinationId = (int) $this->request('destination_account_id', 0);
        $source = $sourceId > 0 ? (new TreasuryAccount())->find($sourceId) : null;
        $destination = $destinationId > 0 ? (new TreasuryAccount())->find($destinationId) : null;
        $sourceAmount = max(0, (float) $this->request('source_amount', 0));
        $rate = max(0, (float) $this->request('exchange_rate', 1));
        if ($source && $destination && $source['currency'] === $destination['currency']) {
            $rate = 1.0;
        }
        return [
            'requested_by' => (int) Auth::id(),
            'source_account_id' => $sourceId,
            'destination_account_id' => $destinationId,
            'source_amount' => $sourceAmount,
            'source_currency' => $source['currency'] ?? '',
            'exchange_rate' => $rate,
            'destination_amount' => round($sourceAmount * $rate, 2),
            'destination_currency' => $destination['currency'] ?? '',
            'purpose' => trim((string) $this->request('purpose', '')),
            'notes' => trim((string) $this->request('notes', '')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $source = $data['source_account_id'] ? (new TreasuryAccount())->find($data['source_account_id']) : null;
        $destination = $data['destination_account_id'] ? (new TreasuryAccount())->find($data['destination_account_id']) : null;
        if ($source === null || $source['status'] !== 'active') {
            $errors['source_account_id'] = 'Sélectionnez un compte source actif.';
        }
        if ($destination === null || $destination['status'] !== 'active') {
            $errors['destination_account_id'] = 'Sélectionnez un compte destinataire actif.';
        }
        if ($data['source_account_id'] === $data['destination_account_id']) {
            $errors['destination_account_id'] = 'Les comptes source et destinataire doivent être différents.';
        }
        if ($data['source_amount'] <= 0) {
            $errors['source_amount'] = 'Le montant doit être supérieur à zéro.';
        }
        if ($source && (float) $source['current_balance'] < $data['source_amount']) {
            $errors['source_amount'] = 'Le solde du compte source est insuffisant.';
        }
        if ($data['exchange_rate'] <= 0) {
            $errors['exchange_rate'] = 'Le taux de change doit être supérieur à zéro.';
        }
        if (strlen($data['purpose']) < 5) {
            $errors['purpose'] = 'Précisez le motif du transfert.';
        }
        return $errors;
    }

    private function renderCreate(array $old, array $errors): void
    {
        $this->view('treasury_transfers.create', [
            'title' => 'Nouveau transfert',
            'accounts' => (new TreasuryAccount())->active(),
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    private function loadTransfer(): array
    {
        $transfer = (new TreasuryTransfer())->find((int) $this->request('id', 0));
        if ($transfer === null) {
            Session::flash('error', 'Transfert introuvable.');
            $this->redirect('treasury_transfers');
        }
        return $transfer;
    }

    private function handleAttachment(): ?array
    {
        if (empty($_FILES['supporting_document']['name'])) {
            return null;
        }
        $validated = Security::validateUpload(
            $_FILES['supporting_document'],
            ['application/pdf', 'image/jpeg', 'image/png'],
            ['pdf', 'jpg', 'jpeg', 'png'],
            5 * 1024 * 1024
        );
        $filename = Security::safeUploadName('transfer', $validated['extension']);
        $relativeDirectory = 'uploads/treasury_transfers';
        $relativePath = $relativeDirectory . '/' . $filename;
        $target = Security::ensureUploadDirectory($relativeDirectory) . '/' . $filename;
        if (!@move_uploaded_file($_FILES['supporting_document']['tmp_name'], $target)) {
            throw new RuntimeException('Impossible de sauvegarder le justificatif.');
        }
        return [
            'original_name' => (string) $_FILES['supporting_document']['name'],
            'file_path' => $relativePath,
            'mime_type' => $validated['mime_type'],
            'file_size' => $validated['size'],
        ];
    }

    private function removeUploadedFile(string $relativePath): void
    {
        $path = PUBLIC_PATH . '/' . ltrim($relativePath, '/');
        if (is_file($path)) {
            unlink($path);
        }
    }
}
