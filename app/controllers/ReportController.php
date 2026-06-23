<?php

declare(strict_types=1);

class ReportController extends Controller
{
    public function index(): void
    {
        $reportModel = new Report();
        $type = (string) $this->request('type', 'executive_global');
        $filters = $this->normalizedFilters($reportModel->filters());

        $this->view('reports.index', [
            'title' => 'Rapports',
            'types' => Report::TYPES,
            'currentType' => isset(Report::TYPES[$type]) ? $type : 'executive_global',
            'filters' => $filters,
            'clients' => (new Client())->active(),
            'services' => $reportModel->services(),
            'statuses' => $reportModel->statuses(),
            'report' => $reportModel->build($type, $filters),
        ]);
    }

    public function export(): void
    {
        $reportModel = new Report();
        $type = (string) $this->request('type', 'executive_global');
        $filters = $this->normalizedFilters($reportModel->filters());
        $report = $reportModel->build($type, $filters);
        $filename = 'wake-report-' . preg_replace('/[^a-z0-9\-]+/', '-', strtolower($type)) . '-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [$report['title']], ';');
        fputcsv($output, ['Période', $filters['start_date'] . ' au ' . $filters['end_date']], ';');
        fputcsv($output, [], ';');
        fputcsv($output, $report['columns'], ';');

        foreach ($report['rows'] as $row) {
            $values = [];
            foreach ($row as $key => $value) {
                $values[] = $key === 'source_type' ? invoice_source_label((string) $value) : $value;
            }
            fputcsv($output, $values, ';');
        }

        fclose($output);
        exit;
    }

    private function normalizedFilters(array $filters): array
    {
        $filters['start_date'] = $this->validDate($filters['start_date']) ? $filters['start_date'] : date('Y-m-01');
        $filters['end_date'] = $this->validDate($filters['end_date']) ? $filters['end_date'] : date('Y-m-d');

        if ($filters['start_date'] > $filters['end_date']) {
            [$filters['start_date'], $filters['end_date']] = [$filters['end_date'], $filters['start_date']];
        }

        return $filters;
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTime && $parsed->format('Y-m-d') === $date;
    }
}
