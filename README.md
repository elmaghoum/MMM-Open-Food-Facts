# MMM - Mieux Manger en Marne

Dashboard interactif de comparaison de produits alimentaires basé sur Open Food Facts

Une application web permettant aux utilisateurs de comparer, analyser et composer leur liste de course pour une alimentation plus saine, en s'appuyant sur les données nutritionnelles de plus de 3 millions de produits.

---

## Table des matières

1. Contexte et Objectifs  
2. Contraintes du Projet  
3. Fonctionnalités Principales  
4. Architecture Technique  
5. Sécurité  
6. Installation et Démarrage  
7. Tests  
8. API Publique  
9. Choix Techniques  
10. Structure du Projet


---

## Contexte et Objectifs

### Problématique

Dans un contexte où l'information nutritionnelle est souvent complexe et difficile à comparer, notre application **MMM** répond à un besoin concret : aider les consommateurs à faire des choix alimentaires éclairés.

### Cas d'usage

Utilisateur type : Un parent de famille qui souhaite comparer les produits avant de faire ses courses.

Scénario d'utilisation :
1. Recherche de produits par nom ou code-barres
2. Comparaison de nutriscores, scores NOVA, et taux de sucre/sel
3. Composition d'une liste de course avec ses produits favoris
4. Visualisation graphique des informations nutritionnelles

### Valeur ajoutée

- Dashboard personnalisable: Chaque utilisateur compose son interface avec les widgets qui l'intéressent
- Données fiables : Utilisation de l'API Open Food Facts (3M+ produits)
- Comparaisons visuelles : Graphiques interactifs pour faciliter la décision
- Liste de courses : Composition réfléchie des produits et possibilité de télécharger sa liste de courses pour faciliter ses achats

### Pertinence fonctionnelle du Dashboard

Le système de dashboard à widgets permet une expérience utilisateur adaptable :

- Flexibilité : Chaque utilisateur choisit les widgets qui correspondent à ses besoins (comparaison nutritionnelle, recherche rapide, liste de course, etc.)
- Ergonomie : Drag & drop pour organiser son interface
- Évolutivité : Nouveaux widgets facilement ajoutables sans refonte complète de l'UI
- Personnalisation : Configuration individuelle de chaque widget (produits à comparer, filtres, etc.)

**Exemple concret** : Un utilisateur diabétique pourra se concentrer sur les widgets de comparaison du sucre, tandis qu’un sportif privilégiera les macronutriments (protéines, lipides).

---

## Contraintes du Projet

Ce projet a été développé dans le cadre d'un test technique avec les contraintes suivantes :

### Contraintes techniques

| Contrainte | Implémentation |
|------------|----------------|
| **Framework** | Symfony 8  |
| **Frontend dynamique** |  JavaScript + Stimulus.js |
| **Conteneurisation** | Docker Compose |
| **Démarrage** | Une seule commande : `docker compose up` |
| **Hot Reload** | FrankenPHP avec rechargement automatique du code |
| **Design System** | Shadcn UI adapté en Twig + Tailwind CSS |

### Contraintes temporelles

- **Délai** : 1 semaine
- **Profil** : Jeune développeur fullstack junior (post-formation) 
- **Objectif** : Démontrer la maîtrise des concepts avancés (Framework Synfony, DDD, TDD, sécurité...)
---

## Fonctionnalités Principales

### Authentification & Sécurité

-  Système de login sécurisé avec hashage bcrypt
-  Authentification 2FA par email (codes à usage unique)
-  Protection anti-bruteforce : Blocage après 5 tentatives échouées (15 min)
-  Gestion des rôles: Utilisateurs normaux vs Administrateurs

### Dashboard Interactif

**7 widgets disponibles** :

1. Recherche Produit : Affichage détaillé d'un produit (nutriscore, NOVA, composition)
2. Recherche Code-barres : Accès rapide par code EAN
3. Comparaison Sucre & Sel : Graphiques comparatifs (1-5 produits)
4. Comparaison Nutriscore : Vue d'ensemble des scores nutritionnels
5. Comparaison NOVA : Niveau de transformation des aliments
6. Graphique Nutritionnel : Répartition macronutriments (protéines, glucides, lipides)
7. Ma Liste de Course : Sauvegarde de produits favoris (max 20)

**Fonctionnalités du dashboard** :

- Drag & Drop : Ajout de widgets par glisser-déposer
- Configuration : Chaque widget est paramétrable (selon les produits qu'il sélectionne)
- Limite : Maximum 10 widgets par utilisateur (pour des questions de performance)
- Persistance : Configuration sauvegardée en base de données

### Interface Administrateur

-  Gestion des utilisateurs : Création, activation/désactivation, suppression
-  Statistiques : Vue d'ensemble (total users, actifs, admins)
-  Sécurité : Les admins ne peuvent pas se supprimer eux-mêmes

### API Publique

-  Endpoint GET `/api/dashboard/{email}` : Récupération de la configuration complète d'un dashboard
-  Format JSON : Prêt pour intégration dans des apps tierces (ex: app mobile ou un autre site)
-  Avec accès public : Pas d'authentification requise (pour démo seulement)

---

## Architecture Technique

### Approche Domain-Driven Design (DDD)

Le projet suit une architecture avec séparation stricte des responsabilités :
```
src/
├── Domain/              # Cœur métier (logique pure, entités, règles business)
│   ├── Dashboard/       # Agrégat Dashboard & Widget
│   └── Identity/        # Agrégat User & Authentification
│
├── Application/         # Cas d'usage (orchestration, commandes, handlers)
│   ├── Dashboard/       # UseCase : AddWidget, RemoveWidget, UpdateConfig
│   └── Identity/        # UseCase : LoginUser, ValidateTwoFactor
│
├── Infrastructure/      # Implémentation technique (DB, email, API externe)
│   ├── Doctrine/        # Repositories & Persistence
│   ├── Security/        # UserProvider, Adapters
│   └── Mail/            # Service d'envoi d'emails
│
└── UI/                  # Interface utilisateur (Controllers, Forms, Templates)
    ├── Controller/      # Contrôleurs HTTP
    ├── Form/            # Formulaires Symfony
    └── Command/         # Commandes CLI
```

#### Pourquoi DDD pour ce projet ?

**Avantages concrets observés** :

1. **Testabilité** : La logique métier (Domain) est isolée et testable sans dépendances externes
2. **Évolutivité** : Ajout de nouveaux widgets sans toucher au cœur métier
3. **Clarté** : Chaque couche a une responsabilité unique et explicite
4. **Maintenabilité** : Changement de base de données ou d'ORM possible sans impact sur le Domain

---

### Démarche Test-Driven Development (TDD)

**Principe appliqué** : "Red → Green → Refactor"

Red : 
- Écrire un test qui décrit le comportement attendu. 
- Lancer les tests → le test échoue (normal). 
- Vérifier que l’échec est bien lié au comportement non implémenté. 

Green:
- Implémenter le minimum de code nécessaire pour faire passer le test.
- Ne pas chercher l’optimisation ou la perfection à ce stade.
- Tous les tests doivent passer.

Refactor:
- Améliorer la structure du code sans modifier le comportement.
- Supprimer les duplications.
- Améliorer la lisibilité et la maintenabilité.
- Relancer les tests à chaque modification.

#### Tests unitaires (Domain & Application)
Objectif : valider la logique métier et les cas d’usage indépendamment des frameworks.

Domain (cœur métier):
- Tests des entités, des Value Objects, agrégats
- Vérification des règles métier
- Gestion des exceptions métier

Avec aucune dépendance externe (pas de base de données, pas d’API)

Application (Use Cases) : 
- Tests des cas d’usage
- Vérification des interactions, événements...
- Validation des scénarios métier : Succès / échec


#### Tests fonctionnels (UI)

Objectif : valider les parcours utilisateur complets.

Tests de bout en bout :
- Simulation d’actions utilisateur :
- Remplissage de formulaire
- Navigation
- Soumission

Vérification :
- Affichage des messages d’erreur
- États de chargement

Tests sur parcours critiques :
- Authentification
- Création / modification de données

Priorité aux scénarios métier clés plutôt qu'au composants visuels.

#### Couverture de tests (estimation)

### Tests Unitaires

**Résultats actuels** :

| Couche | Couverture | Tests | Assertions |
|--------|------------|-------|------------|
| **Domain** | ~90% | 24 tests | 89 assertions |
| **Application** | ~80% | 14 tests | 45 assertions |
| **Infrastructure** | Non testé | Non prioritaire |
| **UI** | Non testé |  Non prioritaire |

**Total** : 38 tests unitaires, 134 assertions

---

### Tests Fonctionnels

**Statut** : Prévus mais non implémentés

Méthode prévue : Les tests fonctionnels devaient utiliser une base de données SQLite en mémoire pour tester les parcours utilisateurs complets sans polluer la base de développement.

#### Approche technique

1. Setup automatique : Création du schéma en mémoire avant chaque test
2. Fixtures de test : Service dédié pour créer des utilisateurs/dashboards de test
3. Isolation complète: Chaque test repart d'une base vierge
4. Performance : Tests rapides (~50-100ms par test) grâce à l'utilisation de la RAM

#### Tests fonctionnels prévus

- Login complet (email + 2FA)
- Ajout/suppression de widgets
- API `/api/dashboard/{email}` 
- Interface admin (création/modification users)
- Téléchargement PDF liste de course

Couverture estimée : ~10 tests fonctionnels supplémentaires

---

### Pourquoi les tests fonctionnels n'ont pas été implémentés ?

#### Contrainte temporelle

- Délai: 1 semaine pour l'ensemble du projet
- Priorités : Architecture DDD, sécurité 2FA, interface complète
- Arbitrage : Tests unitaires (logique métier critique) **>** Tests fonctionnels

---

## Sécurité

### Authentification & Autorisation

#### 1. Hashage des mots de passe
Pourquoi bcrypt ?
- Algorithme éprouvé (résistant au bruteforce)
- Coût computationnel ajustable
- Bruit (Salt) automatique intégré

#### 2. Authentification à double facteur (2FA)
Flux complet :
1. Utilisateur entre email + mot de passe
2. Si valide → Génération d'un code à 6 chiffres
3. Envoi par email (expiration : 10 minutes)
4. Utilisateur entre le code
5. Si valide → Session créée

**Avantages** :
- Protection contre le vol de mot de passe
- Code à usage unique (supprimé après validation)
- Expiration automatique (10 min)

#### 3. Protection anti-bruteforce

**Mécanisme** : Blocage temporaire après 5 tentatives échouées

Avantages :
- Limite les attaques par dictionnaire
- Déblocage automatique après 15 minutes
- Réinitialisation du compteur après login réussi

#### 4. Gestion des rôles

**Deux rôles principaux** :
```php
ROLE_USER  → Utilisateur normal (accès dashboard)
ROLE_ADMIN → Administrateur (accès gestion users)
```

**Protection des routes** :
```php
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController {
    // Accessible uniquement aux admins
}
```

#### 5. Validation des entrées

Toutes les entrées sont soumis à validations. Par exemple, un code-barres doit respecter l’expression régulière suivante : /^\d{8,13}$/ (8 à 13 chiffres uniquement).

**Exemple : Validation du code-barres**
```php
// Application/Dashboard/Handler/AddWidgetHandler.php
if ($configuration['barcode'] && !preg_match('/^\d{8,13}$/', $barcode)) {
    throw new InvalidArgumentException('Code-barres invalide');
}
```

**Tous les formulaires utilisent la validation Symfony** :
Un autre moyen de valider les données consiste à utiliser les outils proposés par Symfony, notamment le composant Validator.

Celui-ci permet de définir des contraintes directement au niveau des entités, des formulaires ou des DTO afin de garantir l’intégrité des données avant leur traitement.


```php
// UI/Form/LoginType.php
$builder
    ->add('email', EmailType::class, [
        'constraints' => [
            new NotBlank(),
            new Email(),
        ],
    ])
    ->add('password', PasswordType::class, [
        'constraints' => [
            new NotBlank(),
            new Length(['min' => 8]),
        ],
    ]);
```

---

## Installation et Démarrage

### Prérequis

- **Docker Desktop** (Windows/Mac) ou **Docker Engine** (Linux)
- **Docker Compose** v2+ (viens avec Docker Desktop généralement)
- **Git** (facultatif : pour clonne le projet)

Pas besoin de PHP, Composer, Node.js ou PostgreSQL en local ! Tout est conteneurisé.

---

### Installation
```bash
# 1. Cloner le repository
git clone https://github.com/elmaghoum/MMM-Open-Food-Facts.git
cd MMM-Open-Food-Facts

# 2. Configurer dans son fichier .env l'adresse mail émettrice pour l'envoie du code 2FA : 
# Dans le .env modifier la variable 'MAILER_DSN' et enregister.

# 3. Démarrer l'application 
docker compose up -d --build

# 4. Attendre 15-20 secondes (démarrage de postgresql + migrations automatiques)

# 5. Créer les classes public/build pour (re)charger le JS/CSS
docker compose exec app npm run dev

# 6. Créer le premier administrateur - Changer l'email et le mot de passe par le votre. Exemple :
docker compose exec app php bin/console app:create-admin noreply.mieuxmangerenmarne@gmail.com AdminPassword123!

# 7. Ouvrir l'application via un naviagateur
# http://localhost:8000
```


**REMARQUE** : Utiliser un vrai mail existant ou une boite mail temporaire comme TempMail (https://temp-mail.org/fr/) lors de la création de votre compte car un vrai mail vous sera envoyé pour le code de confirmation (2FA). Vous en aurez besoin pour vous connecter.


---

### Accès aux services

| Service | URL | Identifiants (exemple) |
|---------|-----|--------------|
| **Application** | http://localhost:8000 | noreply.mieuxmangerenmarne@gmail.com / AdminPassword123! |
| **PostgreSQL** | localhost:5432 | mmm_user / mmm_password |


---

### Commandes annexes utiles
```bash
# Voir les logs en temps réel
docker compose logs -f app

# Arrêter l'application
docker compose down

# Redémarrer (sans rebuild)
docker compose up -d

# Rebuild complet (après modif Dockerfile)
docker compose down
docker compose up -d --build

# Vider le cache Symfony
docker compose exec app php bin/console cache:clear

# Active le rechargement automatique (hot reload), utile pour recharger le JS/CSS
docker compose exec app npm run dev

# Créer un utilisateur normal est possible via l'interface d'administration où par la commande suivante :
docker compose exec app php bin/console app:create-admin user@mmm.com UserPass123!

# Accéder au shell du container "app"
docker compose exec app sh

# Reset complet (attention cela supprime la BDD)
docker compose down -v
docker compose up -d --build
```

---

## Tests

### Exécution des tests
```bash
# Tests unitaires (Domain)
docker compose exec app php bin/phpunit tests/Domain

# Tests fonctionnels (UI)
docker compose exec app php bin/phpunit tests/UI

# Tous les tests
docker compose exec app php bin/phpunit

# Tests avec couverture
docker compose exec app php bin/phpunit --coverage-html var/coverage
```

### Structure des tests
```
tests/
├── Domain/
│   ├── Dashboard/
│   │   └── Entity/
│   │       ├── DashboardTest.php
│   │       └── WidgetTest.php
│   └── Identity/
│       └── Entity/
│           └── UserTest.php
│
├── Application/
│   ├── Dashboard/
│   │   └── Handler/
│   │       └── AddWidgetHandlerTest.php
│   └── Identity/
│       └── Handler/
│           └── LoginUserHandlerTest.php
│
└── UI/
    └── Controller/
        ├── LoginControllerTest.php
        └── DashboardControllerTest.php
```

---

## API Publique
La documentation de l'API exposé est contenue dans le fichier **Documentation_API.md** dans ce même depôt.

---

## Choix Techniques

### Stack Technique

| Composant | Technologie | Version | Justification |
|-----------|-------------|---------|---------------|
| **Backend** | Symfony | 8.0 | Framework PHP moderne, robuste, documentation complète |
| **Serveur Web** | FrankenPHP | 1.3 | Serveur PHP moderne avec worker mode (performance) + hot reload natif |
| **Base de données** | PostgreSQL | 16 | SGBD relationnel fiable, support UUID natif, JSON |
| **ORM** | Doctrine | 3.2 | Mapping objet-relationnel puissant, migrations automatiques |
| **Frontend** | JS + Stimulus | - | Léger, progressif, pas de framework lourd (contrainte projet) |
| **CSS** | Tailwind CSS | 3.4 | Utility-first, rapide à prototyper, cohérence visuelle |
| **Design System** | Shadcn UI (adapté) | - | Design moderne, composants réutilisables en Twig |
| **Build Tool** | Webpack Encore | - | Intégration Symfony, gestion assets (JS/CSS) |
| **Conteneurisation** | Docker Compose | - | Environnement reproductible, isolation, portabilité |

---

### Pourquoi FrankenPHP ?

FrankenPHP est un serveur d'application PHP moderne.

**Avantages par rapport à PHP-FPM + Nginx** :

1. Performance : Worker mode (application PHP chargée en mémoire, pas de bootstrap à chaque requête)
2. Hot Reload: Rechargement automatique du code en dev (pas de rebuild Docker)
3. HTTP/2 & HTTP/3: Support natif des protocoles modernes
4. TTPS automatique : Certificats Let's Encrypt intégrés
5. Simplicité : Un seul binaire, pas de configuration complexe Nginx

**Configuration minimaliste** :
```caddy
# docker/frankenphp/Caddyfile
:80 {
    root * /app/public
    php_server  # C'est tout !
}
```

---

### Pourquoi PostgreSQL ?

**Choix assumé face à MySQL/MariaDB** :

1. Support UUID natif : Type `uuid` en base (vs `CHAR(36)` ou `BINARY(16)` en MySQL)
2. Types avancés : JSON, JSONB (requêtes sur JSON), ARRAY, etc.
3. Conformité SQL : Respect strict des standards
4. Grande communauté et maturité : projet open-source mature avec une large communauté active et une forte documentation complète
5. Extensibilité : Extensions (pg_trgm pour recherche full-text, PostGIS pour géolocalisation future). Mon choix permettra d’anticiper des évolutions futures sans changer de base de données.
6. Performances : Excellent pour lectures complexes, la concurrence élevée, les requêtes complexes et les jointures volumineuses.

**Exemple d'utilisation du type JSON** :
```php
// Stockage de la configuration widget en JSON natif
#[ORM\Column(type: 'json')]
private array $configuration = [];
```

---

### Pourquoi Shadcn UI sans React ?

Problème : Shadcn UI est conçu pour React, mais React était interdit (contrainte projet).

Ma solution : Reproduction manuelle du design system en composants Twig réutilisables.

**Approche** :

1. Utilisation de Tailwind CSS (comme Shadcn UI)
2. Reproduction des variables CSS Shadcn (couleurs, espacements, etc.)
3. Création de composants Twig (Button, Card, Input, Alert, Badge, etc.)
4. Réutilisation via `{% include %}` et `{% embed %}`

**Exemple** :
```twig
{# templates/components/ui/button.html.twig #}
<button class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
    {{ label }}
</button>

{# Utilisation #}
{% include 'components/ui/button.html.twig' with {label: 'Cliquer'} %}
```

**Avantages** :

- Cohérence visuelle (même design que Shadcn UI)
- Composants réutilisables
- Pas de JavaScript lourd 
- SSR natif (Twig côté serveur)

---

### Stimulus.js : 

Pourquoi Stimulus au lieu de vanilla JS pur ?

1. Structure claire (`data-controller`, `data-action`)
2. Réutilisabilité : Contrôleurs attachables à n'importe quel élément HTML
3. Léger : ~30 KB 

---

### Architecture DDD : Pourquoi sur un petit projet ?

Question légitime : L'approche DDD est souvent associée à de gros projets. Pourquoi l'utiliser ici ?

Réponses : Après avoir travaillé sur ce test technique avec cette méthode je peux déjà en tirer quelques conclusions :

1. Démonstration de compétences : Le test technique vise à évaluer la capacité à structurer proprement du code
2. Évolutivité : Facilite l'ajout de fonctionnalités (nouveaux widgets, nouvelles sources de données)
3. Testabilité : Séparation Domain/Infrastructure permet de tester la logique métier sans BDD
4. Clarté : Chaque partie étant bien séparée, chaque développeur sait où mettre son code (adapté à du travail en équipe).

**Exemple concret de gain** :

```
Besoin de changer de BDD (PostgreSQL → MongoDB) ?

→ Seulement Infrastructure/Persistence à modifier
→ Domain & Application restent inchangés

Besoin d'ajouter un widget "Comparaison Prix" ?

→ Nouvelle enum dans Domain/Dashboard/ValueObject/WidgetType
→ Nouveau template dans templates/dashboard/widgets/
→ Aucune modification du système de dashboard existant
```

---

### Gestion des sessions : Pourquoi pas de Redis ?

Question : Redis est souvent recommandé pour les sessions. Pourquoi ne pas l'utiliser ici ?

Réponse : Allez au plus simple dans les délais donnés.

-  Sessions en base de données (via Doctrine)
-  Pas de serveur supplémentaire à gérer (Docker Compose simplifié)
-  Performances suffisantes pour l'échelle du projet (< 1000 users simultanés)
-  En production avec + de trafic → L'ajout de Redis serait pertinent

### UI des pages

Question : Pourquoi avoir utilisé Tailwind et du CSS inline ?

Réponse : Même principe : Allez au plus simple dans les délais donnés.

- Mise en place ultra rapide sans configuration CSS complexe
- Design cohérent grâce aux classes utilitaires prêtes à l’emploi
- Moins de fichiers à maintenir (pas de surcharge avec plusieurs feuilles CSS)
- Idéal pour un projet à deadline courte ou pour un proof of concept.
- Facilement refactorisable par la suite vers une structure plus modulaire

En production à plus grande échelle → Possibilité d’extraire vers des composants dédiés et un CSS mieux structuré

---

##  Structure du Projet
```
mmm/
├── assets/                      # Frontend assets
│   ├── controllers/             # Stimulus.js controllers
│   ├── js/
│   │   ├── dashboard.js         # Logique dashboard (drag&drop, modals)
│   │   └── utils.js             # Helpers (cn() pour Tailwind merge)
│   └── styles/
│       └── app.css              # Tailwind + Variables Shadcn UI
│
├── config/                      # Configuration Symfony
│   ├── packages/
│   ├── routes/
│   └── services.yaml
│
├── docker/                      # Configuration Docker
│   └── frankenphp/
│       ├── Dockerfile           # Image PHP 8.4 + FrankenPHP
│       └── Caddyfile            # Configuration serveur web
│
├── migrations/                  # Migrations Doctrine
│
├── public/                      # Point d'entrée web
│   ├── index.php
│   └── build/                   # Assets compilés (généré)
│
├── src/
│   ├── Domain/                  # Cœur métier (logique pure)
│   │   ├── Dashboard/
│   │   │   ├── Entity/
│   │   │   │   ├── Dashboard.php
│   │   │   │   └── Widget.php
│   │   │   ├── Repository/
│   │   │   │   └── DashboardRepositoryInterface.php
│   │   │   └── ValueObject/
│   │   │       ├── WidgetType.php
│   │   │       └── WidgetPosition.php
│   │   └── Identity/
│   │       ├── Entity/
│   │       │   ├── User.php
│   │       │   ├── TwoFactorCode.php
│   │       │   └── LoginAttempt.php
│   │       ├── Repository/
│   │       │   ├── UserRepositoryInterface.php
│   │       │   └── TwoFactorCodeRepositoryInterface.php
│   │       └── Exception/
│   │           └── UserBlockedException.php
│   │
│   ├── Application/             # Cas d'usage (orchestration)
│   │   ├── Dashboard/
│   │   │   ├── Command/
│   │   │   │   ├── AddWidgetCommand.php
│   │   │   │   └── RemoveWidgetCommand.php
│   │   │   ├── Handler/
│   │   │   │   ├── AddWidgetHandler.php
│   │   │   │   └── RemoveWidgetHandler.php
│   │   │   └── DTO/
│   │   │       └── AddWidgetResult.php
│   │   └── Identity/
│   │       ├── Command/
│   │       │   ├── LoginUserCommand.php
│   │       │   └── ValidateTwoFactorCommand.php
│   │       ├── Handler/
│   │       │   ├── LoginUserHandler.php
│   │       │   └── ValidateTwoFactorHandler.php
│   │       └── DTO/
│   │           └── LoginResult.php
│   │
│   ├── Infrastructure/          # Implémentations techniques
│   │   ├── Doctrine/
│   │   │   └── Repository/
│   │   │       ├── UserRepository.php
│   │   │       ├── DashboardRepository.php
│   │   │       └── TwoFactorCodeRepository.php
│   │   ├── Security/
│   │   │   ├── UserAdapter.php
│   │   │   ├── UserProvider.php
│   │   │   └── TwoFactorSessionStorage.php
│   │   ├── Mail/
│   │   │   └── TwoFactorMailer.php
│   │   └── EventSubscriber/
│   │       └── LoginEventSubscriber.php
│   │
│   └── UI/                      # Interface utilisateur
│       ├── Controller/
│       │   ├── HomeController.php
│       │   ├── LoginController.php
│       │   ├── TwoFactorController.php
│       │   ├── DashboardController.php
│       │   ├── AdminController.php
│       │   └── Api/
│       │       └── DashboardApiController.php
│       ├── Form/
│       │   ├── LoginType.php
│       │   └── TwoFactorType.php
│       └── Command/
│           └── CreateAdminCommand.php
│
├── templates/                   # Templates Twig
│   ├── base.html.twig
│   ├── home/
│   ├── login/
│   ├── two_factor/
│   ├── dashboard/
│   ├── admin/
│   └── components/
│       └── ui/                  # Composants Shadcn UI en Twig
│           ├── button.html.twig
│           ├── card.html.twig
│           ├── input.html.twig
│           ├── alert.html.twig
│           ├── badge.html.twig
│           └── table.html.twig
│
├── tests/                       # Tests (PHPUnit)
│   ├── Domain/
│   ├── Application/
│   └── UI/
│
├── var/                         # Cache, logs, sessions
│
├── .env                         # Variables d'environnement
├── compose.yaml                 # Docker Compose
├── composer.json                # Dépendances PHP
├── package.json                 # Dépendances Node
├── phpunit.xml.dist             # Configuration tests
├── symfony.lock                 # Lock Symfony Flex
├── tailwind.config.js           # Configuration Tailwind
└── webpack.config.js            # Configuration Webpack Encore
```

---

##  Pour aller plus loin

### Améliorations futures

-  Authentification OAuth : Login via Google/Apple/Microsoft
-  Ajout de widgets plus complexes et/ou plus pertinents
-  Récupération des données plus efficace (accès à l'API d'openfoodfact avec des flux de requetes plus important, sans timeout)
-  Récuperation de plus d'informations sur les produits: Images...
-  Ajout le fait d'avoir plusieurs dashboard (via un système d'onglet)
-  Application mobile : Possibilité de scanner un code barre avec son téléphone
-  Cache Redis : Sessions + cache applicatif
-  CI/CD : GitHub Actions (tests auto, déploiement)
-  Monitoring : logs, erreurs, sur docker notamment 

---

## Auteur

**Contexte** : Test technique - Développeur fullstack junior - EL MAGHOUM Fayçal

**Délai** : 1 semaine

**Technologies maîtrisées** : Symfony, Docker, DDD, TDD, PostgreSQL

---

##  Licence
Ce projet est un test technique à des fins pédagogiques.
Données fournies par [Open Food Facts](https://fr.openfoodfacts.org)

---

