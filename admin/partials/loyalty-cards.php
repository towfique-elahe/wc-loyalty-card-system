<?php
// admin/partials/loyalty-cards.php

if (!defined('ABSPATH')) exit;

global $wpdb;

// Handle card status update
if (isset($_POST['update_card_status']) && check_admin_referer('update_card_status')) {
    $card_id = intval($_POST['card_id']);
    $status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $wpdb->prefix . 'loyalty_cards',
        array('status' => $status),
        array('id' => $card_id),
        array('%s'),
        array('%d')
    );
    
    echo '<div class="notice notice-success"><p>' . __('Card status updated.', 'wc-loyalty-system') . '</p></div>';
}

// Get all loyalty cards
$loyalty_cards = $wpdb->get_results(
    "SELECT c.*, u.display_name 
     FROM {$wpdb->prefix}loyalty_cards c
     LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
     ORDER BY c.created_at DESC 
     LIMIT 50"
);

// Get settings
$privilege_price = get_option('wcls_privilege_card_price', 500);
$free_threshold = get_option('wcls_free_card_threshold', 2000);
$privilege_discount = get_option('wcls_privilege_card_discount', 10);
$investor_discount = get_option('wcls_investor_card_discount', 20);
$platinum_discount = get_option('wcls_platinum_card_discount', 20);
?>

<div class="wrap wcls-admin-wrap">
    <h1><?php _e('Loyalty Cards', 'wc-loyalty-system'); ?></h1>

    <div class="wcls-settings-summary">
        <div class="wcls-summary-item">
            <h3><?php _e('Privilege Card', 'wc-loyalty-system'); ?></h3>
            <p><?php echo sprintf(__('Price: %s', 'wc-loyalty-system'), wc_price($privilege_price)); ?></p>
            <p><?php echo sprintf(__('Discount: %d%%', 'wc-loyalty-system'), $privilege_discount); ?></p>
            <p><?php echo sprintf(__('Free with order ≥ %s', 'wc-loyalty-system'), wc_price($free_threshold)); ?></p>
        </div>

        <div class="wcls-summary-item">
            <h3><?php _e('Investor Card', 'wc-loyalty-system'); ?></h3>
            <p><?php echo sprintf(__('Discount: %d%%', 'wc-loyalty-system'), $investor_discount); ?></p>
            <p class="description"><?php _e('Special issuance only', 'wc-loyalty-system'); ?></p>
        </div>

        <div class="wcls-summary-item">
            <h3><?php _e('Platinum Card', 'wc-loyalty-system'); ?></h3>
            <p><?php echo sprintf(__('Discount: %d%%', 'wc-loyalty-system'), $platinum_discount); ?></p>
            <p class="description"><?php _e('Special issuance only', 'wc-loyalty-system'); ?></p>
        </div>
    </div>

    <div class="wcls-cards-list">
        <h2><?php _e('Issued Loyalty Cards', 'wc-loyalty-system'); ?></h2>

        <?php if (empty($loyalty_cards)): ?>
        <p><?php _e('No loyalty cards issued yet.', 'wc-loyalty-system'); ?></p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Card Number', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('User', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Card Type', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Discount', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Status', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Valid Until', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Issued', 'wc-loyalty-system'); ?></th>
                    <th><?php _e('Actions', 'wc-loyalty-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loyalty_cards as $card): ?>
                <tr>
                    <td><code><?php echo esc_html($card->card_number); ?></code></td>
                    <td><?php echo esc_html($card->display_name ?: 'User #' . $card->user_id); ?></td>
                    <td>
                        <span class="wcls-card-type wcls-card-<?php echo esc_attr($card->card_type); ?>">
                            <?php echo ucfirst($card->card_type); ?>
                        </span>
                    </td>
                    <td><?php echo $card->discount_rate; ?>%</td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('update_card_status'); ?>
                            <input type="hidden" name="card_id" value="<?php echo $card->id; ?>" />
                            <select name="status" onchange="this.form.submit()">
                                <option value="active" <?php selected($card->status, 'active'); ?>>
                                    <?php _e('Active', 'wc-loyalty-system'); ?></option>
                                <option value="inactive" <?php selected($card->status, 'inactive'); ?>>
                                    <?php _e('Inactive', 'wc-loyalty-system'); ?></option>
                                <option value="expired" <?php selected($card->status, 'expired'); ?>>
                                    <?php _e('Expired', 'wc-loyalty-system'); ?></option>
                            </select>
                            <input type="hidden" name="update_card_status" value="1" />
                        </form>
                    </td>
                    <td>
                        <?php 
                            if ($card->valid_until) {
                                echo date_i18n(get_option('date_format'), strtotime($card->valid_until));
                                if (strtotime($card->valid_until) < time() && $card->status == 'active') {
                                    echo ' <span class="wcls-expired-warning">(' . __('Expired', 'wc-loyalty-system') . ')</span>';
                                }
                            } else {
                                echo '—';
                            }
                            ?>
                    </td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($card->created_at)); ?></td>
                    <td>
                        <?php if ($card->order_id): ?>
                        <a href="<?php echo admin_url('post.php?post=' . $card->order_id . '&action=edit'); ?>">
                            <?php _e('View Order', 'wc-loyalty-system'); ?> #<?php echo $card->order_id; ?>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="wcls-special-cards-section">
        <h2><?php _e('Special Cards (Investor & Platinum)', 'wc-loyalty-system'); ?></h2>
        <p class="description">
            <?php _e('These cards are issued manually by administrators only.', 'wc-loyalty-system'); ?></p>

        <form method="post" action="" class="wcls-issue-special-card">
            <?php wp_nonce_field('wcls_issue_special_card'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="card_user"><?php _e('Select User', 'wc-loyalty-system'); ?></label>
                    </th>
                    <td>
                        <select name="user_id" id="card_user" required>
                            <option value=""><?php _e('Select a user', 'wc-loyalty-system'); ?></option>
                            <?php
                            $users = get_users(array('fields' => array('ID', 'display_name', 'user_email')));
                            foreach ($users as $user) {
                                echo '<option value="' . $user->ID . '">' . $user->display_name . ' (' . $user->user_email . ')</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="card_type"><?php _e('Card Type', 'wc-loyalty-system'); ?></label>
                    </th>
                    <td>
                        <select name="card_type" id="card_type" required>
                            <option value="investor"><?php _e('Investor Card (20% discount)', 'wc-loyalty-system'); ?>
                            </option>
                            <option value="platinum"><?php _e('Platinum Card (20% discount)', 'wc-loyalty-system'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="issue_special_card" class="button-primary"
                    value="<?php _e('Issue Special Card', 'wc-loyalty-system'); ?>" />
            </p>
        </form>
    </div>

    <?php
    // Handle special card issuance
    if (isset($_POST['issue_special_card']) && check_admin_referer('wcls_issue_special_card')) {
        $user_id = intval($_POST['user_id']);
        $card_type = sanitize_text_field($_POST['card_type']);
        
        $result = Privilege_Cards::issue_special_card($user_id, $card_type, get_current_user_id());
        
        if ($result && !is_wp_error($result)) {
            echo '<div class="notice notice-success"><p>' . sprintf(__('Special card issued successfully! Card number: %s', 'wc-loyalty-system'), $result) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to issue special card.', 'wc-loyalty-system') . '</p></div>';
        }
    }
    ?>
</div>

<style>
.wcls-admin-wrap {
    margin: 20px 0;
}

.wcls-settings-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.wcls-summary-item {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.wcls-summary-item h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.wcls-summary-item p {
    margin: 5px 0;
}

.wcls-card-type {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.wcls-card-privilege {
    background: #e3f2fd;
    color: #0d47a1;
}

.wcls-card-investor {
    background: #e8f5e9;
    color: #1b5e20;
}

.wcls-card-platinum {
    background: #f3e5f5;
    color: #4a148c;
}

.wcls-expired-warning {
    color: #dc3232;
    font-size: 12px;
}

.wcls-cards-list {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
}

.wcls-cards-list h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
</style>