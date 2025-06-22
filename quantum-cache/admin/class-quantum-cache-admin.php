<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Quantum_Cache_Admin {
    protected static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
        add_action( 'wp_ajax_quantum_clear_cache', array($this, 'ajax_clear_cache') );
        add_action( 'wp_ajax_quantum_db_optimize', array($this, 'ajax_db_optimize') );
    }

    public function add_plugin_page() {
        add_options_page('Quantum Cache', 'Quantum Cache', 'manage_options', 'quantum-cache-admin', array( $this, 'create_admin_page' ));
    }
    
    public function ajax_clear_cache() {
        check_ajax_referer('quantum_cache_admin_nonce', 'nonce');
        Quantum_Cache_Caching::clear_cache();
        wp_send_json_success('Cache cleared successfully!');
    }

    public function ajax_db_optimize() {
        check_ajax_referer('quantum_cache_admin_nonce', 'nonce');
        if (!isset($_POST['optimization_type'])) {
            wp_send_json_error('No optimization type specified.');
        }

        $db_optimizer = Quantum_Cache_Database::get_instance();
        $type = sanitize_key($_POST['optimization_type']);
        $result = 0;

        switch($type) {
            case 'revisions': $result = $db_optimizer->delete_post_revisions(); break;
            case 'drafts': $result = $db_optimizer->delete_auto_drafts(); break;
            case 'spam_comments': $result = $db_optimizer->delete_spam_comments(); break;
            case 'all':
                $result += $db_optimizer->delete_post_revisions();
                $result += $db_optimizer->delete_auto_drafts();
                $result += $db_optimizer->delete_spam_comments();
                break;
        }
        wp_send_json_success(['count' => $result]);
    }

    public function enqueue_styles_and_scripts($hook) {
        if ($hook !== 'settings_page_quantum-cache-admin') return;
        wp_enqueue_style( 'quantum-cache-admin-style', QUANTUM_CACHE_URL . '/admin/assets/css/admin-style.css', array(), QUANTUM_CACHE_VERSION );
        wp_enqueue_script( 'quantum-cache-admin-script', QUANTUM_CACHE_URL . '/admin/assets/js/admin-script.js', array( 'jquery' ), QUANTUM_CACHE_VERSION, true );
        wp_localize_script('quantum-cache-admin-script', 'quantumCache', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('quantum_cache_admin_nonce')]);
    }

    public function create_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        ?>
        <div class="wrap quantum-cache-wrap">
            <h1>Quantum Cache Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=quantum-cache-admin&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Dashboard</a>
                <a href="?page=quantum-cache-admin&tab=caching" class="nav-tab <?php echo $active_tab == 'caching' ? 'nav-tab-active' : ''; ?>">Cache</a>
                <a href="?page=quantum-cache-admin&tab=assets" class="nav-tab <?php echo $active_tab == 'assets' ? 'nav-tab-active' : ''; ?>">File Optimization</a>
                <a href="?page=quantum-cache-admin&tab=media" class="nav-tab <?php echo $active_tab == 'media' ? 'nav-tab-active' : ''; ?>">Media</a>
                <a href="?page=quantum-cache-admin&tab=database" class="nav-tab <?php echo $active_tab == 'database' ? 'nav-tab-active' : ''; ?>">Database</a>
            </h2>
            <form method="post" action="options.php">
                <?php
                    settings_fields('quantum_cache_option_group');
                    if ($active_tab == 'dashboard') $this->render_dashboard_tab();
                    elseif ($active_tab == 'caching') do_settings_sections('quantum-cache-caching');
                    elseif ($active_tab == 'assets') do_settings_sections('quantum-cache-assets');
                    elseif ($active_tab == 'media') do_settings_sections('quantum-cache-media');
                    elseif ($active_tab == 'database') $this->render_database_tab();
                    if ($active_tab != 'dashboard' && $active_tab != 'database') submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function render_dashboard_tab() { ?>
        <div class="quantum-cache-dashboard">
             <h3>Quick Actions</h3>
             <p>Perform common actions quickly from your dashboard.</p>
             <div class="quantum-cache-box">
                <button id="quantum-clear-cache" class="button button-primary button-hero">Clear All Cache</button>
                <p class="description">Instantly clear all cached files from your site.</p>
                <span id="quantum-cache-status" class="status-message"></span>
             </div>
        </div>
    <?php }

    private function render_database_tab() { ?>
        <div class="quantum-cache-database-optimization">
             <h3>Database Cleanup</h3>
             <p><strong>It is strongly recommended to take a backup before running these operations.</strong></p>
             <ul class="db-cleanup-list">
                <li><div class="label">Post Revisions</div><div class="action"><button class="button db-optimize-btn" data-type="revisions">Clean Revisions</button></div></li>
                <li><div class="label">Auto Drafts</div><div class="action"><button class="button db-optimize-btn" data-type="drafts">Clean Drafts</button></div></li>
                <li><div class="label">Spam Comments</div><div class="action"><button class="button db-optimize-btn" data-type="spam_comments">Clean Spam</button></div></li>
                <li class="cleanup-all"><div class="label">All Optimizations</div><div class="action"><button class="button button-primary db-optimize-btn" data-type="all">Run All</button></div></li>
             </ul>
             <div id="db-status-message" class="status-message"></div>
        </div>
    <?php }

    public function page_init() {
        register_setting('quantum_cache_option_group', 'quantum_cache_settings', array( $this, 'sanitize' ));
        add_settings_section('setting_section_caching', 'Page Caching', null, 'quantum-cache-caching');
        add_settings_field('enable_page_caching', 'Enable Page Caching', array( $this, 'enable_page_caching_callback' ), 'quantum-cache-caching', 'setting_section_caching');
        add_settings_section('setting_section_assets', 'CSS & JavaScript Optimization', null, 'quantum-cache-assets');
        add_settings_field('minify_css', 'Minify CSS files', array( $this, 'minify_css_callback' ), 'quantum-cache-assets', 'setting_section_assets');
        add_settings_field('defer_js', 'Defer non-essential JavaScript', array( $this, 'defer_js_callback' ), 'quantum-cache-assets', 'setting_section_assets');
        add_settings_section('setting_section_media', 'Media Optimization', null, 'quantum-cache-media');
        add_settings_field('lazy_load_images', 'Enable Lazy Loading for images', array( $this, 'lazy_load_callback' ), 'quantum-cache-media', 'setting_section_media');
    }

    public function sanitize( $input ) {
        $new_input = get_option('quantum_cache_settings') ?: [];
        $new_input['enable_page_caching'] = isset( $input['enable_page_caching'] ) ? 1 : 0;
        $new_input['minify_css'] = isset( $input['minify_css'] ) ? 1 : 0;
        $new_input['defer_js'] = isset( $input['defer_js'] ) ? 1 : 0;
        $new_input['lazy_load_images'] = isset( $input['lazy_load_images'] ) ? 1 : 0;
        Quantum_Cache_Caching::clear_cache();
        return $new_input;
    }

    public function enable_page_caching_callback() {
        $options = get_option('quantum_cache_settings');
        printf('<input type="checkbox" name="quantum_cache_settings[enable_page_caching]" value="1" %s />', checked(1, $options['enable_page_caching'] ?? 0, false));
    }
    public function minify_css_callback() {
        $options = get_option('quantum_cache_settings');
        printf('<input type="checkbox" name="quantum_cache_settings[minify_css]" value="1" %s disabled /> <span class="description">Reduces CSS file sizes. (Feature coming soon)</span>', checked(1, $options['minify_css'] ?? 0, false));
    }
    public function defer_js_callback() {
        $options = get_option('quantum_cache_settings');
        printf('<input type="checkbox" name="quantum_cache_settings[defer_js]" value="1" %s /> <span class="description">Improves load time by deferring JS.</span>', checked(1, $options['defer_js'] ?? 0, false));
    }
    public function lazy_load_callback() {
        $options = get_option( 'quantum_cache_settings' );
        printf('<input type="checkbox" name="quantum_cache_settings[lazy_load_images]" value="1" %s/> <span class="description">Load images only when they are visible.</span>', checked(1, $options['lazy_load_images'] ?? 0, false));
    }
}
