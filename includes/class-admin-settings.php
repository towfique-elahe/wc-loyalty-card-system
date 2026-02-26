<?php
// includes/class-admin-settings.php

defined('ABSPATH') or die('Direct access not allowed');

class Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Loyalty System', 'wc-loyalty-system'),
            __('Loyalty System', 'wc-loyalty-system'),
            'manage_options',
            'wcls-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-awards',
            56
        );
        
        add_submenu_page(
            'wcls-dashboard',
            __('Points Settings', 'wc-loyalty-system'),
            __('Points Settings', 'wc-loyalty-system'),
            'manage_options',
            'wcls-points',
            array($this, 'render_points_settings')
        );
        
        add_submenu_page(
            'wcls-dashboard',
            __('Gift Cards', 'wc-loyalty-system'),
            __('Gift Cards', 'wc-loyalty-system'),
            'manage_options',
            'wcls-gift-cards',
            array($this, 'render_gift_cards')
        );
        
        add_submenu_page(
            'wcls-dashboard',
            __('Loyalty Cards', 'wc-loyalty-system'),
            __('Loyalty Cards', 'wc-loyalty-system'),
            'manage_options',
            'wcls-cards',
            array($this, 'render_loyalty_cards')
        );
        
        add_submenu_page(
            'wcls-dashboard',
            __('Tiers', 'wc-loyalty-system'),
            __('Tiers', 'wc-loyalty-system'),
            'manage_options',
            'wcls-tiers',
            array($this, 'render_tiers')
        );
    }
    
    public function register_settings() {
        // Points settings
        register_setting('wcls_points_settings', 'wcls_points_rate_100');
        register_setting('wcls_points_settings', 'wcls_points_rate_450');
        register_setting('wcls_points_settings', 'wcls_min_redemption_points');
        
        // Card settings
        register_setting('wcls_card_settings', 'wcls_privilege_card_price');
        register_setting('wcls_card_settings', 'wcls_free_card_threshold');
        register_setting('wcls_card_settings', 'wcls_privilege_card_discount');
        register_setting('wcls_card_settings', 'wcls_investor_card_discount');
        register_setting('wcls_card_settings', 'wcls_platinum_card_discount');
        
        // Gift card settings
        register_setting('wcls_gift_card_settings', 'wcls_gift_card_types');
    }
    
    public function render_dashboard() {
        include WCLS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
    public function render_points_settings() {
        include WCLS_PLUGIN_DIR . 'admin/partials/points-settings.php';
    }
    
    public function render_gift_cards() {
        include WCLS_PLUGIN_DIR . 'admin/partials/gift-cards.php';
    }
    
    public function render_loyalty_cards() {
        include WCLS_PLUGIN_DIR . 'admin/partials/loyalty-cards.php';
    }
    
    public function render_tiers() {
        include WCLS_PLUGIN_DIR . 'admin/partials/tiers.php';
    }
}

// Admin menu is handled by WCLS_Admin_Menu in admin/admin-menu.php
// Do not auto-instantiate Admin_Settings here to avoid duplicate menu registration