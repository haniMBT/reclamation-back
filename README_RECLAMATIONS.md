# 📋 Système de Gestion des Réclamations Clients

## 🎯 Vue d'ensemble

Ce système permet aux utilisateurs authentifiés de soumettre des réclamations avec la possibilité d'ajouter plusieurs fichiers joints de manière progressive.

## 🗂️ Structure Backend

### 📁 Fichiers créés

```
epal-crm-api/
├── routes/
│   └── api_reclamation.php                          # Routes pour les réclamations
├── app/
│   ├── Http/Controllers/ReclamationClient/
│   │   └── ReclamationController.php                # Contrôleur principal
│   └── Models/ReclamationClient/
│       ├── Reclamation.php                          # Modèle réclamation
│       └── FichierClient.php                        # Modèle fichier client
├── database/migrations/
│   ├── 2025_07_31_145047_create_t_rec_reclamation_table.php
│   └── 2025_07_31_145054_create_t_rec_fichiers_client_table.php
└── storage/app/public/reclamations/                 # Stockage des fichiers
```

### 🗄️ Structure de base de données

#### Table `t_rec_reclamation`
- `id` (Primary Key)
- `objet` (string) - Objet de la réclamation
- `contenu` (text) - Contenu détaillé
- `user_id` (foreign key) - Utilisateur créateur
- `statut` (enum) - nouvelle, en_cours, traitee, fermee
- `date_creation` (timestamp)
- `date_traitement` (timestamp, nullable)
- `reponse` (text, nullable)
- `traite_par` (foreign key, nullable)

#### Table `t_rec_fichiers_client`
- `id` (Primary Key)
- `reclamation_id` (foreign key) → t_rec_reclamation.id
- `nom_original` (string) - Nom original du fichier
- `nom_stockage` (string) - Nom sur le serveur
- `chemin` (string) - Chemin de stockage
- `taille` (bigint) - Taille en octets
- `type_mime` (string) - Type MIME
- `date_upload` (timestamp)

## 🔧 API Endpoints

### POST `/api/reclamation`

**Protection :** `auth:sanctum` + `check.token.expiration`

**Paramètres :**
- `objet` (required|string|max:255) - Objet de la réclamation
- `contenu` (required|string) - Contenu de la réclamation
- `fichiers.*` (nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,txt|max:10MB)

**Réponse de succès :**
```json
{
    "success": true,
    "message": "Réclamation créée avec succès.",
    "data": {
        "reclamation_id": 1,
        "objet": "Problème avec le service",
        "statut": "nouvelle",
        "date_creation": "2025-07-31T14:50:00.000000Z",
        "nombre_fichiers": 2
    }
}
```

**Réponse d'erreur :**
```json
{
    "success": false,
    "message": "Erreur de validation",
    "errors": {
        "objet": ["L'objet de la réclamation est obligatoire."],
        "fichiers.0": ["Le fichier fourni n'est pas valide."]
    }
}
```

## 🎨 Frontend (Vue.js/Quasar)

### 📄 Composant modifié

**Fichier :** `epal-crm-ui/src/pages/reclamation/Index.vue`

### ✨ Nouvelles fonctionnalités

1. **Ajout progressif de fichiers**
   - Zone de sélection séparée pour nouveaux fichiers
   - Bouton "Ajouter" pour valider la sélection
   - Liste cumulative des fichiers sélectionnés

2. **Gestion des fichiers**
   - Affichage du nom et de la taille
   - Icônes selon le type de fichier
   - Suppression individuelle possible

3. **Intégration API**
   - Envoi via FormData
   - Gestion des erreurs de validation Laravel
   - Notifications utilisateur appropriées

### 🔧 Utilisation

```vue
<template>
  <!-- Zone d'ajout progressif -->
  <q-file v-model="newFiles" multiple @update:model-value="onNewFilesSelected" />
  <q-btn @click="addFiles" :disable="!newFiles">Ajouter</q-btn>
  
  <!-- Liste des fichiers -->
  <div v-for="(file, index) in allFiles" :key="index">
    {{ file.name }} - {{ formatFileSize(file.size) }}
    <q-btn @click="removeFile(index)">Supprimer</q-btn>
  </div>
</template>
```

## 🔒 Sécurité

### ✅ Mesures implémentées

1. **Authentification obligatoire** (`auth:sanctum`)
2. **Vérification expiration token** (`check.token.expiration`)
3. **Validation stricte des fichiers**
   - Types autorisés : jpg, jpeg, png, pdf, doc, docx, txt
   - Taille max : 10 MB par fichier
4. **Transactions de base de données** (rollback en cas d'erreur)
5. **Noms de fichiers uniques** (prévention des conflits)
6. **Nettoyage automatique** en cas d'erreur d'upload

## 🚀 Déploiement

### 1. Migration des tables
```bash
cd epal-crm-api
php artisan migrate
```

### 2. Permissions de stockage
```bash
# Vérifier que le dossier existe et est accessible en écriture
chmod -R 755 storage/app/public/reclamations
```

### 3. Lien symbolique (si nécessaire)
```bash
php artisan storage:link
```

## 🧪 Tests

### Test Manuel Frontend
1. Se connecter à l'application
2. Aller sur la page réclamations
3. Remplir le formulaire (objet + contenu)
4. Ajouter des fichiers progressivement
5. Vérifier l'envoi et la réponse

### Test API Direct
```bash
curl -X POST http://localhost:8000/api/reclamation \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: multipart/form-data" \
  -F "objet=Test réclamation" \
  -F "contenu=Contenu de test" \
  -F "fichiers[0]=@/path/to/file1.pdf" \
  -F "fichiers[1]=@/path/to/file2.jpg"
```

## 📊 Monitoring

### Logs à surveiller
- `storage/logs/laravel.log` - Erreurs générales
- Erreurs de validation de fichiers
- Échecs de transactions de base de données

### Métriques importantes
- Taille moyenne des fichiers uploadés
- Nombre de réclamations par utilisateur
- Temps de traitement des uploads

## 🔄 Évolutions possibles

1. **Gestion des réponses aux réclamations**
2. **Système de notifications**
3. **Interface d'administration pour traiter les réclamations**
4. **Historique des modifications**
5. **Export des données en PDF/Excel**
6. **API de consultation des réclamations** 
