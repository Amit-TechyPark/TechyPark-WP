<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Quantum_Cache_Assets {
    protected static $instance = null;
    protected $options;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = get_option('quantum_cache_settings');
        if ( is_admin() ) return;

        if ( ! empty($this->options['defer_js']) ) {
            add_filter( 'script_loader_tag', array( $this, 'add_defer_attribute' ), 10, 2 );
        }

        if ( ! empty($this->options['lazy_load_images']) ) {
             add_filter( 'the_content', array( $this, 'add_lazy_loading_to_images' ) );
        }
    }

    public function add_defer_attribute( $tag, $handle ) {
        if ( 'jquery-core' === $handle ) {
            return $tag;
        }
        return str_replace( ' src', ' defer src', $tag );
    }

    public function add_lazy_loading_to_images( $content ) {
        if ( is_feed() || is_preview() ) {
            return $content;
        }
        return preg_replace_callback( '/<img[^>]+>/i', function($match) {
            $img = $match[0];
            if (strpos($img, 'loading=') !== false) {
                return $img; // Already has a loading attribute
            }
            return str_replace('<img', '<img loading="lazy"', $img);
        }, $content);
    }
}
