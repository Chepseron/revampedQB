<?php

include "../../../shared/utils.php";
include "../../../shared/configs.php";


$AirtelSessionID = filter_var(isset($_GET['sessionid']) ? $_GET['sessionid'] : "", FILTER_SANITIZE_STRING);
$phone = filter_var(isset($_GET['Msisdn']) ? $_GET['Msisdn'] : "0", FILTER_SANITIZE_STRING);


if (isset($_GET['input'])) {
    $text = filter_var(isset($_GET['input']) ? $_GET['input'] : "", FILTER_SANITIZE_STRING);
}

if (isset($_GET['SUBSCRIBER_INPUT'])) {
    $text = filter_var(isset($_GET['SUBSCRIBER_INPUT']) ? $_GET['SUBSCRIBER_INPUT'] : "", FILTER_SANITIZE_STRING);
}

$ussdCode = filter_var(isset($_GET['ussdCode']) ? $_GET['ussdCode'] : "544", FILTER_SANITIZE_STRING);


$newAirtelSessionID = filter_var($AirtelSessionID, FILTER_SANITIZE_STRING);
$newphone = filter_var($phone, FILTER_SANITIZE_STRING);
$newtext = filter_var($text, FILTER_SANITIZE_STRING);
$newussdCode = filter_var($ussdCode, FILTER_SANITIZE_STRING);

$response_to_network = $default_error_message;
$navUrl = "http://localhost/ssbmobile/USSD/navigator/ussd_navigator.php";



$string = "?MSISDN=$newphone&SESSIONID=$newAirtelSessionID&INPUT=" . urlencode($newtext) . "&USSD_CODE=" . urlencode("*" . $newussdCode . "#") . "&NETWORK=airtel_ss";

$resp = sendRequest($navUrl . "" . $string);

//writeToFile(1, "recieved " . print_r($resp, true));
if (!$resp) {
    echo "END $response_to_network";
    exit();
} else {
    /* $result = json_decode($resp, true);
      // writeToFile(1, "after json_Decode " . print_r($result, true));

     * 
     */
    $result = json_decode($resp, true);
    $message = filter_var($result['USSDMESSAGE'], FILTER_SANITIZE_STRING);
    $end_of_session = filter_var($result['END'], FILTER_SANITIZE_STRING);
    if ($end_of_session) {
        $response_to_network = "END $message";
    } else {
        $response_to_network = "CON $message";
    }
}


echo $response_to_network;

function sendRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    return $result;
}
