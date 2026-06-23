<?php

declare(strict_types=1);

function asset(string $path): string
{
    return rtrim(PUBLIC_URL, '/') . '/assets/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money($amount, string $currency = 'USD'): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' ' . $currency;
}

function status_badge_class(string $status): string
{
    $map = [
        'Draft' => 'badge-neutral',
        'Pending' => 'badge-warning',
        'Approved' => 'badge-success',
        'Executed' => 'badge-success',
        'Rejected' => 'badge-danger',
        'Paid' => 'badge-success',
        'Cancelled' => 'badge-neutral',
        'active' => 'badge-success',
        'inactive' => 'badge-neutral',
    ];

    return $map[$status] ?? 'badge-neutral';
}

function status_label(string $status): string
{
    $map = [
        'Draft' => 'Brouillon',
        'Pending' => 'En attente',
        'Approved' => 'Approuvé',
        'Rejected' => 'Rejeté',
        'Paid' => 'Payé',
        'Cancelled' => 'Annulé',
        'Executed' => 'Exécuté',
        'Created' => 'Création',
        'Submitted' => 'Soumission',
        'Sent' => 'Envoyé',
        'Validated' => 'Validé',
        'Converted' => 'Converti',
        'Open' => 'Ouvert',
        'Partially Paid' => 'Partiellement payé',
        'Overdue' => 'En retard',
        'Prepared' => 'Préparé',
        'Partial' => 'Partiel',
        'Partially Delivered' => 'Partiellement livré',
        'Delivered' => 'Livré',
        'Invoiced' => 'Facturé',
        'Issued' => 'Émis',
        'Planning' => 'Planification',
        'In Progress' => 'En cours',
        'On Hold' => 'En pause',
        'Completed' => 'Terminé',
        'Active' => 'Actif',
        'Suspended' => 'Suspendu',
        'Expired' => 'Expiré',
        'Closed' => 'Clôturé',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'inflow' => 'Entrée',
        'outflow' => 'Sortie',
        'Cash' => 'Espèces',
        'Bank' => 'Banque',
        'Wallet' => 'Portefeuille',
    ];

    return $map[$status] ?? $status;
}

function date_fr(?string $value, bool $withTime = false): string
{
    if (!$value) {
        return '—';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    $months = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $label = date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

    return $withTime ? $label . ' à ' . date('H:i', $timestamp) : $label;
}

function audit_action_label(string $action): string
{
    $map = [
        'login_success' => 'Connexion réussie',
        'login_failed' => 'Échec de connexion',
        'login_blocked' => 'Connexion bloquée',
        'logout' => 'Déconnexion',
        'session_timeout' => 'Session expirée',
        'fund_request_created' => 'Demande de fonds créée',
        'fund_request_submitted' => 'Demande de fonds soumise',
        'fund_request_approved' => 'Demande de fonds approuvée',
        'fund_request_rejected' => 'Demande de fonds rejetée',
        'fund_request_paid' => 'Demande de fonds payée',
        'treasury_account_created' => 'Compte de trésorerie créé',
        'treasury_account_updated' => 'Compte de trésorerie modifié',
        'treasury_transfer_created' => 'Transfert créé',
        'treasury_transfer_submitted' => 'Transfert soumis',
        'treasury_transfer_approved' => 'Transfert approuvé',
        'treasury_transfer_rejected' => 'Transfert rejeté',
        'treasury_transfer_executed' => 'Transfert exécuté',
        'treasury_transfer_cancelled' => 'Transfert annulé',
        'invoice_created' => 'Facture créée',
        'invoice_payment_created' => 'Paiement de facture enregistré',
    ];

    return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
}

function entity_type_label(string $type): string
{
    $map = [
        'user' => 'Utilisateur',
        'fund_request' => 'Demande de fonds',
        'treasury_account' => 'Compte de trésorerie',
        'treasury_transfer' => 'Transfert de fonds',
        'invoice' => 'Facture',
        'payment' => 'Paiement',
        'construction_project' => 'Projet de construction',
        'delivery' => 'Livraison',
    ];

    return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

function project_status_badge(string $status): string
{
    $map = [
        'Planning' => 'badge-neutral',
        'In Progress' => 'badge-success',
        'On Hold' => 'badge-warning',
        'Completed' => 'badge-success',
        'Cancelled' => 'badge-danger',
    ];

    return $map[$status] ?? 'badge-neutral';
}

function placement_status_badge(string $status): string
{
    $map = [
        'Draft' => 'badge-neutral',
        'Active' => 'badge-success',
        'Suspended' => 'badge-warning',
        'Expired' => 'badge-danger',
        'Closed' => 'badge-neutral',
        'Issued' => 'badge-warning',
        'Paid' => 'badge-success',
        'Cancelled' => 'badge-danger',
    ];

    return $map[$status] ?? status_badge_class($status);
}

function sales_status_badge(string $status): string
{
    $map = [
        'Draft' => 'badge-neutral',
        'Validated' => 'badge-success',
        'Converted' => 'badge-success',
        'Open' => 'badge-warning',
        'Partially Delivered' => 'badge-warning',
        'Delivered' => 'badge-success',
        'Invoiced' => 'badge-success',
        'Partially Paid' => 'badge-warning',
        'Paid' => 'badge-success',
        'Closed' => 'badge-neutral',
        'Cancelled' => 'badge-danger',
        'Prepared' => 'badge-warning',
        'Partial' => 'badge-warning',
        'Issued' => 'badge-warning',
        'Sent' => 'badge-warning',
        'Overdue' => 'badge-danger',
    ];

    return $map[$status] ?? 'badge-neutral';
}

function invoice_source_label(?string $source): string
{
    $map = [
        'manual' => 'Facture manuelle',
        'sales_order' => 'Commande client',
        'placement_contract' => 'Contrat de placement',
        'construction_project' => 'Projet construction',
        'other_service' => 'Autre service',
    ];

    return $map[$source ?? 'manual'] ?? 'Facture manuelle';
}

function file_size_label(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', ' ') . ' Mo';
    }

    return number_format(max(0, $bytes) / 1024, 0, ',', ' ') . ' Ko';
}
