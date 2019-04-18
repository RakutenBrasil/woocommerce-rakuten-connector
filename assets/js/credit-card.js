/* global wcRakutenPayParams, RakutenPay */
$form = null;

(function( $ ) {
	'use strict';

	$(function() {

		/**
		 * Process the credit card data when submit the checkout form.
		 */
		$( 'body' ).on( 'click', '#place_order', function() {

			if ( ! $( '#payment_method_rakuten-pay-credit-card' ).is( ':checked' ) ) {
				return true;
			}

			var form              = $( 'form.checkout, form#order_review' ),
				rpay                = new RPay(),
				creditCardForm      = $( '#rakuten-pay-credit-cart-form', form ),
                creditCardFormElem  = creditCardForm.get(0),
				errors              = null,
				errorHtml           = '',
                elements            = {
                  "form": creditCardFormElem,
                  "card-number": creditCardFormElem.querySelector("[data-rkp='card-number']"),
                  "card-cvv": creditCardFormElem.querySelector("[data-rkp='card-cvv']"),
                  "expiration-month": creditCardFormElem.querySelector("[data-rkp='card-expiration-month']"),
                  "expiration-year": creditCardFormElem.querySelector("[data-rkp='card-expiration-year']")
                },
                buyerDocument       = document.getElementById('billing_document').value,
                buyerBirthDate      = document.getElementById('billing_birthdate').value;

			// Lock the checkout form.
			form.addClass( 'processing' );

            function handleSuccess(tokenData, fingerprint) {
                var tokenInput             = creditCardFormElem.querySelector("#credit-card-token"),
                    cardbrandInput         = creditCardFormElem.querySelector("#credit-card-brand"),
                    cardExpiryMonthInput   = creditCardFormElem.querySelector("#credit-card-expiry-month"),
                    cardExpiryYearInput    = creditCardFormElem.querySelector("#credit-card-expiry-year");

                tokenInput.value           = tokenData.cardToken;
                cardbrandInput.value       = tokenData.cardBrand;
                cardExpiryMonthInput.value = tokenData.cardExpirationMonth;
                cardExpiryYearInput.value  = tokenData.cardExpirationYear;

                $('input[name=rakuten_pay_fingerprint]').remove();

                $('<input></input>', {
                  type: 'hidden',
                  id: 'credit-card-fingerprint',
                  name: 'rakuten_pay_fingerprint',
                  value: fingerprint
                }).appendTo(form);

                form.removeClass('processing');
                form.submit();
            }

            function handleErrors(tokenErrors, fingerprintErrors) {
                var errors = [tokenErrors, fingerprintErrors];
                errors = $.map(errors, function(e) { return e; });
                errors = $.grep(errors, function(e) {
                  return typeof(e) == 'object' && e != null;
                });

                // Display the errors in credit card form.
                if ( ! $.isEmptyObject( errors ) ) {
                  form.removeClass( 'processing' );
                  $( '.woocommerce-error', creditCardForm ).remove();

                  errorHtml += '<ul class="woocommerce-error" role="alert">';
                  $.each( errors, function (_idx, error ) {
                    errorHtml += '<li>' + wcRakutenPayParams.error_message + '</li>';
                  });
                  errorHtml += '</ul>';

                  creditCardForm.prepend( '<div>' + errorHtml + '</div>' );
                } else {
                  form.removeClass( 'processing' );
                  $( '.woocommerce-error', creditCardForm ).remove();

                  // Generate the hash.
                  creditCard.generateHash( function ( cardHash ) {
                    // Remove any old hash input.
                    $( 'input[name=rakuten_pay_card_hash]', form ).remove();

                    // Add the hash input.
                    form.append( $( '<input name="rakuten_pay_card_hash" type="hidden" />' ).val( cardHash ) );

                    // Submit the form.
                    form.submit();
                  });
                }
            }

            function tokenize() {
                var defer = $.Deferred();

                rpay.tokenize(elements, function(errors, data) {
                  if (errors)
                    defer.reject(errors);

                  defer.resolve(data);
                });

                return defer;
            }

            function fingerprintfy() {
                var defer = $.Deferred();

                rpay.fingerprint(function(errors, data) {
                  if (errors)
                    defer.reject(errors);

                  defer.resolve(data);
                });

                return defer;
            }

            if ( buyerDocument === "" && buyerBirthDate === "" ) {

                console.log('Informe o CPF/CNPJ e data nascimento' + buyerDocument);

                $('#billing_document').focus();
                $('label[for=billing_document]').css({ color: '#a00' });
                $('label[for=billing_birthdate]').css({ color: '#a00' });

            } else if ( buyerDocument === "" ) {

                console.log('Preencha a data de nascimento');

                alert('Preencha a data de nascimento');
                $('#billing_birthdate').focus();
                $('label[for=billing_document]').css({ color: '#a00' });

            } else if ( buyerBirthDate === "" ) {

                console.log('Preencha a data de nascimento');

                alert('Preencha a data de nascimento');
                $('#billing_birthdate').focus();
                $('label[for=billing_birthdate]').css({ color: '#a00' });

            } else {
                $.when(tokenize(), fingerprintfy()).then(handleSuccess, handleErrors);
            }

			return false;
		});

        $( '#billing_document' ).blur(function(){

            // O CPF ou CNPJ
            var cpf_cnpj = $(this).val();

            // Testa a validação e formata se estiver OK
            if ( valida_cpf_cnpj( cpf_cnpj ) ) {
                $(this).val( formata_cpf_cnpj( cpf_cnpj ) );
                $(this).addClass('validate_cpf_cnpj');
            } else {
                alert('CPF ou CNPJ inválido!');
            }

        });

        /*
         verifica_cpf_cnpj
         Verifica se é CPF ou CNPJ
         @see http://www.tutsup.com/
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

        /*
         calc_digitos_posicoes

         Multiplica dígitos vezes posições

         @param string digitos Os digitos desejados
         @param string posicoes A posição que vai iniciar a regressão
         @param string soma_digitos A soma das multiplicações entre posições e dígitos
         @return string Os dígitos enviados concatenados com o último dígito
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

        /*
         Valida CPF

         Valida se for CPF

         @param  string cpf O CPF com ou sem pontos e traço
         @return bool True para CPF correto - False para CPF incorreto
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

        /*
         valida_cnpj

         Valida se for um CNPJ

         @param string cnpj
         @return bool true para CNPJ correto
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

        /*
         valida_cpf_cnpj

         Valida o CPF ou CNPJ

         @access public
         @return bool true para válido, false para inválido
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

        /*
         formata_cpf_cnpj

         Formata um CPF ou CNPJ

         @access public
         @return string CPF ou CNPJ formatado
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

	});

  $( '#billing_birthdate' ).inputmask({"alias": "date"});
  $( '#billing_document' ).inputmask({mask: ['999.999.999-99', '99.999.999/9999-99']});
  $( '#rakuten-pay-card-holder-document' ).inputmask({mask: ['999.999.999-99', '99.999.999/9999-99']});
  $( '#billing_phone' ).inputmask('(99) 9999[9]-9999');
  $( '#shipping_phone_number' ).inputmask('(99) 9999[9]-9999');

}( jQuery ));
