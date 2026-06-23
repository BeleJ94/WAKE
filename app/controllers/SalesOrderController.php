<?php

declare(strict_types=1);

class SalesOrderController extends Controller
{
    public function index(): void
    {
        $this->view('sales_orders.index', ['title' => 'Commandes clients', 'orders' => (new SalesOrder())->all()]);
    }

    public function show(): void
    {
        $order = (new SalesOrder())->find((int) $this->request('id', 0));
        if ($order === null) {
            Session::flash('error', 'Commande introuvable.');
            $this->redirect('sales_orders/index');
        }
        $this->view('sales_orders.show', ['title' => $order['reference'], 'order' => $order, 'items' => (new SalesOrder())->items((int) $order['id'])]);
    }

    public function generateInvoice(): void
    {
        $this->requireCsrf();
        try {
            $id = (new SalesInvoice())->generateFromOrder((int) $this->request('id', 0), (int) Auth::id());
            AuditLog::record('sales_invoice_generated', 'invoice', $id);
            Notification::push('invoice_created', 'Facture commande générée', 'Une facture a été générée depuis une commande.', url('invoices/show?id=' . $id), 'info', 'invoice', $id);
            Session::flash('success', 'Facture générée.');
            $this->redirect('invoices/show?id=' . $id);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('sales_orders/show?id=' . (int) $this->request('id', 0));
        }
    }
}
