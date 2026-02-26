<?php
// admin/admin-ajax-handlers.php

defined('ABSPATH') or die('Direct access not allowed');

// Check gift card balance
add_action('wp_ajax_check_gift_card_balance', 'wcls_check_gift_card_balance');
add_action('wp_ajax_nopriv_check_gift_card_balance', 'wcls_check_gift_card_balance');

function wcls_check_gift_card_balance() {
    check_ajax_referer('wcls_nonce', 'nonce');
    
    $card_number = sanitize_text_field($_POST['card_number']);
    $card = Gift_Cards::validate_gift_card($card_number);
    
    if (is_wp_error($card)) {
        wp_send_json_error(array(
            'message' => $card->get_error_message()
        ));
    } else {
        wp_send_json_success(array(
            'message' => sprintf(
                __('Gift card balance: %s', 'wc-loyalty-system'),
                wc_price($card->balance)
            ),
            'balance' => $card->balance
        ));
    }
}

// Admin: Create gift card
add_action('wp_ajax_wcls_create_gift_card', 'wcls_create_gift_card');

function wcls_create_gift_card() {
    check_ajax_referer('wcls_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'wc-loyalty-system')));
    }
    
    $amount = floatval($_POST['amount']);
    $user_id = get_current_user_id();
    
    $card_number = Gift_Cards::create_gift_card($amount, $user_id);
    
    if ($card_number) {
        wp_send_json_success(array(
            'message' => __('Gift card created successfully', 'wc-loyalty-system'),
            'card_number' => $card_number
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to create gift card', 'wc-loyalty-system')
        ));
    }
}

// Admin: Update tier settings
add_action('wp_ajax_wcls_update_tiers', 'wcls_update_tiers');

function wcls_update_tiers() {
    check_ajax_referer('wcls_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'wc-loyalty-system')));
    }
    
    $tiers = json_decode(stripslashes($_POST['tiers']), true);
    update_option('wcls_loyalty_tiers', $tiers);
    
    wp_send_json_success(array(
        'message' => __('Tiers updated successfully', 'wc-loyalty-system')
    ));
}

// Get user points for admin
add_action('wp_ajax_wcls_get_user_points', 'wcls_get_user_points');

function wcls_get_user_points() {
    check_ajax_referer('wcls_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'wc-loyalty-system')));
    }
    
    $user_id = intval($_POST['user_id']);
    $points = Loyalty_Points::get_user_points($user_id);
    $tier = Tier_Management::get_user_tier($user_id);
    
    wp_send_json_success(array(
        'points' => $points,
        'tier' => $tier['name']
    ));
}

// Admin: Adjust user points
add_action('wp_ajax_wcls_adjust_points', 'wcls_adjust_points');

function wcls_adjust_points() {
    check_ajax_referer('wcls_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'wc-loyalty-system')));
    }
    
    $user_id = intval($_POST['user_id']);
    $points = intval($_POST['points']);
    $reason = sanitize_text_field($_POST['reason']);
    
    $result = Loyalty_DB::update_user_points(
        $user_id,
        $points,
        'admin_adjustment',
        null,
        $reason
    );
    
    if ($result) {
        wp_send_json_success(array(
            'message' => __('Points adjusted successfully', 'wc-loyalty-system')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to adjust points', 'wc-loyalty-system')
        ));
    }
}