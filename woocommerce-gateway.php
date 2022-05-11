<?php

add_action( 'plugins_loaded', 'init_inntoken_gateway' );

function init_inntoken_gateway() {
    class InnToken_WC_Payment_Gateway extends WC_Payment_Gateway {
        public function __construct(){
            $this->id='inn';
            $this->method_title='توکن نوآوری (INN)';
            $this->method_description='پرداخت با رمز ارز ایرانی توکن نوآوری (INN)';
            $this->icon='https://rotic.ir/images/inntoken-wp.png';
            $this->init_form_fields();
            $this->init_settings();
        }
        function process_payment( $order_id ) {
            global $woocommerce;
            $order = new WC_Order( $order_id );

            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }
    }
    function add_inntoken_gateway( $methods ) {
        $methods[] = 'InnToken_WC_Payment_Gateway';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'add_inntoken_gateway' );

}
