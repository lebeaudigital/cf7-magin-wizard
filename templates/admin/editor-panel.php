<?php
/**
 * Panneau « Magic Wizard » dans l'éditeur Contact Form 7.
 *
 * Variables disponibles :
 *   $form     WPCF7_ContactForm
 *   $config   array Configuration multi-étapes du formulaire
 *   $settings array Réglages globaux du plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

$steps    = !empty($config['steps']) ? $config['steps'] : [];
$colors   = !empty($config['colors']) ? $config['colors'] : [];
$enabled  = !empty($config['enabled']);
$form_id = $form->id();
$form_id = (is_numeric($form_id) && (int) $form_id > 0) ? (int) $form_id : 0;
?>
<div class="cf7mw-admin">

    <?php wp_nonce_field('cf7mw_save_' . $form_id, 'cf7mw_nonce'); ?>

    <div class="cf7mw-card">
        <h3 class="cf7mw-card__title"><?php esc_html_e('Activation du mode multi-étapes', 'cf7-magic-wizard'); ?></h3>
        <p class="cf7mw-card__desc">
            <?php esc_html_e('Ajoutez le tag', 'cf7-magic-wizard'); ?>
            <code>[wizard-break]</code>
            <?php esc_html_e('dans votre formulaire pour séparer les étapes. Configurez ensuite ci-dessous le nom et le libellé de bouton de chaque étape.', 'cf7-magic-wizard'); ?>
        </p>
        <label class="cf7mw-toggle">
            <input type="checkbox" name="cf7mw[enabled]" value="1" <?php checked($enabled); ?>>
            <?php esc_html_e('Activer le wizard multi-étapes pour ce formulaire', 'cf7-magic-wizard'); ?>
        </label>
    </div>

    <div class="cf7mw-card" data-cf7mw-steps-root>
        <h3 class="cf7mw-card__title"><?php esc_html_e('Étapes du formulaire', 'cf7-magic-wizard'); ?></h3>
        <p class="cf7mw-card__desc">
            <?php esc_html_e('Le nombre d\'étapes doit correspondre au nombre de blocs séparés par', 'cf7-magic-wizard'); ?>
            <code>[wizard-break]</code>
            (= <?php esc_html_e('nombre de séparateurs', 'cf7-magic-wizard'); ?> + 1).
            <?php esc_html_e('Glissez les étapes pour les réordonner.', 'cf7-magic-wizard'); ?>
        </p>
        <ol class="cf7mw-steps-list" data-cf7mw-steps-list>
            <?php if (empty($steps)) : ?>
                <?php // Pré-remplir avec 3 étapes par défaut quand rien n'a été configuré ?>
                <?php for ($i = 0; $i < 3; $i++) :
                    $default = CF7MW_Config::default_step($i + 1); ?>
                    <li class="cf7mw-step-row" draggable="true">
                        <span class="cf7mw-step-row__handle" aria-hidden="true">≡</span>
                        <input type="text" name="cf7mw[steps][<?php echo $i; ?>][name]"
                               value="<?php echo esc_attr($default['name']); ?>"
                               placeholder="<?php esc_attr_e('Nom de l\'étape', 'cf7-magic-wizard'); ?>">
                        <input type="text" name="cf7mw[steps][<?php echo $i; ?>][subtitle]"
                               value=""
                               placeholder="<?php esc_attr_e('Sous-titre (facultatif)', 'cf7-magic-wizard'); ?>">
                        <input type="text" name="cf7mw[steps][<?php echo $i; ?>][button]"
                               value="<?php echo esc_attr($default['button']); ?>"
                               placeholder="<?php esc_attr_e('Libellé du bouton', 'cf7-magic-wizard'); ?>">
                        <button type="button" class="cf7mw-step-row__remove"><?php esc_html_e('Supprimer', 'cf7-magic-wizard'); ?></button>
                    </li>
                <?php endfor; ?>
            <?php else : ?>
                <?php foreach ($steps as $i => $step) : ?>
                    <li class="cf7mw-step-row" draggable="true">
                        <span class="cf7mw-step-row__handle" aria-hidden="true">≡</span>
                        <input type="text" name="cf7mw[steps][<?php echo $i; ?>][name]"
                               value="<?php echo esc_attr($step['name'] ?? ''); ?>"
                               placeholder="<?php esc_attr_e('Nom de l\'étape', 'cf7-magic-wizard'); ?>">
                        <input type="text" name="cf7mw[steps][<?php echo $i; ?>][subtitle]"
                               value="<?php echo esc_attr($step['subtitle'] ?? ''); ?>"
                               placeholder="<?php esc_attr_e('Sous-titre (facultatif)', 'cf7-magic-wizard'); ?>">
                        <input type="text" name="cf7mw[steps][<?php echo $i; ?>][button]"
                               value="<?php echo esc_attr($step['button'] ?? ''); ?>"
                               placeholder="<?php esc_attr_e('Libellé du bouton', 'cf7-magic-wizard'); ?>">
                        <button type="button" class="cf7mw-step-row__remove"><?php esc_html_e('Supprimer', 'cf7-magic-wizard'); ?></button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ol>
        <button type="button" class="button cf7mw-add-step" data-cf7mw-add-step>
            <?php esc_html_e('+ Ajouter une étape', 'cf7-magic-wizard'); ?>
        </button>
        <p class="cf7mw-help">
            <?php esc_html_e('La dernière étape conserve le bouton', 'cf7-magic-wizard'); ?>
            <code>[submit]</code>
            <?php esc_html_e('défini dans votre formulaire ; son libellé y est ignoré.', 'cf7-magic-wizard'); ?>
        </p>
    </div>

    <div class="cf7mw-card">
        <h3 class="cf7mw-card__title"><?php esc_html_e('Affichage', 'cf7-magic-wizard'); ?></h3>
        <div class="cf7mw-toggles">
            <label class="cf7mw-toggle">
                <input type="checkbox" name="cf7mw[show_progress_bar]" value="1" <?php checked(CF7MW_Config::resolve_bool($config, 'show_progress_bar')); ?>>
                <?php esc_html_e('Afficher la barre de progression', 'cf7-magic-wizard'); ?>
            </label>
            <label class="cf7mw-toggle">
                <input type="checkbox" name="cf7mw[show_step_numbers]" value="1" <?php checked(CF7MW_Config::resolve_bool($config, 'show_step_numbers')); ?>>
                <?php esc_html_e('Afficher les numéros d\'étape', 'cf7-magic-wizard'); ?>
            </label>
            <label class="cf7mw-toggle">
                <input type="checkbox" name="cf7mw[show_step_names]" value="1" <?php checked(CF7MW_Config::resolve_bool($config, 'show_step_names')); ?>>
                <?php esc_html_e('Afficher les noms d\'étape', 'cf7-magic-wizard'); ?>
            </label>
        </div>
    </div>

    <div class="cf7mw-card">
        <h3 class="cf7mw-card__title"><?php esc_html_e('Couleurs (surcharges pour ce formulaire)', 'cf7-magic-wizard'); ?></h3>
        <p class="cf7mw-card__desc">
            <?php esc_html_e('Laissez un champ vide pour utiliser la couleur globale définie dans Contact > Magic Wizard.', 'cf7-magic-wizard'); ?>
        </p>
        <div class="cf7mw-colors-grid">
            <?php
            $color_fields = [
                'color_circle_active'    => __('Cercle - étape active', 'cf7-magic-wizard'),
                'color_circle_done'      => __('Cercle - étape complétée', 'cf7-magic-wizard'),
                'color_circle_inactive'  => __('Cercle - étape à venir', 'cf7-magic-wizard'),
                'color_line_active'      => __('Ligne - active', 'cf7-magic-wizard'),
                'color_line_inactive'    => __('Ligne - inactive', 'cf7-magic-wizard'),
                'color_text_active'      => __('Texte - étape active', 'cf7-magic-wizard'),
                'color_text_inactive'    => __('Texte - étape inactive', 'cf7-magic-wizard'),
                'color_button_bg'        => __('Bouton Suivant - fond', 'cf7-magic-wizard'),
                'color_button_text'      => __('Bouton Suivant - texte', 'cf7-magic-wizard'),
                'color_button_bg_hover'  => __('Bouton Suivant - survol', 'cf7-magic-wizard'),
                'color_button_prev_bg'   => __('Bouton Précédent - fond', 'cf7-magic-wizard'),
                'color_button_prev_text' => __('Bouton Précédent - texte', 'cf7-magic-wizard'),
                'color_progress_bar'     => __('Barre de progression - couleur', 'cf7-magic-wizard'),
                'color_progress_track'   => __('Barre de progression - piste', 'cf7-magic-wizard'),
            ];
            foreach ($color_fields as $key => $label) :
                $val      = $colors[$key] ?? '';
                $fallback = $settings[$key] ?? '';
                ?>
                <div class="cf7mw-color-field">
                    <label for="<?php echo esc_attr('cf7mw_' . $key); ?>"><?php echo esc_html($label); ?></label>
                    <input type="text"
                           id="<?php echo esc_attr('cf7mw_' . $key); ?>"
                           class="cf7mw-color-input"
                           name="cf7mw[colors][<?php echo esc_attr($key); ?>]"
                           value="<?php echo esc_attr($val); ?>"
                           data-default="<?php echo esc_attr($fallback); ?>"
                           data-alpha-enabled="false">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
