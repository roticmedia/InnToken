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
    $body = array(
        'inntokenAmount' => intval(intval($amount) / 30,0),
        'callBackFunction' => $url==null?get_option('domain'):$url,
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
    $response = wp_remote_retrieve_body($response);
    $json = json_decode($response);
    if ($json->isSuccess) {
        return $json->corrolationId;
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
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route( 'inntoken/v1', '/verify', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods'  => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'verify',
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

