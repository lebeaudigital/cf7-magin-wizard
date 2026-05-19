<?php
/**
 * Routeur REST principal du plugin.
 *
 * Centralise l'enregistrement des routes en déléguant aux classes
 * spécialisées (CF7MW_API_Settings, CF7MW_API_Forms).
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CF7MW_API_Routes {

    public const NAMESPACE = 'cf7mw/v1';

    private static ?CF7MW_API_Routes $instance = null;

    public static function get_instance(): CF7MW_API_Routes {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_routes(): void {
        CF7MW_API_Settings::get_instance()->register();
        CF7MW_API_Forms::get_instance()->register();
    }

    /**
     * Callback de permission partagé : seuls les utilisateurs autorisés
     * à éditer les formulaires CF7 peuvent consommer ces endpoints.
     */
    public static function permission_check(): bool {
        return current_user_can('wpcf7_edit_contact_forms');
    }
}
