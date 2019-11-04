<?php
/**
 * Bank Billet - Payment instructions.
 *
 * @author  GenPay
 * @package WooCommerce_Rakuten_Pay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
window.addEventListener('load', function() {

    function modal() {
        show();
    }

    function show() {
        var modal = document.querySelector('.modal');

        modal.classList.remove('hide');
        modal.classList.add('show-modal');
    }

    function hide() {
        var modal = document.querySelector('.modal');

        modal.classList.remove('show-modal');
        modal.classList.add('hide');

    }

    var content = document.querySelector('.modal');
    var button = document.getElementById('botao');
    var close = document.getElementById('close');

    content.onclick = function() {
        hide();
    };

    button.onclick = function() {
        modal();
    };

    close.onclick = function() {
        hide();
    }

});
</script>
<style>

.modal {
    visibility: hidden;
    position: fixed;
    z-index: 99;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}
.modal-content {
    position: absolute;
    display: flex;
    justify-content: center;
    width: 50rem;
    height: 100%;
    -webkit-border-radius: 10px;
    -moz-border-radius: 10px;
    border-radius: 10px;
    left: 0;
    right: 0;
    margin: 0 auto;
    background: #fff;
    box-shadow: 2px 2px 20px #333;
}
.show-modal {
    visibility: visible;
    opacity: 1;
    -webkit-transition: visibility 0s, opacity 0.1s linear;
    -moz-transition: visibility 0s, opacity 0.1s linear;
    -ms-transition: visibility 0s, opacity 0.1s linear;
    -o-transition: visibility 0s, opacity 0.1s linear;
    transition: visibility 0s, opacity 0.1s linear;
}
iframe {
    border: none;
    margin-left: 30px;
}
.close {
    position: relative;
    float: right;
    width: 3.5rem;
    line-height: 3.5rem;
    text-align: center;
    cursor: pointer;
    font-size: 40px;
    font-weight: bolder;
    color: #fff;
    z-index: 100;
}
.hide {
    visibility: hidden;
    opacity: 0;
    -webkit-transition: visibility 0s, opacity 0.1s linear;
    -moz-transition: visibility 0s, opacity 0.1s linear;
    -ms-transition: visibility 0s, opacity 0.1s linear;
    -o-transition: visibility 0s, opacity 0.1s linear;
    transition: visibility 0s, opacity 0.1s linear;
}

</style>

<div class="woocommerce-message">
	<span>
        <button class="button modal-btn" id="botao"><?php esc_html_e( 'Pay the banking billet', 'woocommerce-rakuten-pay' ); ?></button><?php esc_html_e( 'Please click in the following button to view your banking billet.', 'woocommerce-rakuten-pay' ); ?><br /><?php esc_html_e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-rakuten-pay' ); ?><br /><?php esc_html_e( 'After we receive the banking billet payment confirmation, your order will be processed.', 'woocommerce-rakuten-pay' ); ?>
        <div id="myModal" class="modal hide">
            <span class="close" id="close">&times;</span>

            <div class="modal-content">
                <iframe src="<?php echo esc_url( $url ); ?>" width="900" height="800"></iframe>
            </div>
        </div>
    </span>
</div>
