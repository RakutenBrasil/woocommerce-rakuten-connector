(function( $ ) {

  function insertRefundFields() {
    var field = $('#refund_reason').parentsUntil('tbody').last(),
        html  = $('#refund-banking-data').html();

    field.after(html);
    field.remove();

    $('#refund_customer_document').inputmask({'mask': '999.999.999-99'});
    $('#refund_bank_code').inputmask({'mask': '999'});
    $('#refund_bank_agency').inputmask({'mask': '9{3,5}'});
    $('#refund_bank_number').inputmask({'mask': '9{3,8}-9'});
  }

  $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
    if (originalOptions.data.action === 'woocommerce_refund_line_items') {
      var refund_reason            = $('#refund_reason').val();
      var refund_customer_document = $('#refund_customer_document').val();
      var refund_bank_code         = $('#refund_bank_code').val();
      var refund_bank_agency       = $('#refund_bank_agency').val();
      var refund_bank_number       = $('#refund_bank_number').val();

      var data = $.extend(originalOptions.data, {
        refund_reason            : refund_reason,
        refund_customer_document : refund_customer_document,
        refund_bank_code         : refund_bank_code,
        refund_bank_agency       : refund_bank_agency,
        refund_bank_number       : refund_bank_number
      });

      options.data = $.param(data);
    }
  });

  $(document).ajaxSuccess(function(event, xhr, settings) {
    function isLoadOrderItems() {
      var kvs = settings.data.split("&");
      for (var i = 0; i < kvs.length; i++) {
        var kv = kvs[i].split("=");
        var k = kv[0];
        var v = kv[1];
        if (k == "action" && v == "woocommerce_load_order_items")
          return true;
      }
      return false;
    }

    if (isLoadOrderItems()) {
      insertRefundFields()
    }
  });

  insertRefundFields();

}( jQuery ));
