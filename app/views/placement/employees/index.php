<section class="dashboard-section">
    <div class="section-header">
        <div><span class="section-kicker">Placement</span><h2>Agents</h2></div>
        <?php if (Auth::can('placement.employees.create')): ?><a class="btn btn-primary" href="<?= url('placement/employees/create'); ?>">Nouvel agent</a><?php endif; ?>
    </div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
</section>
<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Qui est placé ?</span><h3>Agents et affectations</h3></div><div class="filters"><input type="search" data-table-search data-target="#employees-table" placeholder="Filtrer"></div></div>
    <div class="table-responsive"><table class="data-table" id="employees-table"><thead><tr><th>Code</th><th>Agent</th><th>Poste</th><th>Client</th><th class="text-right">Coût</th><th class="text-right">Facturé</th><th class="text-right">Marge</th><th>Statut</th></tr></thead><tbody>
        <?php foreach ($employees as $employee): ?><tr><td><?= e($employee['employee_code']); ?></td><td><?= e($employee['first_name'] . ' ' . $employee['last_name']); ?><small class="muted-line"><?= e($employee['phone'] ?? ''); ?></small></td><td><?= e($employee['position_title'] ?? $employee['job_title']); ?></td><td><?= e($employee['client_name'] ?? 'Non affecté'); ?></td><td class="text-right"><?= money($employee['base_salary']); ?></td><td class="text-right"><?= $employee['client_rate'] ? money($employee['client_rate']) : '—'; ?></td><td class="text-right"><?= $employee['margin_amount'] ? money($employee['margin_amount']) : '—'; ?></td><td><span class="badge <?= status_badge_class($employee['status']); ?>"><?= e(status_label($employee['status'])); ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
