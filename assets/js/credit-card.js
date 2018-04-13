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
        };

			// Lock the checkout form.
			form.addClass( 'processing' );

      function handleSuccess(tokenData, fingerprint) {
        var tokenInput            = creditCardFormElem.querySelector("#credit-card-token"),
            cardbrandInput        = creditCardFormElem.querySelector("#credit-card-brand"),
            cardExpiryMonthInput  = creditCardFormElem.querySelector("#credit-card-expiry-month"),
            cardExpiryYearInput   = creditCardFormElem.querySelector("#credit-card-expiry-year");

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
            errorHtml += '<li>' + error["message"] + '</li>';
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

      $.when(tokenize(), fingerprintfy()).then(handleSuccess, handleErrors);

			return false;
		});
	});

  $('#billing_birthdate').inputmask({"alias": "date"});
  $('#billing_document').inputmask({"mask": "999.999.999-99"});
  $('#billing_phone').inputmask('(99) 9999[9]-9999')

}( jQuery ));
