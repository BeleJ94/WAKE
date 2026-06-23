<?php

declare(strict_types=1);

class AuditLogController extends Controller
{
    public function index(): void
    {
        $this->view('audit_logs.index', [
            'title' => 'Journal d’audit',
            'logs' => (new AuditLog())->latest([
                'action' => trim((string) $this->request('action', '')),
                'entity_type' => trim((string) $this->request('entity_type', '')),
                'start_date' => trim((string) $this->request('start_date', '')),
                'end_date' => trim((string) $this->request('end_date', '')),
            ], 200),
            'entityTypes' => (new AuditLog())->entityTypes(),
            'filters' => [
                'action' => trim((string) $this->request('action', '')),
                'entity_type' => trim((string) $this->request('entity_type', '')),
                'start_date' => trim((string) $this->request('start_date', '')),
                'end_date' => trim((string) $this->request('end_date', '')),
            ],
        ]);
    }
}
