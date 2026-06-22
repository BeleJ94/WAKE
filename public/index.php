<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/helpers/functions.php';

spl_autoload_register(static function (string $className): void {
    $directories = [
        APP_PATH . '/core',
        APP_PATH . '/controllers',
        APP_PATH . '/models',
        APP_PATH . '/helpers',
        APP_PATH . '/middleware',
    ];

    foreach ($directories as $directory) {
        $file = $directory . '/' . $className . '.php';

        if (is_readable($file)) {
            require_once $file;
            return;
        }
    }
});

Session::start();
Security::applyHeaders();
Security::enforceSessionTimeout();

set_exception_handler(static function (Throwable $exception): void {
    http_response_code(500);

    if (APP_DEBUG) {
        echo '<pre>';
        echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . PHP_EOL;
        echo htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        echo '</pre>';
        return;
    }

    echo 'Une erreur interne est survenue.';
});

$router = new Router();

$router->get('login', 'AuthController@showLogin');
$router->post('login', 'AuthController@login');
$router->post('logout', 'AuthController@logout', ['auth']);

$router->get('', 'DashboardController@index', ['auth']);
$router->get('home', 'DashboardController@index', ['auth']);
$router->get('dashboard', 'DashboardController@index', ['auth']);
$router->get('dashboard/details', 'DashboardController@details', ['auth']);
$router->get('dashboard/export', 'DashboardController@export', ['auth']);
$router->get('notifications', 'NotificationController@index', ['auth', 'permission:notifications.view']);
$router->get('notifications/index', 'NotificationController@index', ['auth', 'permission:notifications.view']);
$router->post('notifications/read', 'NotificationController@read', ['auth', 'permission:notifications.manage']);
$router->post('notifications/read-all', 'NotificationController@readAll', ['auth', 'permission:notifications.manage']);
$router->post('ajax/validate', 'ValidationAjaxController@validate', ['auth']);

$router->get('users', 'UserController@index', ['auth', 'permission:users.view']);
$router->get('users/index', 'UserController@index', ['auth', 'permission:users.view']);
$router->get('users/create', 'UserController@create', ['auth', 'permission:users.create']);
$router->post('users/store', 'UserController@store', ['auth', 'permission:users.create']);
$router->get('users/edit', 'UserController@edit', ['auth', 'permission:users.edit']);
$router->post('users/update', 'UserController@update', ['auth', 'permission:users.edit']);

$router->get('roles', 'RoleController@index', ['auth', 'permission:roles.view']);
$router->get('roles/index', 'RoleController@index', ['auth', 'permission:roles.view']);
$router->get('roles/permissions', 'RoleController@permissions', ['auth', 'permission:roles.permissions']);
$router->post('roles/permissions', 'RoleController@updatePermissions', ['auth', 'permission:roles.permissions']);
$router->get('audit_logs', 'AuditLogController@index', ['auth', 'permission:audit_logs.view']);
$router->get('audit_logs/index', 'AuditLogController@index', ['auth', 'permission:audit_logs.view']);

$router->get('fund_requests', 'FundRequestController@index', ['auth', 'permission:fund_requests.view']);
$router->get('fund_requests/index', 'FundRequestController@index', ['auth', 'permission:fund_requests.view']);
$router->get('fund_requests/create', 'FundRequestController@create', ['auth', 'permission:fund_requests.create']);
$router->post('fund_requests/store', 'FundRequestController@store', ['auth', 'permission:fund_requests.create']);
$router->get('fund_requests/show', 'FundRequestController@show', ['auth', 'permission:fund_requests.view']);
$router->get('fund_requests/details', 'FundRequestController@details', ['auth', 'permission:fund_requests.view']);
$router->get('fund_requests/export', 'FundRequestController@export', ['auth', 'permission:fund_requests.view']);
$router->post('fund_requests/submit', 'FundRequestController@submit', ['auth', 'permission:fund_requests.create']);
$router->get('fund_requests/approve', 'FundRequestController@approveForm', ['auth', 'permission:fund_requests.approve']);
$router->post('fund_requests/approve', 'FundRequestController@approve', ['auth', 'permission:fund_requests.approve']);
$router->get('fund_requests/payment', 'FundRequestController@paymentForm', ['auth', 'permission:fund_requests.pay']);
$router->post('fund_requests/payment', 'FundRequestController@payment', ['auth', 'permission:fund_requests.pay']);
$router->get('ajax/fund_requests/details', 'FinanceAjaxController@fundRequestDetails', ['auth', 'permission:fund_requests.view']);
$router->get('ajax/treasury_accounts/balance', 'FinanceAjaxController@accountBalance', ['auth', 'permission:cashbanks.view']);
$router->post('ajax/fund_requests/status', 'FinanceAjaxController@status', ['auth', 'permission:fund_requests.view']);

$router->get('treasury_accounts', 'TreasuryAccountController@index', ['auth', 'permission:cashbanks.view']);
$router->get('treasury_accounts/index', 'TreasuryAccountController@index', ['auth', 'permission:cashbanks.view']);
$router->get('treasury_accounts/create', 'TreasuryAccountController@create', ['auth', 'permission:cashbanks.create']);
$router->get('treasury_accounts/details', 'TreasuryAccountController@details', ['auth', 'permission:cashbanks.view']);
$router->get('treasury_accounts/export', 'TreasuryAccountController@export', ['auth', 'permission:cashbanks.view']);
$router->get('treasury_accounts/account-details', 'TreasuryAccountController@accountDetails', ['auth', 'permission:cashbanks.view']);
$router->get('treasury_accounts/account-export', 'TreasuryAccountController@accountExport', ['auth', 'permission:cashbanks.view']);
$router->post('treasury_accounts/store', 'TreasuryAccountController@store', ['auth', 'permission:cashbanks.create']);
$router->post('treasury_accounts/update', 'TreasuryAccountController@update', ['auth', 'permission:cashbanks.manage']);
$router->get('treasury_movements', 'TreasuryMovementController@index', ['auth', 'permission:treasury_movements.view']);
$router->get('treasury_movements/index', 'TreasuryMovementController@index', ['auth', 'permission:treasury_movements.view']);
$router->get('treasury_transfers', 'TreasuryTransferController@index', ['auth', 'permission:treasury_transfers.view']);
$router->get('treasury_transfers/index', 'TreasuryTransferController@index', ['auth', 'permission:treasury_transfers.view']);
$router->get('treasury_transfers/create', 'TreasuryTransferController@create', ['auth', 'permission:treasury_transfers.create']);
$router->post('treasury_transfers/store', 'TreasuryTransferController@store', ['auth', 'permission:treasury_transfers.create']);
$router->get('treasury_transfers/show', 'TreasuryTransferController@show', ['auth', 'permission:treasury_transfers.view']);
$router->post('treasury_transfers/submit', 'TreasuryTransferController@submit', ['auth', 'permission:treasury_transfers.create']);
$router->post('treasury_transfers/decision', 'TreasuryTransferController@decision', ['auth', 'permission:treasury_transfers.approve']);
$router->post('treasury_transfers/execute', 'TreasuryTransferController@execute', ['auth', 'permission:treasury_transfers.execute']);
$router->post('treasury_transfers/cancel', 'TreasuryTransferController@cancel', ['auth', 'permission:treasury_transfers.create']);
$router->get('finance/reports', 'FinanceReportController@index', ['auth', 'permission:finance.reports']);
$router->get('reports', 'ReportController@index', ['auth', 'permission:reports.view']);
$router->get('reports/index', 'ReportController@index', ['auth', 'permission:reports.view']);
$router->get('reports/export', 'ReportController@export', ['auth', 'permission:reports.view']);

$router->get('construction/projects', 'ConstructionProjectController@index', ['auth', 'permission:projects.view']);
$router->get('construction/projects/index', 'ConstructionProjectController@index', ['auth', 'permission:projects.view']);
$router->get('construction/projects/dashboard', 'ConstructionProjectController@dashboard', ['auth', 'permission:projects.view']);
$router->get('construction/projects/create', 'ConstructionProjectController@create', ['auth', 'permission:projects.create']);
$router->post('construction/projects/store', 'ConstructionProjectController@store', ['auth', 'permission:projects.create']);
$router->get('construction/projects/show', 'ConstructionProjectController@show', ['auth', 'permission:projects.view']);
$router->get('construction/projects/edit', 'ConstructionProjectController@edit', ['auth', 'permission:projects.edit']);
$router->post('construction/projects/update', 'ConstructionProjectController@update', ['auth', 'permission:projects.edit']);
$router->get('construction/daily_reports/create', 'ConstructionDailyReportController@create', ['auth', 'permission:sites.reports.create']);
$router->post('construction/daily_reports/store', 'ConstructionDailyReportController@store', ['auth', 'permission:sites.reports.create']);
$router->get('construction/daily_reports/show', 'ConstructionDailyReportController@show', ['auth', 'permission:sites.reports.view']);
$router->get('construction/reports', 'ConstructionReportController@index', ['auth', 'permission:construction.reports']);

$router->get('placement/employees/index', 'PlacementEmployeeController@index', ['auth', 'permission:placement.employees.view']);
$router->get('placement/employees/create', 'PlacementEmployeeController@create', ['auth', 'permission:placement.employees.create']);
$router->post('placement/employees/store', 'PlacementEmployeeController@store', ['auth', 'permission:placement.employees.create']);
$router->get('placement/contracts/index', 'PlacementContractController@index', ['auth', 'permission:placement.contracts.view']);
$router->get('placement/contracts/create', 'PlacementContractController@create', ['auth', 'permission:placement.contracts.create']);
$router->post('placement/contracts/store', 'PlacementContractController@store', ['auth', 'permission:placement.contracts.create']);
$router->get('placement/contracts/show', 'PlacementContractController@show', ['auth', 'permission:placement.contracts.view']);
$router->get('placement/attendance', 'PlacementAttendanceController@index', ['auth', 'permission:placement.attendance.manage']);
$router->post('placement/attendance', 'PlacementAttendanceController@store', ['auth', 'permission:placement.attendance.manage']);
$router->get('placement/invoices', 'PlacementInvoiceController@index', ['auth', 'permission:placement.invoices.manage']);
$router->post('placement/invoices/generate', 'PlacementInvoiceController@generate', ['auth', 'permission:placement.invoices.manage']);
$router->get('placement/reports', 'PlacementReportController@index', ['auth', 'permission:placement.reports']);

$router->get('clients/index', 'ClientController@index', ['auth', 'permission:clients.view']);
$router->get('clients/create', 'ClientController@create', ['auth', 'permission:clients.create']);
$router->post('clients/store', 'ClientController@store', ['auth', 'permission:clients.create']);
$router->get('products/index', 'ProductController@index', ['auth', 'permission:products.view']);
$router->get('products/create', 'ProductController@create', ['auth', 'permission:products.create']);
$router->post('products/store', 'ProductController@store', ['auth', 'permission:products.create']);
$router->get('quotations/index', 'QuotationController@index', ['auth', 'permission:quotations.view']);
$router->get('quotations/create', 'QuotationController@create', ['auth', 'permission:quotations.create']);
$router->post('quotations/store', 'QuotationController@store', ['auth', 'permission:quotations.create']);
$router->post('quotations/validate', 'QuotationController@validateQuote', ['auth', 'permission:quotations.validate']);
$router->post('quotations/convert', 'QuotationController@convert', ['auth', 'permission:quotations.validate']);
$router->get('sales_orders/index', 'SalesOrderController@index', ['auth', 'permission:sales_orders.view']);
$router->get('sales_orders/show', 'SalesOrderController@show', ['auth', 'permission:sales_orders.view']);
$router->post('sales_orders/invoice', 'SalesOrderController@generateInvoice', ['auth', 'permission:sales_invoices.view']);
$router->get('deliveries/index', 'DeliveryController@index', ['auth', 'permission:deliveries.view']);
$router->get('deliveries/create', 'DeliveryController@create', ['auth', 'permission:deliveries.create']);
$router->post('deliveries/store', 'DeliveryController@store', ['auth', 'permission:deliveries.create']);
$router->get('invoices', 'SalesInvoiceController@index', ['auth', 'permission:sales_invoices.view']);
$router->get('invoices/index', 'SalesInvoiceController@index', ['auth', 'permission:sales_invoices.view']);
$router->get('invoices/create', 'SalesInvoiceController@create', ['auth', 'permission:invoices.create']);
$router->post('invoices/store', 'SalesInvoiceController@store', ['auth', 'permission:invoices.create']);
$router->post('invoices/generate-placement', 'SalesInvoiceController@generatePlacement', ['auth', 'permission:invoices.create']);
$router->get('invoices/show', 'SalesInvoiceController@show', ['auth', 'permission:sales_invoices.view']);
$router->get('invoices/payment', 'SalesInvoiceController@payment', ['auth', 'permission:invoices.payment']);
$router->post('invoices/payment', 'SalesInvoiceController@storePayment', ['auth', 'permission:invoices.payment']);
$router->get('invoices/print', 'SalesInvoiceController@print', ['auth', 'permission:invoices.print']);
$router->get('payments/index', 'SalesPaymentController@index', ['auth', 'permission:payments.create']);
$router->post('payments/store', 'SalesPaymentController@store', ['auth', 'permission:payments.create']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
