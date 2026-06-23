<section class="panel">
    <div class="panel-header"><div><span class="section-kicker">Placement de personnel</span><h2>Suivi mensuel des présences</h2><p class="ui-page-intro">Enregistrez les jours prestés, les absences et les heures supplémentaires par agent.</p></div></div>
    <?php if ($message = Session::flash('success')): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
    <form class="form-grid" method="post" action="<?= url('placement/attendance'); ?>" data-validate>
        <?= Csrf::field(); ?>
        <label>Mois <input type="month" name="month" required value="<?= e($month); ?>"></label>
        <label>Agent placé <select name="assignment_id" required><?php foreach ($assignments as $a): ?><option value="<?= (int) $a['id']; ?>"><?= e($a['client_name']); ?> · <?= e($a['first_name'] . ' ' . $a['last_name']); ?></option><?php endforeach; ?></select></label>
        <label>Jours présents <input type="number" step="0.5" min="0" name="days_present" required></label>
        <label>Jours absents <input type="number" step="0.5" min="0" name="days_absent" value="0"></label>
        <label>Heures supplémentaires <input type="number" step="0.5" min="0" name="overtime_hours" value="0"></label>
        <label>Observations <input name="notes" placeholder="Informations utiles sur la période"></label>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-calendar-check"></i> Enregistrer les présences</button></div>
    </form>
</section>
<section class="panel"><div class="panel-header"><div><span class="section-kicker">Historique</span><h3>Présences du mois <?= e($month); ?></h3></div></div><div class="table-responsive"><table class="data-table"><thead><tr><th>Client</th><th>Agent</th><th class="text-right">Jours présents</th><th class="text-right">Jours absents</th><th class="text-right">Heures supplémentaires</th><th>Observations</th></tr></thead><tbody><?php foreach ($attendances as $row): ?><tr><td><?= e($row['client_name']); ?></td><td><?= e($row['first_name'] . ' ' . $row['last_name']); ?></td><td class="text-right"><?= e($row['days_present']); ?></td><td class="text-right"><?= e($row['days_absent']); ?></td><td class="text-right"><?= e($row['overtime_hours']); ?></td><td><?= e($row['notes'] ?: '—'); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
