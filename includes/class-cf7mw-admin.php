<?php
/**
 * Administration : page de réglages globaux, panneau dans l'éditeur CF7,
 * tag generator pour insérer [wizard-break], enregistrement des assets admin.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Admin {

    private static ?CF7MW_Admin $instance = null;

    public static function get_instance(): CF7MW_Admin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Page de réglages globaux dans le menu Contact Form 7.
        add_action('admin_menu', [$this, 'register_settings_page'], 11);

        // Panneau dans l'éditeur CF7 (onglet supplémentaire).
        add_filter('wpcf7_editor_panels', [$this, 'register_editor_panel']);

        // Sauvegarde de la configuration au moment où CF7 enregistre le formulaire.
        add_action('wpcf7_save_contact_form', [$this, 'save_form_config']);

        // Tag generator pour insérer [wizard-break].
        add_action('wpcf7_admin_init', [$this, 'register_tag_generator'], 50);

        // Assets admin.
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /* -----------------------------------------------------------------
     *  Page de réglages globaux
     * ----------------------------------------------------------------- */

    public function register_settings_page(): void {
        add_submenu_page(
            'wpcf7',
            __('Magic Wizard - Réglages', 'cf7-magic-wizard'),
            __('Magic Wizard', 'cf7-magic-wizard'),
            'wpcf7_edit_contact_forms',
            'cf7-magic-wizard',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(): void {
        if (!current_user_can('wpcf7_edit_contact_forms')) {
            return;
        }
        $settings = CF7MW_Settings::get_all();
        $nonce    = wp_create_nonce('wp_rest');
        $rest_url = esc_url_raw(rest_url('cf7mw/v1/settings'));
        include CF7MW_DIR . 'templates/admin/settings-page.php';
    }

    /* -----------------------------------------------------------------
     *  Panneau dans l'éditeur CF7
     * ----------------------------------------------------------------- */

    /**
     * @param array<string,array<string,mixed>> $panels
     * @return array<string,array<string,mixed>>
     */
    public function register_editor_panel(array $panels): array {
        $panels['cf7mw-panel'] = [
            'title'    => __('Magic Wizard', 'cf7-magic-wizard'),
            'callback' => [$this, 'render_editor_panel'],
        ];
        $panels['cf7mw-conditions'] = [
            'title'    => __('Conditions', 'cf7-magic-wizard'),
            'callback' => [$this, 'render_conditions_panel'],
        ];
        return $panels;
    }

    /**
     * @param WPCF7_ContactForm $form
     */
    public function render_editor_panel($form): void {
        $config = CF7MW_Config::get(self::form_id($form));
        $settings = CF7MW_Settings::get_all();
        include CF7MW_DIR . 'templates/admin/editor-panel.php';
    }

    /**
     * @param WPCF7_ContactForm $form
     */
    public function render_conditions_panel($form): void {
        $config = CF7MW_Config::get(self::form_id($form));
        include CF7MW_DIR . 'templates/admin/conditions-panel.php';
    }

    /**
     * Enregistre la configuration depuis le panneau de l'éditeur CF7.
     *
     * @param WPCF7_ContactForm $form
     */
    public function save_form_config($form): void {
        $form_id = self::form_id($form);
        // Formulaire pas encore enregistré : le hook part avant l'attribution de l'ID.
        if ($form_id === 0) {
            return;
        }
        if (!isset($_POST['cf7mw_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cf7mw_nonce'])), 'cf7mw_save_' . $form_id)) {
            return;
        }
        if (!current_user_can('wpcf7_edit_contact_form', $form_id)) {
            return;
        }

        $raw = $_POST['cf7mw'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }

        // wp_unslash récursif sur les valeurs scalaires.
        $raw = wp_unslash($raw);

        CF7MW_Config::save($form_id, $raw);
    }

    /**
     * Identifiant numérique du formulaire, ou 0 s'il n'est pas encore créé.
     */
    private static function form_id($form): int {
        $form_id = (is_object($form) && method_exists($form, 'id')) ? $form->id() : 0;
        if (!is_numeric($form_id) || (int) $form_id <= 0) {
            return 0;
        }
        return (int) $form_id;
    }

    /* -----------------------------------------------------------------
     *  Tag generator pour [wizard-break]
     * ----------------------------------------------------------------- */

    public function register_tag_generator(): void {
        if (!class_exists('WPCF7_TagGenerator')) {
            return;
        }
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add(
            'wizard-break',
            __('séparateur d\'étape', 'cf7-magic-wizard'),
            [$this, 'render_tag_generator']
        );
    }

    public function render_tag_generator($contact_form, $args = ''): void {
        $description = esc_html__(
            'Insère un séparateur entre deux étapes du formulaire. Tout le contenu situé avant ce tag fera partie de l\'étape courante.',
            'cf7-magic-wizard'
        );
        $insert      = esc_attr__('Insérer le séparateur', 'cf7-magic-wizard');
        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo $description; ?></legend>
                <p><code>[wizard-break]</code></p>
            </fieldset>
        </div>
        <div class="insert-box">
            <input type="text" name="wizard-break" class="tag code" readonly onfocus="this.select()" value="[wizard-break]">
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo $insert; ?>">
            </div>
        </div>
        <?php
    }

    /* -----------------------------------------------------------------
     *  Assets admin
     * ----------------------------------------------------------------- */

    public function enqueue_admin_assets(string $hook): void {
        $current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id      = $current_screen?->id ?? '';

        $is_cf7_screen  = in_array($screen_id, ['toplevel_page_wpcf7', 'contact_page_wpcf7-new'], true)
            || str_starts_with($screen_id, 'toplevel_page_wpcf7')
            || str_starts_with($screen_id, 'contact_page_wpcf7');
        $is_settings_screen = ($hook === 'contact_page_cf7-magic-wizard');

        if (!$is_cf7_screen && !$is_settings_screen) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style(
            'cf7-magic-wizard-admin',
            CF7MW_URL . 'assets/css/admin.css',
            ['wp-color-picker'],
            CF7MW_VERSION
        );

        // Modules ES6.
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'cf7-magic-wizard-admin-color',
            CF7MW_URL . 'assets/js/admin/color-picker.js',
            ['jquery', 'wp-color-picker'],
            CF7MW_VERSION,
            true
        );
        wp_enqueue_script(
            'cf7-magic-wizard-admin-steps',
            CF7MW_URL . 'assets/js/admin/steps-manager.js',
            ['jquery'],
            CF7MW_VERSION,
            true
        );
        wp_enqueue_script(
            'cf7-magic-wizard-admin-conditions',
            CF7MW_URL . 'assets/js/admin/conditions-manager.js',
            [],
            CF7MW_VERSION,
            true
        );
        wp_enqueue_script(
            'cf7-magic-wizard-admin-settings',
            CF7MW_URL . 'assets/js/admin/settings.js',
            ['jquery'],
            CF7MW_VERSION,
            true
        );

        // Données pour les appels API REST.
        wp_localize_script(
            'cf7-magic-wizard-admin-settings',
            'CF7MW_AdminData',
            [
                'restUrl'  => esc_url_raw(rest_url('cf7mw/v1/')),
                'nonce'    => wp_create_nonce('wp_rest'),
                'i18n'     => [
                    'addStep'      => __('Ajouter une étape', 'cf7-magic-wizard'),
                    'removeStep'   => __('Supprimer', 'cf7-magic-wizard'),
                    'stepName'     => __('Nom de l\'étape', 'cf7-magic-wizard'),
                    'stepSubtitle' => __('Sous-titre (facultatif)', 'cf7-magic-wizard'),
                    'buttonLabel'  => __('Libellé du bouton', 'cf7-magic-wizard'),
                    'saved'        => __('Réglages enregistrés', 'cf7-magic-wizard'),
                    'error'        => __('Une erreur est survenue', 'cf7-magic-wizard'),
                    'defaultStep'  => __('Étape', 'cf7-magic-wizard'),
                    'defaultNext'  => CF7MW_Settings::get('label_next', __('Suivant', 'cf7-magic-wizard')),
                ],
            ]
        );

        // Charger nos scripts comme modules ES6 (sauf color-picker qui dépend de jQuery).
        add_filter('script_loader_tag', [$this, 'mark_module_scripts'], 10, 3);
    }

    public function mark_module_scripts(string $tag, string $handle, string $src): string {
        $module_handles = ['cf7-magic-wizard-admin-steps', 'cf7-magic-wizard-admin-settings', 'cf7-magic-wizard-admin-conditions'];
        if (!in_array($handle, $module_handles, true)) {
            return $tag;
        }
        if (str_contains($tag, ' type=')) {
            $tag = preg_replace('/ type=("|\')[^"\']*("|\')/', ' type="module"', $tag);
        } else {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }
}
