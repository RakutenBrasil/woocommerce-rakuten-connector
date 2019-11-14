<?php
/**
 * Rakuten Log Shipping Method Settings HTML
 *
 * @package WC_Rakuten_Log
 */

if (!defined('ABSPATH')){
    exit;
}

?>

<div class="rakuten-log-metabox">
    <?php if (empty ($tracking_code) ): ?>
        <p class="form-field">
            <label for="_invoice_series"><?php echo esc_html(__('Invoice Series', 'woocommerce-rakuten-log')) ?></label>
            <input class="input-text" type="text" name="_rakuten_log_invoice_series" role="textbox" value="<?php echo esc_html($invoice_series) ?>"/>
        </p>
        <p class="form-field">
            <label for="_invoice_number"><?php echo esc_html(__('Invoice Number', 'woocommerce-rakuten-log')) ?></label>
            <input class="input-text" type="text" name="_rakuten_log_invoice_number" role="textbox" value="<?php echo esc_html($invoice_number) ?>"/>
        </p>
        <p class="form-field">
            <label for="_invoice_key"><?php echo esc_html(__('Invoice Key', 'woocommerce-rakuten-log')) ?></label>
            <input class="input-text" type="text" name="_rakuten_log_invoice_key" role="textbox" value="<?php echo esc_html($invoice_key) ?>"/>
        </p>
        <p class="form-field">
            <label for="_invoice_cfop"><?php echo esc_html(__('Invoice CFOP', 'woocommerce-rakuten-log')) ?></label>
            <input class="input-text" type="text" name="_rakuten_log_invoice_cfop" role="textbox" value="<?php echo esc_html($invoice_cfop) ?>"/>
        </p>
        <p class="form-field">
            <label for="_invoice_date"><?php echo esc_html(__('Invoice Date', 'woocommerce-rakuten-log')) ?></label>
            <input class="date-picker hasDatePicker" type="text" name="_rakuten_log_invoice_date" role="textbox" value="<?php echo esc_html($invoice_date) ?>" />
        </p>
    <?php else: ?>
        <p class="rakuten-log-tracking-code">
            <strong style="display: block"><?php echo esc_html(__('Batch Code', 'woocommerce-rakuten-log')) ?></strong>
            <span aria-label="<?php esc_attr_e( ' Batch code', 'woocommerce-rakuten-log'); ?>"><?php echo esc_html( $batch_code) ?></span>
        </p>
        <p class="rakuten-log-tracking-code">
            <strong style="display: block"><?php echo esc_html(__('Tracking Code', 'woocommerce-rakuten-log')) ?></strong>
            <span aria-label="<?php esc_attr_e( ' Tracking code', 'woocommerce-rakuten-log'); ?>"><?php echo esc_html( $tracking_code) ?></span>
        </p>
        <p class="rakuten-log-volume">
            <strong style="display: block"><?php echo esc_html(__('Volume', 'woocommerce-rakuten-log')) ?></strong>
            <span aria-label="<?php esc_attr_e( 'Volume', 'woocommerce-rakuten-log'); ?>"><?php echo esc_html( $volume) ?></span>
        </p>
        <?php if(!empty($invoice_series)): ?>
            <p>
                <strong style="display: block"><?php echo esc_html(__('Invoice Series', 'woocommerce-rakuten-log')) ?></strong>
                <span><?php echo esc_html($invoice_series) ?></span>
            </p>
        <?php endif; ?>
        <?php if(!empty($invoice_number)): ?>
            <p>
                <strong style="display: block"><?php echo esc_html(__('Invoice Number', 'woocommerce-rakuten-log')) ?></strong>
                <span><?php echo esc_html($invoice_number) ?></span>
            </p>
        <?php endif; ?>
        <?php if(!empty($invoice_key)): ?>
            <p>
                <strong style="display: block"><?php echo esc_html(__('Invoice Key', 'woocommerce-rakuten-log')) ?></strong>
                <span><?php echo esc_html($invoice_key) ?></span>
            </p>
        <?php endif; ?>
        <?php if(!empty($invoice_cfop)): ?>
            <p>
                <strong style="display: block"><?php echo esc_html(__('Invoice CFOP', 'woocommerce-rakuten-log')) ?></strong>
                <span><?php echo esc_html($invoice_cfop) ?></span>
            </p>
        <?php endif; ?>
        <?php if(!empty($invoice_date)): ?>
            <p>
                <strong style="display: block"><?php echo esc_html(__('Invoice Date', 'woocommerce-rakuten-log')) ?></strong>
                <span><?php echo esc_html($invoice_date) ?></span>
            </p>
        <?php endif; ?>

        <p>
            <h4><?php echo esc_html(__('GenLog actions', 'woocommerce-rakuten-log')) ?></h4>
            <a href="<?php echo esc_html($print_url) ?>" id="rkt-print-tag" class="button" target="_blank"><?php echo esc_html(__('Print tag', 'woocommerce-rakuten-log')) ?></a>
            <a href="<?php echo esc_html($print_batch_url) ?>" id="rkt-print-tag" class="button" target="_blank"><?php echo esc_html(__('Print batch tag', 'woocommerce-rakuten-log')) ?></a>
            <a href="<?php echo esc_html($tracking_url) ?>" id="rkt-follow-order-button" class="button" target="_blank"><?php echo esc_html(__('Follow order', 'woocommerce-rakuten-log')) ?></a>
        </p>
    <?php endif; ?>
</div>
