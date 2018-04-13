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

			var form    = $( 'form.checkout, form#order_review' ),
        formElem  = form.get(0),
				rpay      = new RPay(),
				errors    = null,
				errorHtml = '';

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

      $.when(fingerprintfy()).then(handleSuccess, handleErrors);

			return false;
		});
	});
}( jQuery ));
