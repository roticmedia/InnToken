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
            $this->icon = get_site_url().'/wp-content/plugins/InnToken/assets/Inn-Woocommerce-Icon.png';
            $this->title = 'توکن نوآوری (INN)';
            $this->description = 'پرداخت سریع، امن و مدرن به وسیله رمز ارز INN از طریق درگاه <a href="https://inntoken.ir" target="_blank">توکن نوآوری (INN)</a>';
            $this->init_form_fields();
            $this->init_settings();

        }

        public function init_settings()
        {
            parent::init_settings();
            add_action('woocommerce_update_options_payment_gateways' . $this->id, array($this, 'process_admin_options'));
//            $this->enabled = ! isset( $this->settings['enabled'] ) || (isset($_POST['woocommerce_inn_enabled'] ) && '1' === $_POST['woocommerce_inn_enabled']) ? 'yes' : 'no';
//            $this->update_option('enabled',! isset( $this->settings['enabled'] ) || (isset($_POST['woocommerce_inn_enabled'] ) && '1' === $_POST['woocommerce_inn_enabled']) ? 1 : 0);
        }

        public function init_form_fields()
        {
//            $this->form_fields = array(
//                'enabled' => array(
//                    'title' => __( 'Enable/Disable', 'woocommerce' ),
//                    'type' => 'checkbox',
//                    'label' => 'فعالسازی پرداخت با توکن نوآوری',
//                    'default'=> ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no'
//                ),
//            );
        }

        public function process_payment($order_id)
        {
            global $woocommerce;

            $order = new WC_Order($order_id);
            write_log($order->get_currency());
            $correlation_id = inntoken_purchase_request($order->get_currency() == "IRR" && $order->get_currency() != "IRT" ? ($order->get_total() / 10) : $order->get_total(), get_option('domain').'/wp-json/inntoken/v1/verify');
            $order->update_status('on-hold', "در انتظار پرداخت با توکن نواوری (INN)");
            $woocommerce->cart->empty_cart();
            setcookie('order_id',inntoken_encrypt_decryot($order_id),time()+100000);
            setcookie('tracking_code',inntoken_encrypt_decryot($correlation_id),time()+100000);
            return array(
                'result' => 'success',
                'redirect' => 'https://ipg.inntoken.ir/payment/' . $correlation_id
            );
        }


    }

    add_filter('woocommerce_payment_gateways', 'add_inntoken_gateway');
    function add_inntoken_gateway($methods)
    {
        $methods[] = 'InnToken_WC_Payment_Gateway';
        return $methods;
    }


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
