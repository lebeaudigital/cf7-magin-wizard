<?php
/**
 * Page de réglages globaux du plugin (Contact > Magic Wizard).
 *
 * Variables disponibles :
 *   $settings array Réglages courants (clé => valeur)
 *   $nonce    string Nonce REST
 *   $rest_url string URL REST de l'endpoint /settings
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap cf7mw-admin">
    <h1><?php esc_html_e('CF7 Magic Wizard - Réglages globaux', 'cf7-magic-wizard'); ?></h1>
    <p><?php esc_html_e('Définissez la palette de couleurs et les libellés par défaut utilisés pour tous vos formulaires multi-étapes. Chaque formulaire peut surcharger ces valeurs dans son propre panneau « Magic Wizard ».', 'cf7-magic-wizard'); ?></p>

    <form data-cf7mw-settings-form onsubmit="return false;">

        <div class="cf7mw-card">
            <h2 class="cf7mw-card__title"><?php esc_html_e('Libellés par défaut', 'cf7-magic-wizard'); ?></h2>
            <div class="cf7mw-grid">
                <div class="cf7mw-field">
                    <label for="cf7mw_label_next"><?php esc_html_e('Bouton « Suivant »', 'cf7-magic-wizard'); ?></label>
                    <input id="cf7mw_label_next" type="text" name="label_next" value="<?php echo esc_attr($settings['label_next']); ?>">
                </div>
                <div class="cf7mw-field">
                    <label for="cf7mw_label_previous"><?php esc_html_e('Bouton « Précédent »', 'cf7-magic-wizard'); ?></label>
                    <input id="cf7mw_label_previous" type="text" name="label_previous" value="<?php echo esc_attr($settings['label_previous']); ?>">
                </div>
            </div>
        </div>

        <div class="cf7mw-card">
            <h2 class="cf7mw-card__title"><?php esc_html_e('Comportement', 'cf7-magic-wizard'); ?></h2>
            <div class="cf7mw-toggles">
                <label class="cf7mw-toggle">
                    <input type="checkbox" name="show_progress_bar" value="1" <?php checked(!empty($settings['show_progress_bar'])); ?>>
                    <?php esc_html_e('Afficher la barre de progression', 'cf7-magic-wizard'); ?>
                </label>
                <label class="cf7mw-toggle">
                    <input type="checkbox" name="show_step_numbers" value="1" <?php checked(!empty($settings['show_step_numbers'])); ?>>
                    <?php esc_html_e('Afficher les numéros d\'étape', 'cf7-magic-wizard'); ?>
                </label>
                <label class="cf7mw-toggle">
                    <input type="checkbox" name="show_step_names" value="1" <?php checked(!empty($settings['show_step_names'])); ?>>
                    <?php esc_html_e('Afficher les noms d\'étape', 'cf7-magic-wizard'); ?>
                </label>
                <label class="cf7mw-toggle">
                    <input type="checkbox" name="enable_validation" value="1" <?php checked(!empty($settings['enable_validation'])); ?>>
                    <?php esc_html_e('Activer la validation HTML5 à chaque étape', 'cf7-magic-wizard'); ?>
                </label>
                <label class="cf7mw-toggle">
                    <input type="checkbox" name="scroll_to_top_on_step" value="1" <?php checked(!empty($settings['scroll_to_top_on_step'])); ?>>
                    <?php esc_html_e('Faire défiler vers le haut au changement d\'étape', 'cf7-magic-wizard'); ?>
                </label>
            </div>
        </div>

        <div class="cf7mw-card">
            <h2 class="cf7mw-card__title"><?php esc_html_e('Couleurs par défaut', 'cf7-magic-wizard'); ?></h2>
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
                foreach ($color_fields as $key => $label) : ?>
                    <div class="cf7mw-color-field">
                        <label for="<?php echo esc_attr('cf7mw_' . $key); ?>"><?php echo esc_html($label); ?></label>
                        <input id="<?php echo esc_attr('cf7mw_' . $key); ?>"
                               class="cf7mw-color-input"
                               type="text"
                               name="<?php echo esc_attr($key); ?>"
                               value="<?php echo esc_attr($settings[$key] ?? ''); ?>"
                               data-default="<?php echo esc_attr(CF7MW_Settings::get_defaults()[$key] ?? ''); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cf7mw-actions">
            <button type="button" class="button button-primary" data-cf7mw-save>
                <?php esc_html_e('Enregistrer les réglages', 'cf7-magic-wizard'); ?>
            </button>
            <button type="button" class="button" data-cf7mw-reset>
                <?php esc_html_e('Réinitialiser aux valeurs par défaut', 'cf7-magic-wizard'); ?>
            </button>
            <span class="cf7mw-notice" data-cf7mw-notice style="display:none"></span>
        </div>

    </form>
</div>
