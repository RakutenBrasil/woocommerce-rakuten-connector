<?php
/**
 * GenLog Helper Functions
 *
 * @package WC_Rakuten_Log
 */

if( !defined('ABSPATH') )
{
    exit;
}

function wc_rakuten_log_get( $order, $key )
{
    $order = get_order($order);
    return $order->get_meta($key);
}

function wc_rakuten_log_get_tracking_code( $order )
{
    return wc_rakuten_log_get($order, '_rakuten_log_tracking_code');
}

function wc_rakuten_log_get_tracking_url( $order )
{
    return wc_rakuten_log_get($order, '_rakuten_log_tracking_url');
}

function wc_rakuten_log_get_print_url( $order )
{
    return wc_rakuten_log_get($order, '_rakuten_log_print_url');
}

function wc_rakuten_log_get_batch_code( $order ){
    return wc_rakuten_log_get($order, '_rakuten_log_batch_code');
}

function wc_rakuten_log_get_batch_print_url( $order ){
    return wc_rakuten_log_get($order, '_rakuten_log_batch_print_url');
}

function wc_rakuten_log_get_volume( $order ){
    return wc_rakuten_log_get($order, '_rakuten_log_volume');
}

function wc_rakuten_log_get_invoice_series( $order ){
    return wc_rakuten_log_get($order, '_rakuten_log_invoice_series');
}

function wc_rakuten_log_get_invoice_number( $order ){
    return wc_rakuten_log_get($order, '_rakuten_log_invoice_number');
}

function wc_rakuten_log_get_invoice_key( $order )
{
    return wc_rakuten_log_get($order, '_rakuten_log_invoice_key');
}

function wc_rakuten_log_get_invoice_cfop( $order )
{
    return wc_rakuten_log_get($order, '_rakuten_log_invoice_cfop');
}

function wc_rakuten_log_get_invoice_date( $order ){
    return wc_rakuten_log_get($order, '_rakuten_log_invoice_date');
}

function wc_rakuten_log_update( $meta_key, $meta_value, $update_value, $order ){
    if( '' === $update_value ){
        $order->delete_meta_data($meta_key);
        $order->save();

        return true;
    } elseif( $update_value !== $meta_value ) {
        $order->update_meta_data($meta_key, $update_value);
        $order->save();

        return true;
    }

    return false;
}

function wc_rakuten_log_update_tracking_code( $order, $tracking_code ){
    $meta_tracking_code = wc_rakuten_log_get_tracking_code($order);
    $tracking_code = sanitize_text_field($tracking_code);
    return wc_rakuten_log_update(
        '_rakuten_log_tracking_code',
        $meta_tracking_code,
        $tracking_code,
        $order
    );
}

function wc_rakuten_log_update_tracking_url( $order, $tracking_url ){
    $meta_tracking_url = wc_rakuten_log_get_tracking_url($order);
    $tracking_url = sanitize_text_field($tracking_url);
    return wc_rakuten_log_update(
        '_rakuten_log_tracking_url',
        $meta_tracking_url,
        $tracking_url,
        $order
    );
}

function wc_rakuten_log_update_print_url( $order, $print_url ){
    $meta_print_url = wc_rakuten_log_get_print_url($order);
    $print_url = sanitize_text_field($print_url);
    return wc_rakuten_log_update(
        '_rakuten_log_print_url',
        $meta_print_url,
        $print_url,
        $order
    );
}

function wc_rakuten_log_update_batch_code( $order, $batch_code ) {
    $meta_batch_code = wc_rakuten_log_get_batch_code($order);
    $batch_code = sanitize_text_field($batch_code);
    return wc_rakuten_log_update(
        '_rakuten_log_batch_code',
        $meta_batch_code,
        $batch_code,
        $order
    );
}

function wc_rakuten_log_update_batch_print_url( $order, $batch_print_url ){
    $meta_batch_print_url = wc_rakuten_log_get_batch_print_url($order);
    $batch_print_url = sanitize_text_field($batch_print_url);
    return wc_rakuten_log_update(
        '_rakuten_log_batch_print_url',
        $meta_batch_print_url,
        $batch_print_url,
        $order
    );
}

function wc_rakuten_log_update_volume( $order, $volume ){
    $meta_volume = wc_rakuten_log_get_volume($order);
    $volume = sanitize_text_field($volume);
    return wc_rakuten_log_update(
        '_rakuten_log_volume',
        $meta_volume,
        $volume,
        $order
    );
}

function wc_rakuten_log_update_invoice_series( $order, $invoice_series ){
    $order = get_order($order);
    $meta_invoice_series = wc_rakuten_log_get_invoice_series( $order );
    return wc_rakuten_log_update(
      '_rakuten_log_invoice_series',
      $meta_invoice_series,
      $invoice_series,
      $order
    );
}

function wc_rakuten_log_update_invoice_number( $order, $invoice_number ){
    $order = get_order($order);
    $meta_invoice_number = wc_rakuten_log_get_invoice_number( $order );
    $invoice_number = sanitize_text_field($invoice_number);
    return wc_rakuten_log_update(
        '_rakuten_log_invoice_number',
        $meta_invoice_number,
        $invoice_number,
        $order
    );
}

function wc_rakuten_log_update_invoice_key( $order, $invoice_key ){
    $order = get_order($order);
    $meta_invoice_key = wc_rakuten_log_get_invoice_key( $order );
    $invoice_key = sanitize_text_field($invoice_key);
    return wc_rakuten_log_update(
        '_rakuten_log_invoice_key',
        $meta_invoice_key,
        $invoice_key,
        $order
    );
}

function wc_rakuten_log_update_invoice_cfop( $order, $invoice_cfop ){
    $order = get_order($order);
    $meta_invoice_cfop = wc_rakuten_log_get_invoice_cfop( $order );
    $invoice_cfop = sanitize_text_field($invoice_cfop);
    return wc_rakuten_log_update(
        '_rakuten_log_invoice_cfop',
        $meta_invoice_cfop,
        $invoice_cfop,
        $order
    );
}

function wc_rakuten_log_update_invoice_date( $order, $invoice_date ){
    $order = get_order($order);
    $meta_invoice_date = wc_rakuten_log_get_invoice_date( $order );
    $invoice_date = sanitize_text_field($invoice_date);
    return wc_rakuten_log_update(
        '_rakuten_log_invoice_date',
        $meta_invoice_date,
        $invoice_date,
        $order
    );
}

function get_order( $order ){
    if ( is_numeric($order) ){
        $order = wc_get_order($order);
    }

    return $order;
}

function wc_rakuten_log_trigger_tracking_email( $order, $tracking_url ){
    $mailer = WC()->mailer();
    $notification = $mailer->emails['WC_Rakuten_Log_Tracking_Email'];

    if ( 'yes' === $notification->enabled ){
        $notification->trigger($order->get_id(), $order, $tracking_url);
    }
}
