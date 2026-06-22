<?php

declare(strict_types=1);

class ProductController extends Controller
{
    public function index(): void
    {
        $this->view('products.index', ['title' => 'Produits et stock', 'products' => (new Product())->all()]);
    }

    public function create(): void
    {
        $this->view('products.create', ['title' => 'Ajouter un produit', 'categories' => (new Product())->categories(), 'old' => [], 'errors' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = [
            'product_category_id' => (int) $this->request('product_category_id', 0),
            'sku' => trim((string) $this->request('sku', '')),
            'name' => trim((string) $this->request('name', '')),
            'unit' => trim((string) $this->request('unit', 'u')),
            'cost_price' => max(0, (float) $this->request('cost_price', 0)),
            'sale_price' => max(0, (float) $this->request('sale_price', 0)),
            'stock_quantity' => max(0, (float) $this->request('stock_quantity', 0)),
            'reorder_level' => max(0, (float) $this->request('reorder_level', 0)),
            'status' => (string) $this->request('status', 'active'),
        ];
        if ($data['sku'] === '' || $data['name'] === '') {
            $this->view('products.create', ['title' => 'Ajouter un produit', 'categories' => (new Product())->categories(), 'old' => $data, 'errors' => ['required' => 'La référence SKU et le nom du produit sont obligatoires.']]);
            return;
        }
        $id = (new Product())->create($data);
        AuditLog::record('product_created', 'product', $id);
        Session::flash('success', 'Produit créé.');
        $this->redirect('products/index');
    }
}
