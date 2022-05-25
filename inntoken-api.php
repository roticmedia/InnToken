<?php

$GLOBALS['api_url'] = 'https://testapi.inntoken.ir/';
$GLOBALS['reset_login'] = false;

function inntoken_get_access_token($access_token = null, $refresh_token = null)
{


    if ($refresh_token == null) {
        $headers = array(
            'Content-Type' => "application/json",
        );
        $body = array(
            'phone' => $_POST['phone'],
            'password' => $_POST['password'],
        );
        $args = array(
            'body' => json_encode($body),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
        );
        $response = wp_remote_post($GLOBALS['api_url'] . 'Auth/Login', $args);
        $response = wp_remote_retrieve_body($response);
        return json_decode($response);

    } else {
        $headers = array(
            'Content-Type' => "application/json",
        );
        $body = array(
            'refresh' => $refresh_token,
        );
        $args = array(
            'body' => json_encode($body),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
        );
        $response = wp_remote_post($GLOBALS['api_url'] . 'Auth/Token/Refresh', $args);
        $response = wp_remote_retrieve_body($response);
        return json_decode($response);
    }

}

function inntoken_get_mpa_id()
{


    $headers = array(
        'Content-Type' => "application/json",
    );
    $body = array(
        'phone' => $_POST['phone'],
        'password' => $_POST['password'],
    );
    $args = array(
        'body' => json_encode($body),
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => $headers,
    );
    $response = wp_remote_post($GLOBALS['api_url'] . 'Gateway/GetMpaId', $args);
    $response = wp_remote_retrieve_body($response);
    $json = json_decode($response);
    if ($json->isSuccess) {
        update_option('mpa_id', $json->data->mpaId);
    }
}

function inntoken_purchase_request($amount,$url = null)
{
    $headers = array(
        'Content-Type' => "application/json",
    );
    $conversion_body = array(
        'amount' => $amount,
    );
    $conversion_args = array(
        'body' => json_encode($conversion_body),
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => $headers,
    );


    $response = wp_remote_post($GLOBALS['api_url'] . 'Gateway/ConvertTomanToInntoken', $conversion_args);
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body);
    if ($json->isSuccess) {
        $body = array(
            'inntokenAmount' => $json->value,
            'callBackFunction' => $url==null?get_option('domain').'/wp-json/inntoken/v1/verify':$url,
        );
        $args = array(
            'body' => json_encode($body),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
        );

        $response = wp_remote_post($GLOBALS['api_url'] . 'Gateway/PurchaseRequest/' . get_option('mpa_id'), $args);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body);
        if ($json->isSuccess) {
            return $json->corrolationId;
        } else {
            return null;
        }
    } else {
        return null;
    }

}

function inntoken_get_transactions()
{

    $access = get_option('access');
    $refresh = get_option('refresh');

    $headers = array(
        'Content-Type' => "application/json",
        'Authorization' => "Bearer " . $access,
    );
    $args = array(
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => $headers,
    );
    $response = wp_remote_get($GLOBALS['api_url'] . 'Payment/GatewayTransaction/List', $args);
    $response = wp_remote_retrieve_body($response);
    $json = json_decode($response);

    if ($json->isSuccess) {
        return $json->data;
    } elseif ($json->code == 'token_not_valid') {
        $json = inntoken_get_access_token($access, $refresh);
        if ($json->isSuccess) {
            $GLOBALS['reset_login'] = false;
            update_option('access', $json->data->access);
            return inntoken_get_transactions();
        } else {
            $GLOBALS['reset_login'] = true;
            return [];
        }
    } else {
        $json = inntoken_get_access_token($access, $refresh);
        if ($json->isSuccess) {
            $GLOBALS['reset_login'] = false;
            update_option('access', $json->data->access);
            return inntoken_get_transactions();
        } else {
            $GLOBALS['reset_login'] = true;
            return [];
        }
    }
}

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function inntoken_verify() {
    write_log("OK");
    // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
    return rest_ensure_response( 'Hello World, this is the WordPress REST API' );
}

function inntoken_prefix_register() {
    session_start();
    register_rest_route( 'inntoken/v1', '/verify', array(
        'methods'  => 'GET',
        'callback' => function($request){
            if (inntoken_encrypt_decryot($_COOKIE['order_id'],'d')==null){
                if ($request['status']==1){
                    echo "پرداخت با موفقیت انجام شد!";
                }else{
                    echo "تراکنش شما با خطا مواجه شد!";
                }
                wp_redirect(get_site_url());
                exit;
            }
            else{
                $order = new WC_Order(inntoken_encrypt_decryot($_COOKIE['order_id'],'d'));
                if ($request['status']==1){
                    $order->update_status('completed', "پرداخت شده با توکن نواوری (INN)");
                }else{
                    $order->update_status('failed', "خطا هنگام پرداخت با توکن نواوری (INN)");
                    wp_redirect(get_site_url());
                    setcookie('order_id',null,time()-1000000000);
                    setcookie('tracking_code',null,time()-1000000000);
                    exit;
                }
                wp_redirect( get_site_url()."/index.php/my-account/view-order/".inntoken_encrypt_decryot($_COOKIE['order_id'],'d'));
                setcookie('order_id',null,time()-100000);
                setcookie('tracking_code',null,time()-100000);
                exit;
            }
        },
        'permission_callback' =>'__return_true'
    ) );
}


add_action( 'rest_api_init', 'inntoken_prefix_register' );

if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

function inntoken_encrypt_decryot($stringToHandle = "",$encryptDecrypt = 'e'){
    // Set default output value
    $output = null;
    // Set secret keys
    $secret_key = 'jf8gf8g^3*s2336566ergbvcwd'; // Change this!
    $secret_iv = 'd&&9"dh4%:@@@ssdeer##'; // Change this!
    $key = hash('sha256',$secret_key);
    $iv = substr(hash('sha256',$secret_iv),0,16);
    // Check whether encryption or decryption
    if($encryptDecrypt == 'e'){
        // We are encrypting
        $output = base64_encode(openssl_encrypt($stringToHandle,"AES-256-CBC",$key,0,$iv));
    }else if($encryptDecrypt == 'd'){
        // We are decrypting
        $output = openssl_decrypt(base64_decode($stringToHandle),"AES-256-CBC",$key,0,$iv);
    }
    // Return the final value
    return $output;
}
