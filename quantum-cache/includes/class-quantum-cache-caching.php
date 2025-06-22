<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Quantum_Cache_Caching {
    protected static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $options = get_option('quantum_cache_settings');
        if ( ! empty($options['enable_page_caching']) ) {
            add_action( 'template_redirect', array( $this, 'start_caching' ), 9999 );
        }
    }

    private function should_cache() {
        return ! ( is_user_logged_in() || is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || $_SERVER['REQUEST_METHOD'] === 'POST' );
    }

    public function maybe_serve_cache() {
        if ( ! $this->should_cache() ) {
            return;
        }
        $cache_file = $this->get_cache_file_path();
        if ( file_exists( $cache_file ) && ( time() - filemtime( $cache_file ) ) < 36000 ) {
            readfile( $cache_file );
            exit;
        }
    }

    public function start_caching() {
        $this->maybe_serve_cache();
        if ( $this->should_cache() ) {
            ob_start( array( $this, 'save_cache' ) );
        }
    }
    
    public function save_cache( $buffer ) {
        if ( strlen( $buffer ) < 255 ) {
            return $buffer;
        }
        $cache_file = $this->get_cache_file_path();
        $cache_dir = dirname( $cache_file );
        if ( ! is_dir( $cache_dir ) ) {
            wp_mkdir_p( $cache_dir );
        }
        file_put_contents( $cache_file, $buffer . '<!-- Cached by Quantum Cache on ' . date('Y-m-d H:i:s') . ' -->' );
        return $buffer;
    }
    
    private function get_cache_file_path() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = trailingslashit(str_replace(['/', '?', '&', '='], '_', $request_uri));
        if ($path === '_') {
            $path = 'index';
        }
        return QUANTUM_CACHE_CACHE_PATH . $host . '/' . $path . '.html';
    }

    public static function clear_cache() {
        if ( is_dir( QUANTUM_CACHE_CACHE_PATH ) ) {
            self::recursive_delete( QUANTUM_CACHE_CACHE_PATH );
        }
        wp_mkdir_p( QUANTUM_CACHE_CACHE_PATH );
        $htaccess_file = QUANTUM_CACHE_CACHE_PATH . '.htaccess';
        $content = '<IfModule mod_authz_core.c>' . PHP_EOL . 'Require all denied' . PHP_EOL . '</IfModule>' . PHP_EOL . '<IfModule !mod_authz_core.c>' . PHP_EOL . 'Deny from all' . PHP_EOL . '</IfModule>';
        file_put_contents( $htaccess_file, $content );
    }

    private static function recursive_delete( $dir ) {
        if ( is_dir($dir) ) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        self::recursive_delete($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
}
