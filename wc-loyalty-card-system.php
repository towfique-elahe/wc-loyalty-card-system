<?php
/**
 * Plugin Name: WooCommerce Loyalty Card System
 * Plugin URI: https://towfiqueelahe.com/
 * Description: Complete loyalty point system with gift cards, privilege cards, and tiered memberships for WooCommerce
 * Version: 1.0.0
 * Author: Towfique Elahe
 * Author URI: https://towfiqueelahe.com/
 * License: GPL v2 or later
 * Text Domain: wc-loyalty-system
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * Requires Plugins: woocommerce
 */

// Add HPOS compatibility declaration
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Prevent direct access
defined('ABSPATH') or die('Direct access not allowed');

// Block activation if WooCommerce is not active
register_activation_hook(__FILE__, 'wcls_activation_check');
function wcls_activation_check() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('WooCommerce Loyalty Card System requires WooCommerce to be installed and activated. Please activate WooCommerce first.', 'wc-loyalty-system'),
            __('Plugin Activation Error', 'wc-loyalty-system'),
            array('back_link' => true)
        );
    }
}

// Define plugin constants
define('WCLS_VERSION', '1.0.0');
define('WCLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WCLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Add helper function for contrast color
function wcls_get_contrast_color($hex_color) {
    // Remove # if present
    $hex_color = ltrim($hex_color, '#');
    
    // Convert to RGB
    if (strlen($hex_color) == 3) {
        $r = hexdec(str_repeat(substr($hex_color, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex_color, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex_color, 2, 1), 2));
    } else {
        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));
    }
    
    // Calculate luminance
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    // Return black for light colors, white for dark colors
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}


// Main class
class WC_Loyalty_Card_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Database
        require_once WCLS_PLUGIN_DIR . 'db/schema.php';
        require_once WCLS_PLUGIN_DIR . 'includes/class-database.php';
        
        // Core classes
        require_once WCLS_PLUGIN_DIR . 'includes/class-loyalty-points.php';
        require_once WCLS_PLUGIN_DIR . 'includes/class-gift-cards.php';
        require_once WCLS_PLUGIN_DIR . 'includes/class-privilege-cards.php';
        require_once WCLS_PLUGIN_DIR . 'includes/class-tier-management.php';
        require_once WCLS_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once WCLS_PLUGIN_DIR . 'includes/class-frontend-display.php';
        
        // Payment gateways
        require_once WCLS_PLUGIN_DIR . 'includes/gateways/class-points-gateway.php';
        require_once WCLS_PLUGIN_DIR . 'includes/gateways/class-gift-card-gateway.php';
        
        // Admin
        if (is_admin()) {
            require_once WCLS_PLUGIN_DIR . 'admin/admin-menu.php';
            require_once WCLS_PLUGIN_DIR . 'admin/admin-ajax-handlers.php';
        }
    }

    private function check_database_tables() {
        // Only check once per session
        if (get_transient('wcls_tables_checked')) {
            return;
        }
        
        global $wpdb;
        
        $tables = array(
            'loyalty_points',
            'points_transactions',
            'gift_cards',
            'gift_card_transactions',
            'loyalty_cards',
            'card_purchases',
            'loyalty_tiers'
        );
        
        $missing_tables = false;
        $missing_table_names = array();
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $missing_tables = true;
                $missing_table_names[] = $table_name;
                break;
            }
        }
        
        if ($missing_tables) {
            error_log('WCLS: Missing tables detected: ' . implode(', ', $missing_table_names));
            require_once WCLS_PLUGIN_DIR . 'db/schema.php';
            wcls_create_tables();
            
            // Clear any cached results
            wp_cache_flush();
        }
        
        // Set transient to avoid checking on every page load (check once per day)
        set_transient('wcls_tables_checked', true, DAY_IN_SECONDS);
    }
    
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init hooks
        add_action('init', array($this, 'init'));
        
        // WooCommerce hooks
        add_action('woocommerce_order_status_completed', array($this, 'process_order_points'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'check_free_privilege_card'), 20, 1);
        add_filter('woocommerce_payment_gateways', array($this, 'add_payment_gateways'));
        
        // My Account page
        add_action('woocommerce_account_dashboard', array($this, 'add_loyalty_dashboard_widget'));
        add_filter('woocommerce_account_menu_items', array($this, 'add_loyalty_menu_items'));
        add_action('woocommerce_account_loyalty-points_endpoint', array($this, 'loyalty_points_content'));
        add_action('woocommerce_account_loyalty-cards_endpoint', array($this, 'loyalty_cards_content'));
        add_action('woocommerce_account_gift-cards_endpoint', array($this, 'gift_cards_content'));
        
        // Add endpoints
        add_action('init', array($this, 'add_my_account_endpoints'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function init() {
        load_plugin_textdomain('wc-loyalty-system', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Check database tables
        $this->check_database_tables();
    }
    
    public function activate() {
        // Check if HPOS is enabled and handle accordingly
        if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
            $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            update_option('wcls_hpos_enabled', $hpos_enabled);
        }
        
        // Create database tables
        require_once WCLS_PLUGIN_DIR . 'db/schema.php';
        wcls_create_tables();
        
        // Set default options
        add_option('wcls_points_rate_100', 1);
        add_option('wcls_points_rate_450', 5);
        add_option('wcls_min_redemption_points', 100);
        add_option('wcls_points_expiry_days', 365);
        add_option('wcls_privilege_card_price', 500);
        add_option('wcls_free_card_threshold', 2000);
        add_option('wcls_privilege_card_discount', 10);
        add_option('wcls_investor_card_discount', 20);
        add_option('wcls_platinum_card_discount', 20);
        
        // Create default gift cards
        $this->create_default_gift_cards();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_default_gift_cards() {
        $gift_cards = array(
            array('name' => 'Gift Card 500', 'amount' => 500),
            array('name' => 'Gift Card 1000', 'amount' => 1000),
            array('name' => 'Gift Card 2000', 'amount' => 2000),
            array('name' => 'Gift Card 5000', 'amount' => 5000)
        );
        
        update_option('wcls_gift_card_types', $gift_cards);
    }
    
    private function create_default_tiers() {
        $tiers = array(
            array(
                'name' => 'Bronze',
                'min_points' => 0,
                'max_points' => 499,
                'discount' => 0,
                'color' => '#cd7f32'
            ),
            array(
                'name' => 'Silver',
                'min_points' => 500,
                'max_points' => 1999,
                'discount' => 5,
                'color' => '#c0c0c0'
            ),
            array(
                'name' => 'Gold',
                'min_points' => 2000,
                'max_points' => 4999,
                'discount' => 10,
                'color' => '#ffd700'
            ),
            array(
                'name' => 'Platinum',
                'min_points' => 5000,
                'max_points' => null,
                'discount' => 15,
                'color' => '#e5e4e2'
            )
        );
        
        update_option('wcls_loyalty_tiers', $tiers);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function process_order_points($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            Loyalty_Points::process_order($order);
        }
    }
    
    public function check_free_privilege_card($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order_total = $order->get_total();
            $threshold = get_option('wcls_free_card_threshold', 2000);
            
            if ($order_total >= $threshold) {
                $user_id = $order->get_user_id();
                if ($user_id) {
                    Privilege_Cards::award_free_card($user_id, $order_id);
                }
            }
        }
    }
    
    public function add_payment_gateways($gateways) {
        $gateways[] = 'WC_Points_Gateway';
        $gateways[] = 'WC_Gift_Card_Gateway';
        return $gateways;
    }
    
    public function add_loyalty_dashboard_widget() {
        $user_id = get_current_user_id();
        $points = Loyalty_Points::get_user_points($user_id);
        $tier = Tier_Management::get_user_tier($user_id);
        ?>
<div class="loyalty-dashboard-widget">
    <h3><?php _e('Your Loyalty Status', 'wc-loyalty-system'); ?></h3>
    <div class="loyalty-stats">
        <p><strong><?php _e('Points Balance:', 'wc-loyalty-system'); ?></strong> <?php echo $points; ?></p>
        <p><strong><?php _e('Current Tier:', 'wc-loyalty-system'); ?></strong> <span
                style="color: <?php echo $tier['color']; ?>"><?php echo $tier['name']; ?></span></p>
        <?php if ($tier['discount'] > 0): ?>
        <p><strong><?php _e('Tier Discount:', 'wc-loyalty-system'); ?></strong> <?php echo $tier['discount']; ?>%</p>
        <?php endif; ?>
    </div>
</div>
<?php
    }
    
    public function add_loyalty_menu_items($menu_items) {
        $menu_items = array_slice($menu_items, 0, 1, true) 
            + array('loyalty-points' => __('Loyalty Points', 'wc-loyalty-system'))
            + array('loyalty-cards' => __('My Cards', 'wc-loyalty-system'))
            + array('gift-cards' => __('Gift Cards', 'wc-loyalty-system'))
            + array_slice($menu_items, 1, null, true);
        
        return $menu_items;
    }
    
    public function add_my_account_endpoints() {
        add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('loyalty-cards', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('gift-cards', EP_ROOT | EP_PAGES);
    }
    
    public function loyalty_points_content() {
        include WCLS_PLUGIN_DIR . 'public/templates/loyalty-points.php';
    }
    
    public function loyalty_cards_content() {
        include WCLS_PLUGIN_DIR . 'public/templates/loyalty-cards.php';
    }
    
    public function gift_cards_content() {
        include WCLS_PLUGIN_DIR . 'public/templates/gift-cards.php';
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('wcls-frontend', WCLS_PLUGIN_URL . 'public/css/loyalty-system.css', array(), WCLS_VERSION);
        wp_enqueue_script('wcls-frontend', WCLS_PLUGIN_URL . 'public/js/loyalty-system.js', array('jquery'), WCLS_VERSION, true);
        
        wp_localize_script('wcls-frontend', 'wcls_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcls_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wcls') !== false) {
            wp_enqueue_style('wcls-admin', WCLS_PLUGIN_URL . 'admin/css/admin-style.css', array(), WCLS_VERSION);
            wp_enqueue_script('wcls-admin', WCLS_PLUGIN_URL . 'admin/js/admin-script.js', array('jquery'), WCLS_VERSION, true);
        }
    }
}

// Initialize the plugin — bail early if WooCommerce is not active
function wcls_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wcls_woocommerce_missing_notice');
        return;
    }
    return WC_Loyalty_Card_System::get_instance();
}
add_action('plugins_loaded', 'wcls_init');

function wcls_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php _e('WooCommerce Loyalty Card System', 'wc-loyalty-system'); ?></strong>
            <?php _e('requires WooCommerce to be installed and activated. Please', 'wc-loyalty-system'); ?>
            <a href="<?php echo esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')); ?>">
                <?php _e('install WooCommerce', 'wc-loyalty-system'); ?>
            </a>.
        </p>
    </div>
    <?php
}

// Activation hook to ensure rewrite rules are flushed
register_activation_hook(__FILE__, 'wcls_activate');
function wcls_activate() {
    wcls_init()->activate();
}