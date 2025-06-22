<?php
/**
 * Plugin Name:       Quantum Cache
 * Plugin URI:        https://example.com/quantum-cache
 * Description:       A comprehensive performance plugin to speed up your WordPress site with page caching, asset optimization, and more. Inspired by WP Rocket.
 * Version:           1.1.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quantum-cache
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'QUANTUM_CACHE_VERSION', '1.1.0' );
define( 'QUANTUM_CACHE_FILE', __FILE__ );
define( 'QUANTUM_CACHE_PATH', dirname( QUANTUM_CACHE_FILE ) );
define( 'QUANTUM_CACHE_URL', plugins_url( '', QUANTUM_CACHE_FILE ) );
define( 'QUANTUM_CACHE_INC_PATH', QUANTUM_CACHE_PATH . '/includes/' );
define( 'QUANTUM_CACHE_ADMIN_PATH', QUANTUM_CACHE_PATH . '/admin/' );
define( 'QUANTUM_CACHE_CACHE_PATH', WP_CONTENT_DIR . '/cache/quantum-cache/' );

class Quantum_Cache {
    protected static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once QUANTUM_CACHE_ADMIN_PATH . 'class-quantum-cache-admin.php';
        require_once QUANTUM_CACHE_INC_PATH . 'class-quantum-cache-caching.php';
        require_once QUANTUM_CACHE_INC_PATH . 'class-quantum-cache-assets.php';
        require_once QUANTUM_CACHE_INC_PATH . 'class-quantum-cache-database.php';
        require_once QUANTUM_CACHE_INC_PATH . 'class-quantum-cache-deactivator.php';
    }

    private function init_hooks() {
        register_activation_hook( QUANTUM_CACHE_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( QUANTUM_CACHE_FILE, array( 'Quantum_Cache_Deactivator', 'deactivate' ) );

        Quantum_Cache_Admin::get_instance();
        Quantum_Cache_Caching::get_instance();
        Quantum_Cache_Assets::get_instance();
        Quantum_Cache_Database::get_instance();
    }

    public function activate() {
        if ( ! is_dir( QUANTUM_CACHE_CACHE_PATH ) ) {
            wp_mkdir_p( QUANTUM_CACHE_CACHE_PATH );
        }
        $htaccess_file = QUANTUM_CACHE_CACHE_PATH . '.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $content = '<IfModule mod_authz_core.c>' . PHP_EOL . 'Require all denied' . PHP_EOL . '</IfModule>' . PHP_EOL . '<IfModule !mod_authz_core.c>' . PHP_EOL . 'Deny from all' . PHP_EOL . '</IfModule>';
            file_put_contents( $htaccess_file, $content );
        }
        $defaults = [
            'enable_page_caching' => 1,
            'minify_css' => 0,
            'defer_js' => 0,
            'lazy_load_images' => 1,
            'cdn_url' => '',
        ];
        add_option('quantum_cache_settings', $defaults);
    }
}

Quantum_Cache::get_instance();


