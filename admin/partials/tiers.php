<?php
// admin/partials/tiers.php

if (!defined('ABSPATH')) exit;

// Handle tier update
if (isset($_POST['save_tiers']) && check_admin_referer('wcls_save_tiers')) {
    $tiers = array();
    
    for ($i = 0; $i < count($_POST['tier_name']); $i++) {
        if (!empty($_POST['tier_name'][$i])) {
            $tiers[] = array(
                'name' => sanitize_text_field($_POST['tier_name'][$i]),
                'min_points' => intval($_POST['tier_min'][$i]),
                'max_points' => !empty($_POST['tier_max'][$i]) ? intval($_POST['tier_max'][$i]) : null,
                'discount' => floatval($_POST['tier_discount'][$i]),
                'color' => sanitize_hex_color($_POST['tier_color'][$i])
            );
        }
    }
    
    update_option('wcls_loyalty_tiers', $tiers);
    echo '<div class="notice notice-success"><p>' . __('Tiers updated successfully.', 'wc-loyalty-system') . '</p></div>';
}

// Get current tiers
$tiers = get_option('wcls_loyalty_tiers', array(
    array(
        'name' => 'Bronze',
        'min_points' => 0,
        'max_points' => 499,
        'discount' => 0,
        'color' => '#cd7f32'
    ),
    array(
        'name' => 'Silver',
        'min_points' => 500,
        'max_points' => 1999,
        'discount' => 5,
        'color' => '#c0c0c0'
    ),
    array(
        'name' => 'Gold',
        'min_points' => 2000,
        'max_points' => 4999,
        'discount' => 10,
        'color' => '#ffd700'
    ),
    array(
        'name' => 'Platinum',
        'min_points' => 5000,
        'max_points' => null,
        'discount' => 15,
        'color' => '#e5e4e2'
    )
));
?>

<div class="wrap wcls-admin-wrap">
    <h1><?php _e('Loyalty Tiers', 'wc-loyalty-system'); ?></h1>

    <div class="wcls-info-box">
        <p><?php _e('Tiers are automatically assigned based on customer lifetime points. Higher tiers get better discounts.', 'wc-loyalty-system'); ?>
        </p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('wcls_save_tiers'); ?>

        <div class="wcls-tiers-table">
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th><?php _e('Tier Name', 'wc-loyalty-system'); ?></th>
                        <th><?php _e('Min Points', 'wc-loyalty-system'); ?></th>
                        <th><?php _e('Max Points', 'wc-loyalty-system'); ?></th>
                        <th><?php _e('Discount %', 'wc-loyalty-system'); ?></th>
                        <th><?php _e('Color', 'wc-loyalty-system'); ?></th>
                        <th><?php _e('Actions', 'wc-loyalty-system'); ?></th>
                    </tr>
                </thead>
                <tbody id="wcls-tiers-body">
                    <?php foreach ($tiers as $index => $tier): ?>
                    <tr class="tier-row">
                        <td>
                            <input type="text" name="tier_name[]" value="<?php echo esc_attr($tier['name']); ?>"
                                class="regular-text" required />
                        </td>
                        <td>
                            <input type="number" name="tier_min[]" value="<?php echo esc_attr($tier['min_points']); ?>"
                                min="0" step="1" class="small-text" required />
                        </td>
                        <td>
                            <input type="number" name="tier_max[]" value="<?php echo esc_attr($tier['max_points']); ?>"
                                min="0" step="1" class="small-text"
                                placeholder="<?php _e('Unlimited', 'wc-loyalty-system'); ?>" />
                        </td>
                        <td>
                            <input type="number" name="tier_discount[]"
                                value="<?php echo esc_attr($tier['discount']); ?>" min="0" max="100" step="0.1"
                                class="small-text" required />
                        </td>
                        <td>
                            <input type="color" name="tier_color[]" value="<?php echo esc_attr($tier['color']); ?>"
                                class="wcls-color-picker" />
                        </td>
                        <td>
                            <button type="button" class="button wcls-remove-tier"
                                <?php echo $index === 0 ? 'disabled' : ''; ?>>
                                <?php _e('Remove', 'wc-loyalty-system'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">
                            <button type="button" class="button" id="wcls-add-tier">
                                <?php _e('Add New Tier', 'wc-loyalty-system'); ?>
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="save_tiers" class="button-primary"
                value="<?php _e('Save Tiers', 'wc-loyalty-system'); ?>" />
        </p>
    </form>

    <div class="wcls-tier-preview">
        <h2><?php _e('Preview', 'wc-loyalty-system'); ?></h2>
        <div class="wcls-tier-badges">
            <?php foreach ($tiers as $tier): ?>
            <div class="wcls-tier-badge"
                style="background: <?php echo esc_attr($tier['color']); ?>; color: <?php echo wcls_get_contrast_color($tier['color']); ?>">
                <?php echo esc_html($tier['name']); ?> (<?php echo $tier['discount']; ?>% off)
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add new tier
    $('#wcls-add-tier').on('click', function() {
        var newRow = `
            <tr class="tier-row">
                <td><input type="text" name="tier_name[]" value="" class="regular-text" required /></td>
                <td><input type="number" name="tier_min[]" value="0" min="0" step="1" class="small-text" required /></td>
                <td><input type="number" name="tier_max[]" value="" min="0" step="1" class="small-text" placeholder="Unlimited" /></td>
                <td><input type="number" name="tier_discount[]" value="0" min="0" max="100" step="0.1" class="small-text" required /></td>
                <td><input type="color" name="tier_color[]" value="#cccccc" class="wcls-color-picker" /></td>
                <td><button type="button" class="button wcls-remove-tier">Remove</button></td>
            </tr>
        `;
        $('#wcls-tiers-body').append(newRow);
    });

    // Remove tier
    $(document).on('click', '.wcls-remove-tier', function() {
        if ($('.tier-row').length > 1) {
            $(this).closest('tr').remove();
        }
    });
});

// Helper function for contrast color
function wcls_get_contrast_color($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}
</script>

<style>
.wcls-admin-wrap {
    max-width: 1200px;
    margin: 20px 0;
    margin-right: 20px;
}

.wcls-info-box {
    background: #fff;
    border-left: 4px solid #4CAF50;
    padding: 15px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.wcls-tiers-table {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.wcls-tiers-table table {
    border: none;
}

.wcls-tiers-table th {
    font-weight: 600;
}

.wcls-tiers-table td {
    vertical-align: middle;
}

.wcls-color-picker {
    width: 50px;
    height: 30px;
    padding: 2px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wcls-remove-tier {
    color: #dc3232;
}

.wcls-remove-tier:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.wcls-tier-preview {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.wcls-tier-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.wcls-tier-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.wcls-tier-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
</style>