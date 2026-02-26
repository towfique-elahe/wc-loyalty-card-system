<?php
// includes/class-frontend-display.php

defined('ABSPATH') or die('Direct access not allowed');

class Frontend_Display {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'add_loyalty_floating_widget'));
        add_shortcode('loyalty_points_balance', array($this, 'points_balance_shortcode'));
        add_shortcode('loyalty_tier_status', array($this, 'tier_status_shortcode'));
        add_shortcode('buy_privilege_card', array($this, 'buy_privilege_card_shortcode'));
        add_action('woocommerce_review_order_before_payment', array($this, 'checkout_discount_boxes'));
    }
    
    public function add_loyalty_floating_widget() {
        if (!is_user_logged_in()) return;
        
        $user_id = get_current_user_id();
        $points = Loyalty_Points::get_user_points($user_id);
        $tier = Tier_Management::get_user_tier($user_id);
        ?>
<div class="wcls-floating-widget">
    <div class="wcls-widget-toggle">
        <span class="dashicons dashicons-awards"></span>
    </div>
    <div class="wcls-widget-content">
        <h4><?php _e('Your Loyalty Status', 'wc-loyalty-system'); ?></h4>
        <div class="wcls-points">
            <strong><?php _e('Points:', 'wc-loyalty-system'); ?></strong>
            <span><?php echo $points; ?></span>
        </div>
        <div class="wcls-tier">
            <strong><?php _e('Tier:', 'wc-loyalty-system'); ?></strong>
            <span style="color: <?php echo $tier['color']; ?>"><?php echo $tier['name']; ?></span>
        </div>
        <?php if ($tier['discount'] > 0): ?>
        <div class="wcls-discount">
            <strong><?php _e('Discount:', 'wc-loyalty-system'); ?></strong>
            <span><?php echo $tier['discount']; ?>%</span>
        </div>
        <?php endif; ?>
        <a href="<?php echo wc_get_account_endpoint_url('loyalty-points'); ?>" class="button">
            <?php _e('View Details', 'wc-loyalty-system'); ?>
        </a>
    </div>
</div>

<style>
.wcls-floating-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.wcls-widget-toggle {
    width: 50px;
    height: 50px;
    background: #4CAF50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.wcls-widget-toggle .dashicons {
    color: white;
    font-size: 24px;
}

.wcls-widget-content {
    position: absolute;
    bottom: 60px;
    right: 0;
    width: 250px;
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    display: none;
}

.wcls-floating-widget:hover .wcls-widget-content {
    display: block;
}

.wcls-widget-content h4 {
    margin: 0 0 10px 0;
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
}

.wcls-widget-content div {
    margin-bottom: 8px;
}

.wcls-widget-content .button {
    width: 100%;
    text-align: center;
    margin-top: 10px;
}
</style>
<?php
    }
    
    public function points_balance_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to see your points balance.', 'wc-loyalty-system') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $points = Loyalty_Points::get_user_points($user_id);
        
        return '<div class="wcls-points-balance">' . 
               sprintf(__('Your Points Balance: <strong>%d</strong>', 'wc-loyalty-system'), $points) . 
               '</div>';
    }
    
    public function tier_status_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to see your tier status.', 'wc-loyalty-system') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $tier = Tier_Management::get_user_tier($user_id);
        $progress = Tier_Management::get_tier_progress($user_id);
        
        ob_start();
        ?>
<div class="wcls-tier-status">
    <div class="wcls-current-tier">
        <h4><?php _e('Current Tier:', 'wc-loyalty-system'); ?>
            <span style="color: <?php echo $tier['color']; ?>"><?php echo $tier['name']; ?></span>
        </h4>
        <p><?php _e('Discount:', 'wc-loyalty-system'); ?> <?php echo $tier['discount']; ?>%</p>
    </div>

    <?php if ($progress): ?>
    <div class="wcls-tier-progress">
        <p><?php echo sprintf(
                    __('%s points earned towards %s tier', 'wc-loyalty-system'),
                    $progress['points_earned'],
                    $progress['next_tier']
                ); ?></p>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress['progress']; ?>%;"></div>
        </div>
        <p class="points-needed"><?php echo sprintf(
                    __('%s more points needed', 'wc-loyalty-system'),
                    $progress['points_needed'] - $progress['points_earned']
                ); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.wcls-tier-progress .progress-bar {
    width: 100%;
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
    margin: 10px 0;
}

.wcls-tier-progress .progress-fill {
    height: 100%;
    background: #4CAF50;
    transition: width 0.3s ease;
}

.points-needed {
    font-size: 0.9em;
    color: #666;
}
</style>
<?php
        return ob_get_clean();
    }
    
    public function buy_privilege_card_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to purchase a privilege card.', 'wc-loyalty-system') . '</p>';
        }
        
        $card_price = Privilege_Cards::get_card_price();
        $free_threshold = Privilege_Cards::get_free_card_threshold();
        
        ob_start();
        ?>
<div class="wcls-buy-card">
    <h3><?php _e('Privilege Card', 'wc-loyalty-system'); ?></h3>
    <div class="card-details">
        <p class="price"><?php echo sprintf(__('Price: %s', 'wc-loyalty-system'), wc_price($card_price)); ?></p>
        <p class="discount"><?php _e('Get 10% discount on all purchases', 'wc-loyalty-system'); ?></p>
        <p class="free-info"><?php echo sprintf(
                    __('Spend %s or more in a single order and get it FREE!', 'wc-loyalty-system'),
                    wc_price($free_threshold)
                ); ?></p>

        <form method="post" class="cart">
            <?php wp_nonce_field('buy_privilege_card', 'privilege_card_nonce'); ?>
            <input type="hidden" name="buy_privilege_card" value="1" />
            <button type="submit" class="button alt"><?php _e('Buy Now', 'wc-loyalty-system'); ?></button>
        </form>
    </div>
</div>
<?php
        
        // Handle purchase
        if (isset($_POST['buy_privilege_card']) && wp_verify_nonce($_POST['privilege_card_nonce'], 'buy_privilege_card')) {
            $this->handle_privilege_card_purchase();
        }
        
        return ob_get_clean();
    }
    
    private function handle_privilege_card_purchase() {
        $user_id = get_current_user_id();
        
        // Create a custom order for the card
        $order = wc_create_order(array('customer_id' => $user_id));
        
        // Add card as a product
        $card_price = Privilege_Cards::get_card_price();
        
        $item = new WC_Order_Item_Product();
        $item->set_name(__('Privilege Card', 'wc-loyalty-system'));
        $item->set_quantity(1);
        $item->set_subtotal($card_price);
        $item->set_total($card_price);
        
        $order->add_item($item);
        $order->calculate_totals();
        
        // Set payment method
        $order->set_payment_method('bacs'); // You can change this
        $order->set_payment_method_title(__('Direct Bank Transfer', 'wc-loyalty-system'));
        
        // Create order
        $order_id = $order->save();
        
        if ($order_id) {
            wp_redirect($order->get_checkout_payment_url());
            exit;
        }
    }
    
    public function checkout_discount_boxes() {
        if (!is_user_logged_in() || !WC()->session) return;

        $user_id        = get_current_user_id();
        $points         = Loyalty_Points::get_user_points($user_id);
        $min_points     = get_option('wcls_min_redemption_points', 100);
        $applied_points = intval(WC()->session->get('wcls_points_to_use', 0));
        $applied_gc     = WC()->session->get('wcls_gift_card_applied', null);
        ?>
<div class="wcls-checkout-discounts">

    <?php if ($points >= $min_points || $applied_points > 0): ?>
    <div class="wcls-checkout-section wcls-points-section">
        <h3 class="wcls-section-title"><?php _e('Loyalty Points', 'wc-loyalty-system'); ?></h3>
        <?php if ($applied_points > 0): ?>
            <p class="wcls-applied-notice">
                <?php echo sprintf(
                    __('<strong>%d points</strong> applied — discount: <strong>%s</strong>', 'wc-loyalty-system'),
                    $applied_points,
                    wc_price(Loyalty_Points::get_points_value($applied_points))
                ); ?>
                <button type="button" id="wcls-remove-points" class="wcls-remove-btn"><?php _e('Remove', 'wc-loyalty-system'); ?></button>
            </p>
        <?php else: ?>
            <p class="wcls-available-info">
                <?php echo sprintf(
                    __('You have <strong>%d points</strong> available (worth %s)', 'wc-loyalty-system'),
                    $points,
                    wc_price(Loyalty_Points::get_points_value($points))
                ); ?>
            </p>
            <div class="wcls-input-row">
                <input type="number"
                       id="wcls_points_to_use"
                       min="<?php echo esc_attr($min_points); ?>"
                       max="<?php echo esc_attr($points); ?>"
                       step="1"
                       placeholder="<?php echo esc_attr($min_points); ?>" />
                <button type="button" id="wcls-apply-points" class="button alt"><?php _e('Apply', 'wc-loyalty-system'); ?></button>
            </div>
            <p class="wcls-preview-text">
                <?php _e('Discount preview:', 'wc-loyalty-system'); ?>
                <span id="wcls-points-preview">—</span>
            </p>
            <div id="wcls-points-message" class="wcls-inline-message" style="display:none;"></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="wcls-checkout-section wcls-gift-card-section">
        <h3 class="wcls-section-title"><?php _e('Gift Card', 'wc-loyalty-system'); ?></h3>
        <?php if ($applied_gc): ?>
            <p class="wcls-applied-notice">
                <?php echo sprintf(
                    __('Gift card <strong>***%s</strong> applied — discount: <strong>%s</strong>', 'wc-loyalty-system'),
                    esc_html(substr($applied_gc['number'], -4)),
                    wc_price($applied_gc['discount'])
                ); ?>
                <button type="button" id="wcls-remove-gift-card" class="wcls-remove-btn"><?php _e('Remove', 'wc-loyalty-system'); ?></button>
            </p>
        <?php else: ?>
            <div class="wcls-input-row">
                <input type="text"
                       id="wcls_gift_card_number"
                       placeholder="<?php _e('Enter gift card number', 'wc-loyalty-system'); ?>" />
                <button type="button" id="wcls-apply-gift-card" class="button alt"><?php _e('Apply', 'wc-loyalty-system'); ?></button>
            </div>
            <div id="wcls-gift-card-message" class="wcls-inline-message" style="display:none;"></div>
        <?php endif; ?>
    </div>

</div>
<?php
    }
}

// Initialize frontend display
new Frontend_Display();