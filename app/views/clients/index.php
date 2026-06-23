<?php
$clientInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : 'CL';
};
?>

<section class="clients-page" data-client-portfolio>
    <header class="clients-header">
        <div>
            <span class="section-kicker">Commercial & Ventes</span>
            <h2>Clients</h2>
            <p>Une vue claire de vos relations commerciales et de leur situation financière.</p>
        </div>
        <?php if (Auth::can('clients.create')): ?>
            <a class="btn btn-primary" href="<?= url('clients/create'); ?>">
                <i class="bi bi-plus-lg"></i> Nouveau client
            </a>
        <?php endif; ?>
    </header>

    <?php if ($message = Session::flash('success')): ?>
        <div class="alert alert-success"><?= e($message); ?></div>
    <?php endif; ?>

    <section class="clients-summary" aria-label="Synthèse du portefeuille clients">
        <article>
            <span class="clients-summary-icon"><i class="bi bi-buildings"></i></span>
            <div>
                <small>Clients actifs</small>
                <strong><?= (int) $metrics['active']; ?></strong>
                <span>sur <?= (int) $metrics['total']; ?> enregistrés</span>
            </div>
        </article>
        <article>
            <span class="clients-summary-icon is-blue"><i class="bi bi-receipt"></i></span>
            <div>
                <small>Total facturé</small>
                <strong><?= money($metrics['invoiced']); ?></strong>
                <span>Portefeuille cumulé</span>
            </div>
        </article>
        <article>
            <span class="clients-summary-icon is-amber"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <small>Reste à encaisser</small>
                <strong><?= money($metrics['outstanding']); ?></strong>
                <span><?= number_format((float) $metrics['collection_rate'], 1, ',', ' '); ?>% encaissé</span>
            </div>
        </article>
    </section>

    <section class="clients-directory">
        <div class="clients-toolbar">
            <div>
                <h3>Répertoire clients</h3>
                <span data-client-result-count><?= count($clients); ?> client<?= count($clients) > 1 ? 's' : ''; ?></span>
            </div>
            <div class="clients-toolbar-actions">
                <label class="clients-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        placeholder="Rechercher un client, un contact ou un code"
                        aria-label="Rechercher dans le portefeuille clients"
                        data-client-search
                    >
                </label>
                <label class="clients-status-filter">
                    <span class="sr-only">Filtrer par statut</span>
                    <select data-client-status aria-label="Filtrer les clients par statut">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actifs</option>
                        <option value="inactive">Inactifs</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Activité</th>
                        <th class="text-right">Facturé</th>
                        <th>Encaissement</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody data-client-rows>
                    <?php foreach ($clients as $client): ?>
                        <?php
                        $invoiced = (float) $client['invoiced_total'];
                        $paid = (float) $client['paid_total'];
                        $rate = $invoiced > 0 ? min(100, ($paid / $invoiced) * 100) : 0;
                        ?>
                        <tr data-client-row data-status="<?= e($client['status']); ?>">
                            <td>
                                <div class="client-identity">
                                    <span class="client-avatar" aria-hidden="true"><?= e($clientInitials($client['name'])); ?></span>
                                    <div>
                                        <strong><?= e($client['name']); ?></strong>
                                        <span><?= e($client['client_code']); ?> · <?= e($client['address'] ?: 'Adresse non renseignée'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="client-contact">
                                    <strong><?= e($client['contact_name'] ?: 'Contact non renseigné'); ?></strong>
                                    <span><?= e($client['phone'] ?: ($client['email'] ?: 'Coordonnées indisponibles')); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="client-activity">
                                    <span><strong><?= (int) $client['quotations_count']; ?></strong> devis</span>
                                    <span><strong><?= (int) $client['orders_count']; ?></strong> commandes</span>
                                </div>
                            </td>
                            <td class="text-right client-amount">
                                <strong><?= money($invoiced); ?></strong>
                                <span><?= money($paid); ?> encaissé</span>
                            </td>
                            <td>
                                <div class="client-collection">
                                    <div>
                                        <strong><?= number_format($rate, 0, ',', ' '); ?>%</strong>
                                        <span><?= money(max(0, $invoiced - $paid)); ?> restant</span>
                                    </div>
                                    <span class="client-progress" aria-label="<?= number_format($rate, 0, ',', ' '); ?> pour cent encaissé">
                                        <i style="width: <?= number_format($rate, 2, '.', ''); ?>%"></i>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="client-status <?= $client['status'] === 'active' ? 'is-active' : 'is-inactive'; ?>">
                                    <i aria-hidden="true"></i><?= e(status_label($client['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="clients-empty" data-client-empty <?= $clients !== [] ? 'hidden' : ''; ?>>
            <span><i class="bi bi-people"></i></span>
            <h4>Aucun client trouvé</h4>
            <p>Modifiez votre recherche ou ajoutez un nouveau client.</p>
        </div>
    </section>
</section>
