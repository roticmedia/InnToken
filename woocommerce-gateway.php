<?php

require('inntoken-api.php');


function init_inntoken_gateway()
{

    class InnToken_WC_Payment_Gateway extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $this->id = 'inn';
            $this->method_title = 'توکن نوآوری (INN)';
            $this->method_description = 'تنظیمات درگاه پرداخت رمز ارزی توکن نوآوری (INN) برای افزونه فروشگاه ساز ووکامرس';
            $this->icon = '/wp-content/plugins/InnToken/assets/Inn-Woocommerce-Icon.png';
            $this->title = 'توکن نوآوری (INN)';
            $this->description = 'پرداخت سریع، امن و مدرن به وسیله رمز ارز INN از طریق درگاه <a href="https://inntoken.ir" target="_blank">توکن نوآوری (INN)</a>';
            $this->init_form_fields();
            $this->init_settings();

        }

        public function init_settings()
        {
            add_action('woocommerce_update_options_payment_gateways' . $this->id, array($this, 'process_admin_options'));
        }

        public function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            $correlation_id = purchase_request($order->get_currency() == "IRR" ? $order->get_total() / 10 : $order->get_total(), $_SERVER['HTTP_HOST'] . "/?wc-api=CALLBACK");
            $order->update_status('on-hold', "در انتظار پرداخت با توکن نواوری (INN)");
            return array(
                'result' => 'success',
                'redirect' => 'https://ipg.inntoken.ir/payment/' . $correlation_id
            );
        }

    }

    function add_inntoken_gateway($methods)
    {
        $methods[] = 'InnToken_WC_Payment_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_inntoken_gateway');

    function inntoken_api_callback()
    {
        global $woocommerce;
//        $order = new WC_Order($order_id);
//
//        // Mark as on-hold (we're awaiting the cheque)
//        $order->update_status('on-hold', "در انتظار پرداخت با توکن نواوری (INN)");
        // Remove cart
        $woocommerce->cart->empty_cart();
        header("location: /");
    }

    add_action('woocommerce_api_callback', 'inntoken_api_callback');

    if (!function_exists('write_log')) {

        function write_log($log)
        {
            if (true === WP_DEBUG) {
                if (is_array($log) || is_object($log)) {
                    error_log(print_r($log, true));
                } else {
                    error_log($log);
                }
            }
        }

    }

}
