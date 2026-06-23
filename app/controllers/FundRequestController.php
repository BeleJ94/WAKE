<?php

declare(strict_types=1);

class FundRequestController extends Controller
{
    public function index(): void
    {
        $this->view('fund_requests.index', [
            'title' => 'Demandes de fonds',
            'requests' => (new FundRequest())->all(),
            'metrics' => (new FundRequest())->metrics(),
        ]);
    }

    public function details(): void
    {
        $type = (string) $this->request('type', '');
        $dataset = $this->detailDataset($type);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'type' => $type,
            'title' => $dataset['title'],
            'description' => $dataset['description'],
            'columns' => $dataset['columns'],
            'rows' => $dataset['rows'],
            'exports' => [
                'excel' => url('fund_requests/export?type=' . rawurlencode($type) . '&format=excel'),
                'pdf' => url('fund_requests/export?type=' . rawurlencode($type) . '&format=pdf'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function export(): void
    {
        $type = (string) $this->request('type', '');
        $format = strtolower((string) $this->request('format', 'excel'));
        $dataset = $this->detailDataset($type);
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($type)) ?: 'demandes-fonds';
        AuditLog::record('fund_requests_exported', 'fund_request', null, ['type' => $type, 'format' => $format]);

        if ($format === 'pdf') {
            $this->exportPdf($dataset, 'wake-demandes-fonds-' . $slug . '-' . date('Ymd-His') . '.pdf');
            return;
        }

        $this->exportExcel($dataset, 'wake-demandes-fonds-' . $slug . '-' . date('Ymd-His') . '.xls');
    }

    public function create(): void
    {
        $this->view('fund_requests.create', [
            'title' => 'Nouvelle demande de fonds',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->payload();
        $errors = $this->validatePayload($data);

        if ($errors !== []) {
            $this->renderCreate($data, $errors);
            return;
        }

        $attachment = null;

        try {
            $attachment = $this->handleRequestAttachment();
            $id = (new FundRequest())->create($data, $this->request('action') === 'submit', $attachment);
        } catch (Throwable $exception) {
            if ($attachment !== null) {
                $this->removeUploadedFile($attachment['file_path']);
            }

            $this->renderCreate($data, ['attachment' => $exception->getMessage()]);
            return;
        }

        $submitted = $this->request('action') === 'submit';
        AuditLog::record('fund_request_created', 'fund_request', $id, ['status' => $submitted ? 'Pending' : 'Draft']);
        Notification::push('fund_request_created', 'Nouvelle demande de fonds', $data['title'] . ' - ' . money((new FundRequest())->find($id)['total_amount'] ?? 0), url('fund_requests/show?id=' . $id), 'info', 'fund_request', $id);
        if ($submitted) {
            AuditLog::record('fund_request_submitted', 'fund_request', $id);
        }
        Session::flash('success', 'Demande de fonds créée.');
        $this->redirect('fund_requests/show?id=' . $id);
    }

    public function show(): void
    {
        $request = $this->loadRequest();
        $model = new FundRequest();

        $this->view('fund_requests.show', [
            'title' => $request['reference'],
            'request' => $request,
            'attachments' => $model->attachments((int) $request['id']),
            'approvals' => $model->approvals((int) $request['id']),
            'proofs' => $model->paymentProofs((int) $request['id']),
        ]);
    }

    public function submit(): void
    {
        $this->requireCsrf();
        $id = (int) $this->request('id', 0);

        try {
            (new FundRequest())->submit($id, (int) Auth::id());
            AuditLog::record('fund_request_submitted', 'fund_request', $id);
            Session::flash('success', 'Demande soumise à la Direction.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirect('fund_requests/show?id=' . $id);
    }

    public function approveForm(): void
    {
        $request = $this->loadRequest();
        $accounts = array_values(array_filter(
            (new TreasuryAccount())->active(),
            static fn (array $account): bool => ($account['currency'] ?? '') === ($request['currency'] ?? '')
        ));

        $this->view('fund_requests.approve', [
            'title' => 'Approbation ' . $request['reference'],
            'request' => $request,
            'accounts' => $accounts,
            'attachments' => (new FundRequest())->attachments((int) $request['id']),
        ]);
    }

    public function approve(): void
    {
        $this->requireCsrf();

        $id = (int) $this->request('id', 0);
        $decision = (string) $this->request('decision', '');

        try {
            if ($decision === 'approve') {
                (new FundRequest())->approve($id, (int) $this->request('treasury_account_id', 0), (int) Auth::id(), trim((string) $this->request('comment', '')));
                AuditLog::record('fund_request_approved', 'fund_request', $id, ['account_id' => (int) $this->request('treasury_account_id', 0)]);
                Notification::push('fund_request_approved', 'Demande approuvée', 'La demande #' . $id . ' a été approuvée.', url('fund_requests/show?id=' . $id), 'success', 'fund_request', $id);
                Session::flash('success', 'Demande approuvée.');
            } elseif ($decision === 'reject') {
                $reason = trim((string) $this->request('rejected_reason', ''));
                if ($reason === '') {
                    throw new RuntimeException('Le motif de rejet est obligatoire.');
                }
                (new FundRequest())->reject($id, (int) Auth::id(), $reason);
                AuditLog::record('fund_request_rejected', 'fund_request', $id, ['reason' => $reason]);
                Notification::push('fund_request_rejected', 'Demande rejetée', 'La demande #' . $id . ' a été rejetée : ' . $reason, url('fund_requests/show?id=' . $id), 'danger', 'fund_request', $id);
                Session::flash('success', 'Demande rejetée.');
            } else {
                throw new RuntimeException('Veuillez choisir une décision.');
            }
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirect('fund_requests/show?id=' . $id);
    }

    public function paymentForm(): void
    {
        $request = $this->loadRequest();

        $this->view('fund_requests.payment', [
            'title' => 'Paiement ' . $request['reference'],
            'request' => $request,
            'account' => $request['treasury_account_id'] ? (new TreasuryAccount())->find((int) $request['treasury_account_id']) : null,
        ]);
    }

    public function payment(): void
    {
        $this->requireCsrf();

        $id = (int) $this->request('id', 0);

        try {
            $proof = $this->handleUpload();
            $movementId = (new FundRequest())->pay($id, (int) Auth::id(), trim((string) $this->request('description', 'Paiement demande de fonds')), $proof);
            AuditLog::record('fund_request_paid', 'fund_request', $id, ['movement_id' => $movementId]);
            Notification::push('fund_request_paid', 'Paiement effectué', 'Le paiement de la demande #' . $id . ' a été effectué.', url('fund_requests/show?id=' . $id), 'success', 'fund_request', $id);
            Session::flash('success', 'Paiement effectué et mouvement créé automatiquement.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirect('fund_requests/show?id=' . $id);
    }

    private function loadRequest(): array
    {
        $request = (new FundRequest())->find((int) $this->request('id', 0));

        if ($request === null) {
            Session::flash('error', 'Demande introuvable.');
            $this->redirect('fund_requests');
        }

        return $request;
    }

    private function payload(): array
    {
        return [
            'requested_by' => (int) Auth::id(),
            'title' => trim((string) $this->request('title', '')),
            'department' => trim((string) $this->request('department', '')),
            'purpose' => trim((string) $this->request('purpose', '')),
            'total_amount' => max(0, (float) $this->request('total_amount', 0)),
            'currency' => trim((string) $this->request('currency', 'USD')) ?: 'USD',
            'needed_at' => trim((string) $this->request('needed_at', '')),
        ];
    }

    private function validatePayload(array $data): array
    {
        $errors = [];

        if (strlen($data['title']) < 3) {
            $errors['title'] = 'Le titre est obligatoire.';
        }
        if (strlen($data['department']) < 2) {
            $errors['department'] = 'Le service est obligatoire.';
        }
        if (strlen($data['purpose']) < 8) {
            $errors['purpose'] = 'Veuillez préciser le besoin.';
        }
        if ($data['total_amount'] <= 0) {
            $errors['total_amount'] = 'Le montant demandé doit être supérieur à zéro.';
        }
        if (!in_array($data['currency'], ['USD', 'CDF'], true)) {
            $errors['currency'] = 'Sélectionnez une monnaie valide.';
        }

        return $errors;
    }

    private function renderCreate(array $old, array $errors): void
    {
        $this->view('fund_requests.create', [
            'title' => 'Nouvelle demande de fonds',
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function handleRequestAttachment(): ?array
    {
        if (empty($_FILES['supporting_document']['name'])) {
            return null;
        }

        if (($_FILES['supporting_document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('La pièce justificative n’a pas pu être chargée.');
        }

        $validated = Security::validateUpload(
            $_FILES['supporting_document'],
            ['application/pdf', 'image/jpeg', 'image/png'],
            ['pdf', 'jpg', 'jpeg', 'png'],
            5 * 1024 * 1024
        );

        $filename = Security::safeUploadName('justificatif', $validated['extension']);
        $relativeDirectory = 'uploads/fund_request_attachments';
        $relativePath = $relativeDirectory . '/' . $filename;
        $target = Security::ensureUploadDirectory($relativeDirectory) . '/' . $filename;

        if (!@move_uploaded_file($_FILES['supporting_document']['tmp_name'], $target)) {
            throw new RuntimeException('Impossible de sauvegarder la pièce justificative.');
        }

        return [
            'uploaded_by' => (int) Auth::id(),
            'original_name' => (string) $_FILES['supporting_document']['name'],
            'file_path' => $relativePath,
            'mime_type' => $validated['mime_type'],
            'file_size' => $validated['size'],
        ];
    }

    private function removeUploadedFile(string $relativePath): void
    {
        $absolutePath = PUBLIC_PATH . '/' . ltrim($relativePath, '/');
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private function detailDataset(string $type): array
    {
        $model = new FundRequest();

        switch ($type) {
            case 'pending':
                return $this->requestDataset(
                    'Demandes en attente de décision',
                    'Demandes soumises à la Direction et non encore approuvées ou rejetées.',
                    $model->detailRows('pending')
                );
            case 'approved':
                return $this->requestDataset(
                    'Demandes approuvées à payer',
                    'Demandes autorisées dont le décaissement reste à effectuer.',
                    $model->detailRows('approved')
                );
            case 'paid_month':
                return $this->requestDataset(
                    'Dépenses payées ce mois',
                    'Décaissements issus des demandes de fonds payées pendant le mois courant.',
                    $model->detailRows('paid_month')
                );
        }

        throw new InvalidArgumentException('Indicateur de demandes de fonds inconnu.');
    }

    private function requestDataset(string $title, string $description, array $rows): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'columns' => ['Référence', 'Titre', 'Service', 'Demandeur', 'Montant', 'Monnaie', 'Date souhaitée', 'Statut'],
            'rows' => array_map(static fn (array $row): array => [
                $row['reference'],
                $row['title'],
                $row['department'],
                $row['requester_name'],
                number_format((float) $row['total_amount'], 2, ',', ' '),
                $row['currency'],
                $row['needed_at'] ?: '-',
                $row['status'],
            ], $rows),
        ];
    }

    private function exportExcel(array $dataset, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1"><tr><th colspan="' . count($dataset['columns']) . '">' . e($dataset['title']) . '</th></tr>';
        echo '<tr><td colspan="' . count($dataset['columns']) . '">' . e($dataset['description']) . '</td></tr><tr>';
        foreach ($dataset['columns'] as $column) {
            echo '<th style="background:#0d4f43;color:#fff">' . e((string) $column) . '</th>';
        }
        echo '</tr>';
        foreach ($dataset['rows'] as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . e((string) $value) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    private function exportPdf(array $dataset, string $filename): void
    {
        $lines = [$dataset['title'], $dataset['description'], 'Généré le ' . date('d/m/Y H:i'), ''];
        $lines[] = implode(' | ', $dataset['columns']);
        $lines[] = str_repeat('-', 112);
        foreach ($dataset['rows'] as $row) {
            $line = implode(' | ', array_map(static fn ($value): string => (string) $value, $row));
            foreach (explode("\n", wordwrap($line, 112, "\n", true)) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }
        }
        if ($dataset['rows'] === []) {
            $lines[] = 'Aucune donnée disponible.';
        }

        $pdf = $this->buildPdf($lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function buildPdf(array $lines): string
    {
        $chunks = array_chunk($lines, 48);
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>'];
        $pageReferences = [];
        $objectId = 4;

        foreach ($chunks as $pageLines) {
            $pageId = $objectId++;
            $contentId = $objectId++;
            $pageReferences[] = $pageId . ' 0 R';
            $stream = "BT\n/F1 7 Tf\n30 806 Td\n";
            foreach ($pageLines as $index => $line) {
                if ($index > 0) {
                    $stream .= "0 -15 Td\n";
                }
                $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line) ?: $line;
                $encoded = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
                $stream .= '(' . $encoded . ") Tj\n";
            }
            $stream .= 'ET';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageReferences) . '] /Count ' . count($pageReferences) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function handleUpload(): ?array
    {
        if (empty($_FILES['payment_proof']['name'])) {
            return null;
        }

        if ($_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload de preuve invalide.');
        }

        $validated = Security::validateUpload(
            $_FILES['payment_proof'],
            ['application/pdf', 'image/jpeg', 'image/png'],
            ['pdf', 'jpg', 'jpeg', 'png'],
            5 * 1024 * 1024
        );

        $filename = Security::safeUploadName('proof', $validated['extension']);
        $relativeDirectory = 'uploads/payment_proofs';
        $relativePath = $relativeDirectory . '/' . $filename;
        $target = Security::ensureUploadDirectory($relativeDirectory) . '/' . $filename;

        if (!@move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target)) {
            throw new RuntimeException('Impossible de sauvegarder la preuve.');
        }

        return [
            'original_name' => $_FILES['payment_proof']['name'],
            'file_path' => $relativePath,
            'mime_type' => $validated['mime_type'],
            'file_size' => $validated['size'],
        ];
    }
}
