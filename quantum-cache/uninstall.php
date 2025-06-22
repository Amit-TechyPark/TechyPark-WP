<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('quantum_cache_settings');
delete_site_option('quantum_cache_settings');
