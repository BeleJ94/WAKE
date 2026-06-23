<?php

declare(strict_types=1);

class ValidationAjaxController extends Controller
{
    public function validate(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['valid' => false, 'errors' => ['session' => 'Session expirée. Rechargez la page.'], 'warnings' => []]);
        }

        $type = (string) $this->request('type', '');
        $method = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));
        if (!method_exists($this, $method)) {
            $this->json(['valid' => true, 'errors' => [], 'warnings' => []]);
        }

        $this->json($this->{$method}());
    }

    private function validateFundRequest(): array
    {
        $errors = [];
        $this->minText($errors, 'title', 'Titre trop court.', 3);
        $this->minText($errors, 'department', 'Service obligatoire.', 2);
        $this->minText($errors, 'purpose', 'Besoin insuffisamment décrit.', 8);
        $amount = (float) $this->request('total_amount', 0);
        if ($amount <= 0) {
            $errors['total_amount'] = 'Le montant demandé doit être supérieur à zéro.';
        }
        if (!in_array((string) $this->request('currency', ''), ['USD', 'CDF'], true)) {
            $errors['currency'] = 'Sélectionnez USD ou CDF.';
        }

        return $this->result($errors, $amount > 10000 ? ['Montant important : vérifiez les justificatifs avant soumission.'] : []);
    }

    private function validateApproval(): array
    {
        $errors = [];
        $decision = (string) $this->request('decision', '');

        if ($decision === 'approve' && (int) $this->request('treasury_account_id', 0) <= 0) {
            $errors['treasury_account_id'] = 'Sélectionnez le compte à utiliser.';
        }
        if ($decision === 'reject' && trim((string) $this->request('rejected_reason', '')) === '') {
            $errors['rejected_reason'] = 'Le motif de rejet est obligatoire.';
        }

        return $this->result($errors);
    }

    private function validateFundPayment(): array
    {
        $errors = [];
        $this->minText($errors, 'description', 'Description de paiement obligatoire.', 3);

        return $this->result($errors);
    }

    private function validateProject(): array
    {
        $errors = [];
        foreach (['name', 'client_name', 'start_date', 'end_date', 'location'] as $field) {
            if (trim((string) $this->request($field, '')) === '') {
                $errors[$field] = 'Champ obligatoire.';
            }
        }
        if ((float) $this->request('contract_amount', 0) <= 0 || (float) $this->request('forecast_cost', 0) <= 0) {
            $errors['budget'] = 'Contrat et coût prévisionnel doivent être supérieurs à zéro.';
        }

        return $this->result($errors, (float) $this->request('forecast_cost', 0) > (float) $this->request('contract_amount', 0) ? ['Le coût prévisionnel dépasse le contrat.'] : []);
    }

    private function validateDailyReport(): array
    {
        $errors = [];
        if (trim((string) $this->request('report_date', '')) === '') {
            $errors['report_date'] = 'Date du rapport obligatoire.';
        }

        return $this->result($errors);
    }

    private function validateInvoice(): array
    {
        $errors = [];
        if ((int) $this->request('client_id', 0) <= 0) {
            $errors['client_id'] = 'Client obligatoire.';
        }
        $total = $this->sumFlatLines('quantity', 'unit_price');
        if ($total <= 0) {
            $errors['items'] = 'Ajoutez au moins une ligne facturable.';
        }

        return $this->result($errors);
    }

    private function validateInvoicePayment(): array
    {
        $errors = [];
        if ((int) $this->request('invoice_id', 0) <= 0) {
            $errors['invoice_id'] = 'Facture obligatoire.';
        }
        if ((float) $this->request('amount', 0) <= 0) {
            $errors['amount'] = 'Montant invalide.';
        }

        return $this->result($errors);
    }

    private function validateDelivery(): array
    {
        $errors = [];
        if ((int) $this->request('sales_order_id', 0) <= 0) {
            $errors['sales_order_id'] = 'Commande obligatoire.';
        }
        $total = 0.0;
        foreach (($_POST['quantities'] ?? []) as $qty) {
            $total += max(0, (float) $qty);
        }
        if ($total <= 0) {
            $errors['quantities'] = 'Indiquez au moins une quantité à livrer.';
        }

        return $this->result($errors);
    }

    private function minText(array &$errors, string $field, string $message, int $min): void
    {
        if (strlen(trim((string) $this->request($field, ''))) < $min) {
            $errors[$field] = $message;
        }
    }

    private function sumFlatLines(string $quantityKey, string $amountKey): float
    {
        $total = 0.0;
        $quantities = $_POST[$quantityKey] ?? [];
        $amounts = $_POST[$amountKey] ?? [];
        foreach ($quantities as $index => $quantity) {
            $total += max(0, (float) $quantity) * max(0, (float) ($amounts[$index] ?? 0));
        }
        return $total;
    }

    private function result(array $errors, array $warnings = []): array
    {
        return ['valid' => $errors === [], 'errors' => $errors, 'warnings' => $warnings];
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        exit;
    }
}
