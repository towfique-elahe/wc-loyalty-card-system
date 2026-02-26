<?php
// db/schema.php

defined('ABSPATH') or die('Direct access not allowed');

function wcls_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Points table
    $table_name = $wpdb->prefix . 'loyalty_points';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        points_balance int(11) DEFAULT 0,
        lifetime_points int(11) DEFAULT 0,
        tier_id int(11),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY tier_id (tier_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Points transactions
    $table_name = $wpdb->prefix . 'points_transactions';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        points int(11) NOT NULL,
        type varchar(50) NOT NULL,
        reference_id bigint(20),
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY type (type)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Gift cards - FIXED: Remove duplicate key
    $table_name = $wpdb->prefix . 'gift_cards';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        card_number varchar(50) NOT NULL,
        balance decimal(10,2) DEFAULT 0,
        initial_amount decimal(10,2) NOT NULL,
        expiry_date date,
        status varchar(20) DEFAULT 'active',
        created_by bigint(20),
        assigned_to bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY card_number (card_number),
        KEY status (status)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Gift card transactions
    $table_name = $wpdb->prefix . 'gift_card_transactions';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        card_id bigint(20) NOT NULL,
        order_id bigint(20),
        amount decimal(10,2) NOT NULL,
        transaction_type varchar(20) DEFAULT 'debit',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY card_id (card_id),
        KEY order_id (order_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Loyalty cards - FIXED: Remove duplicate key
    $table_name = $wpdb->prefix . 'loyalty_cards';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        card_number varchar(50) NOT NULL,
        user_id bigint(20) NOT NULL,
        card_type varchar(50) NOT NULL,
        discount_rate decimal(5,2) DEFAULT 0,
        purchase_amount decimal(10,2),
        status varchar(20) DEFAULT 'active',
        valid_from date,
        valid_until date,
        order_id bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY card_number (card_number),
        KEY user_id (user_id),
        KEY card_type (card_type),
        KEY status (status)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Card purchases
    $table_name = $wpdb->prefix . 'card_purchases';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        card_type varchar(50) NOT NULL,
        amount_paid decimal(10,2),
        discount_rate decimal(5,2),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Tiers table
    $table_name = $wpdb->prefix . 'loyalty_tiers';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        tier_name varchar(50) NOT NULL,
        min_points int(11) DEFAULT 0,
        max_points int(11),
        discount_rate decimal(5,2) DEFAULT 0,
        benefits text,
        color varchar(7) DEFAULT '#000000',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // Insert default tiers if table is empty
    $tier_table = $wpdb->prefix . 'loyalty_tiers';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $tier_table");
    
    if ($count == 0) {
        $wpdb->insert($tier_table, array(
            'tier_name' => 'Bronze',
            'min_points' => 0,
            'max_points' => 499,
            'discount_rate' => 0,
            'color' => '#cd7f32'
        ));
        
        $wpdb->insert($tier_table, array(
            'tier_name' => 'Silver',
            'min_points' => 500,
            'max_points' => 1999,
            'discount_rate' => 5,
            'color' => '#c0c0c0'
        ));
        
        $wpdb->insert($tier_table, array(
            'tier_name' => 'Gold',
            'min_points' => 2000,
            'max_points' => 4999,
            'discount_rate' => 10,
            'color' => '#ffd700'
        ));
        
        $wpdb->insert($tier_table, array(
            'tier_name' => 'Platinum',
            'min_points' => 5000,
            'max_points' => null,
            'discount_rate' => 15,
            'color' => '#e5e4e2'
        ));
    }
}