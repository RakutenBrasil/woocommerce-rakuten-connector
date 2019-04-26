<?php
/**
 * Credit Card - Checkout form.
 *
 * @author  Rakuten Pay
 * @package WooCommerce_Rakuten_Pay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<fieldset id="rakuten-pay-credit-cart-form">
    <input type="hidden" data-rkp="method" value="credit_card">
    <input type="hidden" name="rakuten_pay_token" id='credit-card-token' value="">
    <input type="hidden" name="rakuten_pay_card_brand" id="credit-card-brand" value="">
    <input type="hidden" name="rakuten_pay_card_expiry_year" id="credit-card-expiry-year" value="">
    <input type="hidden" name="rakuten_pay_card_expiry_month" id="credit-card-expiry-month" value="">

    <p class="form-row form-row-wide">
        <label for="rakuten-pay-card-holder-name"><?php esc_html_e( 'Card Holder Name', 'woocommerce-rakuten-pay' ); ?><span class="required">*</span></label>
        <input id="rakuten-pay-card-holder-name" class="input-text" data-rkp="card-holder-name" name="rakuten_pay_card_holder_name" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
    </p>
    <p class="form-row form-row-wide">
        <label for="rakuten-pay-card-holder-document"><?php esc_html_e( 'Card Holder Document', 'woocommerce-rakuten-pay' ); ?><span class="required">*</span></label>
        <input id="rakuten-pay-card-holder-document" class="input-text" data-rkp="card-holder-document" name="rakuten_pay_card_holder_document" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
    </p>
    <p class="form-row form-row-wide">
        <label for="rakuten-pay-card-number"><?php esc_html_e( 'Card Number', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <input id="rakuten-pay-card-number" class="input-text wc-credit-card-form-card-number" data-rkp="card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
    </p>
    <div class="clear"></div>
    <p class="form-row form-row-first">
        <label for="rakuten-pay-card-expiry-month"><?php esc_html_e( 'Expiry Month', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <select name="rakuten_pay_card_expiry_month" data-rkp="card-expiration-month" id="rakuten-pay-card-expiry-month" style="font-size: 1.5em; padding: 8px; width: 100%;">
            <?php
            foreach ( range(1, 12) as $n ) :
                $month = sprintf("%02d", $n);
                ?>

                <option value="<?php echo $month; ?>"><?php echo $month; ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="form-row form-row-last">
        <label for="rakuten-pay-card-expiry-year"><?php esc_html_e( 'Expiry Year', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <select name="rakuten_pay_card_expiry_year" data-rkp="card-expiration-year" id="rakuten-pay-card-expiry-year" style="font-size: 1.5em; padding: 8px; width: 100%;">
            <?php
            foreach ( range(2018, 2038) as $year ) :
                ?>
                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <div class="clear"></div>
    <p class="form-row form-row-first">
        <label for="rakuten-pay-card-cvc"><?php esc_html_e( 'Card Code', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <input id="rakuten-pay-card-cvc" class="input-text wc-credit-card-form-card-cvc" name="rakuten_pay_card_cvc" data-rkp="card-cvv" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'CVC', 'woocommerce-rakuten-pay' ); ?>" style="font-size: 1.5em; padding: 8px;" />
    </p>
    <p class="form-row form-row-last">
        <label for="rakuten-pay-card-installments"><?php esc_html_e( 'Installments', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <select name="rakuten_pay_installments" id="rakuten-pay-card-installments" style="font-size: 1.5em; padding: 8px; width: 100%;" onchange="installments_value()">
            <?php


            if ( $buyer_interest == 'yes' ) {

            foreach ( $installments as $installment ) :
                $installment_number = $installment['quantity'];

                $decimals           = wc_get_price_decimals();
                $decimal_separator  = wc_get_price_decimal_separator();
                $thousand_separator = wc_get_price_thousand_separator();
                $installment_amount = number_format( $installment['installment_amount'], $decimals, $decimal_separator, $thousand_separator );
                $interest_amount    = number_format( $installment['interest_amount'], $decimals, $decimal_separator, $thousand_separator );
                ?>
                <option value="<?php echo absint( $installment_number ); ?>"><?php printf( esc_html__( '%1$dx of %2$s (increase of %3$s)', 'woocommerce-rakuten-pay' ), absint( $installment['quantity'] ), esc_html( $installment_amount ), esc_html( $interest_amount ) ); ?></option>
            <?php endforeach; ?>

        </select>
        <script type="text/javascript">

            function installments_value() {

                var installments = document.getElementById('rakuten-pay-card-installments');
                var raw_text = installments.options[installments.selectedIndex].text;
                var text_list = raw_text.split(" ");

                var cart_subtotal = document.querySelectorAll('tr.cart-subtotal .woocommerce-Price-amount');
                var subtotal_value = cart_subtotal[0].innerText;

                var subtotal_number = subtotal_value.replace('R$', '');
                var interest = text_list[5].replace(')', '');
                var total_interest = parseFloat(subtotal_number.replace(',', '.')) + parseFloat(interest.replace(',', '.'));

                var total_value = document.getElementsByClassName('woocommerce-Price-amount');
                var text_value = total_value[total_value.length - 1];

                text_value.innerHTML = 'R$' + parseFloat(total_interest);
            }

            jQuery(document).ready(function () {
                jQuery('#rakuten-pay-card-holder-document').inputmask({mask: ['999.999.999-99', '99.999.999/9999-99']});

                var installments = document.getElementById('rakuten-pay-card-installments');
                var raw_text = installments.options[installments.selectedIndex].text;
                var text_list = raw_text.split(" ");

                var cart_subtotal = document.querySelectorAll('tr.cart-subtotal .woocommerce-Price-amount');
                var subtotal_value = cart_subtotal[0].innerText;

                var subtotal_number = subtotal_value.replace('R$', '');
                var interest = text_list[5].replace(')', '');
                var total_interest = parseFloat(subtotal_number.replace(',', '.')) + parseFloat(interest.replace(',', '.'));

                jQuery('#payment_method_rakuten-pay-banking-billet').click(function () {
                    var total_value = document.getElementsByClassName('woocommerce-Price-amount');
                    var text_value = total_value[total_value.length - 1];

                    text_value.innerHTML = subtotal_value;
                });

                jQuery('#payment_method_rakuten-pay-credit-card').click(function () {

                    var total_value_cart = document.getElementsByClassName('woocommerce-Price-amount');
                    var text_value = total_value_cart[total_value_cart.length - 1];

                    // text_value.innerHTML = 'R$' + text_list[2];
                    text_value.innerHTML = 'R$' + parseFloat(total_interest);
                    // console.log(parseFloat(total_interest));
                });
            });

        </script>
        <?php
        } else {
            $price = WC()->cart->total;
            $price_installment = WC()->cart->total;
            $installment = 1;
            for ($max = 1; $max <= $max_installment; $max++) {
                $price_installment = $price / $max;

                if($price_installment < $smallest_installment) {
                    break;
                }

                echo "<option value='${max}'>${installment}x de " . number_format($price_installment, 2) . " (sem juros)</option>";
                $installment++;
            }
            echo "
        </select>
        <script type=\"text/javascript\">
            jQuery(document).ready(function(){
                jQuery( '#rakuten-pay-card-holder-document' ).inputmask({mask: ['999.999.999-99', '99.999.999/9999-99']});
            });
        </script>";
        }
        ?>
    </p>
</fieldset>
