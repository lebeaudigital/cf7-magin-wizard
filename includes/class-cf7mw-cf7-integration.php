<?php
/**
 * Intégration avec Contact Form 7 : enregistre le tag personnalisé
 * [wizard-break] utilisé pour découper le formulaire en étapes.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_CF7_Integration {

    /**
     * Marqueur HTML injecté dans le formulaire pour identifier les coupures
     * d'étapes côté frontend.
     */
    public const STEP_BREAK_MARKER = '<!--cf7mw-step-break-->';

    private static ?CF7MW_CF7_Integration $instance = null;

    public static function get_instance(): CF7MW_CF7_Integration {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Enregistrer le tag CF7 [wizard-break].
        add_action('wpcf7_init', [$this, 'register_form_tag']);

        // Compter les étapes à partir du contenu du formulaire.
        add_filter('cf7mw_count_steps_from_content', [$this, 'count_steps_from_content'], 10, 1);
    }

    /**
     * Enregistre le tag [wizard-break] auprès de CF7.
     */
    public function register_form_tag(): void {
        if (!function_exists('wpcf7_add_form_tag')) {
            return;
        }
        wpcf7_add_form_tag(
            ['wizard-break', 'wizard-break/'],
            [self::class, 'render_form_tag'],
            ['name-attr' => false]
        );
    }

    /**
     * Rend le tag CF7 [wizard-break] sous forme de commentaire HTML
     * que le frontend pourra détecter pour découper le formulaire.
     */
    public static function render_form_tag($tag): string {
        return self::STEP_BREAK_MARKER;
    }

    /**
     * Compte le nombre de blocs [wizard-break] dans le contenu d'un formulaire CF7.
     */
    public function count_steps_from_content(string $content): int {
        $breaks = preg_match_all('/\[wizard-break\b[^\]]*\]/i', $content);
        if ($breaks === false || $breaks === 0) {
            return 1;
        }
        return $breaks + 1;
    }
}
