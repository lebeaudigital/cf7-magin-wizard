<?php
/**
 * Endpoints REST pour les réglages globaux (couleurs/libellés par défaut).
 *
 *  GET    /cf7mw/v1/settings    → retourne la configuration globale
 *  PUT    /cf7mw/v1/settings    → met à jour la configuration globale
 *  POST   /cf7mw/v1/settings/reset → réinitialise aux valeurs par défaut
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_API_Settings {

    private static ?CF7MW_API_Settings $instance = null;

    public static function get_instance(): CF7MW_API_Settings {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(): void {
        register_rest_route(CF7MW_API_Routes::NAMESPACE, '/settings', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [CF7MW_API_Routes::class, 'permission_check'],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_settings'],
                'permission_callback' => [CF7MW_API_Routes::class, 'permission_check'],
            ],
        ]);

        register_rest_route(CF7MW_API_Routes::NAMESPACE, '/settings/reset', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'reset_settings'],
            'permission_callback' => [CF7MW_API_Routes::class, 'permission_check'],
        ]);
    }

    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'settings' => CF7MW_Settings::get_all(),
            'defaults' => CF7MW_Settings::get_defaults(),
        ], 200);
    }

    public function update_settings(WP_REST_Request $request): WP_REST_Response {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }
        CF7MW_Settings::update($payload);
        return new WP_REST_Response([
            'success'  => true,
            'settings' => CF7MW_Settings::get_all(),
        ], 200);
    }

    public function reset_settings(WP_REST_Request $request): WP_REST_Response {
        update_option(CF7MW_OPTION_KEY, CF7MW_Settings::get_defaults());
        return new WP_REST_Response([
            'success'  => true,
            'settings' => CF7MW_Settings::get_all(),
        ], 200);
    }
}
