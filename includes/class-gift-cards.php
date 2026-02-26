<?php
// includes/class-gift-cards.php

defined('ABSPATH') or die('Direct access not allowed');

class Gift_Cards {
    
    public static function generate_card_number() {
        $prefix = 'GC';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $card_number = $prefix . $timestamp . $random;
        
        // Ensure uniqueness
        global $wpdb;
        $table = $wpdb->prefix . 'gift_cards';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE card_number = %s",
            $card_number
        ));
        
        if ($exists) {
            return self::generate_card_number(); // Recursive until unique
        }
        
        return $card_number;
    }
    
    public static function create_gift_card($amount, $created_by = null, $expiry_days = 365) {
        $card_data = array(
            'card_number' => self::generate_card_number(),
            'balance' => $amount,
            'initial_amount' => $amount,
            'expiry_date' => date('Y-m-d', strtotime("+$expiry_days days")),
            'status' => 'active',
            'created_by' => $created_by
        );
        
        $card_id = Loyalty_DB::create_gift_card($card_data);
        
        if ($card_id) {
            return $card_data['card_number'];
        }
        
        return false;
    }
    
    public static function validate_gift_card($card_number) {
        $card = Loyalty_DB::get_gift_card($card_number);
        
        if (!$card) {
            return new WP_Error('invalid_card', __('Invalid gift card number', 'wc-loyalty-system'));
        }
        
        if ($card->status !== 'active') {
            return new WP_Error('inactive_card', __('Gift card is not active', 'wc-loyalty-system'));
        }
        
        if ($card->expiry_date && strtotime($card->expiry_date) < time()) {
            return new WP_Error('expired_card', __('Gift card has expired', 'wc-loyalty-system'));
        }
        
        if ($card->balance <= 0) {
            return new WP_Error('no_balance', __('Gift card has zero balance', 'wc-loyalty-system'));
        }
        
        return $card;
    }
    
    public static function redeem_gift_card($card_number, $amount, $order_id = null) {
        $card = self::validate_gift_card($card_number);
        
        if (is_wp_error($card)) {
            return $card;
        }
        
        if ($card->balance < $amount) {
            return new WP_Error('insufficient_balance', __('Insufficient gift card balance', 'wc-loyalty-system'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'gift_card_transactions';
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Update card balance
            $updated = Loyalty_DB::update_gift_card_balance($card->id, $amount);
            
            if (!$updated) {
                throw new Exception('Failed to update gift card balance');
            }
            
            // Record transaction with order ID
            $wpdb->insert(
                $table,
                array(
                    'card_id' => $card->id,
                    'order_id' => $order_id, // This works with HPOS as order_id is stored in meta
                    'amount' => $amount,
                    'transaction_type' => 'debit'
                ),
                array('%d', '%d', '%f', '%s')
            );
            
            $wpdb->query('COMMIT');
            return $amount;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('redemption_failed', $e->getMessage());
        }
    }
    
    public static function get_gift_card_balance($card_number) {
        $card = Loyalty_DB::get_gift_card($card_number);
        return $card ? $card->balance : 0;
    }
    
    public static function get_gift_card_types() {
        return get_option('wcls_gift_card_types', array());
    }
    
    public static function add_to_cart_as_product($card_type) {
        $gift_cards = self::get_gift_card_types();
        
        foreach ($gift_cards as $card) {
            if ($card['name'] === $card_type) {
                // Create a custom product or add to cart
                // This would integrate with WooCommerce
                return WC()->cart->add_to_cart(0, 1, 0, array(), array(
                    'gift_card_amount' => $card['amount'],
                    'gift_card_type' => $card['name']
                ));
            }
        }
        
        return false;
    }
}