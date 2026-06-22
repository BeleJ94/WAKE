<?php

declare(strict_types=1);

class TreasuryAccountController extends Controller
{
    public function index(): void
    {
        $model = new TreasuryAccount();
        $accounts = $model->all();

        $this->view('treasury_accounts.index', [
            'title' => 'Caisses & Banques',
            'accounts' => $accounts,
            'totals' => $model->totals(),
            'accountMetrics' => $this->accountMetrics($accounts),
            'users' => (new User())->all(),
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
                'excel' => url('treasury_accounts/export?type=' . rawurlencode($type) . '&format=excel'),
                'pdf' => url('treasury_accounts/export?type=' . rawurlencode($type) . '&format=pdf'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function export(): void
    {
        $type = (string) $this->request('type', '');
        $format = strtolower((string) $this->request('format', 'excel'));
        $dataset = $this->detailDataset($type);
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($type)) ?: 'comptes';

        AuditLog::record('treasury_accounts_exported', 'treasury_account', null, [
            'type' => $type,
            'format' => $format,
        ]);

        if ($format === 'pdf') {
            $this->exportPdf($dataset, 'wake-tresorerie-' . $slug . '-' . date('Ymd-His') . '.pdf');
            return;
        }

        $this->exportExcel($dataset, 'wake-tresorerie-' . $slug . '-' . date('Ymd-His') . '.xls');
    }

    public function accountDetails(): void
    {
        $id = (int) $this->request('id', 0);
        $payload = $this->accountDetailPayload($id);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'account' => $payload['account'],
            'summary' => $payload['summary'],
            'movements' => $payload['movements'],
            'exports' => [
                'excel' => url('treasury_accounts/account-export?id=' . $id . '&format=excel'),
                'pdf' => url('treasury_accounts/account-export?id=' . $id . '&format=pdf'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function accountExport(): void
    {
        $id = (int) $this->request('id', 0);
        $format = strtolower((string) $this->request('format', 'excel'));
        $payload = $this->accountDetailPayload($id);
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($payload['account']['name'])) ?: 'compte';

        AuditLog::record('treasury_account_detail_exported', 'treasury_account', $id, ['format' => $format]);

        if ($format === 'pdf') {
            $this->exportAccountPdf($payload, 'wake-fiche-compte-' . $slug . '-' . date('Ymd-His') . '.pdf');
            return;
        }

        $this->exportAccountExcel($payload, 'wake-fiche-compte-' . $slug . '-' . date('Ymd-His') . '.xls');
    }

    public function create(): void
    {
        $this->view('treasury_accounts.create', [
            'title' => 'Nouveau compte de trésorerie',
            'users' => (new User())->all(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->payload();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('treasury_accounts.create', [
                'title' => 'Nouveau compte de trésorerie',
                'users' => (new User())->all(),
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $id = (new TreasuryAccount())->create($data);
        AuditLog::record('treasury_account_created', 'treasury_account', $id);
        Session::flash('success', 'Compte de trésorerie créé.');
        $this->redirect('treasury_accounts');
    }

    public function update(): void
    {
        $this->requireCsrf();

        $id = (int) $this->request('id', 0);
        $data = $this->payload(false);
        $errors = $this->validate($data);

        if ($id <= 0) {
            $errors['account'] = 'Compte de trésorerie introuvable.';
        }

        if ($errors !== []) {
            Session::flash('error', implode(' ', array_values($errors)));
            $this->redirect('treasury_accounts');
        }

        try {
            (new TreasuryAccount())->update($id, $data);
            AuditLog::record('treasury_account_updated', 'treasury_account', $id, [
                'name' => $data['name'],
                'type' => $data['type'],
                'currency' => $data['currency'],
                'status' => $data['status'],
            ]);
            Session::flash('success', 'Compte de trésorerie modifié avec succès.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirect('treasury_accounts');
    }

    private function payload(bool $withOpeningBalance = true): array
    {
        $data = [
            'name' => trim((string) $this->request('name', '')),
            'type' => (string) $this->request('type', ''),
            'currency' => trim((string) $this->request('currency', 'USD')) ?: 'USD',
            'responsible_user_id' => (int) $this->request('responsible_user_id', 0),
            'status' => (string) $this->request('status', 'active'),
            'notes' => trim((string) $this->request('notes', '')),
        ];

        if ($withOpeningBalance) {
            $data['opening_balance'] = max(0, (float) $this->request('opening_balance', 0));
        }

        return $data;
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (strlen($data['name']) < 3) {
            $errors['name'] = 'Le nom du compte est obligatoire.';
        }
        if (!in_array($data['type'], ['Caisse', 'Banque', 'Mobile Money', 'Autre'], true)) {
            $errors['type'] = 'Type de compte invalide.';
        }
        if (!in_array($data['status'], ['active', 'inactive'], true)) {
            $errors['status'] = 'Statut invalide.';
        }
        if (!in_array($data['currency'], ['USD', 'CDF'], true)) {
            $errors['currency'] = 'Monnaie invalide.';
        }
        return $errors;
    }

    private function accountMetrics(array $accounts): array
    {
        $metrics = [];
        foreach (['Caisse', 'Banque', 'Mobile Money'] as $type) {
            $metrics[$type] = [
                'count' => 0,
                'active' => 0,
                'balances' => ['USD' => 0.0, 'CDF' => 0.0],
            ];
        }

        $metrics['all'] = [
            'count' => count($accounts),
            'active' => 0,
            'balances' => ['USD' => 0.0, 'CDF' => 0.0],
        ];

        foreach ($accounts as $account) {
            $type = $account['type'];
            $currency = $account['currency'];
            $isActive = $account['status'] === 'active';

            if (isset($metrics[$type])) {
                $metrics[$type]['count']++;
                $metrics[$type]['active'] += $isActive ? 1 : 0;
                if ($isActive && isset($metrics[$type]['balances'][$currency])) {
                    $metrics[$type]['balances'][$currency] += (float) $account['current_balance'];
                }
            }

            $metrics['all']['active'] += $isActive ? 1 : 0;
            if ($isActive && isset($metrics['all']['balances'][$currency])) {
                $metrics['all']['balances'][$currency] += (float) $account['current_balance'];
            }
        }

        return $metrics;
    }

    private function detailDataset(string $type): array
    {
        $accounts = (new TreasuryAccount())->all();
        $labels = [
            'Caisse' => ['Caisses', 'Détail des comptes de caisse, de leurs responsables et de leurs soldes.'],
            'Banque' => ['Comptes bancaires', 'Détail des comptes bancaires et de leur disponibilité actuelle.'],
            'Mobile Money' => ['Comptes Mobile Money', 'Détail des portefeuilles Mobile Money enregistrés.'],
            'all' => ['Tous les comptes de trésorerie', 'Vue consolidée des caisses, banques et portefeuilles.'],
        ];

        if (!isset($labels[$type])) {
            throw new InvalidArgumentException('Indicateur de trésorerie inconnu.');
        }

        if ($type !== 'all') {
            $accounts = array_values(array_filter($accounts, static fn (array $account): bool => $account['type'] === $type));
        }

        return [
            'title' => $labels[$type][0],
            'description' => $labels[$type][1],
            'columns' => ['Compte', 'Type', 'Responsable', 'Monnaie', 'Solde initial', 'Solde actuel', 'Statut', 'Notes'],
            'rows' => array_map(static fn (array $account): array => [
                $account['name'],
                $account['type'],
                $account['responsible_name'] ?: '-',
                $account['currency'],
                number_format((float) $account['opening_balance'], 2, ',', ' '),
                number_format((float) $account['current_balance'], 2, ',', ' '),
                $account['status'] === 'active' ? 'Actif' : 'Inactif',
                $account['notes'] ?: '-',
            ], $accounts),
        ];
    }

    private function accountDetailPayload(int $id): array
    {
        $model = new TreasuryAccount();
        $account = $model->find($id);
        if ($account === null) {
            throw new InvalidArgumentException('Compte de trésorerie introuvable.');
        }

        $summary = $model->movementSummary($id);
        $currency = $account['currency'];
        $variation = (float) $account['current_balance'] - (float) $account['opening_balance'];

        return [
            'account' => [
                'id' => (int) $account['id'],
                'name' => $account['name'],
                'type' => $account['type'],
                'currency' => $currency,
                'status' => $account['status'],
                'status_label' => $account['status'] === 'active' ? 'Actif' : 'Inactif',
                'responsible' => $account['responsible_name'] ?: 'Non affecté',
                'opening_balance' => (float) $account['opening_balance'],
                'current_balance' => (float) $account['current_balance'],
                'variation' => $variation,
                'notes' => $account['notes'] ?: 'Aucune note renseignée.',
                'created_at' => $account['created_at'],
                'updated_at' => $account['updated_at'],
            ],
            'summary' => [
                'movement_count' => $summary['movement_count'],
                'total_inflow' => $summary['total_inflow'],
                'total_outflow' => $summary['total_outflow'],
                'last_movement_at' => $summary['last_movement_at'],
            ],
            'movements' => array_map(static fn (array $movement): array => [
                'reference' => $movement['reference'],
                'type' => $movement['movement_type'],
                'type_label' => $movement['movement_type'] === 'inflow' ? 'Entrée' : 'Sortie',
                'description' => $movement['description'],
                'amount' => (float) $movement['amount'],
                'balance_before' => (float) $movement['balance_before'],
                'balance_after' => (float) $movement['balance_after'],
                'created_by' => $movement['created_by_name'],
                'request_reference' => $movement['request_reference'] ?: '-',
                'created_at' => $movement['created_at'],
            ], $model->movements($id)),
        ];
    }

    private function exportAccountExcel(array $payload, string $filename): void
    {
        $account = $payload['account'];
        $summary = $payload['summary'];
        $currency = $account['currency'];

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"><style>
            body{font-family:Arial,sans-serif;color:#172033}
            table{border-collapse:collapse;width:100%}
            td,th{padding:10px;border:1px solid #dce3ec}
            .brand{background:#07111f;color:#fff;font-size:22px;font-weight:bold}
            .subtitle{background:#123a37;color:#d7f7eb}
            .label{background:#f3f6fa;color:#5c6678;font-weight:bold}
            .value{font-weight:bold}
            .metric{background:#eaf8f2;color:#087452;font-size:16px;font-weight:bold}
            .head{background:#0f7f5a;color:#fff;font-weight:bold}
            .outflow{color:#b45309}.inflow{color:#087452}
        </style></head><body><table>';
        echo '<tr><td class="brand" colspan="8">WAKE SERVICES — FICHE DE COMPTE</td></tr>';
        echo '<tr><td class="subtitle" colspan="8">' . e($account['name']) . ' · Généré le ' . date('d/m/Y H:i') . '</td></tr>';
        echo '<tr><td class="label">Type</td><td class="value">' . e($account['type']) . '</td><td class="label">Monnaie</td><td class="value">' . e($currency) . '</td><td class="label">Statut</td><td class="value">' . e($account['status_label']) . '</td><td class="label">Responsable</td><td class="value">' . e($account['responsible']) . '</td></tr>';
        echo '<tr><td class="metric" colspan="2">Solde actuel<br>' . e(money($account['current_balance'], $currency)) . '</td><td class="metric" colspan="2">Solde initial<br>' . e(money($account['opening_balance'], $currency)) . '</td><td class="metric" colspan="2">Entrées cumulées<br>' . e(money($summary['total_inflow'], $currency)) . '</td><td class="metric" colspan="2">Sorties cumulées<br>' . e(money($summary['total_outflow'], $currency)) . '</td></tr>';
        echo '<tr><td class="label">Notes</td><td colspan="7">' . e($account['notes']) . '</td></tr>';
        echo '<tr><td colspan="8"></td></tr><tr><th class="head">Référence</th><th class="head">Date</th><th class="head">Type</th><th class="head">Description</th><th class="head">Montant</th><th class="head">Solde avant</th><th class="head">Solde après</th><th class="head">Créé par</th></tr>';
        foreach ($payload['movements'] as $movement) {
            $class = $movement['type'] === 'inflow' ? 'inflow' : 'outflow';
            echo '<tr><td>' . e($movement['reference']) . '</td><td>' . e($movement['created_at']) . '</td><td class="' . $class . '">' . e($movement['type_label']) . '</td><td>' . e($movement['description']) . '</td><td>' . e(money($movement['amount'], $currency)) . '</td><td>' . e(money($movement['balance_before'], $currency)) . '</td><td>' . e(money($movement['balance_after'], $currency)) . '</td><td>' . e($movement['created_by']) . '</td></tr>';
        }
        if ($payload['movements'] === []) {
            echo '<tr><td colspan="8" style="text-align:center;color:#6b7280">Aucun mouvement enregistré pour ce compte.</td></tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    private function exportAccountPdf(array $payload, string $filename): void
    {
        $account = $payload['account'];
        $summary = $payload['summary'];
        $currency = $account['currency'];
        $lines = [
            'WAKE SERVICES  |  FICHE DETAILLEE DU COMPTE',
            strtoupper($account['name']),
            'Rapport genere le ' . date('d/m/Y H:i'),
            '',
            'IDENTITE DU COMPTE',
            'Type : ' . $account['type'] . '   |   Monnaie : ' . $currency . '   |   Statut : ' . $account['status_label'],
            'Responsable : ' . $account['responsible'],
            'Cree le : ' . $account['created_at'] . '   |   Derniere mise a jour : ' . ($account['updated_at'] ?: '-'),
            '',
            'SYNTHESE FINANCIERE',
            'Solde actuel : ' . money($account['current_balance'], $currency),
            'Solde initial : ' . money($account['opening_balance'], $currency),
            'Variation : ' . money($account['variation'], $currency),
            'Entrees cumulees : ' . money($summary['total_inflow'], $currency),
            'Sorties cumulees : ' . money($summary['total_outflow'], $currency),
            'Nombre de mouvements : ' . $summary['movement_count'],
            '',
            'NOTES',
            $account['notes'],
            '',
            'HISTORIQUE DES MOUVEMENTS',
            'Reference | Date | Type | Montant | Solde apres | Description',
            str_repeat('-', 100),
        ];
        foreach ($payload['movements'] as $movement) {
            $line = implode(' | ', [
                $movement['reference'],
                date('d/m/Y H:i', strtotime($movement['created_at'])),
                $movement['type_label'],
                money($movement['amount'], $currency),
                money($movement['balance_after'], $currency),
                $movement['description'],
            ]);
            foreach (explode("\n", wordwrap($line, 100, "\n", true)) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }
        }
        if ($payload['movements'] === []) {
            $lines[] = 'Aucun mouvement enregistre pour ce compte.';
        }

        $pdf = $this->buildBrandedAccountPdf($lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function buildBrandedAccountPdf(array $lines): string
    {
        $chunks = array_chunk($lines, 43);
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $pageReferences = [];
        $objectId = 5;

        foreach ($chunks as $pageIndex => $pageLines) {
            $pageId = $objectId++;
            $contentId = $objectId++;
            $pageReferences[] = $pageId . ' 0 R';
            $stream = "0.027 0.067 0.122 rg\n0 760 595 82 re f\n0.059 0.624 0.431 rg\n0 750 595 10 re f\n";
            $stream .= "BT\n/F2 17 Tf\n1 1 1 rg\n34 804 Td\n(WAKE SERVICES) Tj\n/F1 8 Tf\n0 -19 Td\n(FINANCE & TRESORERIE  |  DOCUMENT CONFIDENTIEL) Tj\nET\n";
            $stream .= "BT\n/F1 8 Tf\n0.12 0.16 0.23 rg\n34 720 Td\n";
            foreach ($pageLines as $index => $line) {
                if ($index > 0) {
                    $stream .= "0 -15 Td\n";
                }
                $isHeading = in_array($line, ['IDENTITE DU COMPTE', 'SYNTHESE FINANCIERE', 'NOTES', 'HISTORIQUE DES MOUVEMENTS'], true);
                $stream .= $isHeading ? "/F2 10 Tf\n0.04 0.45 0.32 rg\n" : "/F1 8 Tf\n0.12 0.16 0.23 rg\n";
                $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line) ?: $line;
                $encoded = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
                $stream .= '(' . $encoded . ") Tj\n";
            }
            $stream .= "ET\nBT\n/F1 7 Tf\n0.45 0.49 0.56 rg\n34 24 Td\n(Page " . ($pageIndex + 1) . " / " . count($chunks) . "  -  WAKE Business Suite) Tj\nET";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
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
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $size . "\n0000000000 65535 f \n";
        for ($id = 1; $id < $size; $id++) {
            $pdf .= isset($offsets[$id]) ? sprintf("%010d 00000 n \n", $offsets[$id]) : "0000000000 00000 f \n";
        }
        $pdf .= 'trailer << /Size ' . $size . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function exportExcel(array $dataset, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body><table border="1">';
        echo '<tr><th colspan="' . count($dataset['columns']) . '">' . e($dataset['title']) . '</th></tr>';
        echo '<tr><td colspan="' . count($dataset['columns']) . '">' . e($dataset['description']) . '</td></tr><tr>';
        foreach ($dataset['columns'] as $column) {
            echo '<th style="background:#0d1b2f;color:#fff">' . e((string) $column) . '</th>';
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
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $size . "\n0000000000 65535 f \n";
        for ($id = 1; $id < $size; $id++) {
            $pdf .= isset($offsets[$id]) ? sprintf("%010d 00000 n \n", $offsets[$id]) : "0000000000 00000 f \n";
        }
        $pdf .= 'trailer << /Size ' . $size . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }
}
