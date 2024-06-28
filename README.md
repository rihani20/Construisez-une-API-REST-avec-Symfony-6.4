# Construisez une API REST avec Symfony 6.4.

## Prérequis

Assurez-vous d'avoir installé les bundles suivants :

- `make` 
- `orm`
- `orm-fixtures`
- `serializer`
- `sensio/framework-extra-bundle`
- `symfony/validator`
- `doctrine/annotations`

## Étapes de Développement

### 1. Créer les Entités
- Créez les entités `Book` et `Author`.

### 2. Fixtures
- Créez des fixtures et chargez-les.

### 3. Développer l'API CRUD
- Développez une API CRUD pour gérer les entités.

### 4. Gestion des Erreurs
- Gérez les erreurs de l'API.

### 5. Validation
- Ajoutez des validations pour les entités.

### 6. Gestion des Exceptions
- Ajoutez un subscriber pour gérer les exceptions.

### 7. Ajout de l'Entité Utilisateur
- Créez l'entité `User`.

### 8. Authentification JWT
- Installez `lexik/jwt-authentication-bundle` et configurez-le.
- Gérez les droits d'accès.

### 9. Tests de l'API
- Testez votre API pour vérifier son bon fonctionnement.

### 10. Pagination
- Ajoutez une pagination à vos endpoints.

### 11. Système de Cache
- Mettez en place un système de cache pour améliorer les performances.

### 12. HATEOAS
- Installez `willdurand/hateoas-bundle`.

### 13. Sérialisation
- Si vous utilisez `JMS Serializer`, modifiez le contexte dans le contrôleur et utilisez les groupes.

### 14. Versionnage de l'API
- Implémentez un système de versionnage pour votre API.

### 15. Documentation de l'API
- Installez `nelmio/api-doc-bundle` pour documenter votre API.

### 16. Consommation d'une API Externe
- Configurez votre API pour interroger des API externes.

### 17. API Platform
- Installez et configurez `API Platform` pour simplifier le développement de votre API.
