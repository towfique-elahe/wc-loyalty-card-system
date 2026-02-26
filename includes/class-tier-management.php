<?php
// includes/class-tier-management.php

defined('ABSPATH') or die('Direct access not allowed');

class Tier_Management {
    
    /**
     * Get points-based tiers (Silver, Gold, etc.)
     */
    public static function get_tiers() {
        $tiers = get_option('wcls_loyalty_tiers', array());
        
        // Default tiers if none set
        if (empty($tiers)) {
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
        }
        
        return $tiers;
    }
    
    /**
     * Get user's points-based tier
     */
    public static function get_user_tier($user_id) {
        $points = Loyalty_Points::get_user_lifetime_points($user_id);
        $tiers = self::get_tiers();
        
        $current_tier = array(
            'name' => 'Bronze',
            'discount' => 0,
            'color' => '#cd7f32',
            'next_tier' => null,
            'points_to_next' => 0,
            'type' => 'points_tier' // Indicate this is points-based
        );
        
        foreach ($tiers as $index => $tier) {
            if ($points >= $tier['min_points']) {
                if (is_null($tier['max_points']) || $points <= $tier['max_points']) {
                    $current_tier = array(
                        'name' => $tier['name'],
                        'discount' => $tier['discount'],
                        'color' => $tier['color'],
                        'type' => 'points_tier'
                    );
                    
                    // Find next tier
                    if (isset($tiers[$index + 1])) {
                        $next_tier = $tiers[$index + 1];
                        $current_tier['next_tier'] = $next_tier['name'];
                        $current_tier['points_to_next'] = $next_tier['min_points'] - $points;
                    }
                }
            }
        }
        
        return $current_tier;
    }
    
    /**
     * Get user's card-based benefits (Privilege, Investor, Platinum cards)
     */
    public static function get_user_cards($user_id) {
        $cards = Privilege_Cards::get_user_cards($user_id);
        $active_cards = array();
        
        foreach ($cards as $card) {
            if ($card->status === 'active' && (!$card->valid_until || strtotime($card->valid_until) > time())) {
                $active_cards[] = array(
                    'type' => $card->card_type,
                    'name' => Privilege_Cards::CARD_TYPES[$card->card_type],
                    'discount' => $card->discount_rate,
                    'number' => $card->card_number,
                    'valid_until' => $card->valid_until
                );
            }
        }
        
        return $active_cards;
    }
    
    /**
     * Get user's complete benefits summary (tier + cards)
     */
    public static function get_user_benefits_summary($user_id) {
        $tier = self::get_user_tier($user_id);
        $cards = self::get_user_cards($user_id);
        
        $best_discount = $tier['discount'];
        $active_cards = array();
        
        foreach ($cards as $card) {
            $active_cards[] = $card;
            if ($card['discount'] > $best_discount) {
                $best_discount = $card['discount'];
            }
        }
        
        return array(
            'points_tier' => $tier,
            'cards' => $active_cards,
            'best_discount' => $best_discount,
            'has_investor_card' => in_array('investor', array_column($active_cards, 'type')),
            'has_platinum_card' => in_array('platinum', array_column($active_cards, 'type')),
            'has_privilege_card' => in_array('privilege', array_column($active_cards, 'type'))
        );
    }
}