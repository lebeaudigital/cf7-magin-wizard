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
        // Enregistrer le tag CF7 [wizard-break] dès que possible, et redonder
        // sur 'init' pour tenir compte des thèmes qui chargent CF7 tardivement.
        add_action('wpcf7_init', [$this, 'register_form_tag']);
        add_action('init',       [$this, 'register_form_tag'], 20);

        // Compter les étapes à partir du contenu du formulaire.
        add_filter('cf7mw_count_steps_from_content', [$this, 'count_steps_from_content'], 10, 1);
    }

    /**
     * Enregistre le tag [wizard-break] auprès de CF7. Idempotent : on ne
     * réenregistre pas si CF7 le connaît déjà.
     */
    public function register_form_tag(): void {
        if (!function_exists('wpcf7_add_form_tag')) {
            return;
        }

        // Évite la double inscription (qui déclencherait un notice CF7).
        if (class_exists('WPCF7_FormTagsManager')) {
            $manager = WPCF7_FormTagsManager::get_instance();
            if (method_exists($manager, 'tag_type_supported') && $manager->tag_type_supported('wizard-break')) {
                return;
            }
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
     * Nombre de tags [wizard-break] dans le modèle du formulaire.
     */
    public static function count_breaks(string $content): int {
        $breaks = preg_match_all('/\[wizard-break\b[^\]]*\]/i', $content);
        return ($breaks === false) ? 0 : (int) $breaks;
    }

    /**
     * Nombre d'étapes : un séparateur crée une coupure, donc N séparateurs = N + 1 étapes.
     * Sans séparateur, le formulaire n'est pas découpé (0).
     */
    public static function count_steps(string $content): int {
        $breaks = self::count_breaks($content);
        return $breaks > 0 ? $breaks + 1 : 0;
    }

    /**
     * Compte le nombre de blocs [wizard-break] dans le contenu d'un formulaire CF7.
     */
    public function count_steps_from_content(string $content): int {
        $steps = self::count_steps($content);
        return $steps > 0 ? $steps : 1;
    }
}
