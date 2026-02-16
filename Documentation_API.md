## API Publique - Documentation

### Endpoint : Récupérer le dashboard d'un utilisateur

URL : `GET /api/dashboard/{email}`

Description : Récupère les informations utilisateur et la configuration complète de son dashboard (widgets + configuration).

Cas d'usage : Intégration tierce (application mobile, widget externe, export de données).

---

#### Requête

**Méthode** : `GET`

**URL** : `/api/dashboard/{email}`

**Paramètres** :

| Paramètre | Type | Requis | Description | 
|-----------|------|--------|-------------|
| `email` | string (email) | Oui | Email de l'utilisateur |

**Headers** :
```http
Accept: application/json
```

**Exemple CURL** :
```bash
curl -X GET "http://localhost:8000/api/dashboard/admin@mmm.com" \
  -H "Accept: application/json"
```

---

#### Réponses

##### Succès (200 OK)

**Condition** : L'utilisateur existe et possède un dashboard.

**Body** :
```json
{
  "success": true,
  "user": {
    "id": "018d1234-5678-9abc-def0-123456789abc",
    "email": "admin@mmm.com",
    "roles": ["ROLE_USER", "ROLE_ADMIN"],
    "is_active": true,
    "is_admin": true,
    "created_at": "2025-02-17T12:34:56+00:00"
  },
  "dashboard": {
    "id": "018d1234-5678-9abc-def0-987654321abc",
    "widgets_count": 3,
    "widgets": [
      {
        "id": "018d1234-aaaa-bbbb-cccc-111111111111",
        "type": "shopping_list",
        "row": 1,
        "column": 1,
        "configuration": {
          "barcodes": ["3017620422003", "3017620425003"]
        }
      },
      {
        "id": "018d1234-aaaa-bbbb-cccc-222222222222",
        "type": "nutriscore_comparison",
        "row": 1,
        "column": 2,
        "configuration": {
          "barcodes": ["3017620422003", "3017620425003", "3017620429001"]
        }
      },
      {
        "id": "018d1234-aaaa-bbbb-cccc-333333333333",
        "type": "product_search",
        "row": 2,
        "column": 1,
        "configuration": {
          "barcode": "3017620422003"
        }
      }
    ]
  }
}
```

**Schéma de réponse** :

| Champ | Type | Description |
|-------|------|-------------|
| `success` | boolean | Indicateur de succès (toujours `true` en 200) |
| `user` | object | Informations utilisateur |
| `user.id` | string (uuid) | Identifiant unique de l'utilisateur |
| `user.email` | string (email) | Email de l'utilisateur |
| `user.roles` | array<string> | Rôles Symfony (`ROLE_USER`, `ROLE_ADMIN`) |
| `user.is_active` | boolean | Compte activé ou désactivé |
| `user.is_admin` | boolean | L'utilisateur est-il admin ? |
| `user.created_at` | string (ISO 8601) | Date de création du compte |
| `dashboard` | object | Configuration du dashboard |
| `dashboard.id` | string (uuid) | Identifiant unique du dashboard |
| `dashboard.widgets_count` | integer | Nombre de widgets configurés |
| `dashboard.widgets` | array<object> | Liste des widgets |
| `dashboard.widgets[].id` | string (uuid) | Identifiant du widget |
| `dashboard.widgets[].type` | string (enum) | Type de widget (voir types ci-dessous) |
| `dashboard.widgets[].row` | integer (1-10) | Position verticale (ligne) |
| `dashboard.widgets[].column` | integer (1-2) | Position horizontale (colonne) |
| `dashboard.widgets[].configuration` | object | Configuration spécifique au widget |

---

##### Utilisateur non trouvé (404 Not Found)

**Condition** : L'email ne correspond à aucun utilisateur.

**Body** :
```json
{
  "error": "User not found",
  "email": "nonexistent@mmm.com"
}
```

---

##### Dashboard non trouvé (404 Not Found)

**Condition** : L'utilisateur existe mais n'a pas encore créé de dashboard.

**Body** :
```json
{
  "error": "Dashboard not found",
  "email": "user@mmm.com"
}
```

---

#### Types de widgets disponibles

| Type | Valeur enum | Description |
|------|-------------|-------------|
| Recherche Produit | `product_search` | Affichage d'un produit unique |
| Recherche Code-barres | `quick_barcode_search` | Recherche rapide par EAN |
| Comparaison Sucre & Sel | `sugar_salt_comparison` | Graphique comparatif (1-5 produits) |
| Comparaison Nutriscore | `nutriscore_comparison` | Comparaison scores nutritionnels |
| Comparaison NOVA | `nova_comparison` | Niveau de transformation |
| Graphique Nutritionnel | `nutrition_pie` | Répartition macronutriments |
| Liste de Course | `shopping_list` | Liste de produits favoris (max 20) |

---

#### Exemples de configuration par type de widget

**Shopping List** :
```json
{
  "type": "shopping_list",
  "configuration": {
    "barcodes": ["3017620422003", "3017620425003"]
  }
}
```

**Product Search** :
```json
{
  "type": "product_search",
  "configuration": {
    "barcode": "3017620422003"
  }
}
```

**Nutriscore Comparison** :
```json
{
  "type": "nutriscore_comparison",
  "configuration": {
    "barcodes": ["3017620422003", "3017620425003", "3017620429001"]
  }
}
```

---

#### Codes d'erreur

| Code HTTP | Signification | Exemple de réponse |
|-----------|---------------|---------------------|
| `200` | Succès | `{"success": true, ...}` |
| `404` | Ressource non trouvée | `{"error": "User not found", ...}` |
| `500` | Erreur serveur | `{"error": "Internal server error"}` |

---

#### Rate Limiting

Note : Aucune limite de taux n’est actuellement implémentée. Mais si je devais le mettre en production, alors je rajouterais bien évidemment :

- Rate limiting (ex : 100 requêtes/minute/IP)
- Quotas par utilisateur
- Logging et monitoring des requêtes suspectes

---

#### Évolutions futures possibles

- Authentification par token (JWT, OAuth2)
- Endpoint POST pour créer/modifier des widgets via API
- Endpoint DELETE pour supprimer des widgets
- Statistiques d'utilisation du dashboard
