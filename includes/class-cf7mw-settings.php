<?php
/**
 * Gestion des réglages globaux du plugin (palette de couleurs par défaut,
 * libellés par défaut des boutons, etc.).
 *
 * Les valeurs sont stockées dans l'option WordPress CF7MW_OPTION_KEY.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Settings {

    /**
     * Couleurs et libellés par défaut.
     *
     * @return array<string,mixed>
     */
    public static function get_defaults(): array {
        return [
            // Couleurs de l'indicateur d'étapes.
            'color_circle_active'   => '#2563eb',
            'color_circle_done'     => '#2563eb',
            'color_circle_inactive' => '#e5e7eb',
            'color_line_active'     => '#2563eb',
            'color_line_inactive'   => '#e5e7eb',
            'color_text_active'     => '#2563eb',
            'color_text_inactive'   => '#6b7280',

            // Couleurs des boutons.
            'color_button_bg'       => '#2563eb',
            'color_button_text'     => '#ffffff',
            'color_button_bg_hover' => '#1d4ed8',
            'color_button_prev_bg'  => '#f3f4f6',
            'color_button_prev_text' => '#374151',

            // Couleurs de la barre de progression.
            'color_progress_bar'    => '#2563eb',
            'color_progress_track'  => '#e5e7eb',

            // Libellés par défaut.
            'label_next'            => __('Suivant', 'cf7-magic-wizard'),
            'label_previous'        => __('Précédent', 'cf7-magic-wizard'),

            // Comportement.
            'show_progress_bar'     => 1,
            'show_step_numbers'     => 1,
            'show_step_names'       => 1,
            'enable_validation'     => 1,
            'scroll_to_top_on_step' => 1,
        ];
    }

    /**
     * Retourne la configuration globale fusionnée avec les valeurs par défaut.
     *
     * @return array<string,mixed>
     */
    public static function get_all(): array {
        $stored = get_option(CF7MW_OPTION_KEY, []);
        if (!is_array($stored)) {
            $stored = [];
        }
        return array_merge(self::get_defaults(), $stored);
    }

    /**
     * Récupère une valeur unique.
     */
    public static function get(string $key, mixed $default = null): mixed {
        $all = self::get_all();
        return $all[$key] ?? $default;
    }

    /**
     * Met à jour les réglages globaux (le tableau est nettoyé).
     *
     * @param array<string,mixed> $values
     */
    public static function update(array $values): bool {
        $sanitized = self::sanitize($values);
        $merged    = array_merge(self::get_all(), $sanitized);
        return update_option(CF7MW_OPTION_KEY, $merged);
    }

    /**
     * Nettoie un tableau de réglages.
     *
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    public static function sanitize(array $values): array {
        $clean    = [];
        $defaults = self::get_defaults();

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $raw = $values[$key];

            if (str_starts_with($key, 'color_')) {
                $clean[$key] = self::sanitize_color((string) $raw, (string) $default);
                continue;
            }

            if (str_starts_with($key, 'label_')) {
                $clean[$key] = sanitize_text_field((string) $raw);
                continue;
            }

            if (in_array($key, ['show_progress_bar', 'show_step_numbers', 'show_step_names', 'enable_validation', 'scroll_to_top_on_step'], true)) {
                $clean[$key] = !empty($raw) ? 1 : 0;
                continue;
            }
        }
        return $clean;
    }

    /**
     * Vérifie qu'une chaîne est bien une couleur hex valide ; sinon retourne $fallback.
     */
    public static function sanitize_color(string $color, string $fallback = '#000000'): string {
        $color = trim($color);
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color)) {
            return $color;
        }
        return $fallback;
    }
}
