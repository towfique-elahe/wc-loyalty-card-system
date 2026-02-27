<?php
// admin/partials/points-settings.php

if (!defined('ABSPATH')) exit;

// Save settings if form submitted
if (isset($_POST['save_points_settings']) && check_admin_referer('wcls_points_settings')) {
    update_option('wcls_points_rate_100', intval($_POST['wcls_points_rate_100']));
    update_option('wcls_points_rate_450', intval($_POST['wcls_points_rate_450']));
    update_option('wcls_min_redemption_points', intval($_POST['wcls_min_redemption_points']));
    update_option('wcls_points_expiry_days', intval($_POST['wcls_points_expiry_days']));
    echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'wc-loyalty-system') . '</p></div>';
}

// Get current values
$rate_100 = get_option('wcls_points_rate_100', 1);
$rate_450 = get_option('wcls_points_rate_450', 5);
$min_redemption = get_option('wcls_min_redemption_points', 100);
$expiry_days = get_option('wcls_points_expiry_days', 365);
?>

<div class="wrap wcls-admin-wrap">
    <h1><?php _e('Points Settings', 'wc-loyalty-system'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('wcls_points_settings'); ?>

        <div class="wcls-settings-section">
            <h2><?php _e('Points Earning Rates', 'wc-loyalty-system'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wcls_points_rate_100"><?php _e('100 TK = ? Points', 'wc-loyalty-system'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="wcls_points_rate_100" name="wcls_points_rate_100"
                            value="<?php echo $rate_100; ?>" min="0" step="1" class="small-text" />
                        <p class="description"><?php _e('Points earned for every 100 TK spent', 'wc-loyalty-system'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="wcls_points_rate_450"><?php _e('450 TK = ? Points', 'wc-loyalty-system'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="wcls_points_rate_450" name="wcls_points_rate_450"
                            value="<?php echo $rate_450; ?>" min="0" step="1" class="small-text" />
                        <p class="description">
                            <?php _e('Additional points earned for every 450 TK spent', 'wc-loyalty-system'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label
                            for="wcls_min_redemption_points"><?php _e('Minimum Points for Redemption', 'wc-loyalty-system'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="wcls_min_redemption_points" name="wcls_min_redemption_points"
                            value="<?php echo $min_redemption; ?>" min="1" step="1" class="small-text" />
                        <p class="description">
                            <?php _e('Minimum points required to redeem for discounts', 'wc-loyalty-system'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="wcls-settings-section">
            <h2><?php _e('Points Expiration', 'wc-loyalty-system'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label
                            for="wcls_points_expiry_days"><?php _e('Points Expiry (days)', 'wc-loyalty-system'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="wcls_points_expiry_days" name="wcls_points_expiry_days"
                            value="<?php echo $expiry_days; ?>" min="0" step="1" class="small-text" />
                        <p class="description"><?php _e('Set to 0 for no expiry', 'wc-loyalty-system'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="wcls-settings-section">
            <h2><?php _e('Points Value', 'wc-loyalty-system'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Point Value', 'wc-loyalty-system'); ?>
                    </th>
                    <td>
                        <p><strong>1 point = 1 TK</strong></p>
                        <p class="description"><?php _e('This is fixed and cannot be changed', 'wc-loyalty-system'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="save_points_settings" class="button-primary"
                value="<?php _e('Save Settings', 'wc-loyalty-system'); ?>" />
        </p>
    </form>
</div>

<style>
.wcls-admin-wrap {
    max-width: 800px;
    margin: 20px 0;
    margin-right: 20px;
}

.wcls-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.wcls-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-table th {
    width: 200px;
}

.small-text {
    width: 80px;
}
</style>