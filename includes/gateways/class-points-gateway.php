<?php
// includes/gateways/class-points-gateway.php

defined('ABSPATH') or die('Direct access not allowed');

class WC_Points_Gateway extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'points_gateway';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Loyalty Points', 'wc-loyalty-system');
        $this->method_description = __('Pay with loyalty points', 'wc-loyalty-system');
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'wc-loyalty-system'),
                'type' => 'checkbox',
                'label' => __('Enable Points Payment', 'wc-loyalty-system'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'wc-loyalty-system'),
                'type' => 'text',
                'description' => __('Payment method title', 'wc-loyalty-system'),
                'default' => __('Loyalty Points', 'wc-loyalty-system'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'wc-loyalty-system'),
                'type' => 'textarea',
                'description' => __('Payment method description', 'wc-loyalty-system'),
                'default' => __('Pay using your loyalty points (1 point = 1 TK)', 'wc-loyalty-system')
            )
        );
    }
    
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        
        $user_id = get_current_user_id();
        $points = Loyalty_Points::get_user_points($user_id);
        $cart_total = WC()->cart->get_total('edit');
        $min_points = get_option('wcls_min_redemption_points', 100);
        
        $max_points_usable = min($points, floor($cart_total));
        
        if ($points >= $min_points) {
            ?>
<div class="points-payment-fields">
    <p class="points-balance">
        <?php printf(__('Your points balance: %d (Value: %s)', 'wc-loyalty-system'), 
                        $points, 
                        wc_price(Loyalty_Points::get_points_value($points))
                    ); ?>
    </p>
    <p class="points-usage">
        <label for="points_to_use"><?php _e('Points to use:', 'wc-loyalty-system'); ?></label>
        <input type="number" id="points_to_use" name="points_to_use" min="<?php echo $min_points; ?>"
            max="<?php echo $max_points_usable; ?>" step="1" value="0" />
        <span class="points-value">
            <?php _e('Value:', 'wc-loyalty-system'); ?>
            <span id="points_value_display"><?php echo wc_price(0); ?></span>
        </span>
    </p>
    <script>
    jQuery(document).ready(function($) {
        $('#points_to_use').on('input', function() {
            var points = $(this).val();
            var value = points; // 1 point = 1 TK
            $('#points_value_display').text(woocommerce_price(value));
        });
    });
    </script>
</div>
<?php
        } else {
            echo '<p>' . sprintf(__('You need at least %d points to use this payment method.', 'wc-loyalty-system'), $min_points) . '</p>';
        }
    }
    
    public function validate_fields() {
        $user_id = get_current_user_id();
        $points = Loyalty_Points::get_user_points($user_id);
        $points_to_use = isset($_POST['points_to_use']) ? intval($_POST['points_to_use']) : 0;
        $cart_total = WC()->cart->get_total('edit');
        
        if ($points_to_use > 0) {
            if ($points_to_use < get_option('wcls_min_redemption_points', 100)) {
                wc_add_notice(__('Minimum 100 points required for redemption.', 'wc-loyalty-system'), 'error');
                return false;
            }
            
            if ($points_to_use > $points) {
                wc_add_notice(__('Insufficient points balance.', 'wc-loyalty-system'), 'error');
                return false;
            }
            
            if ($points_to_use > $cart_total) {
                wc_add_notice(__('Points value cannot exceed order total.', 'wc-loyalty-system'), 'error');
                return false;
            }
        }
        
        return true;
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        $points_to_use = isset($_POST['points_to_use']) ? intval($_POST['points_to_use']) : 0;
        
        if ($points_to_use > 0) {
            $result = Loyalty_Points::redeem_points($user_id, $points_to_use, $order_id);
            
            if (is_wp_error($result)) {
                wc_add_notice($result->get_error_message(), 'error');
                return array('result' => 'failure');
            }
            
            // Apply discount to order
            $order->add_meta_data('_points_redeemed', $points_to_use);
            $order->add_meta_data('_points_discount', $result);
            $order->save();
            
            // Add order note
            $order->add_order_note(
                sprintf(__('Customer redeemed %d points for a discount of %s', 'wc-loyalty-system'), 
                    $points_to_use, 
                    wc_price($result)
                )
            );
            
            // Recalculate totals
            $order->recalculate_coupons();
        }
        
        // Mark as processing
        $order->payment_complete();
        
        // Empty cart
        WC()->cart->empty_cart();
        
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}