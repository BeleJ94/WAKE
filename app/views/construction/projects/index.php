<section class="dashboard-section">
    <div class="section-header">
        <div><span class="section-kicker">Construction</span><h2>Projets de construction</h2><p class="ui-page-intro">Pilotez le portefeuille, l’avancement, les coûts et les risques des chantiers.</p></div>
        <div class="action-group">
            <a class="btn btn-secondary" href="<?= url('construction/projects/dashboard'); ?>"><i class="bi bi-speedometer"></i> Cockpit portefeuille</a>
            <?php if (Auth::can('projects.create')): ?><a class="btn btn-primary" href="<?= url('construction/projects/create'); ?>">Nouveau projet</a><?php endif; ?>
        </div>
    </div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($message = Session::flash('error')): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div><span class="section-kicker">Portefeuille</span><h3>Liste des projets</h3></div>
        <div class="filters"><input type="search" data-table-search data-target="#projects-table" placeholder="Filtrer les projets"></div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="projects-table">
            <thead><tr><th>Référence</th><th>Projet</th><th>Client</th><th>Chef projet</th><th>Statut</th><th>Avancement</th><th class="text-right">Contrat</th><th class="text-right">Marge réelle</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr data-status="<?= e($project['status']); ?>">
                        <td><?= e($project['reference']); ?></td>
                        <td><?= e($project['name']); ?><small class="muted-line"><?= e($project['location']); ?></small></td>
                        <td><?= e($project['client_name']); ?></td>
                        <td><?= e($project['manager_name'] ?? '-'); ?></td>
                        <td><span class="badge <?= project_status_badge($project['status']); ?>"><?= e(status_label($project['status'])); ?></span></td>
                        <td><div class="progress-track"><i style="width: <?= (int) $project['metrics']['physical_progress']; ?>%"></i></div><small><?= number_format($project['metrics']['physical_progress'], 1, ',', ' '); ?>%</small></td>
                        <td class="text-right"><?= money($project['contract_amount']); ?></td>
                        <td class="text-right"><?= money($project['metrics']['actual_margin']); ?></td>
                        <td class="text-right"><a class="btn btn-secondary btn-compact" href="<?= url('construction/projects/show?id=' . (int) $project['id']); ?>"><i class="bi bi-folder2-open"></i> Ouvrir le cockpit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
