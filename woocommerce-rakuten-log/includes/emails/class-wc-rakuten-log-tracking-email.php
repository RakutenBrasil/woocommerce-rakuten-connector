<?php
/**
 * Rakuten Log Email for Tracking
 *
 * @package WC_Rakuten_Log
 */

if ( !defined('ABSPATH')){
    exit;
}

class WC_Rakuten_Log_Tracking_Email extends WC_Email {
    public function __construct()
    {
        $this->id               = 'rakuten_log_tracking';
        $this->title            = __('GenLog Tracking Code', 'woocommerce-rakuten-log');
        $this->customer_email   = true;
        $this->description      = __('This email is sent when a batch is created for the order.', 'woocommerce-rakuten-log');
        $this->heading          = __('Your order has been sent', 'woocommerce-rakuten-log');
        $this->subject          = __('[{site_title}]] Your order {order_number} has been sent', 'woocommerce-rakuten-log');
        $this->message          = __('Hi there. Your recent order on {site_title} has been sent by GenLog.', 'woocommerce-rakuten-log')
                                  . PHP_EOL . ' ' . PHP_EOL
                                  . __('To track your delivery, use this <a href="{tracking_url}">tracking link</a>.', 'woocommerce-rakuten-log');
        $this->tracking_message = $this->get_option( 'tracking_message', $this->message );
        $this->template_html    = 'emails/rakuten-log-tracking-code.php';
        $this->template_plain   = 'emails/plain/rakuten-log-tracking-code.php';

        parent::__construct();

        $this->template_base = WC_Rakuten_Log::get_templates_path();
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'woocommerce-rakuten-log'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'woocommerce-rakuten-log'),
                'default' => 'yes'
            ),
            'subject' => array(
                'title'       => __('Subject', 'woocommerce-rakuten-log'),
                'type'        => 'text',
                'description' => sprintf(__('This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-rakuten-log'), $this->subject),
                'placeholder' => $this->subject,
                'default'     => '',
                'desc_tip'    => true
            ),
            'heading' => array(
				'title'       => __( 'Email Heading', 'woocommerce-rakuten-log' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-rakuten-log' ), $this->heading ),
				'placeholder' => $this->heading,
				'default'     => '',
				'desc_tip'    => true,
			),
			'tracking_message' => array(
				'title'       => __( 'Email Content', 'woocommerce-rakuten-log' ),
				'type'        => 'textarea',
				'description' => sprintf( __( 'This controls the initial content of the email. Leave blank to use the default content: <code>%s</code>.', 'woocommerce-rakuten-log' ), $this->message ),
				'placeholder' => $this->message,
				'default'     => '',
				'desc_tip'    => true,
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woocommerce-rakuten-log' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce-rakuten-log' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_custom_email_type_options(),
				'desc_tip'    => true,
			)
        );
    }

    protected function get_custom_email_type_options() {
		if ( method_exists( $this, 'get_email_type_options' ) ) {
			return $this->get_email_type_options();
		}
		$types = array( 'plain' => __( 'Plain text', 'woocommerce-rakuten-log' ) );
		if ( class_exists( 'DOMDocument' ) ) {
			$types['html']      = __( 'HTML', 'woocommerce-rakuten-log' );
			$types['multipart'] = __( 'Multipart', 'woocommerce-rakuten-log' );
		}
		return $types;
	}

	public function get_tracking_message(){
        return apply_filters(
            'woocommerce_rakuten_log_email_tracking_message',
            $this->format_string($this->tracking_message),
            $this->object
        );
    }

    public function trigger($order_id, $order=false, $tracking_url = '' ){
        if ($order_id && ! is_a($order, 'WC_Order')){
            $order = wc_get_order($order_id);
        }

        if ( is_object($order) ){
            $this->object = $order;
			if ( method_exists( $order, 'get_billing_email' ) ) {
				$this->recipient = $order->get_billing_email();
			} else {
				$this->recipient = $order->billing_email;
			}
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{date}'] = date_i18n( wc_date_format(), time() );

			if (empty($tracking_url)){
			    $tracking_url = wc_rakuten_log_get_tracking_url($order);
            }

            $this->placeholders['{tracking_url}'] = $tracking_url;
        }

        if (!$this->get_recipient()) {
            return;
        }

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    public function get_content_html()
    {
        ob_start();

        wc_get_template( $this->template_html, array(
            'order'            => $this->object,
            'email_heading'    => $this->get_heading(),
            'tracking_message' => $this->get_tracking_message(),
            'sent_to_admin'    => false,
            'plain_text'       => false,
            'email'            => $this
        ), '', $this->template_base );

        return ob_get_clean();
    }

    public function get_content_plain()
    {
        ob_start();

        $message = $this->get_tracking_message();

        $message = str_replace( '<ul>', "\n", $message );
		$message = str_replace( '<li>', "\n - ", $message );
		$message = str_replace( array( '</ul>', '</li>' ), '', $message );
		wc_get_template( $this->template_plain, array(
			'order'            => $this->object,
			'email_heading'    => $this->get_heading(),
			'tracking_message' => $message,
			'sent_to_admin'    => false,
			'plain_text'       => true,
			'email'            => $this,
		), '', $this->template_base );

        return ob_get_clean();
    }
}

return new WC_Rakuten_Log_Tracking_Email();