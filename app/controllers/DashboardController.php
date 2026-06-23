<?php

declare(strict_types=1);

class DashboardController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function index(): void
    {
        $dashboard = $this->dashboardData();

        $this->view('dashboard.index', [
            'title' => 'Dashboard',
            'summary' => $dashboard['summary'],
            'kpis' => $dashboard['kpis'],
            'revenueExpenseChart' => $dashboard['revenueExpenseChart'],
            'serviceExpenses' => $dashboard['serviceExpenses'],
            'financialRequests' => $dashboard['financialRequests'],
            'criticalProjects' => $dashboard['criticalProjects'],
            'overdueInvoices' => $dashboard['overdueInvoices'],
            'importantAlerts' => $dashboard['importantAlerts'],
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
                'excel' => url('dashboard/export?type=' . rawurlencode($type) . '&format=excel'),
                'pdf' => url('dashboard/export?type=' . rawurlencode($type) . '&format=pdf'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function export(): void
    {
        $type = (string) $this->request('type', '');
        $format = strtolower((string) $this->request('format', 'excel'));
        $dataset = $this->detailDataset($type);
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($type)) ?: 'dashboard';
        AuditLog::record('dashboard_exported', 'dashboard', null, ['type' => $type, 'format' => $format]);

        if ($format === 'pdf') {
            $this->exportPdf($dataset, 'wake-dashboard-' . $slug . '-' . date('Ymd-His') . '.pdf');
            return;
        }

        $this->exportExcel($dataset, 'wake-dashboard-' . $slug . '-' . date('Ymd-His') . '.xls');
    }

    private function dashboardData(): array
    {
        $treasury = (new TreasuryAccount())->totals();
        $projectDashboard = (new ConstructionProject())->dashboard();

        $pendingRequests = $this->one(
            'SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total
             FROM fund_requests
             WHERE status = "Pending" AND currency = "USD"'
        );
        $monthlyExpenses = (float) $this->scalar(
            'SELECT COALESCE(SUM(total_amount), 0)
             FROM fund_requests
             WHERE status = "Paid" AND currency = "USD"
               AND MONTH(paid_at) = MONTH(CURRENT_DATE())
               AND YEAR(paid_at) = YEAR(CURRENT_DATE())'
        );
        $monthlyRevenue = (float) $this->scalar(
            'SELECT COALESCE(SUM(amount), 0)
             FROM payments
             WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
               AND YEAR(payment_date) = YEAR(CURRENT_DATE())'
        );
        $unpaid = $this->one(
            'SELECT COUNT(*) AS count, COALESCE(SUM(total_amount - paid_amount), 0) AS total
             FROM invoices
             WHERE status NOT IN ("Paid", "Cancelled") AND total_amount > paid_amount'
        );
        $ordersOpen = $this->one(
            'SELECT COUNT(*) AS count
             FROM sales_orders
             WHERE status IN ("Open", "Partially Delivered", "Delivered", "Invoiced")'
        );
        $deliveriesPending = $this->one(
            'SELECT COUNT(*) AS count
             FROM deliveries
             WHERE status IN ("Prepared", "Partial")'
        );
        $placementActive = (int) $this->scalar(
            'SELECT COUNT(*) FROM placement_contract_employees WHERE status = "active"'
        );
        $globalMargin = $monthlyRevenue > 0 ? (($monthlyRevenue - $monthlyExpenses) / $monthlyRevenue) * 100 : 0;

        return [
            'summary' => [
                'margin' => $globalMargin,
                'revenues' => $monthlyRevenue,
                'expenses' => $monthlyExpenses,
            ],
            'kpis' => $this->kpis(
                $treasury,
                $pendingRequests,
                $monthlyExpenses,
                $monthlyRevenue,
                $projectDashboard,
                $unpaid,
                $placementActive,
                $ordersOpen,
                $deliveriesPending,
                $globalMargin
            ),
            'revenueExpenseChart' => $this->revenueExpenseChart(),
            'serviceExpenses' => $this->serviceExpenses(),
            'financialRequests' => $this->financialRequests(),
            'criticalProjects' => $this->criticalProjects(),
            'overdueInvoices' => $this->overdueInvoices(),
            'importantAlerts' => $this->importantAlerts($pendingRequests, $unpaid, $deliveriesPending, $projectDashboard),
        ];
    }

    private function kpis(
        array $treasury,
        array $pendingRequests,
        float $monthlyExpenses,
        float $monthlyRevenue,
        array $projectDashboard,
        array $unpaid,
        int $placementActive,
        array $ordersOpen,
        array $deliveriesPending,
        float $globalMargin
    ): array
    {
        return [
            ['key' => 'cash_accounts', 'icon' => 'cash-stack', 'label' => 'Solde total des caisses', 'value' => money($treasury['Caisse'] ?? 0), 'trend' => 'Comptes actifs', 'status' => 'Disponible', 'badge' => 'badge-success'],
            ['key' => 'bank_accounts', 'icon' => 'bank', 'label' => 'Solde total des banques', 'value' => money($treasury['Banque'] ?? 0), 'trend' => 'Comptes actifs', 'status' => 'Confortable', 'badge' => 'badge-success'],
            ['key' => 'pending_requests', 'icon' => 'hourglass-split', 'label' => 'Demandes financières en attente', 'value' => (string) (int) ($pendingRequests['count'] ?? 0), 'trend' => money($pendingRequests['total'] ?? 0), 'status' => 'Action', 'badge' => ((int) ($pendingRequests['count'] ?? 0) > 0 ? 'badge-danger' : 'badge-success')],
            ['key' => 'monthly_expenses', 'icon' => 'graph-down-arrow', 'label' => 'Dépenses du mois', 'value' => money($monthlyExpenses), 'trend' => 'Demandes payées', 'status' => $monthlyExpenses > 0 ? 'Surveiller' : 'Stable', 'badge' => $monthlyExpenses > 0 ? 'badge-warning' : 'badge-success'],
            ['key' => 'monthly_revenue', 'icon' => 'graph-up-arrow', 'label' => 'Revenus du mois', 'value' => money($monthlyRevenue), 'trend' => 'Paiements reçus', 'status' => $monthlyRevenue > 0 ? 'Bon' : 'À suivre', 'badge' => $monthlyRevenue > 0 ? 'badge-success' : 'badge-neutral'],
            ['key' => 'construction_projects', 'icon' => 'buildings', 'label' => 'Projets construction en cours', 'value' => (string) $projectDashboard['active_count'], 'trend' => $projectDashboard['critical_count'] . ' critiques', 'status' => 'Suivi', 'badge' => $projectDashboard['critical_count'] > 0 ? 'badge-warning' : 'badge-success'],
            ['key' => 'project_progress', 'icon' => 'activity', 'label' => 'Avancement moyen des projets', 'value' => number_format((float) $projectDashboard['average_progress'], 0, ',', ' ') . '%', 'trend' => 'Tous projets', 'status' => 'Normal', 'badge' => 'badge-success'],
            ['key' => 'unpaid_invoices', 'icon' => 'receipt', 'label' => 'Factures impayées', 'value' => money($unpaid['total'] ?? 0), 'trend' => ((int) ($unpaid['count'] ?? 0)) . ' factures', 'status' => ((float) ($unpaid['total'] ?? 0) > 0 ? 'Relance' : 'OK'), 'badge' => ((float) ($unpaid['total'] ?? 0) > 0 ? 'badge-danger' : 'badge-success')],
            ['key' => 'placement_staff', 'icon' => 'people', 'label' => 'Personnel placé actif', 'value' => (string) $placementActive, 'trend' => 'Agents affectés', 'status' => 'Actif', 'badge' => 'badge-success'],
            ['key' => 'open_orders', 'icon' => 'cart-check', 'label' => 'Commandes en cours', 'value' => (string) (int) ($ordersOpen['count'] ?? 0), 'trend' => 'Pipeline vente', 'status' => 'Ouvert', 'badge' => 'badge-neutral'],
            ['key' => 'pending_deliveries', 'icon' => 'truck', 'label' => 'Livraisons en attente', 'value' => (string) (int) ($deliveriesPending['count'] ?? 0), 'trend' => 'À traiter', 'status' => ((int) ($deliveriesPending['count'] ?? 0) > 0 ? 'Urgent' : 'OK'), 'badge' => ((int) ($deliveriesPending['count'] ?? 0) > 0 ? 'badge-warning' : 'badge-success')],
            ['key' => 'global_margin', 'icon' => 'percent', 'label' => 'Marge globale estimée', 'value' => number_format($globalMargin, 1, ',', ' ') . '%', 'trend' => 'Mois courant', 'status' => $globalMargin >= 20 ? 'Solide' : 'À piloter', 'badge' => $globalMargin >= 20 ? 'badge-success' : 'badge-warning'],
        ];
    }

    private function detailDataset(string $type): array
    {
        switch ($type) {
            case 'cash_accounts':
            case 'bank_accounts':
                $accountType = $type === 'cash_accounts' ? 'Caisse' : 'Banque';
                $rows = $this->all(
                    'SELECT treasury_accounts.name, treasury_accounts.currency, treasury_accounts.current_balance,
                            treasury_accounts.status, users.name AS responsible
                     FROM treasury_accounts
                     LEFT JOIN users ON users.id = treasury_accounts.responsible_user_id
                     WHERE treasury_accounts.type = :type
                     ORDER BY treasury_accounts.current_balance DESC',
                    ['type' => $accountType]
                );
                return $this->dataset(
                    $accountType === 'Caisse' ? 'Détail des caisses' : 'Détail des comptes bancaires',
                    'Soldes disponibles et responsables des comptes.',
                    ['Compte', 'Devise', 'Solde', 'Statut', 'Responsable'],
                    array_map(static fn ($row) => [
                        $row['name'], $row['currency'], money($row['current_balance'], $row['currency']),
                        $row['status'], $row['responsible'] ?: 'Non affecté',
                    ], $rows)
                );

            case 'pending_requests':
                $rows = $this->all(
                    'SELECT fund_requests.reference, fund_requests.title, fund_requests.department,
                            fund_requests.total_amount, fund_requests.currency, fund_requests.needed_at, users.name AS requester
                     FROM fund_requests
                     INNER JOIN users ON users.id = fund_requests.requested_by
                     WHERE fund_requests.status = "Pending"
                     ORDER BY fund_requests.needed_at ASC, fund_requests.created_at DESC'
                );
                return $this->dataset('Demandes financières en attente', 'Demandes soumises nécessitant une décision.', ['Référence', 'Objet', 'Service', 'Montant', 'Besoin le', 'Demandeur'], array_map(static fn ($row) => [
                    $row['reference'], $row['title'], $row['department'], money($row['total_amount'], $row['currency']),
                    $row['needed_at'] ?: 'Non précisé', $row['requester'],
                ], $rows));

            case 'monthly_expenses':
                $rows = $this->all(
                    'SELECT reference, title, department, total_amount, currency, paid_at
                     FROM fund_requests
                     WHERE status = "Paid" AND currency = "USD"
                       AND MONTH(paid_at) = MONTH(CURRENT_DATE()) AND YEAR(paid_at) = YEAR(CURRENT_DATE())
                     ORDER BY paid_at DESC'
                );
                return $this->dataset('Dépenses du mois', 'Demandes de fonds payées en USD pendant le mois courant.', ['Référence', 'Objet', 'Service', 'Montant', 'Date de paiement'], array_map(static fn ($row) => [
                    $row['reference'], $row['title'], $row['department'], money($row['total_amount'], $row['currency']), $row['paid_at'],
                ], $rows));

            case 'monthly_revenue':
                $rows = $this->all(
                    'SELECT payments.reference, payments.payment_date, payments.amount, payments.method,
                            invoices.reference AS invoice_reference, clients.name AS client_name
                     FROM payments
                     INNER JOIN invoices ON invoices.id = payments.invoice_id
                     INNER JOIN clients ON clients.id = invoices.client_id
                     WHERE MONTH(payments.payment_date) = MONTH(CURRENT_DATE())
                       AND YEAR(payments.payment_date) = YEAR(CURRENT_DATE())
                     ORDER BY payments.payment_date DESC, payments.id DESC'
                );
                return $this->dataset('Revenus du mois', 'Paiements clients encaissés pendant le mois courant.', ['Paiement', 'Date', 'Client', 'Facture', 'Méthode', 'Montant'], array_map(static fn ($row) => [
                    $row['reference'], $row['payment_date'], $row['client_name'], $row['invoice_reference'], $row['method'], money($row['amount']),
                ], $rows));

            case 'construction_projects':
            case 'project_progress':
            case 'critical_projects':
                $projects = (new ConstructionProject())->all();
                if ($type === 'construction_projects') {
                    $projects = array_values(array_filter($projects, static fn ($project) => $project['status'] === 'In Progress'));
                } elseif ($type === 'critical_projects') {
                    $projects = array_values(array_filter($projects, static fn ($project) => (int) $project['metrics']['delay_days'] > 0 || (float) $project['metrics']['cost_variance'] < 0));
                }
                return $this->dataset(
                    $type === 'critical_projects' ? 'Projets critiques' : ($type === 'project_progress' ? 'Avancement des projets' : 'Projets construction en cours'),
                    'Avancement physique, consommation budgétaire et risques projet.',
                    ['Projet', 'Client', 'Responsable', 'Statut', 'Avancement', 'Coût réel', 'Écart budget', 'Retard'],
                    array_map(static fn ($project) => [
                        $project['name'], $project['client_name'], $project['manager_name'] ?: 'Non affecté', $project['status'],
                        number_format((float) $project['metrics']['physical_progress'], 1, ',', ' ') . '%',
                        money($project['metrics']['actual_cost']), money($project['metrics']['cost_variance']),
                        (int) $project['metrics']['delay_days'] . ' jour(s)',
                    ], $projects)
                );

            case 'unpaid_invoices':
            case 'overdue_invoices':
                $where = $type === 'overdue_invoices' ? 'AND invoices.due_date < CURRENT_DATE()' : '';
                $rows = $this->all(
                    'SELECT invoices.reference, COALESCE(invoices.client_name_snapshot, clients.name) AS client_name,
                            invoices.invoice_date, invoices.due_date, invoices.status, invoices.total_amount,
                            invoices.paid_amount, (invoices.total_amount - invoices.paid_amount) AS remaining_amount,
                            GREATEST(DATEDIFF(CURRENT_DATE(), invoices.due_date), 0) AS late_days
                     FROM invoices
                     INNER JOIN clients ON clients.id = invoices.client_id
                     WHERE invoices.status NOT IN ("Paid", "Cancelled")
                       AND invoices.total_amount > invoices.paid_amount ' . $where . '
                     ORDER BY invoices.due_date ASC'
                );
                return $this->dataset(
                    $type === 'overdue_invoices' ? 'Factures en retard' : 'Factures impayées',
                    'Situation détaillée du portefeuille à recouvrer.',
                    ['Facture', 'Client', 'Date', 'Échéance', 'Statut', 'Total', 'Payé', 'Reste', 'Retard'],
                    array_map(static fn ($row) => [
                        $row['reference'], $row['client_name'], $row['invoice_date'], $row['due_date'] ?: 'Non définie', $row['status'],
                        money($row['total_amount']), money($row['paid_amount']), money($row['remaining_amount']), (int) $row['late_days'] . ' jour(s)',
                    ], $rows)
                );

            case 'placement_staff':
                $rows = $this->all(
                    'SELECT employees.employee_code, CONCAT(employees.first_name, " ", employees.last_name) AS employee_name,
                            placement_contracts.client_name, placement_contract_employees.position_title,
                            placement_contract_employees.agent_cost, placement_contract_employees.client_rate,
                            placement_contract_employees.margin_amount
                     FROM placement_contract_employees
                     INNER JOIN employees ON employees.id = placement_contract_employees.employee_id
                     INNER JOIN placement_contracts ON placement_contracts.id = placement_contract_employees.placement_contract_id
                     WHERE placement_contract_employees.status = "active"
                     ORDER BY placement_contracts.client_name, employees.last_name'
                );
                return $this->dataset('Personnel placé actif', 'Agents actuellement affectés aux contrats clients.', ['Matricule', 'Agent', 'Client', 'Poste', 'Coût agent', 'Tarif client', 'Marge'], array_map(static fn ($row) => [
                    $row['employee_code'], $row['employee_name'], $row['client_name'], $row['position_title'],
                    money($row['agent_cost']), money($row['client_rate']), money($row['margin_amount']),
                ], $rows));

            case 'open_orders':
                $rows = $this->all(
                    'SELECT sales_orders.reference, clients.name AS client_name, sales_orders.order_date,
                            sales_orders.status, sales_orders.total_amount, sales_orders.estimated_margin
                     FROM sales_orders
                     INNER JOIN clients ON clients.id = sales_orders.client_id
                     WHERE sales_orders.status IN ("Open", "Partially Delivered", "Delivered", "Invoiced")
                     ORDER BY sales_orders.order_date DESC'
                );
                return $this->dataset('Commandes en cours', 'Pipeline des commandes non clôturées.', ['Commande', 'Client', 'Date', 'Statut', 'Montant', 'Marge estimée'], array_map(static fn ($row) => [
                    $row['reference'], $row['client_name'], $row['order_date'], $row['status'], money($row['total_amount']), money($row['estimated_margin']),
                ], $rows));

            case 'pending_deliveries':
                $rows = $this->all(
                    'SELECT deliveries.reference, deliveries.delivery_date, deliveries.status,
                            sales_orders.reference AS order_reference, clients.name AS client_name
                     FROM deliveries
                     INNER JOIN sales_orders ON sales_orders.id = deliveries.sales_order_id
                     INNER JOIN clients ON clients.id = sales_orders.client_id
                     WHERE deliveries.status IN ("Prepared", "Partial")
                     ORDER BY deliveries.delivery_date ASC'
                );
                return $this->dataset('Livraisons en attente', 'Livraisons préparées ou partiellement exécutées.', ['Livraison', 'Commande', 'Client', 'Date', 'Statut'], array_map(static fn ($row) => [
                    $row['reference'], $row['order_reference'], $row['client_name'], $row['delivery_date'], $row['status'],
                ], $rows));

            case 'performance_chart':
                $chart = $this->revenueExpenseChart();
                $rows = [];
                foreach ($chart['labels'] as $index => $label) {
                    $revenue = (float) $chart['revenues'][$index];
                    $expense = (float) $chart['expenses'][$index];
                    $rows[] = [$label, money($revenue), money($expense), money($revenue - $expense)];
                }
                return $this->dataset('Revenus vs dépenses', 'Évolution financière sur les six derniers mois.', ['Mois', 'Revenus', 'Dépenses', 'Résultat'], $rows);

            case 'service_expenses':
                $rows = $this->all(
                    'SELECT department, COUNT(*) AS operations, COALESCE(SUM(total_amount), 0) AS total
                     FROM fund_requests
                     WHERE status = "Paid" AND currency = "USD"
                       AND MONTH(paid_at) = MONTH(CURRENT_DATE()) AND YEAR(paid_at) = YEAR(CURRENT_DATE())
                     GROUP BY department ORDER BY total DESC'
                );
                return $this->dataset('Dépenses par service', 'Répartition des dépenses USD du mois courant.', ['Service', 'Opérations', 'Montant'], array_map(static fn ($row) => [
                    $row['department'], (string) $row['operations'], money($row['total']),
                ], $rows));

            case 'recent_requests':
                $rows = $this->all(
                    'SELECT fund_requests.reference, fund_requests.title, fund_requests.department,
                            fund_requests.total_amount, fund_requests.currency, fund_requests.status,
                            fund_requests.created_at, users.name AS requester
                     FROM fund_requests
                     INNER JOIN users ON users.id = fund_requests.requested_by
                     ORDER BY fund_requests.created_at DESC LIMIT 25'
                );
                return $this->dataset('Dernières demandes financières', 'Les 25 demandes les plus récentes.', ['Référence', 'Objet', 'Service', 'Montant', 'Statut', 'Demandeur', 'Créée le'], array_map(static fn ($row) => [
                    $row['reference'], $row['title'], $row['department'], money($row['total_amount'], $row['currency']),
                    $row['status'], $row['requester'], $row['created_at'],
                ], $rows));

            case 'global_margin':
                $revenue = (float) $this->scalar('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())');
                $expense = (float) $this->scalar('SELECT COALESCE(SUM(total_amount), 0) FROM fund_requests WHERE status = "Paid" AND currency = "USD" AND MONTH(paid_at) = MONTH(CURRENT_DATE()) AND YEAR(paid_at) = YEAR(CURRENT_DATE())');
                $result = $revenue - $expense;
                $margin = $revenue > 0 ? ($result / $revenue) * 100 : 0;
                return $this->dataset('Marge globale estimée', 'Synthèse du mois courant calculée sur les encaissements et dépenses USD.', ['Indicateur', 'Valeur'], [
                    ['Revenus encaissés', money($revenue)],
                    ['Dépenses payées', money($expense)],
                    ['Résultat estimé', money($result)],
                    ['Marge estimée', number_format($margin, 1, ',', ' ') . '%'],
                ]);
        }

        throw new InvalidArgumentException('Détail de dashboard inconnu.');
    }

    private function dataset(string $title, string $description, array $columns, array $rows): array
    {
        return compact('title', 'description', 'columns', 'rows');
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
        $lines[] = str_repeat('-', 105);
        foreach ($dataset['rows'] as $row) {
            $line = implode(' | ', array_map(static fn ($value) => (string) $value, $row));
            foreach ($this->wrapPdfLine($line, 105) as $wrappedLine) {
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

    private function wrapPdfLine(string $line, int $width): array
    {
        return explode("\n", wordwrap($line, $width, "\n", true));
    }

    private function buildPdf(array $lines): string
    {
        $chunks = array_chunk($lines, 48);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageReferences = [];
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>';
        $objectId = 4;

        foreach ($chunks as $pageLines) {
            $pageId = $objectId++;
            $contentId = $objectId++;
            $pageReferences[] = $pageId . ' 0 R';
            $stream = "BT\n/F1 8 Tf\n36 806 Td\n";
            foreach ($pageLines as $index => $line) {
                if ($index > 0) {
                    $stream .= "0 -15 Td\n";
                }
                $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line) ?: $line;
                $encoded = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
                $stream .= '(' . $encoded . ") Tj\n";
            }
            $stream .= "ET";
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

    private function revenueExpenseChart(): array
    {
        $labels = [];
        $revenues = [];
        $expenses = [];

        for ($index = 5; $index >= 0; $index--) {
            $month = date('Y-m', strtotime('-' . $index . ' months'));
            $labels[] = date('M', strtotime($month . '-01'));
            $revenues[] = (float) $this->scalar(
                'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE_FORMAT(payment_date, "%Y-%m") = :month',
                ['month' => $month]
            );
            $expenses[] = (float) $this->scalar(
                'SELECT COALESCE(SUM(total_amount), 0) FROM fund_requests WHERE status = "Paid" AND currency = "USD" AND DATE_FORMAT(paid_at, "%Y-%m") = :month',
                ['month' => $month]
            );
        }

        return ['labels' => $labels, 'revenues' => $revenues, 'expenses' => $expenses];
    }

    private function serviceExpenses(): array
    {
        $colors = ['#0f9f6e', '#1d4ed8', '#f59e0b', '#64748b', '#dc2626', '#7c3aed'];
        $rows = $this->all(
            'SELECT department AS label, COALESCE(SUM(total_amount), 0) AS value
             FROM fund_requests
             WHERE status = "Paid" AND currency = "USD"
               AND MONTH(paid_at) = MONTH(CURRENT_DATE())
               AND YEAR(paid_at) = YEAR(CURRENT_DATE())
             GROUP BY department
             ORDER BY value DESC
             LIMIT 6'
        );

        if ($rows === []) {
            return [['label' => 'Aucune dépense', 'value' => 1, 'color' => '#64748b']];
        }

        foreach ($rows as $index => &$row) {
            $row['value'] = (float) $row['value'];
            $row['color'] = $colors[$index % count($colors)];
        }

        return $rows;
    }

    private function financialRequests(): array
    {
        $rows = $this->all(
            'SELECT reference, department, total_amount, currency, status
             FROM fund_requests
             ORDER BY created_at DESC
             LIMIT 4'
        );

        return array_map(static fn ($row) => [
            'ref' => $row['reference'],
            'service' => $row['department'],
            'amount' => money($row['total_amount'], $row['currency']),
            'status' => $row['status'],
            'badge' => status_badge_class($row['status']),
        ], $rows);
    }

    private function criticalProjects(): array
    {
        $projects = (new ConstructionProject())->all();
        usort($projects, static fn ($left, $right) => ((int) $right['metrics']['delay_days'] <=> (int) $left['metrics']['delay_days']) ?: ((float) $left['metrics']['cost_variance'] <=> (float) $right['metrics']['cost_variance']));

        return array_map(static function ($project): array {
            $metrics = $project['metrics'];
            $risk = 'Suivi normal';
            if ((int) $metrics['delay_days'] > 0) {
                $risk = 'Retard ' . (int) $metrics['delay_days'] . ' jours';
            } elseif ((float) $metrics['cost_variance'] < 0) {
                $risk = 'Budget dépassé';
            }

            return [
                'name' => $project['name'],
                'progress' => (int) round((float) $metrics['physical_progress']),
                'risk' => $risk,
                'owner' => $project['manager_name'] ?: 'Non affecté',
            ];
        }, array_slice($projects, 0, 3));
    }

    private function overdueInvoices(): array
    {
        $rows = $this->all(
            'SELECT invoices.reference, COALESCE(invoices.client_name_snapshot, clients.name) AS client_name,
                    (invoices.total_amount - invoices.paid_amount) AS remaining_amount,
                    GREATEST(DATEDIFF(CURRENT_DATE(), invoices.due_date), 0) AS late_days
             FROM invoices
             INNER JOIN clients ON clients.id = invoices.client_id
             WHERE invoices.status NOT IN ("Paid", "Cancelled")
               AND invoices.total_amount > invoices.paid_amount
             ORDER BY invoices.due_date ASC
             LIMIT 3'
        );

        return array_map(static fn ($row) => [
            'client' => $row['client_name'],
            'invoice' => $row['reference'],
            'amount' => money($row['remaining_amount']),
            'days' => (int) $row['late_days'] . ' jours',
        ], $rows);
    }

    private function importantAlerts(array $pendingRequests, array $unpaid, array $deliveriesPending, array $projectDashboard): array
    {
        $alerts = [];
        if ((int) ($pendingRequests['count'] ?? 0) > 0) {
            $alerts[] = ['level' => 'Finance', 'badge' => 'badge-warning', 'text' => (int) $pendingRequests['count'] . ' demandes de fonds attendent une décision.'];
        }
        if ((float) ($unpaid['total'] ?? 0) > 0) {
            $alerts[] = ['level' => 'Relance', 'badge' => 'badge-danger', 'text' => money($unpaid['total']) . ' restent à recouvrer sur les factures ouvertes.'];
        }
        if ((int) ($deliveriesPending['count'] ?? 0) > 0) {
            $alerts[] = ['level' => 'Logistique', 'badge' => 'badge-warning', 'text' => (int) $deliveriesPending['count'] . ' livraisons sont en préparation ou partielles.'];
        }
        if ((int) ($projectDashboard['critical_count'] ?? 0) > 0) {
            $alerts[] = ['level' => 'Construction', 'badge' => 'badge-danger', 'text' => (int) $projectDashboard['critical_count'] . ' projets présentent un retard ou un dépassement.'];
        }

        return $alerts ?: [['level' => 'OK', 'badge' => 'badge-success', 'text' => 'Aucune alerte critique détectée sur les données actuelles.']];
    }

    private function scalar(string $sql, array $params = [])
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn() ?: 0;
    }

    private function one(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ?: [];
    }

    private function all(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }
}
