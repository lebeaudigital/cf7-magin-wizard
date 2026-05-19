<?php
/**
 * Endpoints REST pour la configuration multi-étapes d'un formulaire CF7.
 *
 *  GET  /cf7mw/v1/forms/(?P<id>\d+)/config  → configuration multi-étapes
 *  PUT  /cf7mw/v1/forms/(?P<id>\d+)/config  → mise à jour
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_API_Forms {

    private static ?CF7MW_API_Forms $instance = null;

    public static function get_instance(): CF7MW_API_Forms {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(): void {
        register_rest_route(CF7MW_API_Routes::NAMESPACE, '/forms/(?P<id>\d+)/config', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_config'],
                'permission_callback' => [$this, 'permission_check_form'],
                'args'                => [
                    'id' => ['type' => 'integer', 'required' => true],
                ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_config'],
                'permission_callback' => [$this, 'permission_check_form'],
                'args'                => [
                    'id' => ['type' => 'integer', 'required' => true],
                ],
            ],
        ]);
    }

    public function permission_check_form(WP_REST_Request $request): bool {
        $form_id = (int) $request['id'];
        return current_user_can('wpcf7_edit_contact_form', $form_id);
    }

    public function get_config(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request['id'];
        if (get_post_type($form_id) !== 'wpcf7_contact_form') {
            return new WP_REST_Response(['error' => 'invalid_form'], 404);
        }
        return new WP_REST_Response([
            'form_id' => $form_id,
            'config'  => CF7MW_Config::get($form_id),
        ], 200);
    }

    public function update_config(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request['id'];
        if (get_post_type($form_id) !== 'wpcf7_contact_form') {
            return new WP_REST_Response(['error' => 'invalid_form'], 404);
        }
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }
        CF7MW_Config::save($form_id, $payload);
        return new WP_REST_Response([
            'success' => true,
            'form_id' => $form_id,
            'config'  => CF7MW_Config::get($form_id),
        ], 200);
    }
}
