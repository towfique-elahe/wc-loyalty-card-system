<?php
// includes/class-database.php

defined('ABSPATH') or die('Direct access not allowed');

class Loyalty_DB {
    
    public static function get_user_points($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_points';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        return $result;
    }
    
    public static function update_user_points($user_id, $points, $type = 'earn', $reference_id = null, $description = '') {
        global $wpdb;
        $points_table = $wpdb->prefix . 'loyalty_points';
        $transactions_table = $wpdb->prefix . 'points_transactions';
        
        // Get current points
        $current = self::get_user_points($user_id);
        
        if (!$current) {
            // Insert new record
            $wpdb->insert(
                $points_table,
                array(
                    'user_id' => $user_id,
                    'points_balance' => $points,
                    'lifetime_points' => $points
                ),
                array('%d', '%d', '%d')
            );
        } else {
            // Update existing
            $new_balance = $current->points_balance + $points;
            $new_lifetime = $current->lifetime_points + ($type == 'earn' ? $points : 0);
            
            $wpdb->update(
                $points_table,
                array(
                    'points_balance' => $new_balance,
                    'lifetime_points' => $new_lifetime
                ),
                array('user_id' => $user_id),
                array('%d', '%d'),
                array('%d')
            );
        }
        
        // Record transaction
        $wpdb->insert(
            $transactions_table,
            array(
                'user_id' => $user_id,
                'points' => $points,
                'type' => $type,
                'reference_id' => $reference_id,
                'description' => $description
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
        
        return true;
    }
    
    public static function get_points_transactions($user_id, $limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'points_transactions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    public static function create_gift_card($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'gift_cards';
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
    
    public static function get_gift_card($card_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'gift_cards';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE card_number = %s",
            $card_number
        ));
    }
    
    public static function update_gift_card_balance($card_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . 'gift_cards';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET balance = balance - %f WHERE id = %d AND balance >= %f",
            $amount,
            $card_id,
            $amount
        ));
    }
    
    public static function create_loyalty_card($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_cards';
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
    
    public static function get_user_loyalty_cards($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_cards';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'active' ORDER BY created_at DESC",
            $user_id
        ));
    }
    
    public static function get_tiers() {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_tiers';
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY min_points ASC");
    }
    
    public static function get_tier_by_points($points) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_tiers';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE min_points <= %d AND (max_points >= %d OR max_points IS NULL) ORDER BY min_points DESC LIMIT 1",
            $points,
            $points
        ));
    }
}