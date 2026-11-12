<?php
/**
 * Mise à jour automatique du plugin via les releases GitHub
 * (https://github.com/lebeaudigital/cf7-magin-wizard).
 *
 * Dès qu'une nouvelle release est publiée avec un tag de version supérieur
 * (ex: v1.0.1), une notification de mise à jour apparaît dans
 * Extensions → Mises à jour disponibles.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_Updater {

    private static ?CF7MW_Updater $instance = null;

    private string $slug;
    private string $plugin_file;
    private string $github_username;
    private string $github_repo;
    private string $github_api_url;
    private string $access_token;
    private array $plugin_data;
    private ?object $github_response = null;

    public static function get_instance(): CF7MW_Updater {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->slug            = CF7MW_SLUG;
        $this->plugin_file     = CF7MW_BASENAME;
        $this->github_username = 'lebeaudigital';
        $this->github_repo     = 'cf7-magin-wizard'; // Tel que fourni par l'utilisateur.
        $this->github_api_url  = 'https://api.github.com/repos/' . $this->github_username . '/' . $this->github_repo;
        $this->access_token    = ''; // Vide pour un repo public.

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data(CF7MW_FILE);

        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'post_install'], 10, 3);
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
        add_filter('plugin_action_links_' . CF7MW_BASENAME, [$this, 'plugin_action_links']);
        add_action('admin_init', [$this, 'handle_force_check']);
    }

    /* -----------------------------------------------------------------
     *  Récupération de la release GitHub
     * ----------------------------------------------------------------- */

    private function get_github_release(): ?object {
        if ($this->github_response !== null) {
            return $this->github_response;
        }

        $cached = get_transient('cf7mw_github_release');
        if ($cached !== false) {
            $this->github_response = $cached;
            return $this->github_response;
        }

        $url  = $this->github_api_url . '/releases/latest';
        $args = [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'CF7-Magic-Wizard-Updater',
            ],
        ];

        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            error_log('[CF7 Magic Wizard Updater] API Error: ' . $response->get_error_message());
            return null;
        }
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response));
        if (empty($data) || !isset($data->tag_name)) {
            return null;
        }

        set_transient('cf7mw_github_release', $data, 6 * HOUR_IN_SECONDS);
        $this->github_response = $data;
        return $this->github_response;
    }

    /* -----------------------------------------------------------------
     *  Hooks WordPress
     * ----------------------------------------------------------------- */

    public function check_update($transient) {
        if (!is_object($transient) || empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_github_release();
        if ($release === null) {
            return $transient;
        }

        $github_version  = ltrim((string) $release->tag_name, 'v');
        $current_version = $this->plugin_data['Version'] ?? CF7MW_VERSION;

        if (version_compare($github_version, $current_version, '>')) {
            $transient->response[$this->plugin_file] = (object) [
                'slug'         => $this->slug,
                'plugin'       => $this->plugin_file,
                'new_version'  => $github_version,
                'url'          => 'https://github.com/' . $this->github_username . '/' . $this->github_repo,
                'package'      => $this->get_download_url($release),
                'tested'       => '6.5',
                'requires_php' => '8.0',
                'compatibility' => new stdClass(),
            ];
        }

        return $transient;
    }

    private function get_download_url(object $release): string {
        if (!empty($release->assets)) {
            foreach ($release->assets as $asset) {
                if (str_ends_with((string) $asset->name, '.zip')) {
                    return (string) $asset->browser_download_url;
                }
            }
        }
        return (string) ($release->zipball_url ?? '');
    }

    public function plugin_info($result, $action, $args) {
        if (!is_string($action) || $action !== 'plugin_information') {
            return $result;
        }
        if (is_array($args)) {
            $args = (object) $args;
        }
        if (!is_object($args) || empty($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $release = $this->get_github_release();
        if ($release === null) {
            return $result;
        }
        $github_version = ltrim((string) $release->tag_name, 'v');

        return (object) [
            'name'           => $this->plugin_data['Name']       ?? 'CF7 Magic Wizard',
            'slug'           => $this->slug,
            'version'        => $github_version,
            'author'         => $this->plugin_data['Author']     ?? 'Le Beau Digital',
            'author_profile' => 'https://github.com/' . $this->github_username,
            'homepage'       => 'https://github.com/' . $this->github_username . '/' . $this->github_repo,
            'requires'       => '6.0',
            'tested'         => '6.5',
            'requires_php'   => '8.0',
            'last_updated'   => $release->published_at ?? '',
            'sections'       => [
                'description' => $this->plugin_data['Description'] ?? '',
                'changelog'   => $this->format_changelog($release),
            ],
            'download_link'  => $this->get_download_url($release),
        ];
    }

    private function format_changelog(object $release): string {
        $out = '<h4>' . esc_html((string) $release->tag_name) . '</h4>';
        if (!empty($release->body)) {
            $body = esc_html((string) $release->body);
            $body = preg_replace('/^### (.+)$/m', '<h5>$1</h5>', $body);
            $body = preg_replace('/^## (.+)$/m', '<h4>$1</h4>', $body);
            $body = preg_replace('/^# (.+)$/m', '<h3>$1</h3>', $body);
            $body = preg_replace('/^[\*-] (.+)$/m', '<li>$1</li>', $body);
            $out .= '<div>' . nl2br($body) . '</div>';
        }
        $out .= '<p><a href="' . esc_url((string) $release->html_url) . '" target="_blank">'
              . esc_html__('Voir la release sur GitHub', 'cf7-magic-wizard') . '</a></p>';
        return $out;
    }

    public function post_install($response, $hook_extra, $result) {
        if (!is_array($hook_extra) || !is_array($result)) {
            return $result;
        }
        global $wp_filesystem;
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $result;
        }
        $plugin_folder = WP_PLUGIN_DIR . '/' . $this->slug;
        if ($result['destination'] !== $plugin_folder) {
            $wp_filesystem->move($result['destination'], $plugin_folder);
            $result['destination'] = $plugin_folder;
        }
        activate_plugin($this->plugin_file);
        delete_transient('cf7mw_github_release');
        return $result;
    }

    public function plugin_row_meta($links, $file) {
        if (!is_array($links)) {
            $links = [];
        }
        if (!is_string($file) || $file !== $this->plugin_file) {
            return $links;
        }
        $links[] = '<a href="https://github.com/' . $this->github_username . '/' . $this->github_repo . '" target="_blank">' . __('GitHub', 'cf7-magic-wizard') . '</a>';
        $links[] = '<a href="https://github.com/' . $this->github_username . '/' . $this->github_repo . '/issues" target="_blank">' . __('Signaler un bug', 'cf7-magic-wizard') . '</a>';
        return $links;
    }

    public function plugin_action_links($links): array {
        if (!is_array($links)) {
            $links = [];
        }
        $settings = '<a href="' . esc_url(admin_url('admin.php?page=cf7-magic-wizard')) . '">' . esc_html__('Réglages', 'cf7-magic-wizard') . '</a>';
        array_unshift($links, $settings);

        $url = wp_nonce_url(
            add_query_arg('cf7mw_force_update', '1', admin_url('plugins.php')),
            'cf7mw_force_update'
        );
        $links[] = '<a href="' . esc_url($url) . '">' . esc_html__('Vérifier la MàJ', 'cf7-magic-wizard') . '</a>';
        return $links;
    }

    public function handle_force_check(): void {
        if (!isset($_GET['cf7mw_force_update'])) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'cf7mw_force_update')) {
            return;
        }
        if (!current_user_can('update_plugins')) {
            return;
        }
        delete_transient('cf7mw_github_release');
        delete_site_transient('update_plugins');

        add_action('admin_notices', function (): void {
            echo '<div class="notice notice-success is-dismissible"><p>'
               . esc_html__('Cache de mise à jour CF7 Magic Wizard purgé. Rechargez la page Extensions pour voir le résultat.', 'cf7-magic-wizard')
               . '</p></div>';
        });
    }
}
