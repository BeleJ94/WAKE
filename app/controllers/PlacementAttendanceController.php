<?php

declare(strict_types=1);

class PlacementAttendanceController extends Controller
{
    public function index(): void
    {
        $month = (string) $this->request('month', date('Y-m'));
        $this->view('placement.attendance', [
            'title' => 'Présence mensuelle',
            'month' => $month,
            'assignments' => (new PlacementAttendance())->assignments(),
            'attendances' => (new PlacementAttendance())->forMonth($month),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        (new PlacementAttendance())->save([
            'assignment_id' => (int) $this->request('assignment_id', 0),
            'month' => (string) $this->request('month', date('Y-m')),
            'days_present' => max(0, (float) $this->request('days_present', 0)),
            'days_absent' => max(0, (float) $this->request('days_absent', 0)),
            'overtime_hours' => max(0, (float) $this->request('overtime_hours', 0)),
            'notes' => trim((string) $this->request('notes', '')),
            'created_by' => (int) Auth::id(),
        ]);
        AuditLog::record('placement_attendance_saved', 'placement_attendance', null);
        Session::flash('success', 'Présence enregistrée.');
        $this->redirect('placement/attendance?month=' . urlencode((string) $this->request('month', date('Y-m'))));
    }
}

