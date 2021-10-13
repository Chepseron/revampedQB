<?php
include "utils.php";
$output = false;
$encrypt_method = "AES-256-CBC";
$secret_key = 'Stanbic south sudan secret key';
$secret_iv = 'Stanbic south sudan secret iv';
$key = hash('sha256', $secret_key);
$iv = substr(hash('sha256', $secret_iv), 0, 16);
$output = openssl_decrypt(base64_decode($_REQUEST['string']), $encrypt_method, $key, 0, $iv);
echo $output;
?>
