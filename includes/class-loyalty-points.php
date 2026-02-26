<?php
// includes/class-loyalty-points.php

defined('ABSPATH') or die('Direct access not allowed');

class Loyalty_Points {
    
    public static function process_order($order) {
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $order_total = $order->get_total();
        $points_earned = self::calculate_points($order_total);
        
        if ($points_earned > 0) {
            Loyalty_DB::update_user_points(
                $user_id,
                $points_earned,
                'earn',
                $order->get_id(), // Use get_id() instead of accessing post ID directly
                sprintf(__('Points earned from order #%s', 'wc-loyalty-system'), $order->get_order_number())
            );
            
            // Use CRUD methods instead of meta functions
            $order->update_meta_data('_points_earned', $points_earned);
            $order->save();
            
            $order->add_order_note(
                sprintf(__('Customer earned %d loyalty points from this order', 'wc-loyalty-system'), $points_earned)
            );
        }
    }
    
    public static function calculate_points($amount) {
        $points = 0;
        
        // Rate 1: 100 TK = 1 point
        $points += floor($amount / 100) * 1;
        
        // Rate 2: 450 TK = 5 points (additional 5 points for every 450)
        $points += floor($amount / 450) * 5;
        
        return $points;
    }
    
    public static function get_user_points($user_id) {
        $points_data = Loyalty_DB::get_user_points($user_id);
        return $points_data ? $points_data->points_balance : 0;
    }
    
    public static function get_user_lifetime_points($user_id) {
        $points_data = Loyalty_DB::get_user_points($user_id);
        return $points_data ? $points_data->lifetime_points : 0;
    }
    
    public static function redeem_points($user_id, $points_to_redeem, $order_id = null) {
        $current_points = self::get_user_points($user_id);
        $min_redeem = get_option('wcls_min_redemption_points', 100);
        
        if ($points_to_redeem < $min_redeem) {
            return new WP_Error('insufficient_points', __('Minimum 100 points required for redemption', 'wc-loyalty-system'));
        }
        
        if ($current_points < $points_to_redeem) {
            return new WP_Error('insufficient_points', __('Insufficient points balance', 'wc-loyalty-system'));
        }
        
        // Calculate discount amount (1 point = 1 TK)
        $discount_amount = $points_to_redeem;
        
        // Deduct points
        Loyalty_DB::update_user_points(
            $user_id,
            -$points_to_redeem,
            'redeem',
            $order_id,
            sprintf(__('Redeemed %d points', 'wc-loyalty-system'), $points_to_redeem)
        );
        
        return $discount_amount;
    }
    
    public static function get_points_history($user_id, $limit = 20) {
        return Loyalty_DB::get_points_transactions($user_id, $limit);
    }
    
    public static function get_points_value($points) {
        return $points; // 1 point = 1 TK
    }
}