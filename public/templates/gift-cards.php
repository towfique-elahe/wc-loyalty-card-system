<?php
// public/templates/gift-cards.php

if (!defined('ABSPATH')) exit;
?>

<div class="wcls-gift-cards-page">
    <h2><?php _e('Gift Cards', 'wc-loyalty-system'); ?></h2>

    <!-- Check balance section -->
    <div class="wcls-gc-section">
        <h3><?php _e('Check Gift Card Balance', 'wc-loyalty-system'); ?></h3>
        <p><?php _e('Enter a gift card number below to check its current balance.', 'wc-loyalty-system'); ?></p>
        <div class="wcls-gc-check-row">
            <input type="text"
                   id="gift_card_number"
                   class="wcls-gc-input"
                   placeholder="<?php _e('Enter gift card number', 'wc-loyalty-system'); ?>" />
            <button type="button" id="check_gift_card" class="button alt">
                <?php _e('Check Balance', 'wc-loyalty-system'); ?>
            </button>
        </div>
        <div id="gift_card_balance_display" class="wcls-gc-result" style="display:none;"></div>
    </div>

    <!-- How to use section -->
    <div class="wcls-gc-info-box">
        <h3><?php _e('How to Use a Gift Card', 'wc-loyalty-system'); ?></h3>
        <ol>
            <li><?php _e('Add products to your cart and proceed to checkout.', 'wc-loyalty-system'); ?></li>
            <li><?php _e('In the <strong>Gift Card</strong> box (shown above the payment section), enter your gift card number.', 'wc-loyalty-system'); ?></li>
            <li><?php _e('Click <strong>Apply</strong> — the discount will be deducted from your order total automatically.', 'wc-loyalty-system'); ?></li>
            <li><?php _e('If your gift card balance is more than the order total, the remaining balance stays on the card for future use.', 'wc-loyalty-system'); ?></li>
        </ol>
    </div>

    <!-- Available gift card types -->
    <?php
    $gc_types = Gift_Cards::get_gift_card_types();
    if (!empty($gc_types)):
    ?>
    <div class="wcls-gc-types-section">
        <h3><?php _e('Available Gift Card Denominations', 'wc-loyalty-system'); ?></h3>
        <div class="wcls-gc-types-grid">
            <?php foreach ($gc_types as $gc): ?>
            <div class="wcls-gc-type-card">
                <div class="wcls-gc-type-name"><?php echo esc_html($gc['name']); ?></div>
                <div class="wcls-gc-type-amount"><?php echo wc_price($gc['amount']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="wcls-gc-purchase-note">
            <?php _e('To purchase a gift card, please contact us or visit our store.', 'wc-loyalty-system'); ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<style>
.wcls-gift-cards-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.wcls-gc-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.wcls-gc-section h3 {
    margin-top: 0;
}

.wcls-gc-check-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.wcls-gc-input {
    flex: 1;
    min-width: 200px;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.wcls-gc-input:focus {
    border-color: #4caf50;
    outline: none;
}

.wcls-gc-result {
    margin-top: 12px;
    padding: 10px 14px;
    border-radius: 4px;
    font-size: 14px;
}

.wcls-gc-result.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.wcls-gc-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.wcls-gc-info-box {
    background: #e3f2fd;
    border: 1px solid #90caf9;
    border-radius: 8px;
    padding: 20px 24px;
    margin-bottom: 20px;
}

.wcls-gc-info-box h3 {
    margin-top: 0;
    color: #1565c0;
}

.wcls-gc-info-box ol {
    margin: 0;
    padding-left: 20px;
}

.wcls-gc-info-box li {
    margin-bottom: 8px;
    color: #333;
}

.wcls-gc-types-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
}

.wcls-gc-types-section h3 {
    margin-top: 0;
}

.wcls-gc-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    margin: 16px 0;
}

.wcls-gc-type-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: #fff;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
}

.wcls-gc-type-name {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.wcls-gc-type-amount {
    font-size: 22px;
    font-weight: bold;
}

.wcls-gc-purchase-note {
    font-size: 13px;
    color: #666;
    margin: 0;
}
</style>
