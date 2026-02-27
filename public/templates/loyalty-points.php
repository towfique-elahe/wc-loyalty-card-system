<?php
// public/templates/loyalty-points.php

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$points = Loyalty_Points::get_user_points($user_id);
$lifetime_points = Loyalty_Points::get_user_lifetime_points($user_id);
$tier = Tier_Management::get_user_tier($user_id);
$benefits = Tier_Management::get_user_benefits_summary($user_id);
$transactions = Loyalty_Points::get_points_history($user_id, 20);
$progress = Tier_Management::get_tier_progress($user_id);
?>

<div class="wcls-points-page">
    <h2><?php _e('My Loyalty Points', 'wc-loyalty-system'); ?></h2>

    <div class="wcls-points-summary">
        <div class="wcls-summary-card">
            <h3><?php _e('Current Balance', 'wc-loyalty-system'); ?></h3>
            <div class="wcls-points-value"><?php echo $points; ?></div>
            <div class="wcls-points-worth">
                <?php echo sprintf(__('Worth: %s', 'wc-loyalty-system'), wc_price(Loyalty_Points::get_points_value($points))); ?>
            </div>
        </div>

        <div class="wcls-summary-card">
            <h3><?php _e('Lifetime Points', 'wc-loyalty-system'); ?></h3>
            <div class="wcls-lifetime-value"><?php echo $lifetime_points; ?></div>
        </div>

        <div class="wcls-summary-card">
            <h3><?php _e('Current Tier', 'wc-loyalty-system'); ?></h3>
            <div class="wcls-tier-name" style="color: <?php echo $tier['color']; ?>"><?php echo $tier['name']; ?></div>
            <div class="wcls-tier-discount">
                <?php echo sprintf(__('%d%% Discount', 'wc-loyalty-system'), $tier['discount']); ?></div>
        </div>
    </div>

    <div class="wcls-benefits-summary">
        <h3><?php _e('Your Benefits', 'wc-loyalty-system'); ?></h3>

        <div class="wcls-points-tier">
            <h4><?php _e('Points Tier', 'wc-loyalty-system'); ?></h4>
            <p>
                <strong><?php _e('Current Tier:', 'wc-loyalty-system'); ?></strong>
                <span style="color: <?php echo $benefits['points_tier']['color']; ?>">
                    <?php echo $benefits['points_tier']['name']; ?>
                </span>
                (<?php echo $benefits['points_tier']['discount']; ?>% discount)
            </p>
        </div>

        <?php if (!empty($benefits['cards'])): ?>
        <div class="wcls-active-cards">
            <h4><?php _e('Your Active Cards', 'wc-loyalty-system'); ?></h4>
            <div class="wcls-cards-grid">
                <?php foreach ($benefits['cards'] as $card):
                    $type = esc_attr($card['type']);
                    $icons = array(
                        'privilege' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/>',
                        'investor'  => '<path d="M3 3h18v2H3zm0 4h18v2H3zm0 4h12v2H3zm0 4h12v2H3zm0 4h18v2H3z" fill="currentColor"/><path d="M16 14l5-5-1.5-1.5L16 11l-2-2L12.5 10.5z" fill="currentColor"/>',
                        'platinum'  => '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 2.18l7 3.12V11c0 4.52-3.08 8.76-7 9.93-3.92-1.17-7-5.41-7-9.93V6.3l7-3.12z" fill="currentColor"/>',
                    );
                    $icon_path = isset($icons[$card['type']]) ? $icons[$card['type']] : $icons['privilege'];
                ?>
                <div class="wcls-card-visual wcls-card-visual-<?php echo $type; ?>">
                    <div class="wcls-card-visual-top">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true">
                            <?php echo $icon_path; ?>
                        </svg>
                        <span class="wcls-card-visual-name"><?php echo esc_html($card['name']); ?></span>
                    </div>
                    <div class="wcls-card-visual-discount">
                        <?php echo esc_html($card['discount']); ?><span>%</span>
                    </div>
                    <div class="wcls-card-visual-label"><?php _e('Discount on every order', 'wc-loyalty-system'); ?>
                    </div>
                    <div class="wcls-card-visual-footer">
                        <?php if ($card['valid_until']): ?>
                        <span class="wcls-card-valid-label"><?php _e('Valid until', 'wc-loyalty-system'); ?></span>
                        <span
                            class="wcls-card-valid-date"><?php echo date_i18n(get_option('date_format'), strtotime($card['valid_until'])); ?></span>
                        <?php else: ?>
                        <span class="wcls-card-valid-label"><?php _e('No expiry', 'wc-loyalty-system'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="wcls-best-discount">
            <p><strong><?php _e('Your Best Available Discount:', 'wc-loyalty-system'); ?></strong>
                <?php echo $benefits['best_discount']; ?>%</p>
        </div>
    </div>

    <?php if ($progress): ?>
    <div class="wcls-tier-progress-section">
        <h3><?php _e('Tier Progress', 'wc-loyalty-system'); ?></h3>
        <div class="wcls-progress-container">
            <div class="wcls-progress-labels">
                <span><?php echo $progress['current_tier']; ?></span>
                <span><?php echo $progress['next_tier']; ?></span>
            </div>
            <div class="wcls-progress-bar">
                <div class="wcls-progress-fill" style="width: <?php echo $progress['progress']; ?>%;"></div>
            </div>
            <div class="wcls-progress-stats">
                <?php echo sprintf(
                    __('%d of %d points earned (%d remaining)', 'wc-loyalty-system'),
                    $progress['points_earned'],
                    $progress['points_needed'],
                    $progress['points_needed'] - $progress['points_earned']
                ); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="wcls-points-history">
        <h3><?php _e('Points History', 'wc-loyalty-system'); ?></h3>

        <?php if (empty($transactions)): ?>
        <p class="wcls-no-data"><?php _e('No points transactions yet.', 'wc-loyalty-system'); ?></p>
        <?php else: ?>
        <table class="wcls-history-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Description', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Points', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Type', 'wc-loyalty-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($transaction->created_at)); ?></td>
                    <td><?php echo esc_html($transaction->description); ?></td>
                    <td class="<?php echo $transaction->points > 0 ? 'points-earned' : 'points-redeemed'; ?>">
                        <?php echo $transaction->points > 0 ? '+' . $transaction->points : $transaction->points; ?>
                    </td>
                    <td>
                        <?php 
                            $type_labels = array(
                                'earn' => __('Earned', 'wc-loyalty-system'),
                                'redeem' => __('Redeemed', 'wc-loyalty-system'),
                                'admin_adjustment' => __('Admin Adjustment', 'wc-loyalty-system')
                            );
                            echo isset($type_labels[$transaction->type]) ? $type_labels[$transaction->type] : $transaction->type;
                            ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="wcls-points-info">
        <h3><?php _e('How to Earn Points', 'wc-loyalty-system'); ?></h3>
        <ul>
            <li><?php _e('Spend 100 TK = 1 point', 'wc-loyalty-system'); ?></li>
            <li><?php _e('Spend 450 TK = 5 bonus points', 'wc-loyalty-system'); ?></li>
            <li><?php _e('Points are added after order completion', 'wc-loyalty-system'); ?></li>
        </ul>

        <h3><?php _e('How to Redeem Points', 'wc-loyalty-system'); ?></h3>
        <ul>
            <li><?php _e('Minimum 100 points required', 'wc-loyalty-system'); ?></li>
            <li><?php _e('1 point = 1 TK discount', 'wc-loyalty-system'); ?></li>
            <li><?php _e('At checkout, enter how many points to use in the "Loyalty Points" box and click Apply', 'wc-loyalty-system'); ?>
            </li>
            <li><?php _e('The discount is applied to your order total — pay the remainder with any payment method', 'wc-loyalty-system'); ?>
            </li>
        </ul>
    </div>
</div>

<style>
.wcls-points-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.wcls-points-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.wcls-summary-card {
    background: #f8f8f8;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.wcls-summary-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.wcls-points-value {
    font-size: 36px;
    font-weight: bold;
    color: #4CAF50;
}

.wcls-lifetime-value {
    font-size: 36px;
    font-weight: bold;
    color: #2196F3;
}

.wcls-tier-name {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.wcls-points-worth {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.wcls-tier-progress-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
}

.wcls-progress-container {
    margin-top: 15px;
}

.wcls-progress-labels {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 12px;
    color: #666;
}

.wcls-progress-bar {
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
}

.wcls-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    transition: width 0.3s ease;
}

.wcls-progress-stats {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
    text-align: center;
}

.wcls-points-history {
    margin: 30px 0;
}

.wcls-history-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #e0e0e0;
}

.wcls-history-table th,
.wcls-history-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.wcls-history-table th {
    background: #f8f8f8;
    font-weight: bold;
}

.points-earned {
    color: #4CAF50;
    font-weight: bold;
}

.points-redeemed {
    color: #f44336;
    font-weight: bold;
}

.wcls-points-info {
    background: #e3f2fd;
    border: 1px solid #90caf9;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
}

.wcls-points-info h3 {
    margin: 20px 0 10px 0;
}

.wcls-points-info h3:first-child {
    margin-top: 0;
}

.wcls-points-info ul {
    margin: 0 0 20px 0;
    padding-left: 20px;
}

.wcls-points-info li {
    margin-bottom: 5px;
}

.wcls-no-data {
    padding: 30px;
    text-align: center;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    color: #666;
}

/* ── Active Cards ─────────────────────────────── */
.wcls-active-cards {
    margin-top: 20px;
}

.wcls-active-cards h4 {
    margin: 0 0 14px;
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.wcls-cards-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.wcls-card-visual {
    position: relative;
    width: 220px;
    border-radius: 14px;
    padding: 20px 20px 16px;
    color: #fff;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 6px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Subtle diagonal pattern overlay */
.wcls-card-visual::before {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(135deg,
            rgba(255, 255, 255, 0.06) 0px,
            rgba(255, 255, 255, 0.06) 1px,
            transparent 1px,
            transparent 18px);
    pointer-events: none;
}

/* Per-type colours */
.wcls-card-visual-privilege {
    background: linear-gradient(135deg, #b8860b 0%, #daa520 60%, #8b6914 100%);
}

.wcls-card-visual-investor {
    background: linear-gradient(135deg, #1a3c2e 0%, #2e7d52 60%, #145a38 100%);
}

.wcls-card-visual-platinum {
    background: linear-gradient(135deg, #555 0%, #888 60%, #444 100%);
}

.wcls-card-visual-top {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    opacity: 0.92;
    letter-spacing: 0.3px;
}

.wcls-card-visual-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wcls-card-visual-discount {
    font-size: 42px;
    font-weight: 800;
    line-height: 1;
    margin: 6px 0 2px;
    letter-spacing: -1px;
}

.wcls-card-visual-discount span {
    font-size: 22px;
    font-weight: 600;
    vertical-align: top;
    margin-top: 6px;
    display: inline-block;
}

.wcls-card-visual-label {
    font-size: 11px;
    opacity: 0.75;
    letter-spacing: 0.3px;
}

.wcls-card-visual-footer {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 12px;
}

.wcls-card-valid-label {
    opacity: 0.7;
}

.wcls-card-valid-date {
    font-weight: 600;
}

@media (max-width: 480px) {
    .wcls-card-visual {
        width: 100%;
    }
}

.wcls-best-discount {
    margin-top: 20px;
}
</style>