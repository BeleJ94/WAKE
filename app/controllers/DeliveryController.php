<?php

declare(strict_types=1);

class DeliveryController extends Controller
{
    public function index(): void
    {
        $this->view('deliveries.index', ['title' => 'Livraisons', 'deliveries' => (new Delivery())->all(), 'orders' => (new SalesOrder())->all()]);
    }

    public function create(): void
    {
        $order = (new SalesOrder())->find((int) $this->request('order_id', 0));
        if ($order === null) {
            Session::flash('error', 'Commande introuvable.');
            $this->redirect('sales_orders/index');
        }
        $this->view('deliveries.create', ['title' => 'Préparer livraison', 'order' => $order, 'items' => (new SalesOrder())->items((int) $order['id'])]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        try {
            $id = (new Delivery())->create((int) $this->request('sales_order_id', 0), $_POST['quantities'] ?? [], trim((string) $this->request('notes', '')), (int) Auth::id());
            AuditLog::record('delivery_created', 'delivery', $id);
            Notification::push('delivery_pending', 'Livraison en attente', 'Une livraison vient d’être préparée.', url('deliveries/index'), 'warning', 'delivery', $id);
            Session::flash('success', 'Livraison enregistrée.');
            $this->redirect('deliveries/index');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('deliveries/create?order_id=' . (int) $this->request('sales_order_id', 0));
        }
    }
}
