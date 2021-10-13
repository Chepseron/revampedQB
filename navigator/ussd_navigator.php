<?php

include_once '../shared/configs.php';
include_once '../shared/utils.php';

//$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
//writeToFile(1, "USSD Details received::::::::" . print_r($_GET, true));


$oldmobileNumber = isset($_GET['MSISDN']) ? filter_var($_GET['MSISDN'], FILTER_SANITIZE_STRING) : 0;
$oldsessionID = isset($_GET['SESSIONID']) ? filter_var($_GET['SESSIONID'], FILTER_SANITIZE_STRING) : 0;
$oldnetwork = isset($_GET['NETWORK']) ? filter_var($_GET['NETWORK'], FILTER_SANITIZE_STRING) : "";
$oldIMSI = isset($_GET['IMSI']) ? filter_var($_GET['IMSI'], FILTER_SANITIZE_STRING) : "";



$mobileNumber = filter_var($oldmobileNumber, FILTER_SANITIZE_STRING);
$sessionID = filter_var($oldsessionID, FILTER_SANITIZE_STRING);
$network = filter_var($oldnetwork, FILTER_SANITIZE_STRING);
$IMSI = filter_var($oldIMSI, FILTER_SANITIZE_STRING);


$ussdCode = "*544#"; //$_GET['USSD_CODE'];
$inputArr = explode("*", filter_var($_GET['INPUT'], FILTER_SANITIZE_STRING));
$input = filter_var(end($inputArr), FILTER_SANITIZE_STRING);


//$ussdCode = $defaultCode;
$extra = 'null';
//connect to db


try {
# MySQL with PDO_MYSQL
    $db_connect = new PDO("mysql:host=$dbHost;dbname=$ussdDataBaseName", $dbUser, $DBpassword);

    $db_connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $ex) {
    writeToFile(0, "Error when connecting to DB error " . $ex->getMessage());
    output($default_error_message);
    exit();
}

$sessionInfo = getSessionInfo($db_connect, $mobileNumber, $sessionID, $ussdCode, $IMSI, $network);
if (!$sessionInfo['status']) {    //error when getting the session
    writeToFile(0, "ERROR when getting sessionINFO");
    output($default_error_message);
    exit();
}

$opCode = "";
$sessionID = $sessionInfo['sessionID'];
if ($sessionInfo['state'] == "NEW") {    //create the hop with the level as 1
    writeToFile(1, "NEW session");
    //get the USSD url
    $ussdCodeUrl = getUssdUrl($db_connect, $ussdCode);
    if (!$ussdCodeUrl) {
        updateSessionDetails($sessionID, 1, 0, $default_error_message, null, null, true);
        // output($default_error_message);
        output($ussd_code_not_exist);

        writeToFile(0, "Unable to get the url to code $ussdCode in the database");
        exit();
    }

    writeToFile(1, "url to code '$ussdCode' '$ussdCodeUrl'");
    //update this session with the fetched URL
    $update = updateSessionURL($sessionID, $mobileNumber, $ussdCodeUrl);

    $ussdLevel = 1;
    $created = createSessionNav($mobileNumber, $sessionID, $ussdLevel);
    if (!$created) {
        //error when adding the level
        updateSessionDetails($sessionID, 1, 0, $default_error_message, null, null, true);
        output($default_error_message);
        exit();
    }
    $opCode = "BEGIN";
} else {
    $opCode = "CONTINUE";
    writeToFile(1, "continuing");
    $dd = getNextUssdLevel($sessionID, $mobileNumber);
    $ussdLevel = $dd['nextLevel'];
    $extra = $dd['extra'];
    $ussdCodeUrl = $sessionInfo['url'];
}
$ussdPayload = array("mobileNumber" => $mobileNumber, "input" => $input, "level" => $ussdLevel, "extra" => $extra, "session_id" => $sessionID, 'opCode' => $opCode, 'network' => $network, 'imsi' => $IMSI);
writeToFile(1, "--------------About to invoke url '$ussdCodeUrl' for USSD code '$ussdCode' with params " . serialize($ussdPayload));
$response = postPayload($ussdCodeUrl, $ussdPayload);

writeToFile(1, "=============the response recieved is " . print_r($response, true));

if (!$response) {    //error
    updateSessionDetails($sessionID, 1, 0, $default_error_message, null, null, true);
    output($default_error_message);
    exit();
} else {
    writeToFile(1, "----> the response recieved ------------->" . print_r($response, true));
    $response = json_decode($response, true);
    $response['nextLevel'] = isset($response['nextLevel']) ? $response['nextLevel'] : 0;
    $response['ussdMessage'] = isset($response['ussdMessage']) ? $response['ussdMessage'] : 'We are unable to complete your request at the moment, Please try again later';
    $response['close'] = isset($response['close']) ? $response['close'] : true;

    if (isset($response['ussdMessage']) & isset($response['nextLevel']) & isset($response['close'])) {
        $ussdMessage = $response['ussdMessage'];
        $nextLevel = $response['nextLevel'];
        $closeSession = $response['close'];
        $inputTypeExpected = isset($response['inputTypeExpected']) ? $response['inputTypeExpected'] : "alphanum";
        $extra = isset($response['extra']) ? $response['extra'] : "";
        $save = saveNextLevel($mobileNumber, $nextLevel, $sessionID, $extra);
        if ($closeSession) {
            closeSession($sessionID, $mobileNumber);
        }
    } else {
        $ussdMessage = "INTERNAL ERROR";
        $closeSession = true;
        $nextLevel = 0;
    }

    $ussd_array = array("END" => $closeSession, "USSDMESSAGE" => $ussdMessage, "SESSIONID" => $sessionID, "MSISDN" => $mobileNumber);

    updateSessionDetails($sessionID, $ussdLevel, $nextLevel, $ussdMessage, $sessionInfo['sessionActivity'], $input, $closeSession);
    output(json_encode($ussd_array));
}
?>
