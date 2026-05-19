<?php
/**
 * Frontend : enregistre les assets, ajoute l'indicateur d'étapes et la barre
 * de progression autour du formulaire CF7, et expose la configuration au JS.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Frontend {

    private static ?CF7MW_Frontend $instance = null;

    /** Formulaires déjà rencontrés sur la page (pour ne charger qu'une fois). */
    private bool $assets_enqueued = false;

    public static function get_instance(): CF7MW_Frontend {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Enregistrer (mais ne pas enqueuer) les assets.
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // Encapsuler le formulaire avec la structure du wizard.
        add_filter('wpcf7_form_elements', [$this, 'maybe_wrap_form_elements'], 9999);
        add_filter('wpcf7_form_class_attr', [$this, 'maybe_add_form_class'], 10);
    }

    /**
     * Enregistre les feuilles de style et scripts.
     */
    public function register_assets(): void {
        wp_register_style(
            'cf7-magic-wizard-frontend',
            CF7MW_URL . 'assets/css/frontend.css',
            [],
            CF7MW_VERSION
        );

        // On déclare le JS principal comme module ES6.
        wp_register_script(
            'cf7-magic-wizard-frontend',
            CF7MW_URL . 'assets/js/frontend/wizard.js',
            [],
            CF7MW_VERSION,
            true
        );
        // Charger comme module ES6 pour utiliser les imports.
        add_filter('script_loader_tag', [$this, 'mark_module_scripts'], 10, 3);
    }

    /**
     * Transforme nos scripts en modules ES6 (attribut type="module").
     */
    public function mark_module_scripts(string $tag, string $handle, string $src): string {
        if (!in_array($handle, ['cf7-magic-wizard-frontend'], true)) {
            return $tag;
        }
        if (str_contains($tag, ' type=')) {
            $tag = preg_replace('/ type=("|\')[^"\']*("|\')/', ' type="module"', $tag);
        } else {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }

    private function enqueue_assets(): void {
        if ($this->assets_enqueued) {
            return;
        }
        wp_enqueue_style('cf7-magic-wizard-frontend');
        wp_enqueue_script('cf7-magic-wizard-frontend');
        $this->assets_enqueued = true;
    }

    /**
     * Détermine si on doit activer le wizard pour le formulaire courant.
     *
     * @param int $form_id
     * @param string $html
     */
    private function should_activate(int $form_id, string $html): bool {
        $config = CF7MW_Config::get($form_id);

        // Cas 1 : le wizard est explicitement activé via la metabox.
        if (!empty($config['enabled'])) {
            return true;
        }

        // Cas 2 : auto-activation si l'utilisateur a juste écrit [wizard-break]
        //         sans encore avoir activé la case dans l'éditeur CF7.
        if ($this->html_contains_break_marker($html)) {
            return true;
        }
        return false;
    }

    private function html_contains_break_marker(string $html): bool {
        if (str_contains($html, CF7MW_CF7_Integration::STEP_BREAK_MARKER)) {
            return true;
        }
        // Texte brut [wizard-break] non interprété (filet de sécurité).
        return (bool) preg_match('/\[wizard-break\b[^\]]*\]/i', $html);
    }

    /**
     * Ajoute la classe CSS cf7mw-form au <form> CF7 quand le wizard est actif.
     */
    public function maybe_add_form_class(string $class_attr): string {
        $form = WPCF7_ContactForm::get_current();
        if (!$form) {
            return $class_attr;
        }
        // On ne peut pas inspecter le HTML rendu ici, on se base sur la config.
        $config = CF7MW_Config::get($form->id());
        if (empty($config['enabled'])) {
            // Inspecter le contenu brut pour détecter une auto-activation.
            $form_content = (string) $form->prop('form');
            if (stripos($form_content, '[wizard-break') === false) {
                return $class_attr;
            }
        }
        return trim($class_attr . ' cf7mw-form');
    }

    /**
     * Injecte la structure HTML du wizard autour des éléments du formulaire CF7.
     */
    public function maybe_wrap_form_elements(string $html): string {
        $form = WPCF7_ContactForm::get_current();
        if (!$form) {
            return $html;
        }
        $form_id = $form->id();

        if (!$this->should_activate($form_id, $html)) {
            return $html;
        }

        $config = CF7MW_Config::get($form_id);

        // Filet de sécurité : si CF7 n'a pas interprété [wizard-break] (plugin
        // chargé tardivement, version CF7 ancienne, cache), on substitue
        // manuellement par le marqueur HTML.
        $html = preg_replace(
            '#(?:<p[^>]*>\s*)?\[wizard-break\b[^\]]*\](?:\s*</p>)?#i',
            CF7MW_CF7_Integration::STEP_BREAK_MARKER,
            $html
        );

        // Si après tout cela il n'y a toujours pas de coupure, on abandonne.
        if (!str_contains($html, CF7MW_CF7_Integration::STEP_BREAK_MARKER)) {
            return $html;
        }

        $this->enqueue_assets();

        $steps_config = $this->normalize_steps_config($config['steps'], $html);
        $colors       = CF7MW_Config::resolve_colors($config);

        $show_progress = CF7MW_Config::resolve_bool($config, 'show_progress_bar');
        $show_numbers  = CF7MW_Config::resolve_bool($config, 'show_step_numbers');
        $show_names    = CF7MW_Config::resolve_bool($config, 'show_step_names');

        $instance_id = 'cf7mw-' . $form_id . '-' . wp_generate_uuid4();

        $indicator_html = $this->render_indicator($instance_id, $steps_config, $show_numbers, $show_names);
        $progress_html  = $show_progress ? $this->render_progress_bar($instance_id) : '';
        $css_vars       = $this->build_css_vars($colors);
        $config_json    = wp_json_encode([
            'instanceId'        => $instance_id,
            'steps'             => $steps_config,
            'showProgress'      => $show_progress,
            'enableValidation'  => (bool) CF7MW_Settings::get('enable_validation', 1),
            'scrollToTopOnStep' => (bool) CF7MW_Settings::get('scroll_to_top_on_step', 1),
            'labelPrevious'     => CF7MW_Settings::get('label_previous'),
            'breakMarker'       => CF7MW_CF7_Integration::STEP_BREAK_MARKER,
        ]);

        // Wrapper englobant le contenu, l'indicateur et la progression.
        $output  = '<div class="cf7mw-wrapper" id="' . esc_attr($instance_id) . '" style="' . esc_attr($css_vars) . '">';
        $output .= $indicator_html;
        $output .= '<div class="cf7mw-steps" data-cf7mw-steps>' . $html . '</div>';
        $output .= $progress_html;
        $output .= '<script type="application/json" class="cf7mw-config">' . $config_json . '</script>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Garantit qu'on dispose d'autant d'étapes configurées qu'il y a de blocs
     * dans le formulaire (sinon on complète avec des étapes par défaut).
     *
     * @param array<int,array<string,string>> $steps
     * @return array<int,array<string,string>>
     */
    private function normalize_steps_config(array $steps, string $rendered_html): array {
        $count_breaks = substr_count($rendered_html, CF7MW_CF7_Integration::STEP_BREAK_MARKER);
        $needed       = $count_breaks + 1;

        $normalized = [];
        for ($i = 0; $i < $needed; $i++) {
            $normalized[] = $steps[$i] ?? CF7MW_Config::default_step($i + 1);
        }
        return $normalized;
    }

    /**
     * Génère l'indicateur visuel d'étapes (cercles + noms + lignes).
     *
     * @param array<int,array<string,string>> $steps
     */
    private function render_indicator(string $instance_id, array $steps, bool $show_numbers, bool $show_names): string {
        $total = count($steps);
        $items = '';

        foreach ($steps as $index => $step) {
            $items .= '<li class="cf7mw-indicator__item' . ($index === 0 ? ' is-active' : '') . '" data-step="' . esc_attr((string) $index) . '">';

            $items .= '<div class="cf7mw-indicator__node">';
            $items .= '<div class="cf7mw-indicator__circle" aria-hidden="true">';
            if ($show_numbers) {
                $items .= '<span class="cf7mw-indicator__number">' . esc_html((string) ($index + 1)) . '</span>';
            }
            $items .= '<svg class="cf7mw-indicator__check" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4 4 10-10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            $items .= '</div>';
            $items .= '</div>';

            if ($show_names) {
                $items .= '<div class="cf7mw-indicator__label">';
                $items .= '<span class="cf7mw-indicator__name">' . esc_html($step['name']) . '</span>';
                if (!empty($step['subtitle'])) {
                    $items .= '<span class="cf7mw-indicator__subtitle">' . esc_html($step['subtitle']) . '</span>';
                }
                $items .= '</div>';
            }

            $items .= '</li>';
        }

        return '<nav class="cf7mw-indicator" aria-label="' . esc_attr__('Étapes du formulaire', 'cf7-magic-wizard') . '"><ol class="cf7mw-indicator__list" data-cf7mw-indicator>' . $items . '</ol></nav>';
    }

    private function render_progress_bar(string $instance_id): string {
        return '<div class="cf7mw-progress" data-cf7mw-progress role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">'
             . '<div class="cf7mw-progress__track">'
             . '<div class="cf7mw-progress__bar" data-cf7mw-progress-bar style="width:0%"></div>'
             . '</div>'
             . '<span class="cf7mw-progress__label" data-cf7mw-progress-label>0%</span>'
             . '</div>';
    }

    /**
     * Convertit la palette de couleurs en variables CSS injectées sur le wrapper.
     *
     * @param array<string,string> $colors
     */
    private function build_css_vars(array $colors): string {
        $map = [
            'color_circle_active'    => '--cf7mw-circle-active',
            'color_circle_done'      => '--cf7mw-circle-done',
            'color_circle_inactive'  => '--cf7mw-circle-inactive',
            'color_line_active'      => '--cf7mw-line-active',
            'color_line_inactive'    => '--cf7mw-line-inactive',
            'color_text_active'      => '--cf7mw-text-active',
            'color_text_inactive'    => '--cf7mw-text-inactive',
            'color_button_bg'        => '--cf7mw-btn-bg',
            'color_button_text'      => '--cf7mw-btn-text',
            'color_button_bg_hover'  => '--cf7mw-btn-bg-hover',
            'color_button_prev_bg'   => '--cf7mw-btn-prev-bg',
            'color_button_prev_text' => '--cf7mw-btn-prev-text',
            'color_progress_bar'     => '--cf7mw-progress-bar',
            'color_progress_track'   => '--cf7mw-progress-track',
        ];

        $css = [];
        foreach ($map as $key => $var) {
            if (!empty($colors[$key])) {
                $css[] = $var . ':' . $colors[$key];
            }
        }
        return implode(';', $css);
    }
}
