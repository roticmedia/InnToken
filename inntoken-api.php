<?php

$GLOBALS['api_url'] = 'https://testapi.inntoken.ir/';
$GLOBALS['reset_login'] = false;

function get_access_token($access_token = null, $refresh_token = null)
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

function get_mpa_id()
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

function purchase_request($amount,$url = null)
{
    $headers = array(
        'Content-Type' => "application/json",
    );
    $body = array(
        'inntokenAmount' => intval(intval($amount) / 30,0),
        'callBackFunction' => $url==null?$_SERVER['HTTP_HOST']:$url,
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

function get_transactions()
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
        $json = get_access_token($access, $refresh);
        if ($json->isSuccess) {
            $GLOBALS['reset_login'] = false;
            update_option('access', $json->data->access);
            return get_transactions();
        } else {
            $GLOBALS['reset_login'] = true;
            return [];
        }
    } else {
        $json = get_access_token($access, $refresh);
        if ($json->isSuccess) {
            $GLOBALS['reset_login'] = false;
            update_option('access', $json->data->access);
            return get_transactions();
        } else {
            $GLOBALS['reset_login'] = true;
            return [];
        }
    }
}

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

