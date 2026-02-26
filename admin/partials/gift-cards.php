<?php
// admin/partials/gift-cards.php

if (!defined('ABSPATH')) exit;

global $wpdb;

// Handle create gift card
if (isset($_POST['create_gift_card']) && check_admin_referer('wcls_create_gift_card')) {
    $amount = floatval($_POST['gift_card_amount']);
    $user_id = get_current_user_id();
    
    $card_number = Gift_Cards::create_gift_card($amount, $user_id);
    
    if ($card_number) {
        echo '<div class="notice notice-success"><p>' . sprintf(__('Gift card created successfully! Card number: %s', 'wc-loyalty-system'), $card_number) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . __('Failed to create gift card.', 'wc-loyalty-system') . '</p></div>';
    }
}

// Handle delete gift card
if (isset($_GET['delete_gift_card']) && check_admin_referer('delete_gift_card')) {
    $card_id = intval($_GET['delete_gift_card']);
    $wpdb->delete($wpdb->prefix . 'gift_cards', array('id' => $card_id), array('%d'));
    echo '<div class="notice notice-success"><p>' . __('Gift card deleted.', 'wc-loyalty-system') . '</p></div>';
}

// Get all gift cards
$gift_cards = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}gift_cards 
     ORDER BY created_at DESC 
     LIMIT 50"
);

// Get gift card types
$gift_card_types = get_option('wcls_gift_card_types', array(
    array('name' => 'Gift Card 500', 'amount' => 500),
    array('name' => 'Gift Card 1000', 'amount' => 1000),
    array('name' => 'Gift Card 2000', 'amount' => 2000),
    array('name' => 'Gift Card 5000', 'amount' => 5000)
));
?>

<div class="wrap wcls-admin-wrap">
    <h1><?php _e('Gift Cards', 'wc-loyalty-system'); ?></h1>

    <div class="wcls-two-column">
        <div class="wcls-column">
            <div class="wcls-card">
                <h2><?php _e('Create New Gift Card', 'wc-loyalty-system'); ?></h2>

                <form method="post" action="">
                    <?php wp_nonce_field('wcls_create_gift_card'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label
                                    for="gift_card_amount"><?php _e('Card Amount (TK)', 'wc-loyalty-system'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="gift_card_amount" name="gift_card_amount" min="1" step="1"
                                    required class="regular-text" />
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="create_gift_card" class="button-primary"
                            value="<?php _e('Create Gift Card', 'wc-loyalty-system'); ?>" />
                    </p>
                </form>
            </div>

            <div class="wcls-card">
                <h2><?php _e('Gift Card Types', 'wc-loyalty-system'); ?></h2>
                <p><?php _e('Available gift card values for customers:', 'wc-loyalty-system'); ?></p>

                <ul class="wcls-gift-card-types">
                    <?php foreach ($gift_card_types as $type): ?>
                    <li><?php echo esc_html($type['name']); ?> - <?php echo wc_price($type['amount']); ?></li>
                    <?php endforeach; ?>
                </ul>

                <p class="description">
                    <?php _e('These are shown to customers when purchasing gift cards.', 'wc-loyalty-system'); ?></p>
            </div>
        </div>

        <div class="wcls-column">
            <div class="wcls-card">
                <h2><?php _e('Recent Gift Cards', 'wc-loyalty-system'); ?></h2>

                <?php if (empty($gift_cards)): ?>
                <p><?php _e('No gift cards created yet.', 'wc-loyalty-system'); ?></p>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Card Number', 'wc-loyalty-system'); ?></th>
                            <th><?php _e('Initial Amount', 'wc-loyalty-system'); ?></th>
                            <th><?php _e('Balance', 'wc-loyalty-system'); ?></th>
                            <th><?php _e('Status', 'wc-loyalty-system'); ?></th>
                            <th><?php _e('Expiry', 'wc-loyalty-system'); ?></th>
                            <th><?php _e('Actions', 'wc-loyalty-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gift_cards as $card): ?>
                        <tr>
                            <td><code><?php echo esc_html($card->card_number); ?></code></td>
                            <td><?php echo wc_price($card->initial_amount); ?></td>
                            <td><?php echo wc_price($card->balance); ?></td>
                            <td>
                                <span class="wcls-status-<?php echo esc_attr($card->status); ?>">
                                    <?php echo ucfirst($card->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    if ($card->expiry_date) {
                                        echo date_i18n(get_option('date_format'), strtotime($card->expiry_date));
                                        if (strtotime($card->expiry_date) < time()) {
                                            echo ' <span class="wcls-expired">(' . __('Expired', 'wc-loyalty-system') . ')</span>';
                                        }
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                            </td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wcls-gift-cards&delete_gift_card=' . $card->id), 'delete_gift_card'); ?>"
                                    class="wcls-delete"
                                    onclick="return confirm('<?php _e('Are you sure?', 'wc-loyalty-system'); ?>')">
                                    <?php _e('Delete', 'wc-loyalty-system'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.wcls-admin-wrap {
    margin: 20px 0;
}

.wcls-two-column {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
}

.wcls-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.wcls-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
}

.wcls-status-active {
    color: #46b450;
    font-weight: bold;
}

.wcls-status-inactive {
    color: #dc3232;
    font-weight: bold;
}

.wcls-status-used {
    color: #ffb900;
    font-weight: bold;
}

.wcls-expired {
    color: #dc3232;
    font-size: 12px;
}

.wcls-gift-card-types {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.wcls-gift-card-types li {
    margin-bottom: 5px;
}

.wcls-delete {
    color: #dc3232;
    text-decoration: none;
}

.wcls-delete:hover {
    color: #a00;
}

@media (max-width: 768px) {
    .wcls-two-column {
        grid-template-columns: 1fr;
    }
}
</style>