<?php
/**
 * Plugin Name: CF7 Magic Wizard
 * Plugin URI: https://github.com/lebeaudigital/cf7-magin-wizard
 * Description: Transformez vos formulaires Contact Form 7 en assistants multi-étapes, et affichez ou masquez des champs selon les réponses, sans écrire de code.
 * Version: 1.1.0
 * Author: Le Beau Digital
 * Author URI: https://lebeaudigital.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7-magic-wizard
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * GitHub Plugin URI: lebeaudigital/cf7-magin-wizard
 * GitHub Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CF7MW_VERSION', '1.1.0');
define('CF7MW_FILE', __FILE__);
define('CF7MW_DIR', plugin_dir_path(__FILE__));
define('CF7MW_URL', plugin_dir_url(__FILE__));
define('CF7MW_BASENAME', plugin_basename(__FILE__));
define('CF7MW_SLUG', 'cf7-magic-wizard');

// Clé de meta utilisée pour stocker la configuration multi-étapes sur chaque formulaire CF7.
define('CF7MW_META_KEY', '_cf7mw_config');

// Option WordPress contenant les couleurs globales par défaut.
define('CF7MW_OPTION_KEY', 'cf7mw_settings');

require_once CF7MW_DIR . 'includes/class-cf7mw-plugin.php';

/**
 * Démarre le plugin une fois WordPress prêt.
 */
function cf7mw_bootstrap(): void {
    CF7MW_Plugin::get_instance();
}

cf7mw_bootstrap();

register_activation_hook(__FILE__, ['CF7MW_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['CF7MW_Plugin', 'deactivate']);
