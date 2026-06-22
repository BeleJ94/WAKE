# WAKE Business Suite - Guide court

## Installation

Prérequis :
- PHP 7.4 ou plus
- MySQL 5.7 ou plus
- Apache/XAMPP ou serveur PHP integre

Installation locale :
1. Placer le projet dans le serveur web, par exemple `/Applications/XAMPP/xamppfiles/htdocs/WAKE`.
2. Verifier les parametres dans `config/config.php`.
3. Creer la base et charger le schema :

```bash
/Applications/XAMPP/xamppfiles/bin/mysql --protocol=TCP -h127.0.0.1 -uroot -e "SOURCE database/schema.sql"
```

4. Charger les donnees de demonstration :

```bash
/Applications/XAMPP/xamppfiles/bin/mysql --protocol=TCP -h127.0.0.1 -uroot wake_business_suite -e "SOURCE database/seed.sql"
```

5. Lancer en serveur PHP local si besoin :

```bash
APP_URL=http://127.0.0.1:8027 php -S 127.0.0.1:8027 -t public
```

## Configuration base de donnees

Fichier : `config/config.php`

Parametres par defaut :
- `DB_HOST` : `127.0.0.1`
- `DB_PORT` : `3306`
- `DB_NAME` : `wake_business_suite`
- `DB_USER` : `root`
- `DB_PASS` : vide
- `DB_CHARSET` : `utf8mb4`

En production, mettre `APP_DEBUG` a `false`, proteger les secrets hors du code et servir l'application depuis le dossier `public`.

## Compte admin par defaut

- Email : `admin@wake-services.local`
- Mot de passe : `Admin@12345`
- Role : `Super Admin`

Changer ce mot de passe avant toute utilisation reelle.

## Modules disponibles

- Authentification, roles, permissions et audit
- Dashboard Direction
- Finance et tresorerie
- Demandes de fonds
- Caisses, banques et mouvements de tresorerie
- Projets de construction et rapports journaliers
- Placement de personnel
- Clients, produits, devis, commandes et livraisons
- Facturation centralisee et paiements partiels
- Rapports de gestion
- Notifications internes

## Roles utilisateurs

- Super Admin : acces complet
- Direction : pilotage, rapports, approbations et audit
- Finance : facturation, paiements facture, rapports finance
- Responsable Caisse/Banque : paiement des demandes approuvees
- Chef de Projet : projets construction et suivi chantier
- RH Placement : agents, contrats, presence et facturation placement
- Commercial : clients, produits, devis, commandes et factures commerciales
- Logistique : livraisons et suivi stock

## Workflows principaux

Demande de fonds :
1. Creation en `Draft` ou soumission directe.
2. Passage en `Pending`.
3. Approbation ou rejet par la Direction.
4. Choix du compte de tresorerie lors de l'approbation.
5. Paiement par le responsable du compte uniquement.
6. Creation automatique d'un mouvement de tresorerie.
7. Justificatif optionnel et journalisation audit.

Construction :
1. Creation du projet avec client, budget, dates et chef de projet.
2. Definition des travaux et consommables prevus.
3. Saisie de rapports journaliers : avancement, consommations, depenses, photos, blocages.
4. Cockpit projet : avancement physique, budget consomme, ecarts, marge estimee et alertes.

Placement personnel :
1. Creation des agents.
2. Creation du contrat client.
3. Affectation des agents avec cout, tarif client et marge.
4. Suivi des presences.
5. Generation des factures de placement.

Ventes, commandes et livraisons :
1. Client.
2. Devis.
3. Validation du devis.
4. Transformation en commande.
5. Preparation et livraison partielle ou totale.
6. Generation de facture.
7. Paiement et cloture operationnelle.

Facturation centralisee :
1. Creation manuelle ou generation depuis commande/contrat.
2. Statuts : `Draft`, `Sent`, `Partially Paid`, `Paid`, `Overdue`, `Cancelled`.
3. Paiements partiels avec calcul automatique du reste a payer.
4. Impression via le template facture.

## Securite

- Sessions avec timeout.
- Protection CSRF sur formulaires critiques.
- Echappement HTML via helper `e()`.
- Requetes preparees PDO.
- Middleware `auth` et `permission`.
- Validations serveur et validations AJAX.
- Uploads controles par extension, MIME et taille.
- Acces direct aux fichiers sensibles bloque par `.htaccess`.
- Actions critiques enregistrees dans `audit_logs`.
