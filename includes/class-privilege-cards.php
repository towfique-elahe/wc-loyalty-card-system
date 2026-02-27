<?php
// includes/class-privilege-cards.php

defined('ABSPATH') or die('Direct access not allowed');

class Privilege_Cards {
    
    const CARD_TYPES = array(
        'privilege' => 'Privilege Card',
        'investor' => 'Investor Card',
        'platinum' => 'Platinum Card'
    );
    
    // Card issuance methods
    const ISSUANCE_METHODS = array(
        'privilege' => 'purchase',  // Can be purchased
        'investor' => 'special',     // Special issuance only
        'platinum' => 'special'      // Special issuance only
    );
    
    public static function generate_card_number($type) {
        $prefix = '';
        switch ($type) {
            case 'privilege':
                $prefix = 'PC';
                break;
            case 'investor':
                $prefix = 'IC';
                break;
            case 'platinum':
                $prefix = 'PLC';
                break;
        }
        
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return $prefix . $timestamp . $random;
    }
    
    public static function get_discount_rate($card_type) {
        switch ($card_type) {
            case 'privilege':
                return get_option('wcls_privilege_card_discount', 10);
            case 'investor':
                return get_option('wcls_investor_card_discount', 20);
            case 'platinum':
                return get_option('wcls_platinum_card_discount', 20);
            default:
                return 0;
        }
    }
    
    /**
     * Purchase a card (only for privilege cards)
     */
    public static function purchase_card($user_id, $card_type, $order_id = null) {
        // Only privilege cards can be purchased
        if ($card_type !== 'privilege') {
            return new WP_Error('invalid_purchase', __('This card type cannot be purchased.', 'wc-loyalty-system'));
        }

        // Prevent duplicate active privilege cards
        if (self::has_card_type($user_id, 'privilege')) {
            return new WP_Error('duplicate_card', __('You already have an active Privilege Card.', 'wc-loyalty-system'));
        }

        $card_price = get_option('wcls_privilege_card_price', 500);
        $discount_rate = self::get_discount_rate($card_type);
        
        $card_data = array(
            'card_number' => self::generate_card_number($card_type),
            'user_id' => $user_id,
            'card_type' => $card_type,
            'discount_rate' => $discount_rate,
            'purchase_amount' => $card_price,
            'status' => 'active',
            'valid_from' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+1 year')),
            'order_id' => $order_id
        );
        
        $card_id = Loyalty_DB::create_loyalty_card($card_data);
        
        if ($card_id) {
            // Record purchase
            global $wpdb;
            $table = $wpdb->prefix . 'card_purchases';
            $wpdb->insert(
                $table,
                array(
                    'order_id' => $order_id,
                    'user_id' => $user_id,
                    'card_type' => $card_type,
                    'amount_paid' => $card_price,
                    'discount_rate' => $discount_rate
                ),
                array('%d', '%d', '%s', '%f', '%f')
            );
            
            return $card_data['card_number'];
        }
        
        return false;
    }
    
    /**
     * Issue a special card (Investor or Platinum) - Admin only
     */
    public static function issue_special_card($user_id, $card_type, $issued_by = null) {
        if (!in_array($card_type, array('investor', 'platinum'))) {
            return new WP_Error('invalid_card', __('Invalid special card type.', 'wc-loyalty-system'));
        }

        // Prevent duplicate active special cards of the same type
        if (self::has_card_type($user_id, $card_type)) {
            return new WP_Error('duplicate_card', sprintf(
                __('This user already has an active %s.', 'wc-loyalty-system'),
                self::CARD_TYPES[$card_type]
            ));
        }

        $discount_rate = self::get_discount_rate($card_type);
        
        $card_data = array(
            'card_number' => self::generate_card_number($card_type),
            'user_id' => $user_id,
            'card_type' => $card_type,
            'discount_rate' => $discount_rate,
            'purchase_amount' => 0, // Free/special issuance
            'status' => 'active',
            'valid_from' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+1 year')),
            'order_id' => null
        );
        
        $card_id = Loyalty_DB::create_loyalty_card($card_data);
        
        if ($card_id) {
            // Log the issuance
            error_log(sprintf('Special card issued - Type: %s, User: %d, Issued by: %s', 
                $card_type, $user_id, $issued_by ?: 'system'));
            
            return $card_data['card_number'];
        }
        
        return false;
    }
    
    /**
     * Award free privilege card (when purchase threshold met)
     */
    public static function award_free_card($user_id, $order_id = null) {
        // Don't award if user already has an active privilege card
        if (self::has_card_type($user_id, 'privilege')) {
            return false;
        }

        $discount_rate = self::get_discount_rate('privilege');
        
        $card_data = array(
            'card_number' => self::generate_card_number('privilege'),
            'user_id' => $user_id,
            'card_type' => 'privilege',
            'discount_rate' => $discount_rate,
            'purchase_amount' => 0, // Free
            'status' => 'active',
            'valid_from' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+1 year')),
            'order_id' => $order_id
        );
        
        $card_id = Loyalty_DB::create_loyalty_card($card_data);
        
        if ($card_id) {
            return $card_data['card_number'];
        }
        
        return false;
    }
    
    public static function get_user_cards($user_id) {
        return Loyalty_DB::get_user_loyalty_cards($user_id);
    }
    
    /**
     * Validate and get user's best active card
     */
    public static function validate_card($user_id, $card_type = null) {
        $cards = self::get_user_cards($user_id);
        
        if (empty($cards)) {
            return false;
        }
        
        if ($card_type) {
            foreach ($cards as $card) {
                if ($card->card_type === $card_type && $card->status === 'active') {
                    if (!$card->valid_until || strtotime($card->valid_until) > time()) {
                        return $card;
                    }
                }
            }
            return false;
        }
        
        // Return the best card (highest discount)
        $best_card = null;
        $best_discount = 0;
        
        foreach ($cards as $card) {
            if ($card->status === 'active' && (!$card->valid_until || strtotime($card->valid_until) > time())) {
                if ($card->discount_rate > $best_discount) {
                    $best_discount = $card->discount_rate;
                    $best_card = $card;
                }
            }
        }
        
        return $best_card;
    }
    
    /**
     * Check if user has a specific card type
     */
    public static function has_card_type($user_id, $card_type) {
        $card = self::validate_card($user_id, $card_type);
        return $card !== false;
    }
    
    public static function apply_card_discount($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $card = self::validate_card($user_id);
        
        if ($card) {
            $discount = $card->discount_rate;
            $cart_total = $cart->get_subtotal();
            $discount_amount = ($cart_total * $discount) / 100;
            
            $cart->add_fee(
                sprintf(__('%s Discount (%s%%)', 'wc-loyalty-system'), 
                    self::CARD_TYPES[$card->card_type], 
                    $discount
                ),
                -$discount_amount
            );
        }
    }
    
    public static function get_card_price() {
        return get_option('wcls_privilege_card_price', 500);
    }
    
    public static function get_free_card_threshold() {
        return get_option('wcls_free_card_threshold', 2000);
    }
}

// Hook the discount application
add_action('woocommerce_cart_calculate_fees', array('Privilege_Cards', 'apply_card_discount'));