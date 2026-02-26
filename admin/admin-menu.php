<?php
// admin/admin-menu.php

defined('ABSPATH') or die('Direct access not allowed');

class WCLS_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_pages() {
        // Main menu
        add_menu_page(
            __('Loyalty System', 'wc-loyalty-system'),
            __('Loyalty System', 'wc-loyalty-system'),
            'manage_options',
            'wcls-loyalty',
            array($this, 'dashboard_page'),
            'dashicons-awards',
            55
        );
        
        // Submenus
        add_submenu_page(
            'wcls-loyalty',
            __('Dashboard', 'wc-loyalty-system'),
            __('Dashboard', 'wc-loyalty-system'),
            'manage_options',
            'wcls-loyalty',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'wcls-loyalty',
            __('Points Settings', 'wc-loyalty-system'),
            __('Points Settings', 'wc-loyalty-system'),
            'manage_options',
            'wcls-points',
            array($this, 'points_settings_page')
        );
        
        add_submenu_page(
            'wcls-loyalty',
            __('Gift Cards', 'wc-loyalty-system'),
            __('Gift Cards', 'wc-loyalty-system'),
            'manage_options',
            'wcls-gift-cards',
            array($this, 'gift_cards_page')
        );
        
        add_submenu_page(
            'wcls-loyalty',
            __('Loyalty Cards', 'wc-loyalty-system'),
            __('Loyalty Cards', 'wc-loyalty-system'),
            'manage_options',
            'wcls-cards',
            array($this, 'loyalty_cards_page')
        );
        
        add_submenu_page(
            'wcls-loyalty',
            __('Tiers', 'wc-loyalty-system'),
            __('Tiers', 'wc-loyalty-system'),
            'manage_options',
            'wcls-tiers',
            array($this, 'tiers_page')
        );
        
        add_submenu_page(
            'wcls-loyalty',
            __('Reports', 'wc-loyalty-system'),
            __('Reports', 'wc-loyalty-system'),
            'manage_options',
            'wcls-reports',
            array($this, 'reports_page')
        );
    }
    
    public function register_settings() {
        // Points settings
        register_setting('wcls_points_group', 'wcls_points_rate_100');
        register_setting('wcls_points_group', 'wcls_points_rate_450');
        register_setting('wcls_points_group', 'wcls_min_redemption_points');
        
        // Card settings
        register_setting('wcls_cards_group', 'wcls_privilege_card_price');
        register_setting('wcls_cards_group', 'wcls_free_card_threshold');
        register_setting('wcls_cards_group', 'wcls_privilege_card_discount');
        register_setting('wcls_cards_group', 'wcls_investor_card_discount');
        register_setting('wcls_cards_group', 'wcls_platinum_card_discount');
        
        // Gift card settings
        register_setting('wcls_gift_cards_group', 'wcls_gift_card_types');
    }
    
    public function dashboard_page() {
        global $wpdb;
        
        // Get statistics
        $total_users = count_users();
        $total_points = $wpdb->get_var("SELECT SUM(points_balance) FROM {$wpdb->prefix}loyalty_points");
        $total_gift_cards = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gift_cards WHERE status = 'active'");
        $total_loyalty_cards = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_cards WHERE status = 'active'");
        
        // Recent transactions
        $recent_transactions = $wpdb->get_results(
            "SELECT t.*, u.display_name 
             FROM {$wpdb->prefix}points_transactions t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             ORDER BY t.created_at DESC 
             LIMIT 10"
        );
        
        include WCLS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
    public function points_settings_page() {
        include WCLS_PLUGIN_DIR . 'admin/partials/points-settings.php';
    }
    
    public function gift_cards_page() {
        global $wpdb;
        
        // Get all gift cards
        $gift_cards = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}gift_cards 
             ORDER BY created_at DESC 
             LIMIT 50"
        );
        
        include WCLS_PLUGIN_DIR . 'admin/partials/gift-cards.php';
    }
    
    public function loyalty_cards_page() {
        global $wpdb;
        
        // Get all loyalty cards
        $loyalty_cards = $wpdb->get_results(
            "SELECT c.*, u.display_name 
             FROM {$wpdb->prefix}loyalty_cards c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             ORDER BY c.created_at DESC 
             LIMIT 50"
        );
        
        include WCLS_PLUGIN_DIR . 'admin/partials/loyalty-cards.php';
    }
    
    public function tiers_page() {
        $tiers = get_option('wcls_loyalty_tiers', array());
        include WCLS_PLUGIN_DIR . 'admin/partials/tiers.php';
    }
    
    public function reports_page() {
        include WCLS_PLUGIN_DIR . 'admin/partials/reports.php';
    }
}

// Initialize admin menu
new WCLS_Admin_Menu();