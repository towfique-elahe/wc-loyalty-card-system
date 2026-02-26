<?php
// public/templates/loyalty-cards.php

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$cards   = Privilege_Cards::get_user_cards($user_id);

$card_labels = array(
    'privilege' => __('Privilege Card', 'wc-loyalty-system'),
    'investor'  => __('Investor Card', 'wc-loyalty-system'),
    'platinum'  => __('Platinum Card', 'wc-loyalty-system'),
);

$card_colors = array(
    'privilege' => '#4CAF50',
    'investor'  => '#2196F3',
    'platinum'  => '#9C27B0',
);
?>

<div class="wcls-cards-page">
    <h2><?php _e('My Cards', 'wc-loyalty-system'); ?></h2>

    <?php if (empty($cards)): ?>
        <div class="wcls-no-cards">
            <p><?php _e('You do not have any loyalty cards yet.', 'wc-loyalty-system'); ?></p>
            <p><?php echo sprintf(
                __('Spend %s or more in a single order to receive a free Privilege Card, or purchase one below.', 'wc-loyalty-system'),
                wc_price(Privilege_Cards::get_free_card_threshold())
            ); ?></p>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-points')); ?>" class="button">
                <?php _e('View My Points', 'wc-loyalty-system'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="wcls-cards-grid">
            <?php foreach ($cards as $card):
                $type   = esc_html($card->card_type);
                $label  = isset($card_labels[$type]) ? $card_labels[$type] : ucfirst($type);
                $color  = isset($card_colors[$type]) ? $card_colors[$type] : '#333';
                $active = ($card->status === 'active' && (!$card->valid_until || strtotime($card->valid_until) > time()));
            ?>
            <div class="wcls-loyalty-card" style="--card-color: <?php echo esc_attr($color); ?>">
                <div class="wcls-loyalty-card-header">
                    <span class="wcls-card-type-label"><?php echo esc_html($label); ?></span>
                    <span class="wcls-card-status <?php echo $active ? 'active' : 'expired'; ?>">
                        <?php echo $active ? __('Active', 'wc-loyalty-system') : __('Expired', 'wc-loyalty-system'); ?>
                    </span>
                </div>
                <div class="wcls-loyalty-card-number">
                    <?php echo esc_html($card->card_number); ?>
                </div>
                <div class="wcls-loyalty-card-footer">
                    <div class="wcls-card-discount">
                        <span class="wcls-discount-label"><?php _e('Discount', 'wc-loyalty-system'); ?></span>
                        <span class="wcls-discount-value"><?php echo esc_html($card->discount_rate); ?>%</span>
                    </div>
                    <div class="wcls-card-validity">
                        <?php if ($card->valid_until): ?>
                            <span class="wcls-validity-label"><?php _e('Valid until', 'wc-loyalty-system'); ?></span>
                            <span class="wcls-validity-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($card->valid_until)); ?>
                            </span>
                        <?php else: ?>
                            <span class="wcls-validity-label"><?php _e('No expiry', 'wc-loyalty-system'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="wcls-card-info-box">
        <h3><?php _e('About Loyalty Cards', 'wc-loyalty-system'); ?></h3>
        <ul>
            <li><?php echo sprintf(__('<strong>Privilege Card</strong> — %d%% discount on all purchases. Price: %s', 'wc-loyalty-system'),
                Privilege_Cards::get_discount_rate('privilege'),
                wc_price(Privilege_Cards::get_card_price())
            ); ?></li>
            <li><?php echo sprintf(__('<strong>Investor Card</strong> — %d%% discount. Issued by admin only.', 'wc-loyalty-system'),
                Privilege_Cards::get_discount_rate('investor')
            ); ?></li>
            <li><?php echo sprintf(__('<strong>Platinum Card</strong> — %d%% discount. Issued by admin only.', 'wc-loyalty-system'),
                Privilege_Cards::get_discount_rate('platinum')
            ); ?></li>
            <li><?php echo sprintf(__('Spend %s or more in one order to earn a free Privilege Card.', 'wc-loyalty-system'),
                wc_price(Privilege_Cards::get_free_card_threshold())
            ); ?></li>
        </ul>
    </div>
</div>

<style>
.wcls-cards-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.wcls-no-cards {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    margin: 20px 0;
}

.wcls-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0 30px;
}

.wcls-loyalty-card {
    background: linear-gradient(135deg, var(--card-color, #4CAF50) 0%, color-mix(in srgb, var(--card-color, #4CAF50) 70%, #000) 100%);
    color: #fff;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.wcls-loyalty-card::after {
    content: '';
    position: absolute;
    top: -30px;
    right: -30px;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.wcls-loyalty-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.wcls-card-type-label {
    font-size: 14px;
    font-weight: 600;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.wcls-card-status {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.wcls-card-status.active {
    background: rgba(255,255,255,0.3);
}

.wcls-card-status.expired {
    background: rgba(0,0,0,0.3);
}

.wcls-loyalty-card-number {
    font-family: monospace;
    font-size: 13px;
    letter-spacing: 1px;
    opacity: 0.85;
    margin-bottom: 20px;
    word-break: break-all;
}

.wcls-loyalty-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.wcls-card-discount {
    display: flex;
    flex-direction: column;
}

.wcls-discount-label {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
}

.wcls-discount-value {
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
}

.wcls-card-validity {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.wcls-validity-label {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
}

.wcls-validity-date {
    font-size: 14px;
    font-weight: 500;
}

.wcls-card-info-box {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.wcls-card-info-box h3 {
    margin-top: 0;
    color: #2e7d32;
}

.wcls-card-info-box ul {
    margin: 0;
    padding-left: 20px;
}

.wcls-card-info-box li {
    margin-bottom: 8px;
    color: #333;
}
</style>
