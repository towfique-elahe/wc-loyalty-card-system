<?php
// includes/gateways/class-gift-card-gateway.php

defined('ABSPATH') or die('Direct access not allowed');

class WC_Gift_Card_Gateway extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'gift_card_gateway';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Gift Card', 'wc-loyalty-system');
        $this->method_description = __('Pay with gift cards', 'wc-loyalty-system');
        
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
                'label' => __('Enable Gift Card Payment', 'wc-loyalty-system'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'wc-loyalty-system'),
                'type' => 'text',
                'description' => __('Payment method title', 'wc-loyalty-system'),
                'default' => __('Gift Card', 'wc-loyalty-system'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'wc-loyalty-system'),
                'type' => 'textarea',
                'description' => __('Payment method description', 'wc-loyalty-system'),
                'default' => __('Pay using your gift card', 'wc-loyalty-system')
            )
        );
    }
    
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        
        ?>
<div class="gift-card-payment-fields">
    <p class="form-row">
        <label for="gift_card_number"><?php _e('Gift Card Number', 'wc-loyalty-system'); ?> <span
                class="required">*</span></label>
        <input type="text" id="gift_card_number" name="gift_card_number" class="input-text"
            placeholder="<?php _e('Enter your gift card number', 'wc-loyalty-system'); ?>" />
    </p>
    <p class="form-row">
        <button type="button" id="check_gift_card"
            class="button"><?php _e('Check Balance', 'wc-loyalty-system'); ?></button>
    </p>
    <div id="gift_card_balance_display" style="display:none;"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#check_gift_card').on('click', function() {
        var card_number = $('#gift_card_number').val();
        if (!card_number) {
            alert('<?php _e('Please enter a gift card number', 'wc-loyalty-system'); ?>');
            return;
        }

        $.ajax({
            url: wcls_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_gift_card_balance',
                card_number: card_number,
                nonce: wcls_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#gift_card_balance_display')
                        .html('<p class="success">' + response.data.message + '</p>')
                        .show();
                } else {
                    $('#gift_card_balance_display')
                        .html('<p class="error">' + response.data.message + '</p>')
                        .show();
                }
            }
        });
    });
});
</script>
<?php
    }
    
    public function validate_fields() {
        $card_number = isset($_POST['gift_card_number']) ? sanitize_text_field($_POST['gift_card_number']) : '';
        
        if (empty($card_number)) {
            wc_add_notice(__('Please enter your gift card number.', 'wc-loyalty-system'), 'error');
            return false;
        }
        
        $validation = Gift_Cards::validate_gift_card($card_number);
        
        if (is_wp_error($validation)) {
            wc_add_notice($validation->get_error_message(), 'error');
            return false;
        }
        
        $cart_total = WC()->cart->get_total('edit');
        
        if ($validation->balance < $cart_total) {
            wc_add_notice(
                sprintf(__('Insufficient gift card balance. Available: %s', 'wc-loyalty-system'), 
                    wc_price($validation->balance)
                ), 
                'error'
            );
            return false;
        }
        
        return true;
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $card_number = sanitize_text_field($_POST['gift_card_number']);
        $order_total = $order->get_total();
        
        $result = Gift_Cards::redeem_gift_card($card_number, $order_total, $order_id);
        
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            return array('result' => 'failure');
        }
        
        // Add order meta
        $order->add_meta_data('_gift_card_number', $card_number);
        $order->add_meta_data('_gift_card_amount', $order_total);
        $order->save();
        
        // Add order note
        $order->add_order_note(
            sprintf(__('Paid with gift card %s - Amount: %s', 'wc-loyalty-system'),
                substr($card_number, -4),
                wc_price($order_total)
            )
        );
        
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