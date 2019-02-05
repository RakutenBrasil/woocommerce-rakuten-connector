<script type="text/template" id="refund-banking-data">
  <tr>
    <td class="label"><label for="refund_reason"><?php esc_html_e( 'Reason', 'woocommerce' ); ?>:</label></td>
    <td class="total">
      <select id="refund_reason" name="refund_reason" style="width: 96%">
        <option value="" disabled selected>
          <?php esc_html_e( 'Select a Reason', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="customer_return_order">
          <?php esc_html_e( 'Buyer returned the order', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="customer_return_item">
          <?php esc_html_e( 'Buyer returned an item', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="customer_replace_order">
          <?php esc_html_e( 'Buyer requested to change order', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="customer_replace_item">
          <?php esc_html_e( 'Buyer requested to exchange an item', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="customer_other">
          <?php esc_html_e( 'Other buyer related issue', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="merchant_unavailable_stock">
          <?php esc_html_e( 'Merchant without items in stock', 'woocommerce-rakuten-pay' ) ?>
        </option>
        <option value="merchant_other">
          <?php esc_html_e( 'Other merchant related issue', 'woocommerce-rakuten-pay' ) ?>
        </option>
      </select>
      <div class="clear"></div>
    </td>
  </tr>
  <tr>
    <td class="label"><label for="refund_customer_document"><?php esc_html_e( 'Document', 'woocommerce' ); ?>:</label></td>
    <td class="total">
      <input type="text" id="refund_customer_document" name="refund_customer_document" />
      <div class="clear"></div>
    </td>
  </tr>
  <tr>
    <td class="label"><label for="refund_bank_code"><?php esc_html_e( 'Bank code', 'woocommerce' ); ?>:</label></td>
    <td class="total">
      <input type="text" id="refund_bank_code" name="refund_bank_code" />
      <div class="clear"></div>
    </td>
  </tr>
  <tr>
    <td class="label"><label for="refund_bank_agency"><?php esc_html_e( 'Bank agency', 'woocommerce' ); ?>:</label></td>
    <td class="total">
      <input type="text" id="refund_bank_agency" name="refund_bank_agency" />
      <div class="clear"></div>
    </td>
  </tr>
  <tr>
    <td class="label"><label for="refund_bank_number"><?php esc_html_e( 'Bank number', 'woocommerce' ); ?>:</label></td>
    <td class="total">
      <input type="text" id="refund_bank_number" name="refund_bank_number" />
      <div class="clear"></div>
    </td>
  </tr>
</script>
