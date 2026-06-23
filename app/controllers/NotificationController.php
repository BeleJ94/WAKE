<?php

declare(strict_types=1);

class NotificationController extends Controller
{
    public function index(): void
    {
        $model = new Notification();
        $model->scanSystemAlerts();

        $this->view('notifications.index', [
            'title' => 'Notifications',
            'notifications' => $model->recent((int) Auth::id(), 80),
            'unreadCount' => $model->unreadCount((int) Auth::id()),
        ]);
    }

    public function read(): void
    {
        $this->requireCsrf();
        (new Notification())->markAsRead((int) $this->request('id', 0), (int) Auth::id());

        if (($this->request('ajax', '') === '1') || $this->isAjax()) {
            $this->json(['ok' => true, 'unread' => (new Notification())->unreadCount((int) Auth::id())]);
        }

        $this->redirect('notifications');
    }

    public function readAll(): void
    {
        $this->requireCsrf();
        (new Notification())->markAllAsRead((int) Auth::id());

        if (($this->request('ajax', '') === '1') || $this->isAjax()) {
            $this->json(['ok' => true, 'unread' => 0]);
        }

        $this->redirect('notifications');
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        exit;
    }
}
