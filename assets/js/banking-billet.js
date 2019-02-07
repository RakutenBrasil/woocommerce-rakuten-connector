/* global wcRakutenPayParams, RakutenPay */
$form = null;

(function( $ ) {
	'use strict';

	$(function() {

		/**
		 * Process the credit card data when submit the checkout form.
		 */
		$( 'body' ).on( 'click', '#place_order', function() {

			if ( ! $( '#payment_method_rakuten-pay-banking-billet' ).is( ':checked' ) ) {
				return true;
			}

			var form                = $( 'form.checkout, form#order_review' ),
                formElem            = form.get(0),
				rpay                = new RPay(),
				errors              = null,
				errorHtml           = '',
                buyerDocument       = document.getElementById('billing_document').value,
                buyerBirthDate      = document.getElementById('billing_birthdate').value;

			// Lock the checkout form.
			form.addClass( 'processing' );

      function handleSuccess(fingerprint) {

        $('input[name=rakuten_pay_fingerprint]').remove();

        $('<input></input>', {
          type: 'hidden',
          id: 'banking-billet-fingerprint',
          name: 'rakuten_pay_fingerprint',
          value: fingerprint
        }).appendTo(form);

        form.removeClass('processing');
        form.submit();
      }

      function handleErrors(fingerprintErrors) {
        console.log(fingerprintErrors);
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
                $.when(fingerprintfy()).then(handleSuccess, handleErrors);
            }

			return false;
		});
	});
}( jQuery ));
