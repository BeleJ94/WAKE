<section class="dashboard-section">
    <div class="section-header">
        <div>
            <span class="section-kicker">Vue direction</span>
            <h2>Pilotage opérationnel</h2>
        </div>
        <div class="action-group">
            <button class="btn btn-secondary" type="button">Exporter</button>
            <button class="btn btn-primary" type="button" data-modal-open="demo-modal">Nouvelle action</button>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Socle prêt.</strong>
        Les composants UI sont disponibles pour intégrer progressivement les modules métiers.
    </div>

    <div class="kpi-grid">
        <article class="kpi-card">
            <div class="kpi-meta">
                <span>Trésorerie disponible</span>
                <span class="badge badge-success">Stable</span>
            </div>
            <strong>0 USD</strong>
            <small>Connexion aux données à venir</small>
        </article>
        <article class="kpi-card">
            <div class="kpi-meta">
                <span>Projets actifs</span>
                <span class="badge badge-warning">Suivi</span>
            </div>
            <strong>0</strong>
            <small>Module construction en préparation</small>
        </article>
        <article class="kpi-card">
            <div class="kpi-meta">
                <span>Factures ouvertes</span>
                <span class="badge badge-neutral">Nouveau</span>
            </div>
            <strong>0</strong>
            <small>Workflow facturation à configurer</small>
        </article>
        <article class="kpi-card accent">
            <div class="kpi-meta">
                <span>Demandes à valider</span>
                <span class="badge badge-danger">Action</span>
            </div>
            <strong>0</strong>
            <small>Approbations futures</small>
        </article>
    </div>
</section>

<section class="dashboard-grid">
    <div class="panel panel-wide">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Opérations</span>
                <h3>Activité récente</h3>
            </div>
            <div class="filters">
                <select aria-label="Filtrer par module">
                    <option>Tous les modules</option>
                    <option>Finance</option>
                    <option>Projets</option>
                    <option>Facturation</option>
                </select>
                <input type="date" aria-label="Filtrer par date">
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Module</th>
                        <th>Responsable</th>
                        <th>Statut</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>WBS-0001</td>
                        <td>Finance & Trésorerie</td>
                        <td>Direction</td>
                        <td><span class="badge badge-success">Validé</span></td>
                        <td class="text-right">0 USD</td>
                    </tr>
                    <tr>
                        <td>WBS-0002</td>
                        <td>Projets Construction</td>
                        <td>Chef projet</td>
                        <td><span class="badge badge-warning">En attente</span></td>
                        <td class="text-right">0 USD</td>
                    </tr>
                    <tr>
                        <td>WBS-0003</td>
                        <td>Facturation</td>
                        <td>Comptabilité</td>
                        <td><span class="badge badge-neutral">Brouillon</span></td>
                        <td class="text-right">0 USD</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <span class="section-kicker">Composants</span>
                <h3>Etat du socle</h3>
            </div>
        </div>

        <div class="empty-state compact">
            <span class="empty-icon" aria-hidden="true"></span>
            <h4>Aucun module métier actif</h4>
            <p>La plateforme est prête à recevoir les workflows WAKE SERVICES.</p>
        </div>

        <form class="form-stack">
            <label>
                Client
                <input type="text" placeholder="Nom du client">
            </label>
            <label>
                Priorité
                <select>
                    <option>Normale</option>
                    <option>Haute</option>
                    <option>Critique</option>
                </select>
            </label>
            <button class="btn btn-secondary full-width" type="button">Prévisualiser</button>
        </form>
    </aside>
</section>

<div class="modal" id="demo-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="demo-modal-title">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="demo-modal-title">Action rapide</h3>
            <button class="icon-button" type="button" data-modal-close aria-label="Fermer">x</button>
        </div>
        <p>Cette modale sert de composant réutilisable pour les futures créations, validations et confirmations.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button" data-modal-close>Annuler</button>
            <button class="btn btn-primary" type="button" data-modal-close>Confirmer</button>
        </div>
    </div>
</div>

