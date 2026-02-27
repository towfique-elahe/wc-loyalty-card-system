<?php
// admin/partials/dashboard.php

if (!defined('ABSPATH')) exit;

// Get statistics
global $wpdb;

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
?>

<div class="wrap wcls-admin-wrap">
    <h1><?php _e('Loyalty System Dashboard', 'wc-loyalty-system'); ?></h1>

    <div class="wcls-stats-grid">
        <div class="wcls-stat-card">
            <div class="wcls-stat-icon">👥</div>
            <div class="wcls-stat-content">
                <h3><?php _e('Total Users', 'wc-loyalty-system'); ?></h3>
                <p class="wcls-stat-number"><?php echo $total_users['total_users']; ?></p>
            </div>
        </div>

        <div class="wcls-stat-card">
            <div class="wcls-stat-icon">⭐</div>
            <div class="wcls-stat-content">
                <h3><?php _e('Total Points Issued', 'wc-loyalty-system'); ?></h3>
                <p class="wcls-stat-number"><?php echo number_format($total_points ?: 0); ?></p>
            </div>
        </div>

        <div class="wcls-stat-card">
            <div class="wcls-stat-icon">🎁</div>
            <div class="wcls-stat-content">
                <h3><?php _e('Active Gift Cards', 'wc-loyalty-system'); ?></h3>
                <p class="wcls-stat-number"><?php echo $total_gift_cards ?: 0; ?></p>
            </div>
        </div>

        <div class="wcls-stat-card">
            <div class="wcls-stat-icon">💳</div>
            <div class="wcls-stat-content">
                <h3><?php _e('Active Loyalty Cards', 'wc-loyalty-system'); ?></h3>
                <p class="wcls-stat-number"><?php echo $total_loyalty_cards ?: 0; ?></p>
            </div>
        </div>
    </div>

    <div class="wcls-quick-actions">
        <h2><?php _e('Quick Actions', 'wc-loyalty-system'); ?></h2>
        <div class="wcls-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=wcls-points'); ?>" class="button button-primary">
                <?php _e('Configure Points', 'wc-loyalty-system'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wcls-gift-cards'); ?>" class="button">
                <?php _e('Manage Gift Cards', 'wc-loyalty-system'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wcls-cards'); ?>" class="button">
                <?php _e('View Loyalty Cards', 'wc-loyalty-system'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wcls-tiers'); ?>" class="button">
                <?php _e('Manage Tiers', 'wc-loyalty-system'); ?>
            </a>
        </div>
    </div>

    <div class="wcls-recent-transactions">
        <h2><?php _e('Recent Points Transactions', 'wc-loyalty-system'); ?></h2>

        <?php if (empty($recent_transactions)): ?>
        <p><?php _e('No transactions yet.', 'wc-loyalty-system'); ?></p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Points', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Type', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Description', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Date', 'wc-loyalty-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_transactions as $transaction): ?>
                <tr>
                    <td><?php echo esc_html($transaction->display_name ?: 'User #' . $transaction->user_id); ?></td>
                    <td class="<?php echo $transaction->points > 0 ? 'wcls-positive' : 'wcls-negative'; ?>">
                        <?php echo $transaction->points > 0 ? '+' . $transaction->points : $transaction->points; ?>
                    </td>
                    <td><?php echo ucfirst($transaction->type); ?></td>
                    <td><?php echo esc_html($transaction->description); ?></td>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction->created_at)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
.wcls-admin-wrap {
    margin: 20px 0;
    margin-right: 20px;
}

.wcls-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0 30px;
}

.wcls-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.wcls-stat-icon {
    font-size: 32px;
    margin-right: 15px;
}

.wcls-stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
}

.wcls-stat-number {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.wcls-quick-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.wcls-quick-actions h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.wcls-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.wcls-recent-transactions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
}

.wcls-recent-transactions h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.wcls-positive {
    color: #46b450;
    font-weight: bold;
}

.wcls-negative {
    color: #dc3232;
    font-weight: bold;
}

@media (max-width: 768px) {
    .wcls-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>