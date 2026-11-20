<?php
/**
 * Classe principale (singleton) du plugin CF7 Magic Wizard.
 *
 * Charge les dépendances, enregistre les hooks, gère l'activation
 * et la désactivation, et expose des helpers globaux.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Plugin {

    private static ?CF7MW_Plugin $instance = null;

    public static function get_instance(): CF7MW_Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Charge toutes les classes du plugin.
     */
    private function load_dependencies(): void {
        // Helpers et modèles.
        require_once CF7MW_DIR . 'includes/class-cf7mw-settings.php';
        require_once CF7MW_DIR . 'includes/class-cf7mw-config.php';
        require_once CF7MW_DIR . 'includes/class-cf7mw-conditions.php';

        // Intégration Contact Form 7 (tag personnalisé, splitter).
        require_once CF7MW_DIR . 'includes/class-cf7mw-cf7-integration.php';

        // Frontend (rendu et assets).
        require_once CF7MW_DIR . 'includes/class-cf7mw-frontend.php';

        // Administration (metabox, settings page, tag generator).
        require_once CF7MW_DIR . 'includes/class-cf7mw-admin.php';

        // Endpoints REST.
        require_once CF7MW_DIR . 'api/class-cf7mw-api-routes.php';
        require_once CF7MW_DIR . 'api/class-cf7mw-api-settings.php';
        require_once CF7MW_DIR . 'api/class-cf7mw-api-forms.php';

        // Updater GitHub.
        require_once CF7MW_DIR . 'includes/class-cf7mw-updater.php';
    }

    /**
     * Branche le plugin aux hooks WordPress.
     */
    private function init_hooks(): void {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('plugins_loaded', [$this, 'maybe_warn_cf7_missing'], 20);

        // Intégration CF7 (uniquement si CF7 est actif).
        add_action('plugins_loaded', function (): void {
            if (!$this->is_cf7_active()) {
                return;
            }

            CF7MW_CF7_Integration::get_instance();
            CF7MW_Conditions::get_instance();
            CF7MW_Frontend::get_instance();

            if (is_admin()) {
                CF7MW_Admin::get_instance();
            }
        }, 30);

        // API REST (toujours chargée).
        add_action('rest_api_init', function (): void {
            CF7MW_API_Routes::get_instance()->register_routes();
        });

        // Updater (admin uniquement).
        if (is_admin()) {
            CF7MW_Updater::get_instance();
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'cf7-magic-wizard',
            false,
            dirname(CF7MW_BASENAME) . '/languages'
        );
    }

    /**
     * Affiche une notice si Contact Form 7 n'est pas activé.
     */
    public function maybe_warn_cf7_missing(): void {
        if ($this->is_cf7_active()) {
            return;
        }

        add_action('admin_notices', function (): void {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__(
                'CF7 Magic Wizard nécessite que l\'extension Contact Form 7 soit installée et activée.',
                'cf7-magic-wizard'
            );
            echo '</p></div>';
        });
    }

    public function is_cf7_active(): bool {
        return defined('WPCF7_VERSION') || class_exists('WPCF7');
    }

    /**
     * Routine exécutée à l'activation : initialise les options par défaut.
     */
    public static function activate(): void {
        require_once CF7MW_DIR . 'includes/class-cf7mw-settings.php';

        if (!get_option(CF7MW_OPTION_KEY)) {
            add_option(CF7MW_OPTION_KEY, CF7MW_Settings::get_defaults());
        }
    }

    public static function deactivate(): void {
        // Rien à nettoyer pour l'instant : on conserve les configurations utilisateur.
    }
}
