# CF7 Magic Wizard

> Transformez vos formulaires **Contact Form 7** en assistants multi-étapes élégants,
> responsives et entièrement personnalisables.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb3)
![License](https://img.shields.io/badge/license-GPLv2-green)

## Fonctionnalités

- **Multi-étapes natif CF7** : ajoutez simplement `[wizard-break]` entre vos blocs
  de champs pour découper le formulaire en étapes.
- **Nom + sous-titre par étape** (comme dans l'image d'inspiration).
- **Libellé personnalisé pour chaque bouton « Suivant »**.
- **Barre de progression** sous le formulaire (pourcentage de complétion).
- **Indicateur visuel** avec cercles, numéros et lignes de connexion.
- **Couleurs 100% personnalisables** (cercles, lignes, boutons, barre de progression)
  globalement ou par formulaire.
- **Responsive** : adapté tablette, mobile et très petits écrans.
- **Validation HTML5** à chaque étape avant de passer à la suivante.
- **Accessible** : navigation clavier, ARIA, `prefers-reduced-motion`.
- **Mises à jour automatiques via GitHub Releases** (comme `beaubot` et `crm-cf7-connector`).

## Installation

1. Téléchargez le ZIP de la dernière
   [release GitHub](https://github.com/lebeaudigital/cf7-magin-wizard/releases/latest)
2. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**
3. Activez le plugin
4. Assurez-vous que **Contact Form 7** est aussi activé

## Utilisation

### 1. Découper le formulaire

Dans l'éditeur CF7 d'un formulaire, insérez le tag `[wizard-break]` entre les blocs
de champs qui doivent appartenir à des étapes différentes :

```text
<label> Votre prénom [text* first-name] </label>
<label> Votre e-mail [email* your-email] </label>

[wizard-break]

<label> Votre entreprise [text company] </label>
<label> Votre fonction  [text position] </label>

[wizard-break]

[textarea message]
[submit "Envoyer"]
```

### 2. Configurer les étapes

Dans l'éditeur CF7, ouvrez l'onglet **Magic Wizard** :

- Cochez **Activer le wizard multi-étapes pour ce formulaire**
- Renseignez le **nom**, le **sous-titre** facultatif et le **libellé du bouton « Suivant »**
  pour chaque étape
- Surchargez (optionnellement) les couleurs spécifiques à ce formulaire

### 3. Définir les couleurs globales

Allez dans **Contact → Magic Wizard** pour configurer la palette par défaut
(couleur des cercles, des lignes, des boutons et de la barre de progression),
ainsi que les libellés par défaut.

## Architecture du code

```text
cf7-magic-wizard/
├── cf7-magic-wizard.php            # Bootstrap du plugin
├── includes/                        # Classes PHP cœur
│   ├── class-cf7mw-plugin.php       # Singleton principal
│   ├── class-cf7mw-settings.php     # Réglages globaux
│   ├── class-cf7mw-config.php       # Configuration par formulaire
│   ├── class-cf7mw-cf7-integration.php  # Tag [wizard-break]
│   ├── class-cf7mw-frontend.php     # Rendu (wrap CF7)
│   ├── class-cf7mw-admin.php        # Page settings + panneau éditeur
│   └── class-cf7mw-updater.php      # Mises à jour GitHub
├── api/                             # Endpoints REST (classes PHP)
│   ├── class-cf7mw-api-routes.php   # Routeur principal
│   ├── class-cf7mw-api-settings.php # /cf7mw/v1/settings
│   └── class-cf7mw-api-forms.php    # /cf7mw/v1/forms/{id}/config
├── templates/admin/                 # Templates PHP de l'admin
│   ├── editor-panel.php
│   └── settings-page.php
└── assets/
    ├── css/
    │   ├── frontend.css             # Styles publics (responsive)
    │   └── admin.css                # Styles admin
    └── js/
        ├── api/                     # Wrapper fetch + endpoints
        │   ├── http.js
        │   ├── settings-api.js
        │   └── forms-api.js
        ├── frontend/                # Un fichier ES6 par fonctionnalité
        │   ├── wizard.js            # Orchestrateur
        │   ├── splitter.js
        │   ├── navigation.js
        │   ├── validation.js
        │   ├── indicator.js
        │   └── progress.js
        └── admin/
            ├── color-picker.js
            ├── steps-manager.js
            └── settings.js
```

### Endpoints REST

| Méthode | Route                                    | Description                          |
|---------|------------------------------------------|--------------------------------------|
| GET     | `/wp-json/cf7mw/v1/settings`             | Récupère les réglages globaux        |
| PUT     | `/wp-json/cf7mw/v1/settings`             | Met à jour les réglages globaux      |
| POST    | `/wp-json/cf7mw/v1/settings/reset`       | Réinitialise les réglages globaux    |
| GET     | `/wp-json/cf7mw/v1/forms/{id}/config`    | Configuration multi-étapes d'un formulaire |
| PUT     | `/wp-json/cf7mw/v1/forms/{id}/config`    | Met à jour la configuration multi-étapes |

Toutes les routes requièrent la capability `wpcf7_edit_contact_forms`.

## Mises à jour

Le plugin se met à jour automatiquement comme un plugin du repository officiel
WordPress, dès qu'une nouvelle release est publiée sur GitHub avec un tag
de version supérieur (ex: `v1.0.1`).

Pour publier une nouvelle version :

```bash
git tag v1.0.1
git push origin v1.0.1
# Puis créer la release sur GitHub à partir du tag :
# https://github.com/lebeaudigital/cf7-magin-wizard/releases/new
```

Sur la page **Extensions** de chaque site, vous pouvez aussi cliquer sur
**Vérifier la MàJ** pour purger le cache et forcer la détection.

## Compatibilité

- WordPress 6.0+
- PHP 8.0+
- Contact Form 7 5.5+
- Tous les navigateurs modernes (ES6 modules)

## Licence

GPL v2 ou ultérieure.
