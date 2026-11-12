<?php
/**
 * Configuration multi-étapes spécifique à un formulaire CF7.
 *
 * Stockée en post meta (clé CF7MW_META_KEY) sur le post type wpcf7_contact_form.
 *
 * Structure :
 *  [
 *    'enabled' => bool,
 *    'steps'   => [
 *        ['name' => 'Détails du groupe', 'subtitle' => '', 'button' => 'Suivant'],
 *        ...
 *    ],
 *    'colors'  => [...overrides...], // facultatif, sinon on utilise les couleurs globales
 *    'show_progress_bar' => bool,
 *    'show_step_numbers' => bool,
 *    'show_step_names'   => bool,
 *  ]
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Config {

    /**
     * Charge la configuration d'un formulaire CF7.
     *
     * Un formulaire neuf (WPCF7_ContactForm::get_template()) n'a pas encore
     * d'identifiant : id() vaut null. Dans ce cas on renvoie la configuration
     * par défaut, sans lire de post meta.
     *
     * @return array<string,mixed>
     */
    public static function get(?int $form_id): array {
        $form_id = self::form_id($form_id);
        if ($form_id === 0) {
            return self::with_defaults([]);
        }

        $raw = get_post_meta($form_id, CF7MW_META_KEY, true);
        if (!is_array($raw)) {
            $raw = [];
        }
        return self::with_defaults($raw);
    }

    /**
     * Enregistre la configuration d'un formulaire CF7 (avec assainissement).
     *
     * Sans identifiant numérique, aucune meta n'est écrite.
     *
     * @param array<string,mixed> $config
     */
    public static function save(?int $form_id, array $config): bool {
        $form_id = self::form_id($form_id);
        if ($form_id === 0) {
            return false;
        }

        $clean = self::sanitize($config);
        return (bool) update_post_meta($form_id, CF7MW_META_KEY, $clean);
    }

    /**
     * Identifiant de post utilisable. 0 = formulaire pas encore enregistré.
     */
    private static function form_id(?int $form_id): int {
        if (!is_numeric($form_id) || (int) $form_id <= 0) {
            return 0;
        }
        return (int) $form_id;
    }

    /**
     * Fusionne la configuration brute avec des valeurs par défaut.
     *
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public static function with_defaults(array $config): array {
        $defaults = [
            'enabled'           => false,
            'steps'             => [],
            'colors'            => [],
            'show_progress_bar' => null,
            'show_step_numbers' => null,
            'show_step_names'   => null,
        ];
        $merged = array_merge($defaults, $config);
        $merged['steps']  = is_array($merged['steps'])  ? array_values($merged['steps']) : [];
        $merged['colors'] = is_array($merged['colors']) ? $merged['colors'] : [];
        return $merged;
    }

    /**
     * Retourne une étape par défaut.
     *
     * @return array<string,string>
     */
    public static function default_step(int $index): array {
        return [
            'name'     => sprintf(__('Étape %d', 'cf7-magic-wizard'), $index),
            'subtitle' => '',
            'button'   => CF7MW_Settings::get('label_next', __('Suivant', 'cf7-magic-wizard')),
        ];
    }

    /**
     * Nettoie une configuration avant enregistrement.
     *
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public static function sanitize(array $config): array {
        $clean = [];

        $clean['enabled'] = !empty($config['enabled']) ? 1 : 0;

        $steps = $config['steps'] ?? [];
        if (!is_array($steps)) {
            $steps = [];
        }

        $clean['steps'] = [];
        $i = 1;
        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }
            $clean['steps'][] = [
                'name'     => sanitize_text_field((string) ($step['name']     ?? sprintf(__('Étape %d', 'cf7-magic-wizard'), $i))),
                'subtitle' => sanitize_text_field((string) ($step['subtitle'] ?? '')),
                'button'   => sanitize_text_field((string) ($step['button']   ?? CF7MW_Settings::get('label_next'))),
            ];
            $i++;
        }

        // Couleurs spécifiques au formulaire (peuvent surcharger les couleurs globales).
        $colors = $config['colors'] ?? [];
        $clean['colors'] = [];
        if (is_array($colors)) {
            foreach ($colors as $key => $value) {
                if (!str_starts_with((string) $key, 'color_')) {
                    continue;
                }
                $value = trim((string) $value);
                if ($value === '') {
                    continue; // vide = on hérite du global
                }
                $clean['colors'][sanitize_key($key)] = CF7MW_Settings::sanitize_color($value, '#000000');
            }
        }

        foreach (['show_progress_bar', 'show_step_numbers', 'show_step_names'] as $bool_key) {
            $clean[$bool_key] = !empty($config[$bool_key]) && (string) $config[$bool_key] !== '0' ? 1 : 0;
        }

        return $clean;
    }

    /**
     * Fusionne les couleurs globales avec celles spécifiques au formulaire.
     *
     * @param array<string,mixed> $config Sortie de self::get()
     * @return array<string,string>
     */
    public static function resolve_colors(array $config): array {
        $colors = [];
        foreach (CF7MW_Settings::get_all() as $key => $value) {
            if (str_starts_with($key, 'color_')) {
                $colors[$key] = (string) $value;
            }
        }
        if (!empty($config['colors']) && is_array($config['colors'])) {
            foreach ($config['colors'] as $k => $v) {
                if ($v !== '' && $v !== null) {
                    $colors[$k] = (string) $v;
                }
            }
        }
        return $colors;
    }

    /**
     * Résout une option booléenne (form override → global).
     */
    public static function resolve_bool(array $config, string $key): bool {
        if (array_key_exists($key, $config) && $config[$key] !== null) {
            return (bool) $config[$key];
        }
        return (bool) CF7MW_Settings::get($key, 1);
    }
}
