<?php


//function pluginprefix_install()
//{
//
//    global $wpdb;
//
//    $table_name = $wpdb->prefix . "inntoken";
//    $charset_collate = $wpdb->get_charset_collate();
//
//    $sql = 'CREATE TABLE '.$table_name.' (
//    id int(11) NOT NULL,
//    link text NOT NULL,
//    reference_id int(11) DEFAULT NULL,
//    transaction_id int(11) NOT NULL,
//    toman_cost int(11) NOT NULL,
//    token_cost int(11) NOT NULL,
//    created_at timestamp NOT NULL DEFAULT current_timestamp(),
//    updated_at timestamp NOT NULL DEFAULT current_timestamp(),
//    status tinyint(4) NOT NULL DEFAULT 0,
//    paid_user_id int(11) NOT NULL,
//    phone varchar(500) DEFAULT NULL,
//    email varchar(500) DEFAULT NULL
//    ) '.$charset_collate.';
//    ALTER TABLE '.$table_name.'
//    ADD PRIMARY KEY (id),
//    ADD UNIQUE KEY link (link) USING HASH;
//    ADD UNIQUE KEY reference_id (reference_id) USING HASH;
//    ADD UNIQUE KEY transaction_id (transaction_id) USING HASH;
//    ALTER TABLE '.$table_name.' MODIFY id int(11) NOT NULL AUTO_INCREMENT; COMMIT;';
//
//    maybe_create_table($table_name,$sql);
//
//    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
//
//    dbDelta($sql);
//
//
//
//}

$innotoken_db_version = '1.0';

function innotoken_install() {
    global $wpdb;
    global $innotoken_db_version;

    $table_name = $wpdb->prefix . "inntoken";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = 'CREATE TABLE '.$table_name.' (
    id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    link text NOT NULL UNIQUE KEY ,
    reference_id int(11) DEFAULT NULL UNIQUE KEY ,
    transaction_id int(11) NOT NULL UNIQUE KEY,
    toman_cost int(11) NOT NULL,
    token_cost int(11) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp(),
    status tinyint(4) NOT NULL DEFAULT 0,
    paid_user_id int(11) NOT NULL,
    phone varchar(500) DEFAULT NULL,
    email varchar(500) DEFAULT NULL
    ) '.$charset_collate.';';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    add_option( 'innotoken_db_version', $innotoken_db_version );
}

function innotoken_insert($link,$reference_id,$transaction_id,$toman_cost,$token_cost,$paid_user_id,$phone,$email) {
    global $wpdb;

    $table_name = $wpdb->prefix . "inntoken";

    $wpdb->insert(
        $table_name,
        array(
            'link' => $link,
            'reference_id' => $reference_id,
            'transaction_id' => $transaction_id,
            'toman_cost' => $toman_cost,
            'token_cost' => $token_cost,
            'paid_user_id' => $paid_user_id,
            'phone' => $phone,
            'email' => $email,
        )
    );
}
