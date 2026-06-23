<?php

declare(strict_types=1);

class Report extends Model
{
    public const TYPES = [
        'treasury_daily' => 'Rapport journalier de trésorerie',
        'fund_requests' => 'Rapport des demandes financières',
        'expenses_by_service' => 'Rapport des dépenses par service',
        'expenses_by_project' => 'Rapport des dépenses par projet',
        'site_progress' => 'Rapport d’avancement chantier',
        'consumption_variance' => 'Rapport consommation prévue vs réelle',
        'project_margin' => 'Rapport marge par projet',
        'placement_staff' => 'Rapport personnel placé',
        'placement_margin' => 'Rapport marge placement personnel',
        'orders_deliveries' => 'Rapport commandes et livraisons',
        'unpaid_invoices' => 'Rapport factures impayées',
        'executive_global' => 'Rapport global Direction',
    ];

    public function filters(): array
    {
        return [
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
            'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'service' => trim((string) ($_GET['service'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];
    }

    public function services(): array
    {
        return $this->db->query(
            'SELECT DISTINCT department AS name FROM fund_requests WHERE department <> ""
             UNION
             SELECT DISTINCT category AS name FROM construction_project_expenses WHERE category <> ""
             ORDER BY name ASC'
        )->fetchAll();
    }

    public function statuses(): array
    {
        return [
            'Draft', 'Pending', 'Approved', 'Rejected', 'Paid', 'Cancelled',
            'Sent', 'Partially Paid', 'Overdue',
            'Open', 'Delivered', 'Invoiced', 'Closed',
            'Planning', 'In Progress', 'On Hold', 'Completed',
            'Active', 'Suspended', 'Expired',
        ];
    }

    public function build(string $type, array $filters): array
    {
        if (!isset(self::TYPES[$type])) {
            $type = 'executive_global';
        }

        $method = 'report' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));
        return $this->{$method}($filters);
    }

    private function reportTreasuryDaily(array $filters): array
    {
        $rows = $this->fetchAll(
            'SELECT DATE(tm.created_at) AS report_date, ta.name AS account, ta.type,
                    SUM(CASE WHEN tm.movement_type = "inflow" THEN tm.amount ELSE 0 END) AS inflows,
                    SUM(CASE WHEN tm.movement_type = "outflow" THEN tm.amount ELSE 0 END) AS outflows,
                    MAX(tm.balance_after) AS balance_after,
                    COUNT(*) AS movements
             FROM treasury_movements tm
             INNER JOIN treasury_accounts ta ON ta.id = tm.treasury_account_id
             WHERE DATE(tm.created_at) BETWEEN :start_date AND :end_date
             GROUP BY DATE(tm.created_at), ta.id
             ORDER BY report_date DESC, ta.name ASC',
            $this->dateParams($filters)
        );

        return $this->dataset(self::TYPES['treasury_daily'], 'Flux journaliers par caisse, banque et wallet.', ['Date', 'Compte', 'Type', 'Entrées', 'Sorties', 'Solde après', 'Mouvements'], $rows, ['inflows', 'outflows'], 'account', 'outflows');
    }

    private function reportFundRequests(array $filters): array
    {
        [$where, $params] = $this->baseWhere('fr.created_at', $filters);
        if ($filters['service'] !== '') {
            $where[] = 'fr.department = :service';
            $params['service'] = $filters['service'];
        }
        if ($filters['status'] !== '') {
            $where[] = 'fr.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT fr.reference, fr.title, fr.department, fr.status, fr.total_amount, fr.currency, fr.needed_at, fr.created_at
             FROM fund_requests fr
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY fr.created_at DESC',
            $params
        );

        return $this->dataset(self::TYPES['fund_requests'], 'Demandes financières par service, statut et période.', ['Référence', 'Titre', 'Service', 'Statut', 'Montant', 'Devise', 'Besoin le', 'Créée le'], $rows, ['total_amount'], 'department', 'total_amount');
    }

    private function reportExpensesByService(array $filters): array
    {
        [$where, $params] = $this->baseWhere('paid_at', $filters);
        $where[] = 'status = "Paid"';
        if ($filters['service'] !== '') {
            $where[] = 'department = :service';
            $params['service'] = $filters['service'];
        }
        $rows = $this->fetchAll(
            'SELECT department AS service, COUNT(*) AS requests_count, SUM(total_amount) AS total_amount
             FROM fund_requests
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY department
             ORDER BY total_amount DESC',
            $params
        );

        return $this->dataset(self::TYPES['expenses_by_service'], 'Dépenses payées et ventilées par service demandeur.', ['Service', 'Demandes', 'Montant total'], $rows, ['total_amount'], 'service', 'total_amount');
    }

    private function reportExpensesByProject(array $filters): array
    {
        [$where, $params] = $this->baseWhere('e.expense_date', $filters);
        if ($filters['service'] !== '') {
            $where[] = 'e.category = :service';
            $params['service'] = $filters['service'];
        }
        $this->appendConstructionClientFilter($where, $params, $filters);
        $rows = $this->fetchAll(
            'SELECT p.reference, p.name, p.client_name, e.category, SUM(e.amount) AS total_amount, COUNT(*) AS expenses_count
             FROM construction_project_expenses e
             INNER JOIN construction_projects p ON p.id = e.construction_project_id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY p.id, e.category
             ORDER BY total_amount DESC',
            $params
        );

        return $this->dataset(self::TYPES['expenses_by_project'], 'Dépenses chantier par projet et catégorie.', ['Projet', 'Nom', 'Client', 'Catégorie', 'Montant', 'Lignes'], $rows, ['total_amount'], 'reference', 'total_amount');
    }

    private function reportSiteProgress(array $filters): array
    {
        [$where, $params] = $this->baseWhere('p.start_date', $filters, 'p.end_date');
        $this->appendConstructionClientFilter($where, $params, $filters);
        if ($filters['status'] !== '') {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT p.reference, p.name, p.client_name, p.status,
                    COALESCE(AVG(t.progress_percent), 0) AS progress_percent,
                    COUNT(t.id) AS tasks_count,
                    p.start_date, p.end_date
             FROM construction_projects p
             LEFT JOIN construction_project_tasks t ON t.construction_project_id = p.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY p.id
             ORDER BY progress_percent ASC',
            $params
        );

        return $this->dataset(self::TYPES['site_progress'], 'Avancement physique moyen par chantier.', ['Projet', 'Nom', 'Client', 'Statut', 'Avancement %', 'Travaux', 'Début', 'Fin'], $rows, ['progress_percent'], 'reference', 'progress_percent');
    }

    private function reportConsumptionVariance(array $filters): array
    {
        [$where, $params] = $this->baseWhere('dr.report_date', $filters);
        $this->appendConstructionClientFilter($where, $params, $filters);
        $rows = $this->fetchAll(
            'SELECT p.reference, p.name AS project_name, m.name AS material, m.unit,
                    COALESCE(pm.planned_quantity, 0) AS planned_quantity,
                    COALESCE(SUM(dc.quantity_used), 0) AS actual_quantity,
                    COALESCE(pm.planned_cost, 0) AS planned_cost,
                    COALESCE(SUM(dc.total_cost), 0) AS actual_cost,
                    COALESCE(SUM(dc.total_cost), 0) - COALESCE(pm.planned_cost, 0) AS variance_cost
             FROM construction_daily_consumptions dc
             INNER JOIN construction_daily_reports dr ON dr.id = dc.construction_daily_report_id
             INNER JOIN construction_projects p ON p.id = dr.construction_project_id
             INNER JOIN construction_materials m ON m.id = dc.construction_material_id
             LEFT JOIN construction_project_materials pm ON pm.construction_project_id = p.id AND pm.construction_material_id = m.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY p.id, m.id
             ORDER BY variance_cost DESC',
            $params
        );

        return $this->dataset(self::TYPES['consumption_variance'], 'Écart de consommation entre le prévisionnel et le réel.', ['Projet', 'Nom projet', 'Matériau', 'Unité', 'Qté prévue', 'Qté réelle', 'Coût prévu', 'Coût réel', 'Écart coût'], $rows, ['planned_cost', 'actual_cost', 'variance_cost'], 'material', 'actual_cost');
    }

    private function reportProjectMargin(array $filters): array
    {
        [$where, $params] = $this->baseWhere('p.start_date', $filters, 'p.end_date');
        $this->appendConstructionClientFilter($where, $params, $filters);
        if ($filters['status'] !== '') {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT p.reference, p.name, p.client_name, p.status, p.contract_amount, p.forecast_cost, p.forecast_margin,
                    (COALESCE(e.expenses_total, 0) + COALESCE(c.consumption_total, 0)) AS actual_cost,
                    p.contract_amount - (COALESCE(e.expenses_total, 0) + COALESCE(c.consumption_total, 0)) AS actual_margin
             FROM construction_projects p
             LEFT JOIN (SELECT construction_project_id, SUM(amount) AS expenses_total FROM construction_project_expenses GROUP BY construction_project_id) e ON e.construction_project_id = p.id
             LEFT JOIN (
                SELECT dr.construction_project_id, SUM(dc.total_cost) AS consumption_total
                FROM construction_daily_consumptions dc
                INNER JOIN construction_daily_reports dr ON dr.id = dc.construction_daily_report_id
                GROUP BY dr.construction_project_id
             ) c ON c.construction_project_id = p.id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY actual_margin DESC',
            $params
        );

        return $this->dataset(self::TYPES['project_margin'], 'Marge prévisionnelle et marge réelle estimée par projet.', ['Projet', 'Nom', 'Client', 'Statut', 'Contrat', 'Coût prévu', 'Marge prévue', 'Coût réel', 'Marge réelle'], $rows, ['contract_amount', 'forecast_margin', 'actual_margin'], 'reference', 'actual_margin');
    }

    private function reportPlacementStaff(array $filters): array
    {
        [$where, $params] = $this->baseWhere('pce.start_date', $filters, 'COALESCE(pce.end_date, CURRENT_DATE())');
        $this->appendPlacementClientFilter($where, $params, $filters);
        if ($filters['status'] !== '') {
            $where[] = 'pc.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT e.employee_code, CONCAT(e.first_name, " ", e.last_name) AS employee_name, pc.client_name, pc.reference,
                    pce.position_title, pce.agent_cost, pce.client_rate, pce.margin_amount, pce.start_date, pce.end_date, pce.status
             FROM placement_contract_employees pce
             INNER JOIN employees e ON e.id = pce.employee_id
             INNER JOIN placement_contracts pc ON pc.id = pce.placement_contract_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY pc.client_name ASC, e.last_name ASC',
            $params
        );

        return $this->dataset(self::TYPES['placement_staff'], 'Agents placés, client d’affectation, coût, tarif et marge.', ['Code', 'Agent', 'Client', 'Contrat', 'Poste', 'Coût', 'Tarif client', 'Marge', 'Début', 'Fin', 'Statut'], $rows, ['agent_cost', 'client_rate', 'margin_amount'], 'client_name', 'margin_amount');
    }

    private function reportPlacementMargin(array $filters): array
    {
        [$where, $params] = $this->baseWhere('pc.start_date', $filters, 'COALESCE(pc.end_date, CURRENT_DATE())');
        $this->appendPlacementClientFilter($where, $params, $filters);
        if ($filters['status'] !== '') {
            $where[] = 'pc.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT pc.reference, pc.client_name, pc.status, COUNT(pce.id) AS agents,
                    COALESCE(SUM(pce.agent_cost), 0) AS total_cost,
                    COALESCE(SUM(pce.client_rate), 0) AS total_revenue,
                    COALESCE(SUM(pce.margin_amount), 0) AS total_margin
             FROM placement_contracts pc
             LEFT JOIN placement_contract_employees pce ON pce.placement_contract_id = pc.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY pc.id
             ORDER BY total_margin DESC',
            $params
        );

        return $this->dataset(self::TYPES['placement_margin'], 'Rentabilité par contrat de placement.', ['Contrat', 'Client', 'Statut', 'Agents', 'Coût total', 'Revenu total', 'Marge'], $rows, ['total_cost', 'total_revenue', 'total_margin'], 'client_name', 'total_margin');
    }

    private function reportOrdersDeliveries(array $filters): array
    {
        [$where, $params] = $this->baseWhere('so.order_date', $filters);
        $this->appendSalesClientFilter($where, $params, $filters, 'so');
        if ($filters['status'] !== '') {
            $where[] = 'so.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT so.reference, c.name AS client_name, so.order_date, so.status, so.total_amount, so.estimated_margin,
                    COUNT(DISTINCT d.id) AS deliveries_count,
                    COALESCE(SUM(di.quantity), 0) AS delivered_quantity
             FROM sales_orders so
             INNER JOIN clients c ON c.id = so.client_id
             LEFT JOIN deliveries d ON d.sales_order_id = so.id
             LEFT JOIN delivery_items di ON di.delivery_id = d.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY so.id
             ORDER BY so.order_date DESC',
            $params
        );

        return $this->dataset(self::TYPES['orders_deliveries'], 'Commandes clients, livraisons partielles ou totales et marge estimée.', ['Commande', 'Client', 'Date', 'Statut', 'Montant', 'Marge', 'Livraisons', 'Qté livrée'], $rows, ['total_amount', 'estimated_margin'], 'client_name', 'total_amount');
    }

    private function reportUnpaidInvoices(array $filters): array
    {
        [$where, $params] = $this->baseWhere('i.invoice_date', $filters);
        $where[] = 'i.status NOT IN ("Paid", "Cancelled")';
        $where[] = 'i.total_amount > i.paid_amount';
        $this->appendSalesClientFilter($where, $params, $filters, 'i');
        if ($filters['status'] !== '') {
            $where[] = 'i.status = :status';
            $params['status'] = $filters['status'];
        }
        $rows = $this->fetchAll(
            'SELECT i.reference, COALESCE(i.client_name_snapshot, c.name) AS client_name, i.source_type, i.invoice_date, i.due_date, i.status,
                    i.total_amount, i.paid_amount, (i.total_amount - i.paid_amount) AS remaining_amount
             FROM invoices i
             INNER JOIN clients c ON c.id = i.client_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY i.due_date ASC',
            $params
        );

        return $this->dataset(self::TYPES['unpaid_invoices'], 'Factures ouvertes, retards et reste à payer.', ['Facture', 'Client', 'Source', 'Date', 'Échéance', 'Statut', 'Total', 'Payé', 'Reste'], $rows, ['total_amount', 'paid_amount', 'remaining_amount'], 'client_name', 'remaining_amount');
    }

    private function reportExecutiveGlobal(array $filters): array
    {
        $rows = [
            ['indicator' => 'Solde caisses', 'scope' => 'Trésorerie', 'value' => $this->scalar('SELECT COALESCE(SUM(current_balance), 0) FROM treasury_accounts WHERE type = "Caisse"')],
            ['indicator' => 'Solde banques', 'scope' => 'Trésorerie', 'value' => $this->scalar('SELECT COALESCE(SUM(current_balance), 0) FROM treasury_accounts WHERE type = "Banque"')],
            ['indicator' => 'Demandes en attente', 'scope' => 'Finance', 'value' => $this->scalar('SELECT COALESCE(SUM(total_amount), 0) FROM fund_requests WHERE status = "Pending"')],
            ['indicator' => 'Dépenses payées période', 'scope' => 'Finance', 'value' => $this->scalar('SELECT COALESCE(SUM(total_amount), 0) FROM fund_requests WHERE status = "Paid" AND DATE(paid_at) BETWEEN :start_date AND :end_date', $this->dateParams($filters))],
            ['indicator' => 'Factures impayées', 'scope' => 'Facturation', 'value' => $this->scalar('SELECT COALESCE(SUM(total_amount - paid_amount), 0) FROM invoices WHERE status NOT IN ("Paid", "Cancelled")')],
            ['indicator' => 'Commandes période', 'scope' => 'Ventes', 'value' => $this->scalar('SELECT COALESCE(SUM(total_amount), 0) FROM sales_orders WHERE order_date BETWEEN :start_date AND :end_date', $this->dateParams($filters))],
            ['indicator' => 'Marge placement active', 'scope' => 'Placement', 'value' => $this->scalar('SELECT COALESCE(SUM(margin_amount), 0) FROM placement_contract_employees WHERE status = "active"')],
            ['indicator' => 'Marge projets estimée', 'scope' => 'Construction', 'value' => $this->scalar('SELECT COALESCE(SUM(forecast_margin), 0) FROM construction_projects WHERE status IN ("Planning", "In Progress", "On Hold")')],
        ];

        return $this->dataset(self::TYPES['executive_global'], 'Vue globale pour la Direction : trésorerie, ventes, facturation, projets et placement.', ['Indicateur', 'Périmètre', 'Valeur'], $rows, ['value'], 'scope', 'value');
    }

    private function dataset(string $title, string $description, array $columns, array $rows, array $amountKeys, string $labelKey, string $valueKey): array
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row[$valueKey] ?? 0);
        }

        return [
            'title' => $title,
            'description' => $description,
            'columns' => $columns,
            'rows' => $rows,
            'amount_keys' => $amountKeys,
            'summary' => [
                'rows' => count($rows),
                'total' => $total,
                'average' => count($rows) > 0 ? $total / count($rows) : 0,
            ],
            'chart' => $this->chartPayload($rows, $labelKey, $valueKey),
        ];
    }

    private function chartPayload(array $rows, string $labelKey, string $valueKey): array
    {
        $rows = array_slice($rows, 0, 8);
        return [
            'labels' => array_map(static fn($row) => (string) ($row[$labelKey] ?? '-'), $rows),
            'values' => array_map(static fn($row) => (float) ($row[$valueKey] ?? 0), $rows),
        ];
    }

    private function baseWhere(string $dateColumn, array $filters, ?string $endColumn = null): array
    {
        if ($endColumn !== null) {
            return [
                ['(' . $dateColumn . ' <= :end_date AND ' . $endColumn . ' >= :start_date)'],
                $this->dateParams($filters),
            ];
        }

        return [
            ['DATE(' . $dateColumn . ') BETWEEN :start_date AND :end_date'],
            $this->dateParams($filters),
        ];
    }

    private function dateParams(array $filters): array
    {
        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ];
    }

    private function appendSalesClientFilter(array &$where, array &$params, array $filters, string $alias): void
    {
        if ($filters['client_id'] > 0) {
            $where[] = $alias . '.client_id = :client_id';
            $params['client_id'] = $filters['client_id'];
        }
    }

    private function appendConstructionClientFilter(array &$where, array &$params, array $filters): void
    {
        $clientName = $this->clientName((int) $filters['client_id']);
        if ($clientName !== null) {
            $where[] = 'p.client_name = :client_name';
            $params['client_name'] = $clientName;
        }
    }

    private function appendPlacementClientFilter(array &$where, array &$params, array $filters): void
    {
        $clientName = $this->clientName((int) $filters['client_id']);
        if ($clientName !== null) {
            $where[] = 'pc.client_name = :client_name';
            $params['client_name'] = $clientName;
        }
    }

    private function clientName(int $clientId): ?string
    {
        if ($clientId <= 0) {
            return null;
        }
        $statement = $this->db->prepare('SELECT name FROM clients WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $clientId]);
        $name = $statement->fetchColumn();
        return $name !== false ? (string) $name : null;
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    private function scalar(string $sql, array $params = [])
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn() ?: 0;
    }
}
