<?php

/*
Plugin Name: توکن نوآوری
Plugin URI: https://inntoken.ir/
Description: وب سایت وردپرسی خود را به درگاه رمز ارزی "توکن نوآوری" متصل کنید!
Version: 0.0.1
Author: شرکت دانش بنیان روتیک
Author URI: http://rotic.ir
License: GPLv2
*/

require_once 'table_creator.php';

global $innotoken_db_version;


register_activation_hook(__FILE__, 'innotoken_install');


add_option("innotoken_db_version", "1.0");

add_action('admin_menu', 'menu_builder');
function menu_builder()
{
    add_menu_page('توکن نوآوری (INN)', 'توکن نوآوری (INN)', 'manage_options', 'inntoken', '', "https://rotic.ir/images/inntoken-wp.png");
}

add_action('admin_menu', 'option_builder');
function option_builder()
{
    add_options_page("توکن نوآوری (INN)", "توکن نوآوری (INN)", 'manage_options', 'inntoken', 'page_builder');
}

add_action('admin_enqueue_scripts', 'callback_for_setting_up_scripts');
function callback_for_setting_up_scripts()
{
    wp_register_style('semantic', '/wp-content/plugins/InnToken/css/semantic.min.css');
    wp_register_style('icon', '/wp-content/plugins/InnToken/css/icon.min.css');
    wp_register_style('inntoken', '/wp-content/plugins/InnToken/css/inntoken.css');
    wp_register_script( 'jquery', '/wp-content/plugins/InnToken/js/jquery-3.1.1.min.js');
    wp_register_script( 'autoNumeric', '/wp-content/plugins/InnToken/js/autoNumeric.js');
    wp_register_script( 'semantic', '/wp-content/plugins/InnToken/js/semantic.min.js');
    wp_register_script( 'inntoken', '/wp-content/plugins/InnToken/js/inntoken.js');

    wp_enqueue_style('semantic');
    wp_enqueue_style('icon');
    wp_enqueue_style('inntoken');
    wp_enqueue_script('jquery');
    wp_enqueue_script('autoNumeric');
    wp_enqueue_script('semantic');
    wp_enqueue_script('inntoken');
}


function get_access_token($access_token = null, $refresh_token = null)
{

    $api_url = 'https://testapi.inntoken.ir/';


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
        $response = wp_remote_post($api_url . 'Auth/Login', $args);
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
        $response = wp_remote_post($api_url . 'Auth/Token/Refresh', $args);
        $response = wp_remote_retrieve_body($response);
        return json_decode($response);
    }

}

function get_mpa_id()
{

    $api_url = 'https://testapi.inntoken.ir/';

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
    $response = wp_remote_post($api_url . 'Gateway/GetMpaId', $args);
    $response = wp_remote_retrieve_body($response);
    $json = json_decode($response);
    if ($json->isSuccess) {
        update_option('mpa_id', $json->data->mpaId);
    }
}

function purchase_request($amount)
{

    $api_url = 'https://testapi.inntoken.ir/';

    $headers = array(
        'Content-Type' => "application/json",
    );
    $body = array(
        'inntokenAmount' => $amount / 30,
        'callBackFunction' => $_SERVER['HTTP_HOST'],
    );
    $args = array(
        'body' => json_encode($body),
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => $headers,
    );
    $response = wp_remote_post($api_url . 'Gateway/PurchaseRequest/' . get_option('mpa_id'), $args);
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

    $api_url = 'https://testapi.inntoken.ir/';
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
    $response = wp_remote_get($api_url . 'Payment/GatewayTransaction/List', $args);
    $response = wp_remote_retrieve_body($response);
    $json = json_decode($response);


    if ($json->isSuccess) {
        return $json->data;
    } elseif ($json->code == 'token_not_valid') {
        $json = get_access_token($access, $refresh);
        if ($json->isSuccess) {
            update_option('access', $json->data->access);
            return get_transactions();
        }
    }
}

function page_builder()
{
    $api_url = 'https://testapi.inntoken.ir/';
    $access = get_option('access');
    $refresh = get_option('refresh');

    if (isset($_POST['save'])) {
        $_POST['phone'] = esc_sql($_POST['phone']);
        $_POST['password'] = esc_sql($_POST['password']);

        $json = get_access_token();
        if ($json->isSuccess) {
            update_option('access', $json->data->access);
            update_option('refresh', $json->data->refresh);
            get_mpa_id();
            $access = get_option('access');
            echo '<div id="message" class="updated notice is-dismissible persian" ><p>تنظیمات با موفقیت ذخیره شد</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">رد کردن این اخطار</span></button></div>';
        } elseif ($json->code == 'token_not_valid') {
            $json = get_access_token($access, $refresh);
            if ($json->isSuccess) {
                update_option('access', $json->data->access);
            }
        }
    } elseif (isset($_POST['generate'])) {
        $amount = intval(esc_sql(str_replace(['تومان', ',', ' ', ',', ' تومان'], '', $_POST['amount'])));
        $correlation_id = purchase_request($amount);

        $purchase = "https://ipg.inntoken.ir/payment/" . $correlation_id;
    }
    if ($access != null) {

        $transactions = get_transactions();

    }

    ?>
    <div class="wrap">
        <h1 class="persian">تنظیمات افزونه توکن نوآوری (INN)</h1>
        <p class="description persian" id="home-description">

        </p>
        <div class="ui top attached tabular menu">
            <?php if ($access == null): ?>
                <a class="active item persian" data-tab="first">تنظیمات</a>
            <?php else: ?>
                <a class="<?php if ($purchase == null): ?> active <?php else: ?> disable <?php endif; ?> item persian"
                   data-tab="second">تراکنش ها</a>
                <a class="<?php if ($purchase != null): ?> active <?php endif; ?> item persian" data-tab="third">ایجاد
                    فاکتور</a>
            <?php endif; ?>
        </div>
        <?php if ($access == null): ?>
            <div class="ui bottom attached <?php if ($access == null): ?> active <?php endif; ?> tab segment persian"
                 data-tab="first">
                <form method="post" class="persian" enctype="multipart/form-data">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="phone">شماره موبایل</label>
                            </th>
                            <td>
                                <div class="ui input">
                                    <input name="phone" type="tel" id="phone"
                                           value="<?php echo empty($phone) ? '' : $phone ?>"
                                           class="regular-text persian">
                                </div>

                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="password">گذرواژه</label>
                            </th>
                            <td>
                                <div class="ui input">
                                    <input name="password" type="password" id="password"
                                           value="<?php echo empty($password) ? '' : $password ?>"
                                           class="regular-text persian">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button type="submit" name="save" value="ذخیره" id="submit" class="ui button persian">
                                    ذخیره
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        <?php else: ?>
            <div class=" ui bottom attached <?php if ($access != null && $purchase == null): ?> active <?php endif; ?> tab segment persian"
                 data-tab="second" >
                <table class="ui celled padded table" style="text-align: center" >
                    <thead>
                    <tr class="persian">
                        <th>
                            #
                        </th>
                        <th>
                            هش تراکنش
                        </th>
                        <th>
                            توکن
                        </th>
                        <th>
                            مبلغ
                        </th>
                        <th>
                            پرداخت کننده
                        </th>
                        <th>
                            تاریخ
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $key => $transaction): ?>
                        <tr class="persian">
                            <td>
                                <?php echo $key ?>
                            </td>
                            <td class="persian" >
                                <button class="ui gray right labeled icon button persian rtl"
                                        style="border-radius: 3px 3px 3px 3px !important; width: 100%"
                                        onclick="copy('<?php echo $transaction->txHash ?>')" type="button">
                                    <i class="copy icon"></i>
                                    ... <?php echo substr($transaction->txHash, 0, 20) ?>
                                </button>

                            </td>
                            <td class="persian">
                                <?php echo number_format($transaction->value) ?> توکن

                            </td>
                            <td class="persian">
                                <?php if ($transaction->transactionStatus == 1): ?>
                                    <button class="ui tag label teal text-white persian">موفق</button>
                                <?php else: ?>
                                    <button class="ui tag label red text-white persian">ناموفق</button>
                                <?php endif; ?>

                                <?php echo number_format($transaction->value * 30) ?> تومان
                            </td>
                            <td>
                                <?php echo $transaction->fromAddress ?>
                            </td>
                            <td class="rtl" >
                                <?php echo date('H:i:s Y-m-d', $transaction->timeStamp); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="ui bottom attached <?php if ($purchase != null): ?> active <?php endif; ?> tab segment persian"
                 data-tab="third">
                <form method="post" class="persian" enctype="multipart/form-data">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="amount">مبلغ فاکتور (تومان)</label>
                            </th>
                            <td>
                                <div class="ui input">
                                    <input name="amount" type="text" id="amount" class="regular-text persian">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="amount">معادل توکن نوآوری</label>
                            </th>
                            <td>
                                <div class="ui input">
                                    <p class="persian" id="token"></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <button type="submit" name="generate" value="ایجاد فاکتور" id="generate"
                                        class="ui button persian">
                                    ایجاد فاکتور
                                </button>
                            </th>
                            <?php if ($purchase != null): ?>
                                <td>
                                    <div class="ui action input persian">
                                        <button class="ui teal right labeled icon button persian"
                                                style="border-radius: 0px 3px 3px 0px !important;"
                                                onclick="copy('<?php echo $purchase ?>')" type="button">
                                            <i class="copy icon"></i>
                                            کپی
                                        </button>
                                        <input name="link" type="url" value="<?php echo $purchase ?>" id="link"
                                               class="regular-text persian">
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>

                        </tbody>
                    </table>
                </form>
            </div>
        <?php endif; ?>

    </div>
    <?php
}

//function add_script()
//{
//    $token = get_option('token')['token'];
//    $api = get_option('token')['api'];
//    $driver = get_option('token')['driver'];
//    $side = isset(get_option('token')['side']) && get_option('token')['side'] != null ? get_option('token')['side'] : "right";
//    echo '<script src="https://api.rotic.ir/v2/enterprise/' . $token . '/widget/' . $api . '"></script>';
//    if ($driver != 'rotic') {
//        echo '<script>window.addEventListener("rotic-start", function () { Rotic.setScroll(1000); Rotic.setDriver("' . $driver . '"); Rotic.setSide("' . $side . '");})</script>';
//    } else {
//        echo '<script>window.addEventListener("rotic-start", function () { Rotic.setScroll(1000); Rotic.setSide("' . $side . '");})</script>';
//    }
//}
//
//add_action('wp_footer', 'add_script');
