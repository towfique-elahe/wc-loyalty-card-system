<?php
// admin/admin-ajax-handlers.php

defined('ABSPATH') or die('Direct access not allowed');

// ---------------------------------------------------------------
// Checkout discount actions (points & gift card as coupons)
// ---------------------------------------------------------------

// Apply loyalty points at checkout
add_action('wp_ajax_wcls_apply_points', 'wcls_apply_points_checkout');

function wcls_apply_points_checkout() {
    check_ajax_referer('wcls_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Please log in to use loyalty points.', 'wc-loyalty-system')));
    }

    $user_id     = get_current_user_id();
    $points      = intval($_POST['points']);
    $min_points  = get_option('wcls_min_redemption_points', 100);
    $available   = Loyalty_Points::get_user_points($user_id);

    if ($points < $min_points) {
        wp_send_json_error(array('message' => sprintf(
            __('Minimum %d points required.', 'wc-loyalty-system'), $min_points
        )));
    }

    if ($points > $available) {
        wp_send_json_error(array('message' => __('You do not have enough points.', 'wc-loyalty-system')));
    }

    // Cap discount to remaining cart total
    $cart_total = WC()->cart->get_subtotal() + WC()->cart->get_shipping_total();
    $discount   = min(Loyalty_Points::get_points_value($points), $cart_total);
    $points     = (int) $discount; // 1 pt = 1 TK, so points == discount after cap

    WC()->session->set('wcls_points_to_use', $points);

    wp_send_json_success(array(
        'message' => sprintf(
            __('<strong>%d points</strong> applied — discount: <strong>%s</strong>', 'wc-loyalty-system'),
            $points,
            wc_price($discount)
        ),
        'points'   => $points,
        'discount' => $discount,
    ));
}

// Remove loyalty points from checkout
add_action('wp_ajax_wcls_remove_points', 'wcls_remove_points_checkout');

function wcls_remove_points_checkout() {
    check_ajax_referer('wcls_nonce', 'nonce');
    WC()->session->set('wcls_points_to_use', 0);
    wp_send_json_success(array('message' => __('Loyalty points discount removed.', 'wc-loyalty-system')));
}

// Apply gift card at checkout
add_action('wp_ajax_wcls_apply_gift_card_checkout', 'wcls_apply_gift_card_checkout');

function wcls_apply_gift_card_checkout() {
    check_ajax_referer('wcls_nonce', 'nonce');

    $card_number = sanitize_text_field($_POST['card_number']);
    $card        = Gift_Cards::validate_gift_card($card_number);

    if (is_wp_error($card)) {
        wp_send_json_error(array('message' => $card->get_error_message()));
    }

    // Calculate how much the gift card can cover after any points discount
    $points_discount    = Loyalty_Points::get_points_value(intval(WC()->session->get('wcls_points_to_use', 0)));
    $cart_total         = WC()->cart->get_subtotal() + WC()->cart->get_shipping_total();
    $remaining_total    = max(0, $cart_total - $points_discount);
    $discount           = min($card->balance, $remaining_total);

    WC()->session->set('wcls_gift_card_applied', array(
        'number'   => $card_number,
        'balance'  => $card->balance,
        'discount' => $discount,
    ));

    wp_send_json_success(array(
        'message' => sprintf(
            __('Gift card <strong>***%s</strong> applied — discount: <strong>%s</strong>', 'wc-loyalty-system'),
            esc_html(substr($card_number, -4)),
            wc_price($discount)
        ),
        'discount'    => $discount,
        'card_number' => $card_number,
    ));
}

// Remove gift card from checkout
add_action('wp_ajax_wcls_remove_gift_card_checkout', 'wcls_remove_gift_card_checkout');

function wcls_remove_gift_card_checkout() {
    check_ajax_referer('wcls_nonce', 'nonce');
    WC()->session->set('wcls_gift_card_applied', null);
    wp_send_json_success(array('message' => __('Gift card discount removed.', 'wc-loyalty-system')));
}

// ---------------------------------------------------------------
// Check gift card balance (existing)
// ---------------------------------------------------------------

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