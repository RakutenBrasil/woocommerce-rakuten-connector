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
<style>
    .border-error {
        border: 1px solid #ff0000 !important;
    }
    .border-success {
        border: 1px solid #6ae8a6 !important;
    }
    .show { display: block; color: #ff0000; font-weight: 500; }
    .hide { display: none;}
</style>

<fieldset id="rakuten-pay-credit-cart-form">
    <input type="hidden" data-rkp="method" value="credit_card">
    <input type="hidden" name="rakuten_pay_token" id='credit-card-token' value="">
    <input type="hidden" name="rakuten_pay_card_brand" id="credit-card-brand" value="">
    <input type="hidden" name="rakuten_pay_card_expiry_year" id="credit-card-expiry-year" value="">
    <input type="hidden" name="rakuten_pay_card_expiry_month" id="credit-card-expiry-month" value="">

    <p class="form-row form-row-wide">
        <label for="rakuten-pay-card-holder-name"><?php esc_html_e( 'Card Holder Name', 'woocommerce-rakuten-pay' ); ?><span class="required">*</span></label>
        <input id="rakuten-pay-card-holder-name" class="input-text" onchange="validateCardHolderName()" data-rkp="card-holder-name" name="rakuten_pay_card_holder_name" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
        <span id="card-name-error" class="hide">Campo nome do cartão está em branco ou contém números.</span>
    </p>
    <p class="form-row form-row-wide">
        <label for="rakuten-pay-card-holder-document"><?php esc_html_e( 'Card Holder Document', 'woocommerce-rakuten-pay' ); ?><span class="required">*</span></label>
        <input id="rakuten-pay-card-holder-document" class="input-text" data-rkp="card-holder-document" name="rakuten_pay_card_holder_document" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
        <span id="error-cpf" class="hide">Campo CPF/CNPJ do cartão inválido.</span>
    </p>
    <p class="form-row form-row-wide">
        <label for="rakuten-pay-card-number"><?php esc_html_e( 'Card Number', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <input id="rakuten-pay-card-number" class="input-text wc-credit-card-form-card-number" onchange="validateCardNumber()" data-rkp="card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
        <span id="card-number-error" class="hide">Campo número do cartão está em branco ou contém letras.</span>
        <span id="card-number-error-digits" class="hide">Número de cartão inválido.</span>
    </p>
    <div class="clear"></div>
    <p class="form-row form-row-first">
        <label for="rakuten-pay-card-expiry-month"><?php esc_html_e( 'Expiry Month', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <select name="rakuten_pay_card_expiry_month" data-rkp="card-expiration-month" id="rakuten-pay-card-expiry-month" style="font-size: 1.5em; padding: 8px; width: 100%;">
            <option value="" disabled selected>Mês</option>
            <?php
            foreach ( range(1, 12) as $n ) :
                $month = sprintf("%02d", $n);
                ?>

                <option value="<?php echo $month; ?>"><?php echo $month; ?></option>
            <?php endforeach; ?>
        </select>
        <span id="card-month-error" class="hide">Campo mês de validade do cartão está vazio ou contém números.</span>
    </p>
    <p class="form-row form-row-last">
        <label for="rakuten-pay-card-expiry-year"><?php esc_html_e( 'Expiry Year', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <select name="rakuten_pay_card_expiry_year" data-rkp="card-expiration-year" id="rakuten-pay-card-expiry-year" style="font-size: 1.5em; padding: 8px; width: 100%;">
            <option value="" disabled selected>Ano</option>
            <?php
            foreach ( range(2019, 2038) as $year ) :
                ?>
                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
            <?php endforeach; ?>
        </select>
        <span id="card-year-error" class="hide">Campo número do cartão está em branco ou contém letras.</span>
    </p>
    <div class="clear"></div>
    <p class="form-row form-row-first">
        <label for="rakuten-pay-card-cvv"><?php esc_html_e( 'Card Code', 'woocommerce-rakuten-pay' ); ?> <span class="required">*</span></label>
        <input id="rakuten-pay-card-cvv" class="input-text wc-credit-card-form-card-cvc" onchange="validateCardCVV()" name="rakuten_pay_card_cvc" data-rkp="card-cvv" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'CVC', 'woocommerce-rakuten-pay' ); ?>" style="font-size: 1.5em; padding: 8px;" />
        <span id="card-cvv" class="hide">Campo CVV do cartão está em branco ou contém letras.</span>
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
        <span id="card-installment" class="hide">Escolha a quantidade de parcelas.</span>
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

                text_value.innerHTML = 'R$' + parseFloat(total_interest).toFixed(2);
            }

            function validateCardNumber() {

                var cardNumber = document.querySelector("[data-rkp='card-number']");
                var error = document.getElementById('card-number-error');
                var error_digits = document.getElementById('card-number-error-digits');

                cardNumber.addEventListener('blur', function () {
                    if (cardNumber.value.length == 0 || Number.isInteger(parseInt(cardNumber.value)) !== true) {

                        error.classList.remove('hide');
                        error.classList.add('show');

                        cardNumber.classList.add('border-error');

                    } else {

                        error.classList.remove('show');
                        error.classList.add('hide');

                        cardNumber.classList.remove('border-error');
                        cardNumber.style.border = "initial";
                        cardNumber.style.border = "thin solid #6ae8a6";

                    }

                    if (cardNumber.value.length < 14) {

                        error_digits.classList.remove('hide');
                        error_digits.classList.add('show');

                        cardNumber.classList.add('border-error');
                    } else {

                        var rpay = new RPay();
                        cardValidate = rpay.cardValidate(cardNumber.value);

                        if (cardValidate.valid) {
                            console.log('cardNumber valid');
                        } else {
                            error_digits.classList.remove('hide');
                            error_digits.classList.add('show');

                            cardNumber.classList.add('border-error');
                            return false;
                        }

                        error_digits.classList.remove('show');
                        error_digits.classList.add('hide');

                        cardNumber.classList.remove('border-error');
                        cardNumber.style.border = "initial";
                        cardNumber.style.border = "thin solid #6ae8a6";

                    }
                })
            }

            function validateCardHolderName() {

                var cardHolderName = document.querySelector("[data-rkp='card-holder-name']");
                var error = document.getElementById('card-name-error');

                cardHolderName.addEventListener('blur', function () {
                    if (cardHolderName.value.length == 0 || Number.isInteger(parseInt(cardHolderName.value)) == true) {

                        error.classList.remove('hide');
                        error.classList.add('show');

                        cardHolderName.classList.add('border-error');

                    } else {

                        error.classList.remove('show');
                        error.classList.add('hide');

                        cardHolderName.classList.remove('border-error');
                        cardHolderName.style.border = "initial";
                        cardHolderName.style.border = "thin solid #6ae8a6";
                    }
                })
            }

            validateCardMonthYear();
            function validateCardMonthYear() {

                var cardMonthSelect = document.querySelector("#rakuten-pay-card-expiry-month");
                var cardMonth = document.querySelector('[data-rkp=card-expiration-month]');
                var cardYearSelect = document.querySelector("#rakuten-pay-card-expiry-year");
                var cardYear = document.querySelector("[data-rkp=card-expiration-year]");
                var error_month = document.getElementById('card-month-error');
                var error_year = document.getElementById('card-year-error');

                cardMonthSelect.addEventListener('blur', function () {
                    if (cardMonthSelect.value.length == 0 || Number.isInteger(parseInt(cardMonthSelect.value)) !== true) {

                        error_month.classList.remove('hide');
                        error_month.classList.add('show');

                        cardMonthSelect.classList.add('border-error');

                    } else {

                        error_month.classList.remove('show');
                        error_month.classList.add('hide');

                        cardMonthSelect.classList.remove('border-error');
                        cardMonthSelect.style.border = "initial";
                        cardMonthSelect.style.border = "thin solid #6ae8a6";
                        cardMonth.setAttribute('value', cardMonthSelect.value);

                    }
                });

                cardYearSelect.addEventListener('blur', function () {
                    if (cardYearSelect.value.length == 0 || Number.isInteger(parseInt(cardYearSelect.value)) !== true) {

                        error_year.classList.remove('hide');
                        error_year.classList.add('show');

                        cardYearSelect.classList.add('border-error');

                    } else {

                        error_year.classList.remove('show');
                        error_year.classList.add('hide');

                        cardYearSelect.classList.remove('border-error');
                        cardYearSelect.style.border = "initial";
                        cardYearSelect.style.border = "thin solid #6ae8a6";
                        cardYear.setAttribute('value', cardYearSelect.value);

                    }
                })
            }

            function validateBlankFields() {

                var cardNumberField = document.querySelector("[data-rkp='card-number']");
                var cardHolderNameField = document.querySelector("[data-rkp='card-holder-name']");
                var cardMonthField = document.getElementById("rakuten-pay-card-expiry-month");
                var cardYearField = document.querySelector("#rakuten-pay-card-expiry-year");
                var cardDocument = document.getElementById("rakuten-pay-card-holder-document");
                var cardCVV = document.querySelector("[data-rkp='card-cvv']");
                var cardInstallment = document.getElementById("rakuten-pay-card-installments");

                if (cardHolderNameField.value == 0) {

                    cardHolderNameField.classList.add('border-error');

                }

                if (cardNumberField.value == 0) {

                    cardNumberField.classList.add('border-error');

                }

                if (cardMonthField.value == 0) {

                    cardMonthField.classList.add('border-error');

                }

                if (cardYearField.value == 0) {

                    cardYearField.classList.add('border-error');

                }

                if (cardDocument.value == 0) {

                    cardDocument.classList.add('border-error');

                }

                if (cardCVV.value == 0) {

                    cardCVV.classList.add('border-error');

                }

                if (cardInstallment.value == 0) {

                    cardInstallment.classList.add('border-error');

                }

            }

            function validateCardCVV() {

                var cardCVV = document.querySelector("[data-rkp='card-cvv']");
                var error = document.getElementById('card-cvv');

                cardCVV.addEventListener('blur', function () {
                    if (cardCVV.value.length == 0 || cardCVV.value.length < 3|| Number.isInteger(parseInt(cardCVV.value)) !== true) {

                        error.classList.remove('hide');
                        error.classList.add('show');

                        cardCVV.classList.add('border-error');

                    } else {

                        error.classList.remove('show');
                        error.classList.add('hide');

                        cardCVV.classList.remove('border-error');
                        cardCVV.style.border = "initial";
                        cardCVV.style.border = "thin solid #6ae8a6";
                    }
                })
            }
            validateCardInstallment();
            function validateCardInstallment() {

                var cardInstallment = document.getElementById("rakuten-pay-card-installments");
                var error = document.getElementById('card-installment');

                cardInstallment.addEventListener('change', function () {
                    if (cardInstallment.value.length == 0) {

                        error.classList.remove('hide');
                        error.classList.add('show');

                        cardInstallment.classList.add('border-error');

                    } else {

                        error.classList.remove('show');
                        error.classList.add('hide');

                        cardInstallment.classList.remove('border-error');
                        cardInstallment.style.border = "initial";
                        cardInstallment.style.border = "thin solid #6ae8a6";

                    }
                })
            }

            /**
             * verifica cpf/cnpj
             *
             * @return cpf/cnpj validos
             */
            function verifica_cpf_cnpj ( valor ) {

                // Garante que o valor é uma string
                valor = valor.toString();

                // Remove caracteres inválidos do valor
                valor = valor.replace(/[^0-9]/g, '');

                // Verifica CPF
                if ( valor.length === 11 ) {
                    return 'CPF';
                }

                // Verifica CNPJ
                else if ( valor.length === 14 ) {
                    return 'CNPJ';
                }

                // Não retorna nada
                else {
                    return false;
                }

            } // verifica_cpf_cnpj

            /**
             *
             * calc_digitos_posicoes
             * Multiplica dígitos vezes posições
             *
             * @param string digitos Os digitos desejados
             * @param string posicoes A posição que vai iniciar a regressão
             * @param string soma_digitos A soma das multiplicações entre posições e dígitos
             *
             * @return string Os dígitos enviados concatenados com o último dígito
             */
            function calc_digitos_posicoes( digitos, posicoes = 10, soma_digitos = 0 ) {

                // Garante que o valor é uma string
                digitos = digitos.toString();

                // Faz a soma dos dígitos com a posição
                // Ex. para 10 posições:
                //   0    2    5    4    6    2    8    8   4
                // x10   x9   x8   x7   x6   x5   x4   x3  x2
                //   0 + 18 + 40 + 28 + 36 + 10 + 32 + 24 + 8 = 196
                for ( var i = 0; i < digitos.length; i++  ) {
                    // Preenche a soma com o dígito vezes a posição
                    soma_digitos = soma_digitos + ( digitos[i] * posicoes );

                    // Subtrai 1 da posição
                    posicoes--;

                    // Parte específica para CNPJ
                    // Ex.: 5-4-3-2-9-8-7-6-5-4-3-2
                    if ( posicoes < 2 ) {
                        // Retorno a posição para 9
                        posicoes = 9;
                    }
                }

                // Captura o resto da divisão entre soma_digitos dividido por 11
                // Ex.: 196 % 11 = 9
                soma_digitos = soma_digitos % 11;

                // Verifica se soma_digitos é menor que 2
                if ( soma_digitos < 2 ) {
                    // soma_digitos agora será zero
                    soma_digitos = 0;
                } else {
                    // Se for maior que 2, o resultado é 11 menos soma_digitos
                    // Ex.: 11 - 9 = 2
                    // Nosso dígito procurado é 2
                    soma_digitos = 11 - soma_digitos;
                }

                // Concatena mais um dígito aos primeiro nove dígitos
                // Ex.: 025462884 + 2 = 0254628842
                var cpf = digitos + soma_digitos;

                // Retorna
                return cpf;

            } // calc_digitos_posicoes

            /**
             * Valida CPF
             *
             * Valida se for CPF
             *
             * @param  string cpf O CPF com ou sem pontos e traço
             *
             * @return bool True para CPF correto - False para CPF incorreto
             */
            function valida_cpf( valor ) {

                // Garante que o valor é uma string
                valor = valor.toString();

                // Remove caracteres inválidos do valor
                valor = valor.replace(/[^0-9]/g, '');


                // Captura os 9 primeiros dígitos do CPF
                // Ex.: 02546288423 = 025462884
                var digitos = valor.substr(0, 9);

                // Faz o cálculo dos 9 primeiros dígitos do CPF para obter o primeiro dígito
                var novo_cpf = calc_digitos_posicoes( digitos );

                // Faz o cálculo dos 10 dígitos do CPF para obter o último dígito
                var novo_cpf = calc_digitos_posicoes( novo_cpf, 11 );

                // Verifica se o novo CPF gerado é idêntico ao CPF enviado
                if ( novo_cpf === valor ) {
                    // CPF válido
                    return true;
                } else {
                    // CPF inválido
                    return false;
                }

            } // valida_cpf

            /**
             * valida_cnpj
             *
             * Valida se for um CNPJ
             *
             * @param string cnpj
             *
             * @return bool true para CNPJ correto
             */
            function valida_cnpj ( valor ) {

                // Garante que o valor é uma string
                valor = valor.toString();

                // Remove caracteres inválidos do valor
                valor = valor.replace(/[^0-9]/g, '');

                // O valor original
                var cnpj_original = valor;

                // Captura os primeiros 12 números do CNPJ
                var primeiros_numeros_cnpj = valor.substr( 0, 12 );

                // Faz o primeiro cálculo
                var primeiro_calculo = calc_digitos_posicoes( primeiros_numeros_cnpj, 5 );

                // O segundo cálculo é a mesma coisa do primeiro, porém, começa na posição 6
                var segundo_calculo = calc_digitos_posicoes( primeiro_calculo, 6 );

                // Concatena o segundo dígito ao CNPJ
                var cnpj = segundo_calculo;

                // Verifica se o CNPJ gerado é idêntico ao enviado
                if ( cnpj === cnpj_original ) {
                    return true;
                }

                // Retorna falso por padrão
                return false;

            } // valida_cnpj

            /**
             * valida_cpf_cnpj
             *
             * Valida o CPF ou CNPJ
             *
             * @access public
             *
             * @return bool true para válido, false para inválido
             */
            function valida_cpf_cnpj ( valor ) {

                // Verifica se é CPF ou CNPJ
                var valida = verifica_cpf_cnpj( valor );

                // Garante que o valor é uma string
                valor = valor.toString();

                // Remove caracteres inválidos do valor
                valor = valor.replace(/[^0-9]/g, '');

                // Valida CPF
                if ( valida === 'CPF' ) {
                    // Retorna true para cpf válido
                    return valida_cpf( valor );
                }

                // Valida CNPJ
                else if ( valida === 'CNPJ' ) {
                    // Retorna true para CNPJ válido
                    return valida_cnpj( valor );
                }

                // Não retorna nada
                else {
                    return false;
                }

            } // valida_cpf_cnpj

            /**
             * formata_cpf_cnpj
             *
             * Formata um CPF ou CNPJ
             *
             * @access public
             *
             * @return string CPF ou CNPJ formatado
             */
            function formata_cpf_cnpj( valor ) {

                // O valor formatado
                var formatado = false;

                // Verifica se é CPF ou CNPJ
                var valida = verifica_cpf_cnpj( valor );

                // Garante que o valor é uma string
                valor = valor.toString();

                // Remove caracteres inválidos do valor
                valor = valor.replace(/[^0-9]/g, '');


                // Valida CPF
                if ( valida === 'CPF' ) {

                    // Verifica se o CPF é válido
                    if ( valida_cpf( valor ) ) {

                        // Formata o CPF ###.###.###-##
                        formatado  = valor.substr( 0, 3 ) + '.';
                        formatado += valor.substr( 3, 3 ) + '.';
                        formatado += valor.substr( 6, 3 ) + '-';
                        formatado += valor.substr( 9, 2 ) + '';

                    }

                }

                // Valida CNPJ
                else if ( valida === 'CNPJ' ) {

                    // Verifica se o CNPJ é válido
                    if ( valida_cnpj( valor ) ) {

                        // Formata o CNPJ ##.###.###/####-##
                        formatado  = valor.substr( 0,  2 ) + '.';
                        formatado += valor.substr( 2,  3 ) + '.';
                        formatado += valor.substr( 5,  3 ) + '/';
                        formatado += valor.substr( 8,  4 ) + '-';
                        formatado += valor.substr( 12, 14 ) + '';

                    }

                }

                // Retorna o valor
                return formatado;

            } // formata_cpf_cnpj

            jQuery( '#rakuten-pay-card-holder-document' ).blur(function(){

                // O CPF ou CNPJ
                var cpf_cnpj = jQuery(this).val();

                // Testa a validação e formata se estiver OK
                if ( valida_cpf_cnpj( cpf_cnpj ) ) {
                    jQuery(this).val( formata_cpf_cnpj( cpf_cnpj ) );
                    jQuery(this).addClass('validate_cpf_cnpj');

                    document.getElementById('error-cpf').classList.remove('show');
                    document.getElementById('error-cpf').classList.add('hide');
                    document.getElementById('rakuten-pay-card-holder-document').classList.remove('border-error');
                    document.getElementById('rakuten-pay-card-holder-document').classList.add('border-success');
                } else {
                    jQuery(this).addClass('border-error');
                    document.getElementById('rakuten-pay-card-holder-document').classList.remove('border-success');
                    document.getElementById('error-cpf').classList.remove('hide');
                    document.getElementById('error-cpf').classList.add('show');

                }

            });

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
                    text_value.innerHTML = 'R$' + parseFloat(total_interest).toFixed(2);
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
