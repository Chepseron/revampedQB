<?php

include "../../../shared/utils.php";
include "../../../shared/configs.php";

//$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
$_GET = array_change_key_case($_GET, CASE_LOWER);
writeToFile(1, "Params from the network " . print_r($_GET, true));
$MSISDN = filter_var(@$_GET['msisdn'], FILTER_SANITIZE_STRING);
$sessionID = filter_var(@$_GET['session'], FILTER_SANITIZE_STRING);
$input = filter_var(@$_GET['input'], FILTER_SANITIZE_STRING);
$imsi = filter_var(isset($_GET['imsi']) ? $_GET['imsi'] : "", FILTER_SANITIZE_STRING);
$new_request = filter_var(@$_GET['new_request'], FILTER_SANITIZE_STRING);


$ussdCode = "544"; //@$_GET['input'];

if ($new_request == "1") {
    $input = filter_var(@$_GET['input'], FILTER_SANITIZE_STRING);
} else if ($new_request == "0") {
    $input = filter_var(@$_GET['input'], FILTER_SANITIZE_STRING);
}
$newMSISDN = filter_var($MSISDN, FILTER_SANITIZE_STRING);
$newsessionID = filter_var($sessionID, FILTER_SANITIZE_STRING);
$newinput = filter_var($input, FILTER_SANITIZE_STRING);
$newimsi = filter_var($imsi, FILTER_SANITIZE_STRING);
$newnew_request = filter_var($new_request, FILTER_SANITIZE_STRING);


//hack for shortcuts
$allowedMultipleMenuCode = array(544);
$allowedSingleMenuShortCuts = array("*544*1#", "*544*2#", "*544*3#");
$shortcutArr = explode("*", $input);
if (in_array($ussdCode, $allowedMultipleMenuCode)) {
    if (strlen(trim($newinput)) > 0) {
        //$shortcutArr = explode("*", $input);
        $ussdCode = $ussdCode . "*" . $shortcutArr[0];
    }
} elseif (in_array("*" . $ussdCode . "*" . $shortcutArr[0] . "#", $allowedSingleMenuShortCuts)) {
    $ussdCode = $ussdCode . "*" . $shortcutArr[0];
}

$response_to_network = $default_error_message;
$navUrl = "http://localhost:8000/QB2/QBTest/navigator/ussd_navigator.php";
$string = "?MSISDN=$newMSISDN&SESSIONID=$newsessionID&INPUT=" . urlencode($newinput) . "&USSD_CODE=*544#&NETWORK=MTN&IMSI=$newimsi";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $navUrl . "" . $string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
//curl_setopt($ch, CURLOPT_POST, 1);

$result = curl_exec($ch);
writeToFile(1, "the result " . print_r($result, true));
//$result=false;
//$result = curl_exec($ch);
if (!$result) { //some error occured
    // writeToFile(0, "error when posting the payload to ussd script $navUrl$string erro");
    echo "END $response_to_network";
    exit();
} else {



    writeToFile(1, "got the response " . serialize($result) . " from URL " . $navUrl . "" . $string);
    $result = json_decode($result, true);
    $message = filter_var($result['USSDMESSAGE'], FILTER_SANITIZE_STRING);
    $end_of_session = filter_var($result['END'], FILTER_SANITIZE_STRING);

    /* CP shud respond with free flow continue FC to continue the session and free flow break FB to end the session.  Based on this tag,  ussd will continue or end the session . */

    if ($end_of_session) {
        header('Freeflow: FB');
        header('charge: Y');
        header('amount: 0');
        header('Pragma: no-cache');
        header('Expires: -1');
        header('Cache-Control: max-age=0');
        header('Content-Type: text/plain');
    } else {
        header('Freeflow: FC');
        header('charge: Y');
        header('amount: 0');
        header('Pragma: no-cache');
        header('Expires: -1');
        header('Cache-Control: max-age=0');
        header('Content-Type: text/plain');
    }

    if ($end_of_session) {
        // $response_to_network = "FB $message";
        $response_to_network = "$message";
    } else {
        //$response_to_network = "FC  $message";
        $response_to_network = "$message";
    }
}
//writeToFile(1, "response we gave to network $response_to_network");
echo $response_to_network;
//echo "END Pesa Plus service coming soon";
?>
