=== CF7 Magic Wizard ===
Contributors: lebeaudigital
Tags: contact-form-7, multi-step, wizard, multistep, form
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transformez vos formulaires Contact Form 7 en assistants multi-étapes élégants, responsives et entièrement personnalisables.

== Description ==

CF7 Magic Wizard ajoute un mode multi-étapes à Contact Form 7 avec :

* Nom et sous-titre par étape
* Libellé personnalisé pour chaque bouton « Suivant »
* Indicateur visuel (cercles + numéros + lignes)
* Barre de progression sous le formulaire
* Couleurs entièrement personnalisables (globales ou par formulaire)
* Validation HTML5 à chaque étape
* Design responsive
* Mises à jour automatiques via GitHub Releases

== Installation ==

1. Téléversez le dossier `cf7-magic-wizard` dans `/wp-content/plugins/`.
2. Activez l'extension via le menu Extensions.
3. Allez dans Contact > Magic Wizard pour configurer les couleurs par défaut.
4. Dans chaque formulaire CF7, ouvrez l'onglet Magic Wizard pour activer le mode multi-étapes et nommer les étapes.

== Frequently Asked Questions ==

= Comment découper le formulaire en étapes ? =

Insérez le tag `[wizard-break]` dans le contenu de votre formulaire entre les blocs de champs.

= Puis-je avoir des couleurs différentes par formulaire ? =

Oui : chaque formulaire peut surcharger les couleurs globales.

== Changelog ==

= 1.0.1 =
* Auto-activation du wizard dès qu'au moins un `[wizard-break]` est présent dans le formulaire (plus besoin de cocher manuellement la case).
* Filet de sécurité : `[wizard-break]` non interprété est désormais remplacé automatiquement par le marqueur HTML.
* Double inscription du form tag sur `wpcf7_init` et `init` pour les thèmes chargeant CF7 tardivement.

= 1.0.0 =
* Version initiale : multi-étapes, indicateur, barre de progression, couleurs personnalisables, responsive, updater GitHub.
