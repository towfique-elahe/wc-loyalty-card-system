<?php
// admin/partials/reports.php

if (!defined('ABSPATH')) exit;

global $wpdb;

// Get date range from request
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get summary statistics
$total_points_earned = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(points) FROM {$wpdb->prefix}points_transactions 
     WHERE type = 'earn' 
     AND DATE(created_at) BETWEEN %s AND %s",
    $date_from,
    $date_to
));

$total_points_redeemed = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(ABS(points)) FROM {$wpdb->prefix}points_transactions 
     WHERE type = 'redeem' 
     AND DATE(created_at) BETWEEN %s AND %s",
    $date_from,
    $date_to
));

$total_gift_cards_sold = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}gift_cards 
     WHERE DATE(created_at) BETWEEN %s AND %s",
    $date_from,
    $date_to
));

$total_loyalty_cards_issued = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_cards 
     WHERE DATE(created_at) BETWEEN %s AND %s",
    $date_from,
    $date_to
));

// Get points by tier.
// Tiers are stored as WP options and calculated from lifetime_points — there is no
// tier_id column in loyalty_points, so we compute the distribution in PHP.
$all_user_points = $wpdb->get_results(
    "SELECT user_id, points_balance, lifetime_points FROM {$wpdb->prefix}loyalty_points"
);

$tiers_config      = Tier_Management::get_tiers();
$tier_distribution = array();

// Seed every tier with zero so all tiers always appear in the table.
foreach ($tiers_config as $t) {
    $tier_distribution[$t['name']] = (object) array(
        'tier_name'    => $t['name'],
        'user_count'   => 0,
        'total_points' => 0,
    );
}

foreach ($all_user_points as $row) {
    $user_tier = Tier_Management::get_user_tier($row->user_id);
    $name      = $user_tier['name'];

    if (!isset($tier_distribution[$name])) {
        $tier_distribution[$name] = (object) array(
            'tier_name'    => $name,
            'user_count'   => 0,
            'total_points' => 0,
        );
    }

    $tier_distribution[$name]->user_count++;
    $tier_distribution[$name]->total_points += (int) $row->points_balance;
}

$points_by_tier = array_values($tier_distribution);

// Get daily points activity
$daily_activity = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(created_at) as date,
            SUM(CASE WHEN type = 'earn' THEN points ELSE 0 END) as points_earned,
            SUM(CASE WHEN type = 'redeem' THEN ABS(points) ELSE 0 END) as points_redeemed
     FROM {$wpdb->prefix}points_transactions
     WHERE DATE(created_at) BETWEEN %s AND %s
     GROUP BY DATE(created_at)
     ORDER BY date DESC",
    $date_from,
    $date_to
));
?>

<div class="wrap wcls-admin-wrap">
    <h1><?php _e('Loyalty Reports', 'wc-loyalty-system'); ?></h1>

    <div class="wcls-date-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="wcls-reports" />

            <label for="date_from"><?php _e('From:', 'wc-loyalty-system'); ?></label>
            <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" />

            <label for="date_to"><?php _e('To:', 'wc-loyalty-system'); ?></label>
            <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" />

            <input type="submit" class="button" value="<?php _e('Filter', 'wc-loyalty-system'); ?>" />
        </form>
    </div>

    <div class="wcls-reports-grid">
        <div class="wcls-report-card">
            <h3><?php _e('Points Earned', 'wc-loyalty-system'); ?></h3>
            <p class="wcls-report-number"><?php echo number_format($total_points_earned ?: 0); ?></p>
            <p class="wcls-report-label"><?php _e('Total points earned', 'wc-loyalty-system'); ?></p>
        </div>

        <div class="wcls-report-card">
            <h3><?php _e('Points Redeemed', 'wc-loyalty-system'); ?></h3>
            <p class="wcls-report-number"><?php echo number_format($total_points_redeemed ?: 0); ?></p>
            <p class="wcls-report-label"><?php _e('Total points redeemed', 'wc-loyalty-system'); ?></p>
        </div>

        <div class="wcls-report-card">
            <h3><?php _e('Redemption Rate', 'wc-loyalty-system'); ?></h3>
            <?php 
            $redemption_rate = 0;
            if ($total_points_earned > 0) {
                $redemption_rate = ($total_points_redeemed / $total_points_earned) * 100;
            }
            ?>
            <p class="wcls-report-number"><?php echo number_format($redemption_rate, 1); ?>%</p>
            <p class="wcls-report-label"><?php _e('Points redeemed vs earned', 'wc-loyalty-system'); ?></p>
        </div>

        <div class="wcls-report-card">
            <h3><?php _e('Gift Cards Sold', 'wc-loyalty-system'); ?></h3>
            <p class="wcls-report-number"><?php echo $total_gift_cards_sold ?: 0; ?></p>
            <p class="wcls-report-label"><?php _e('New gift cards issued', 'wc-loyalty-system'); ?></p>
        </div>

        <div class="wcls-report-card">
            <h3><?php _e('Loyalty Cards', 'wc-loyalty-system'); ?></h3>
            <p class="wcls-report-number"><?php echo $total_loyalty_cards_issued ?: 0; ?></p>
            <p class="wcls-report-label"><?php _e('New loyalty cards issued', 'wc-loyalty-system'); ?></p>
        </div>
    </div>

    <div class="wcls-report-section">
        <h2><?php _e('Points Distribution by Tier', 'wc-loyalty-system'); ?></h2>

        <?php if (empty($points_by_tier)): ?>
        <p><?php _e('No data available.', 'wc-loyalty-system'); ?></p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Tier', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Number of Users', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Total Points', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Average Points per User', 'wc-loyalty-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($points_by_tier as $tier): ?>
                <tr>
                    <td><?php echo esc_html($tier->tier_name ?: __('Unassigned', 'wc-loyalty-system')); ?></td>
                    <td><?php echo number_format($tier->user_count); ?></td>
                    <td><?php echo number_format($tier->total_points); ?></td>
                    <td><?php echo $tier->user_count > 0 ? number_format($tier->total_points / $tier->user_count) : 0; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="wcls-report-section">
        <h2><?php _e('Daily Points Activity', 'wc-loyalty-system'); ?></h2>

        <?php if (empty($daily_activity)): ?>
        <p><?php _e('No activity in this period.', 'wc-loyalty-system'); ?></p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Points Earned', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Points Redeemed', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Net Change', 'wc-loyalty-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily_activity as $day): ?>
                <tr>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($day->date)); ?></td>
                    <td class="wcls-positive">+<?php echo number_format($day->points_earned ?: 0); ?></td>
                    <td class="wcls-negative">-<?php echo number_format($day->points_redeemed ?: 0); ?></td>
                    <td><?php echo number_format(($day->points_earned ?: 0) - ($day->points_redeemed ?: 0)); ?></td>
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

.wcls-date-filter {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.wcls-date-filter form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.wcls-date-filter label {
    font-weight: 500;
}

.wcls-date-filter input[type="date"] {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wcls-reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.wcls-report-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.wcls-report-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.wcls-report-number {
    margin: 0;
    font-size: 32px;
    font-weight: bold;
    color: #333;
}

.wcls-report-label {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #999;
}

.wcls-report-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.wcls-report-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.wcls-positive {
    color: #46b450;
    font-weight: bold;
}

.wcls-negative {
    color: #dc3232;
    font-weight: bold;
}
</style>