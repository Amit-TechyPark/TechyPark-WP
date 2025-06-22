<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Quantum_Cache_Deactivator {
    public static function deactivate() {
        require_once QUANTUM_CACHE_INC_PATH . 'class-quantum-cache-caching.php';
        Quantum_Cache_Caching::clear_cache();
    }
}
