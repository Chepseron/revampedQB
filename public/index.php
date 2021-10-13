<?php

//https://skedpg01.stanbicbank.co.ke:9045/ssblocal/ssbmobile/USSD/menus/stanbic_south_sudan.php
/*
  alter table ForexRequests add column T24RefNumber varchar(100);
 * alter table SelfRegistrations add column T24RefNumber varchar(100);
 */

include_once '../shared/configs.php';
include_once '../shared/utils.php';


date_default_timezone_set('Africa/Nairobi');
$mobileNumber = filter_var(0 + isset($_REQUEST['mobileNumber']) ? $_REQUEST['mobileNumber'] : 0, FILTER_SANITIZE_STRING);
$level = filter_var(0 + isset($_REQUEST['level']) ? $_REQUEST['level'] : 0, FILTER_SANITIZE_STRING);
$input = filter_var(0 + isset($_REQUEST['input']) ? $_REQUEST['input'] : 0, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
$extra = 0 + isset($_REQUEST['extra']) ? $_REQUEST['extra'] : 0;
$opCode = filter_var(0 + isset($_REQUEST['opCode']) ? $_REQUEST['opCode'] : 0, FILTER_SANITIZE_STRING);
$sessionID = filter_var(0 + isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : 0, FILTER_SANITIZE_STRING);
$network = filter_var(0 + isset($_REQUEST['network']) ? $_REQUEST['network'] : 0, FILTER_SANITIZE_STRING);

$redis = new \Redis();
$redis->connect('192.168.1.100', 6379);

if (isset($extra)) {
    $extraArr = json_decode($extra, true);
    $extraArr['invalidInput'] = "";
}

$msisdn = $mobileNumber;
$shortcode = $opCode;
//$Country = $countryName;
$month;
$day;
$year;
$passr = array();
$extraArr = array();
//$base_url="https://live.api.quickbus.com/v2";
// $base_url="https://sandbox.api.quickbus.com/v1";
$base_url = "https://dev.api.quickbus.com/v1";
$to = 'chepseron@gmail.com';
$headers = ['From' => 'chepseron@gmail.com',
    'Reply-To' => 'chepseron@gmail.com'];

//
//
if (isset($extra)) {
    $extraArr = json_decode($extra, true);
    $extraArr['invalidInput'] = "";
}
$response = array();
$response['action'] = 'con';
$response['nextLevel'] = $level;
$response['close'] = false;
$CITIES = "";
$CITIESJSON = "";
$mhisaErrorStatus = 404;
$mhisaSuccessStatus = 200;
$mhisaSuccessStatusCreate = 201;
DEFINE('EXITKEY', '000. Exit');
DEFINE('BACKKEY', '00. Return to menu');
DEFINE('EXITKEYVALUE', '000');
DEFINE('BACKKEYVALUE', '00');
DEFINE('APPNAME', 'Quick Bus');
DEFINE('SEPARATOR', '');
DEFINE('MAINTENANCEMODE', false);


//End points
//Display Codes - root
DEFINE('PINLENGTH', 4);
DEFINE('BALANCE', 1000);
DEFINE('MINISTATEMENT', 2000);
DEFINE('FULLSTATEMENT', 3000);
DEFINE('FOREX', 4000);
DEFINE('AIRTIME', 5000);
DEFINE('SENDMONEY', 6000);
//DEFINE('CHANGEPIN', 7000);
DEFINE('INTERBANKFT', 6001);
DEFINE('CROSSBORDERFT', 6002);
DEFINE('PESALINKFT', 6003);
DEFINE('CHANGEPIN', 7);


DEFINE('ACTIVESTATUS', 1);
DEFINE('INACTIVESTATUS', 2);
DEFINE('LOCKEDSTATUS', 3);
DEFINE('BLOCKEDSTATUS', 4);
DEFINE('BLOCKEDOTPSTATUS', 5);
DEFINE('TEMPORARYLOCKSTATUS', 6);
DEFINE('EXPIREDOTP', 7);
DEFINE('PENDINGSTATUS', 0); //customers needs to change OTP
DEFINE('NOACCOUNTSSTATUS', 1533);
DEFINE('TECHERROR', 'TECHERROR');

//errors
DEFINE('CDSMAXTRIES', 3);
DEFINE('ENVIRONMENT', 'STAGING');
DEFINE('PINMAXTRIES', 3);
DEFINE('IDMAXTRIES', 3);
DEFINE('OTPEXPIRYPERIOD', 10); //MINUTES
DEFINE('OTPEXPIRYPERIODFIRSTTIME', 1440); //MINUTES



DEFINE('DATABASENAME', $ussdDataBaseName);
DEFINE('DBUSERNAME', $dbUser);
DEFINE('DBPASSWORD', $DBpassword);
DEFINE('DBHOST', $dbHost);


DEFINE('CUSTOMERNOTFOUNDSTATUS', 1533);


switch (ENVIRONMENT) {
    case "PRODUCTION":
        $flogPath = $defaultLogPath . date('dmY') . "_QUICKBUS_PRODUCTION.log";
        break;
    case "STAGING":
        $flogPath = $defaultLogPath . date('dmY') . "_QUICKBUS_STAGING.log";
        break;
    default :
}

$SesionStartDatetime = date('Y-m-d H:i:s');
flog($flogPath, "-----------------------Start Time::" . $SesionStartDatetime . " || Phone :: " . $mobileNumber . "|| Input:: " . $input);


$tempLevels = array(
    '1' => 'mainMenu',
    '2' => 'mainMenuRegisteredCustomers',
    '3' => 'menuProcessor',
    '5' => 'termsAndConditions',
    '99' => 'source',
    '100' => 'processFullstatementAccount',
    '101' => 'processFullstatementEmail',
    '102' => 'destination',
    '103' => 'travelConfirmation',
    '104' => 'travelTime',
    '105' => 'travelTimeExtra160',
    '106' => 'boardingPoints',
    '107' => 'promptPassNumber',
    '108' => 'checkPassengerNumber',
    '109' => 'secondPassenger',
    '110' => 'firstPassenger',
    '111' => 'checkIDOrEmergency',
    '112' => 'passengerConfirmation',
    '113' => 'seats',
    '114' => 'payphone',
    '116' => 'otherphonepaymentconfirmation',
    '117' => 'payment',
    '123' => 'quickBusRequestsPost',
    '124' => 'quickBusRequestsGet',
    '125' => 'bookreturn',
    '126' => 'customerservices',
    '127' => 'menuProcessorCustomerServices',
    '128' => 'bookUpComing',
    '129' => 'bookingHistoryDetails',
    '130' => 'bookingHistory',
    '131' => 'contact',
    '132' => 'cancelticket',
    '133' => 'whatsapphome',
    '134' => 'refer',
    '135' => 'Viewrewards'
);
//The following code decides which templevel controller function will be run
if (isset($tempLevels[$level])) {
    if ($level == 20 && $input == 15) { //temporary hack for double invocation
        mainMenu($input);
    } elseif ($level == 2 && $input == 15) {
        mainMenu($input);
    } elseif ($level == 3 && $input == 15) {
        mainMenu($input);
    } elseif ($level == 40 && $input == 15) {
        mainMenu($input);
    } else {
        $results = $tempLevels[$level]($input);
    }
}

//$rootIDs = jso$extraArr['rootIDs'];
flog($flogPath, "$mobileNumber|$level|$input|$extra|$opCode");

function mainMenu($input = Null) { //level 1
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;

    if (MAINTENANCEMODE == true) {
        maintenanceScreen();
    } else {
        processTheMainMenu($input);
    }
}

function checkIfNumberIsWhitelisted($msisdn) {
    global $flogPath;
    $conn = connectToDb();
    try {
        $query = "SELECT MSISDN from whiteList where MSISDN =:mobile AND coopNo =:coopNo";
        $stmt = $conn->prepare($query);
        $stmt->execute([':mobile' => $msisdn, 'coopNo' => CORPORATE_NO]);
        $row = $stmt->fetch();
        if (!empty($row)) {
            return true;
        }
    } catch (Exception $ex) {
        flog($flogPath, "Exception " . $ex->getMessage());
    }

    return false;
}

function whatsapphome() {
    try {

        $request = "{ \"templateParams\": 
                        {\"firstName\":\"Customer\"},
            \"id\":\"b16254ce-6c3d-47f9-91e8-c90dda7d1b66\",
            \"mobile\":\"" . $mobileNumber . "\" }";
        flog($mobileNumber, "Whatsapp REQUEST : " . $request);
        $url = $base_url . "/gen/utility/sms";
        $results = $this->quickBusRequestsPost($url, $request);
        flog($mobileNumber, "Whatsapp response : " . $request);

        $extraArr['PREVMSG'] = "Book tickets using WhatsApp for return journeys, seat selection and no USSD session charge.\n\n  
                                              Simply, text \"hi\" using WhatsApp to +254110000078\n" . BACKKEYVALUE;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    } catch (\Throwable $e) {
        $subject = 'WHATSAPP ERROR';
        $message = $e->getMessage();
        $this->email($subject, $message);
        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later';
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function maintenanceScreen() { //if the system is not avalibale
    global $message, $extraArr, $response;

    $extraArr['PREVMSG'] = "Sorry, " . APPNAME . " is currently under maintenance. Please retry after some time or call " . CUSTOMERCARENUMBER . " for assistance.";
    $message = $extraArr['PREVMSG'];
    $response['close'] = true;
}

function processTheMainMenu($input = Null) { //general function
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;

    $isCustomerRegistered = checkIfUserIsRegisteredOnMB();
    if ($isCustomerRegistered != CUSTOMERNOTFOUNDSTATUS) {
        flog($flogPath, "passed the condition with status::" . $isCustomerRegistered);

        switch ($isCustomerRegistered) {
            case ACTIVESTATUS:
                mainMenuRegisteredCustomers(); //proceed to login
                break;
            case INACTIVESTATUS:
                firstMenuNonRegisteredCustomers(); //go to registration...past terms and conditions
                break;
            case BLOCKEDSTATUS:
                firstMenuBLockedCustomers();
                break;
            case BLOCKEDOTPSTATUS:
                firstMenuBLockedCustomers();
                break;
            case EXPIREDOTP:
                firstMenuExpiredOTP();
                break;
            case LOCKEDSTATUS:
                firstMenuBLockedCustomers();
                break;
            case PENDINGSTATUS:
                firstMenuPendingOTPEntryCustomers(); //enter OTP
                break;
            case CUSTOMERNOTFOUNDSTATUS:
                flog($flogPath, "no accounts");
                firstMenuNonRegisteredCustomers();
                break;
            case TECHERROR:
                flog($flogPath, "This is a technical error");
                defaultErrorScreen();
            default: //no aacount
                flog($flogPath, "no status received");
                defaultErrorScreen(); //no cds thus give customer instructions
                break;
        }
    } else {
        flog($flogPath, "The account was not found");
        firstMenuNonRegisteredCustomers();
    }
}

//thought works functions
function checkIfUserIsRegisteredOnMB() { //this is the first function which is called
    global $mobileNumber, $extraArr, $flogPath;
    $conn = connectToDb();
    try {
        return 1;
    } catch (Exception $ex) {
        flog($flogPath, "Exception " . $ex->getMessage());
    }
    return CUSTOMERNOTFOUNDSTATUS;
}

function email($subject, $emailMessage) {

    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $emails = array();
    $html = '<html><h1>5% off its awesome</h1><p>Go get it now !</p></html>';
    $emails = ['chepseron@gmail', 'spencer@quickbus.com', 'dondausi@gmail'];
    try {
        Mail::send(array(), array(), function ($emailMessage) use ($html) {
            $message->to($emails)
                    ->subject($subject)
                    ->from("chepseron@gmail")
                    ->setBody('<html><h1>Error Message!</p></html>', $emailMessage);
        });
    } catch (\Throwable $e) {
        $message = $e->getMessage();
        flog($flogPath, "MAIL EXCEPTION : " . $message);
    }
}

function login() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    flog($flogPath, $base_url);
    $url = $base_url . "/auth/login";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"key\":\"ussd136675\",
                                               \"secret\":\"5gH9jWBwbq5Yt9s5uM4fuCeBvKMMrt8TZsGvkH\",
                                                \"referrer\":\"" . $extraArr['REFFEREDNUMBER'] . "\",
                                               \"currency\":\"KES\",
                                               \"mobile\":\"" . $mobileNumber . "\"}");
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $results = curl_exec($ch);
    if (curl_errno($ch)) {
        flog($flogPath, "TOKENS RESPONSE ERROR: " . curl_error($ch));
    }
    curl_close($ch);
    flog($flogPath, "LOGIN REQUEST: " . "{\"key\":\"ussd136675\",
                                               \"secret\":\"5gH9jWBwbq5Yt9s5uM4fuCeBvKMMrt8TZsGvkH\",
                                                \"referrer\":\"" . $extraArr['REFFEREDNUMBER'] . "\",
                                               \"currency\":\"KES\",
                                               \"mobile\":\"" . $mobileNumber . "\"}");
    flog($flogPath, "LOGIN: " . $results);
    $data2 = json_decode($results, true);
    $message = "";
    $access_token = $data2['access_token'];
    $session_token = $data2['session_token'];
    return $access_token . ":" . $session_token;
}

function mainMenuRegisteredCustomers() { //level 2
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $extraArr['REFFEREDNUMBER'] = $input;
//    $pos = substr($input, 0, 1) === '0';
//    flog($flogPath, "RFFFERER AVAILABLE : " . $pos);
//    flog($flogPath, "RFFFERER LENGTH : " . strlen($input));
//    if ($pos === true && strlen($input) > 1) {
//
//        $extraArr['REFFEREDNUMBER'] = substr($input, 1);
//        flog($flogPath, "REFERAL CODE  XX" . $extraArr['REFFEREDNUMBER']);
//        $access_token = login();
//        $extraArr['MARKETINGMESSAGE'] = marketingMessage();
//        $extraArr['REFERMARKETINGMESSAGE'] = marketingMessageFlexi();
//        $extraArr['REFERMARKETINGMESSAGE'] = marketingMessageRefer();
//    }
    $access_token = login();
    $mktingMsgs = marketingMessage();
    // $extraArr['FLEXIMARKETINGMESSAGE'] = marketingMessageFlexi();
    $responses = explode(':', $access_token);
    $extraArr['ACCESSTOKEN'] = $responses[0];
    $extraArr['SESSIONTOKEN'] = $responses[1];
    $url = $base_url . "/destinations/cities?index=1&size=1000";
    $request = "";
    $result = quickBusRequestsGet($url, $request);
    $num = 1;
    $city_name = array();
    $data = json_decode($result, true);
    foreach ($data as $item) {
        $city_name[] = $item['city_id'] . ":" . $item['city_name'];
        $num++;
    }

    $_SESSION['CITIESJSON'] = $result;
    $_SESSION['CITIES'] = implode(",", $city_name);
//    $redis->set("CITIESJSON", $result);
//    $redis->set("CITIES", implode(",", $city_name));
    flog($flogPath, "marketing message " . $mktingMsgs);
    $extraArr['MENU_ITEMS'] = "Welcome to QuickBus." . $mktingMsgs . "\nChoose:\n1.Book One-way\n2.Book Return\n3.Cust Services\n4.Whatsapp\n5.Refer a friend\n000.Exit";
    $extraArr['PREVMSG'] = $extraArr['MENU_ITEMS'];
    $message = $extraArr['PREVMSG'];
    $response['nextLevel'] = 3;
}

function menuProcessor($input) { //level 3
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    flog($flogPath, " Enterered value " . $input);
    if ($input === BACKKEYVALUE) {
        mainMenuRegisteredCustomers();
    } elseif ($input == EXITKEYVALUE) {
        $extraArr['PREVMSG'] = "Thankyou for using " . APPNAME . " system";
        $message = $extraArr['PREVMSG'];
        $response['action'] = 'end';
    } else {
        if (!empty($input)) {
            switch ($input) {
                case 1:
                    source();
                    break;
                case 2:
                    bookreturn();
                    break;
                case 3:
                    customerservices();
                    break;
                case 4:
                    whatsapphome();
                    break;
                case 5:
                    refer();
                    break;
                default: //time to exit
                    $extraArr['PREVMSG'] = "No menu or function found for this item." . BACKKEY;
                    $message = $extraArr['PREVMSG'];
                    $response['nextLevel'] = 71;
                    break;
            }
        } else {
            flog($flogPath, "LOGIN REQUEST: " . "{\"key\":\"ussd136675\",
                                               \"secret\":\"5gH9jWBwbq5Yt9s5uM4fuCeBvKMMrt8TZsGvkH\",
                                                \"referrer\":\"" . $extraArr['REFFEREDNUMBER'] . "\",
                                               \"currency\":\"KES\",
                                               \"mobile\":\"" . $mobileNumber . "\"}");
            $extraArr['PREVMSG'] = $extraArr['MENU_ITEMS'];
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 3;
        }
    }
}

function marketingMessageRefer() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $message = "";
        $url2 = $base_url . "/marketing/slugs/get";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"slug\":\"refer-friend-offer\",
                  \"platform\":\"ussd\",
                  \"language\":\"EN\",                  
                  \"country\":\"KE\",
                  \"phone\":\"" . $mobileNumber . "\",
                  \"templateParams\":{
                  \"userid\":\"" . $this->GetUserId() . "\"}
              }"
        );
        flog($flogPath, "REQUEST URL : " . $url2 . " " .
                "{\"slug\":\"refer-friend-offer\",
                  \"platform\":\"ussd\",
                  \"language\":\"EN\",                  
                  \"country\":\"KE\",
                  \"phone\":\"" . $mobileNumber . "\",
                  \"templateParams\":{
                  \"userid\":\"" . $this->GetUserId() . "\"}
              }"
        );
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $results = curl_exec($ch);
        if (curl_errno($ch)) {
            flog($flogPath, "TOKENS RESPONSE ERROR: " . curl_error($ch));
        }
        curl_close($ch);
        flog($mobileNumber, "REFER MARKETING MESSAGE: " . $results);
        $data2 = json_decode($results, true);
        $message = "";
        if ($results == "{}") {
            
        } else {
            $message = $data2['text'];
        }
        return $message;
    } catch (\Throwable $e) {
        $subject = 'MARKETING MESSAGE ERROR';
        $message = $e->getMessage();
        $this->email($subject, $message);

        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function marketingMessageFlexi() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        flog($flogPath, $base_url);
        $url = $base_url . "/marketing/slugs/get";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"slug\":\"flex-menu-offer\",\"platform\":\"ussd\",\"language\":\"EN\",\"country\":\"KE\",\"phone\":\"" . $mobileNumber . "\"}");
        flog($flogPath, "REQUEST URL : " . $url . " " . "{\"slug\":\"flex-menu-offer\",\"platform\":\"ussd\",\"language\":\"EN\",\"country\":\"KE\",\"phone\":\"" . $mobileNumber . "\"}");
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $results = curl_exec($ch);
        if (curl_errno($ch)) {
            flog($flogPath, "TOKENS RESPONSE ERROR: " . curl_error($ch));
        }
        curl_close($ch);
        flog($flogPath, "FLEXI MARKETING MESSAGE: " . $results);
        $data2 = json_decode($results, true);
        $message = "";
        if ($results == "{}") {
            
        } else {
            $message = $data2['text'];
        }
        return $message;
    } catch (\Throwable $e) {
        $subject = 'MARKETING MESSAGE ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEYVALUE;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function refer() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $extraArr['REFERMARKETINGMESSAGE'] = $this->marketingMessageRefer();
        $extraArr['PREVMSG'] = $extraArr['REFERMARKETINGMESSAGE'] . "\n1.View rewards\n0.Back\n000.Exit";
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 135;
    } catch (\Throwable $e) {
        $subject = 'REFER ERROR';
        $message = $e->getMessage();
        flog($flogPath, "REFER EXCEPTION : " . $message);
        $this->email($subject, $message);

        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function Viewrewards() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;

    $message = "";
    $phone = substr($mobileNumber, 0, 1) === '+' ? $mobileNumber : '+' . $mobileNumber;

    $url = $base_url . "/user/referrals";
    try {
        $request = "{
            \"platform\":\"ussd\",
             \"mobile\":\"" . $phone . "\"}";

        flog($flogPath, "REQUEST URL : " . $url);
        flog($flogPath, "VIEW REWARDS : " . $request);
        //$url = 'https://api2.quickbus.com/v1/flexi/reservation';

        $results = $this->quickBusRequestsPost($url, $request);
        flog($flogPath, "VIEW REWARDS RESPONSE  : " . $results);
        $data = json_decode($results, true);
        $message = $data['referrals_tickets_sold'] . " bookings made by referral and  " . $data['referrals_confirmed_journeys'] . "  journeys taken. Commissions due " . $data['referral_prize'] . " To claim call +254716292929";
        $extraArr['PREVMSG'] = $message . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    } catch (\Throwable $e) {
        $subject = 'BOOK RETURN ERROR';
        $message = $e->getMessage();
        $this->email($subject, $message);

        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function marketingMessage() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {

        $url = $base_url . "/marketing/slugs/get";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "
                {\"slug\":\"homemenu-offer\",
                  \"platform\":\"ussd\",
                  \"language\":\"EN\",
                  \"country\":\"KE\",
                  \"phone\":\"" . $mobileNumber . "\"}"
        );


        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $results = curl_exec($ch);


        if (curl_errno($ch)) {
            flog($flogPath, "TOKENS RESPONSE ERROR: " . curl_error($ch));
        }
        curl_close($ch);

        $data2 = json_decode($results, true);

        $message = "";
        if ($results == "{}") {
            
        } else {
            $message = $data2['text'];
        }

        flog($flogPath, "MARKETING MESSAGE: " . $message);
        return $message;
    } catch (\Throwable $e) {
        $subject = 'MARKETING MESSAGE ERROR';
        $message = $e->getMessage();
        flog($flogPath, "MARKETING MESSAGE ERROR : " . $message);
        email($subject, $message);

        $response = (object) array('id' => 'marketingMessage', 'action' => 'end', 'response' => 'There was a problem processing your request, Please try again later', 'map' => array((object) array('menu' => 'marketingMessage')), 'type' => 'static');
        return $response;
    }
}

function quickBusRequestsGet($url, $request) {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $headers = array();
    $headers[] = 'Authorization:Bearer ' . $extraArr['ACCESSTOKEN'];
    $headers[] = 'session:' . $extraArr['SESSIONTOKEN'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function quickBusRequestsPost($url, $request) {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    $headers = array();
    $headers[] = 'Authorization:Bearer ' . $extraArr['ACCESSTOKEN'];
    $headers[] = 'session:' . $extraArr['SESSIONTOKEN'];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $results = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $results;
}

function cancelticket() {
    $extraArr['PREVMSG'] = "Standard tickets have free cancellation before 72h\n\nTo request a change of travel date, get ticket ref number from Bookings menu and call +254716292929\n0. Back";
    $message = $extraArr['PREVMSG'];
    $response['nextLevel'] = 3;
}

function contact() {
    $extraArr['PREVMSG'] = "Call/WhatsApp us at +254716292929 or email support@quickbus.com\n\nAlso see:\nquickbus.com/faq\nquickbus.com/privacy\nquickbus.com/terms \n\n0. Back";
    $message = $extraArr['PREVMSG'];
    $response['nextLevel'] = 3;
}

function bookingHistory() {
    try {
        $request = "{\"mobile\":\"+" . str_replace('+', '', $mobileNumber) . "\"}";
        flog($flogPath, "REQUEST : " . $request);
        // $url = 'https://api2.quickbus.com/v1/user/bookings';
        $url = $base_url . '/user/bookings';
        $results = $this->quickBusRequestsPost($url, $request);
        flog($flogPath, "RESULTS : " . $results);
        $num = "1";
        $data = json_decode($results, true);
        $res = Array();
        $message = "";

        $pos = strpos($results, "\"history\":[]");
        if ($pos == true) {
            $message .= "You have no previous journeys" . PHP_EOL;
        } else if (strpos($results, "\"400\"") == true) {
            $message .= "Failed to obtain your booking history" . PHP_EOL;
        } else {
            $message .= "Previous:" . PHP_EOL . "\n Choose No:\n" . PHP_EOL;
            foreach ($data['history'] as $item) {

                if ($num <= "6") {
                    $date = new DateTime($item['date']);
                    $travelTime = strtoupper($date->format('j/n'));
                    $message .= $num . " " . str_replace(" To ", "-", $item['city']) . " " . $travelTime . PHP_EOL;
                    $res[] = $num . ":" . str_replace("To", "-", $item['city']) . ":" . $item['type'] . ":" . $item['seats'] . ":" . $item['ref'] . ":" . $travelTime;
                    $num++;
                }
            }
        }
        $extraArr['BOOKINGS'] = implode(",", $res);
        $extraArr['MENU_ITEMS'] = $message;
        $extraArr['PREVMSG'] = $extraArr['MENU_ITEMS'];
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 129;
    } catch (\Throwable $e) {
        $subject = 'BOOK HISTORY ERROR';
        $message = $e->getMessage();
        $this->email($subject, $message);

        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function bookUpComing($input) {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $request = "{\"mobile\":\"+" . str_replace('+', '', $mobileNumber) . "\"}";
        flog($flogPath, "REQUEST : " . $request);
        // $url = 'https://api2.quickbus.com/v1/user/bookings';
        $url = $base_url . '/user/bookings';
        $results = $this->quickBusRequestsPost($url, $request);
        flog($flogPath, "RESULTS : " . $results);
        $num = "1";
        $data = json_decode($results, true);
        $res = Array();
        $message = "";
        if (strpos($results, "\"upcoming\":[]") == true) {
            $message .= "You have no upcoming journeys" . PHP_EOL;
        } else if (strpos($results, "\"400\"") == true) {
            $message .= "Failed to obtain your booking history" . PHP_EOL;
        } else {
            $message .= "Coming:" . PHP_EOL . "\n Choose No:\n";
            foreach ($data['upcoming'] as $item) {
                $date = new DateTime($item['date']);
                $travelTime = strtoupper($date->format('j/n'));
                $message .= $num . " " . str_replace(" To ", "-", $item['city']) . " " . $travelTime . PHP_EOL;
                $res[] = $num . ":" . str_replace("To", "-", $item['city']) . ":" . $item['type'] . ":" . $item['seats'] . ":" . $item['ref'] . ":" . $travelTime;
                $num++;
            }
        }
        $extraArr['BOOKINGS'] = implode(",", $res);
        $extraArr['MENU_ITEMS'] = $message;
        $extraArr['PREVMSG'] = $extraArr['MENU_ITEMS'];
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 129;
    } catch (\Throwable $e) {
        $subject = 'BOOK HISTORY ERROR';
        $message = $e->getMessage();
        $this->email($subject, $message);
        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function bookingHistorydetails($input) {
    try {
        $bookings = explode(',', $extraArr['BOOKINGS']);
        $Selected = $input;
        $Index = $Selected - 1;
        $actualBooking = $bookings[$Index];
        $details = explode(':', $actualBooking);
        $request = "";


        flog($flogPath, "REQUEST : " . $details);
        $url = $base_url . '/user/bookings/' . $details[4];
        $results = $this->quickBusRequestsPost($url, $request);


        flog($flogPath, "RESULTS : " . $results);
        $data = json_decode($results, true);
        $date = new DateTime($data[0]['date']);
        $travelTime = strtoupper($date->format('j/n'));


        $message = str_replace("To", "to", $data[0]['city']) . " " . $travelTime . PHP_EOL . PHP_EOL;
        $message .= "TNR:" . $data[0]['tnr'] . PHP_EOL;
        $message .= "Ref:" . $data[0]['ref'] . PHP_EOL;
        $message .= "Passengers:" . $data[0]['seats'] . PHP_EOL;
        $message .= "Paid " . $data[0]['paid'] . PHP_EOL;
        $message .= "Due:" . $data[0]['dues'] . PHP_EOL;
        $message .= "Type:" . $data[0]['offer'] . "-Free to cancel before 72h. Call +254716292929" . PHP_EOL;

        $extraArr['PREVMSG'] = $message . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
        return $response;
    } catch (\Throwable $e) {

        $subject = 'BOOKING HISTORY DETAILS ERROR';
        $message = $e->getMessage();
        $this->email($subject, $message);


        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function customerservices() {
    $extraArr['MENU_ITEMS'] = "How can we help you?\n1.View tickets\n2.Cancel or change tkt\n3.Previous journeys\n4.Call us\n5.FAQ\n0.Back\n000.Exit";
    $extraArr['PREVMSG'] = $extraArr['MENU_ITEMS'];
    $message = $extraArr['PREVMSG'];
    $response['nextLevel'] = 127;
}

function menuProcessorCustomerServices($input) { //level 3
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;

    flog($flogPath, " Enterered value " . $input);

    if ($input === BACKKEYVALUE) {
        mainMenuRegisteredCustomers();
    } elseif ($input == EXITKEYVALUE) {
        $extraArr['PREVMSG'] = "Thankyou for using " . APPNAME . " system";
        $message = $extraArr['PREVMSG'];
        $response['action'] = 'end';
    } else {
        switch ($input) {
            case 1:
                bookUpComing();
                break;
            case 2:
                cancelticket();
                break;
            case 3:
                bookingHistory();
                break;
            case 4:
                contact();
                break;
            default: //time to exit
                $extraArr['PREVMSG'] = "No menu or function found for this item." . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 71;
                break;
        }
    }
}

function bookreturn() {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $message = "";
        $request = "{ \"templateParams\":{
                                 \"firstName\":\"Customer\"
                                 },
            \"id\":\"397d0139-0451-4d07-a1a3-7683f4f1831b\",
            \"mobile\":\"" . $mobileNumber . "\" }";
        flog($flogPath, "BOOK RETURN REQUEST : " . $request);
        $url = $base_url . "/gen/utility/sms";
        $results = $this->quickBusRequestsPost($url, $request);
        flog($flogPath, "BOOK RETURN RESULT : " . $results);
        $extraArr['PREVMSG'] = "Only one-way tickets are available over USSD. For return tickets, text \"hi\" using WhatsApp to +254110000078" . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    } catch (\Throwable $e) {

        $subject = 'BOOK RETURN ERROR';
        $message = $e->getMessage();
        flog($flogPath, "BOOK RETURN EXCEPTION : " . $message);
        email($subject, $message);

        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 3;
    }
}

function source() {

    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;

    try {
        $access_token = $extraArr['ACCESSTOKEN'];
        $prefix = substr($mobileNumber, 0, 3);
        if ($prefix !== "+254" || $prefix !== "+256" || $prefix !== "+255") {
            $prefix = "+254";
        }
        $country_code = "KE";
        $url = $base_url . "/destinations/populars-cities?country_code=" . $country_code;
        $request = "";
        $result = quickBusRequestsGet($url, $request);
        $num = 1;
        $Accounts = array();
        $AccountsID = array();
        $message = "";
        flog($flogPath, "SOURCES : " . $url . " RESULTS " . $result);
        //flog($flogPath,  "SOURCES : " . $url . " RESULTS " . $result);
        $message .= " Choose city you are travelling from or type name\n";
        $data = json_decode($result, true);
        foreach ($data as $item) { //foreach element in $arr
            $Accounts[] = $item['city_name'];
            $AccountsID[] = $item['city_id'];
            $message .= $num . " " . ucwords(strtolower($item['city_name'])) . "" . PHP_EOL;
            $num++;
        }

        $extraArr['SOURCES'] = implode(",", $Accounts);
        $extraArr['SOURCESID'] = implode(",", $AccountsID);
        $extraArr['CURRENTINDEX'] = $num;

        $extraArr['PREVMSG'] = $message . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 102;
    } catch (\Throwable $e) {
        $subject = 'GET SOURCES ERROR';
        $message = $e->getMessage();
        email($subject, $message);

        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function destination($input) {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $sourceJourney = "";
        $src = explode(",", $extraArr['SOURCES']);
        if (is_numeric($input)) {
            $Index1 = $input - 1;
            $sourceJourney = $src[$Index1];
            $extraArr['source'] = $sourceJourney;
        } else {
            $sourceJourney = $input;


            $totalCities = $redis->get("CITIESJSON");
            $data = json_decode($totalCities, true);
            foreach ($data as $item) {
                if (strpos($item['city_name'], ucwords(strtolower($sourceJourney))) !== FALSE) {
                    $sourceJourney = $item['city_name'];
                    $extraArr['source'] = $sourceJourney;
                }
            }
        }
        $access_token = $extraArr['ACCESSTOKEN'];

        flog($flogPath, "++++++++++++++++++++available cities" . $redis->get("CITIES"));
        if (strpos($redis->get("CITIES"), ucwords(strtolower($sourceJourney))) != FALSE) {
            $prefix2 = "";
            $prefix = substr($mobileNumber, 0, 3);
            if ($prefix !== "+254" || $prefix !== "+256" || $prefix !== "+255") {
                $prefix = "+254";
                $prefix2 = "254";
            }
            $country_code = "KE";

            // $url = $base_url . "/destinations/route-pairs?country_code=" . $prefix2 . "&country_name=KE&departure_city=" . $sourceJourney . "&arrival_country_code=" . $prefix2 . "&popular=true";
            $url = $base_url . "/destinations/route-pairs?departure_city=" . $sourceJourney;
            $request = "";
            $result = quickBusRequestsGet($url, $request);
            $num = 1;
            $data = json_decode($result, true);
            $message = "";
            $message .= $extraArr['DESTINATIONMISSINGMESSAGE'];
            $message .= " Choose where you want to go to or type or type name";

            $Accounts = array();
            $AccountsID = array();



            flog($flogPath, "DESTINATIONS : " . $url . " RESULTS " . $result);
            foreach ($data as $item) {
                if ($item['city_to'] == $sourceJourney) {
                    
                } else {
                    $Accounts[] = $item['city_to'];
                    $AccountsID[] = $item['city_to_id'];
                    $message .= $num . " " . ucwords(strtolower($item['city_to'])) . "" . PHP_EOL;
                    $num++;
                }
            }
            if (count($AccountsID) > 7 || strtolower($sourceJourney) === "nairobi") {
                $url = $base_url . "/destinations/populars-cities?country_code=" . $country_code;
                $request = "";
                $result = quickBusRequestsGet($url, $request);
                $num = 1;
                $data = json_decode($result, true);
                $message = "";
                $message .= $extraArr['DESTINATIONMISSINGMESSAGE'];
                //$message .= " You have chosen " . $sourceJourney . '.';
                $message .= "Choose where you want to go to or type name\n";
                $Accounts = array();
                $AccountsID = array();
                flog($flogPath, "DESTINATIONS : " . $url . " RESULTS " . $result);
                foreach ($data as $item2) {
                    if ($item2['city_name'] == $sourceJourney) {
                        
                    } else {
                        $Accounts[] = $item2['city_name'];
                        $AccountsID[] = $item2['city_id'];
                        $message .= $num . " " . ucwords(strtolower($item2['city_name'])) . "" . PHP_EOL;
                        $num++;
                    }
                }

                $extraArr['DESTINATIONS'] = implode(",", $Accounts);
                $extraArr['DESTINATIONSID'] = implode(",", $AccountsID);
                $message .= "0. Back";

                $extraArr['DESTINATIONMESSAGERESPONSE'] = $message;
                $response = (object) array('id' => 'destination', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'travelConfirmation')), 'type' => 'form');
                return $response;
            }

            $extraArr['DESTINATIONS'] = implode(",", $Accounts);
            $extraArr['DESTINATIONSID'] = implode(",", $AccountsID);
            $message .= "0. Back";
            $extraArr['DESTINATIONMESSAGERESPONSE'] = $message;


            $extraArr['PREVMSG'] = $message . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 103;
        } else {


            $extraArr['PREVMSG'] = 'We are unable to find your source city......' . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 71;
        }
    } catch (\Throwable $e) {
        $subject = 'GET DESTINATION ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        flog($flogPath, "DESTINATION ERROR : " . $message);
        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function travelConfirmation($input) {
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $dst = explode(",", $extraArr['DESTINATIONS']);
    if (is_numeric($input)) {
        $Index = $input - 1;
        $destinationJourney = $dst[$Index];
    } else {
        $destinationJourney = $input;
        $totalCities = $redis->get("CITIESJSON");
        $data = json_decode($totalCities, true);
        foreach ($data as $item) {
            if (strpos($item['city_name'], ucwords(strtolower($destinationJourney))) !== FALSE) {
                $destinationJourney = $item['city_name'];
            }
        }
    }
    try {
        if (strpos($redis->get("CITIES"), ucwords(strtolower($destinationJourney))) != FALSE) {
            $src = explode(",", $extraArr['SOURCES']);
            if (is_numeric($extraArr['source'])) {
                $Index1 = $extraArr['source'] - 1;
                $sourceJourney = $src[$Index1];
            } else {
                $sourceJourney = $extraArr['source'];
                $totalCities = $redis->get("CITIESJSON");
                $data = json_decode($totalCities, true);
                foreach ($data as $item) {
                    if (strpos($item['city_name'], ucwords(strtolower($sourceJourney))) !== FALSE) {
                        $sourceJourney = $item['city_name'];
                    }
                }
            }

            $extraArr['selectedSource'] = $sourceJourney;
            $extraArr['selectedDestination'] = $destinationJourney;

            $message = "";
            $message .= "1.Today" . PHP_EOL;
            $message .= "2.Tomorrow" . PHP_EOL;
            $message .= "3." . substr(date("d/m l", strtotime("+2 day")), 0, 8) . PHP_EOL;
            $message .= "4." . substr(date("d/m l", strtotime("+3 day")), 0, 8) . PHP_EOL;
            $message .= "5." . substr(date("d/m l", strtotime("+4 day")), 0, 8) . PHP_EOL;
            $message .= "6." . substr(date("d/m l", strtotime("+5 day")), 0, 8) . PHP_EOL;
            $message .= "7." . substr(date("d/m l", strtotime("+6 day")), 0, 8) . PHP_EOL;
            $message .= "8." . substr(date("d/m l", strtotime("+7 day")), 0, 8) . PHP_EOL;
            $message .= "9." . substr(date("d/m l", strtotime("+8 day")), 0, 8) . PHP_EOL;
            $message .= "10." . substr(date("d/m l", strtotime("+9 day")), 0, 8) . PHP_EOL;
            $message .= "98.More" . PHP_EOL;

            if ($sourceJourney !== $destinationJourney) {
                $extraArr['PREVMSG'] = '' . ucwords(strtolower($sourceJourney)) . ' to ' . ucwords(strtolower($destinationJourney)) . '' . PHP_EOL . 'Choose travel date:' . PHP_EOL . $message . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 104;
            } else {
                $extraArr['PREVMSG'] = 'Your source and destination journey cannot be the same' . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 71;
            }
        } else {
            $extraArr['PREVMSG'] = 'We are unable to find your destination.' . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 103;
        }
    } catch (Throwable $e) {
        $subject = 'TRAVEL CONFIRMATION ERROR';
        $message = $e->getMessage();
        flog($flogPath, "TRAVEL CONFIRMATION ERROR" . $message);
        email($subject, $message);
        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 103;
    }
}

function travelTime($input) {//104
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;

    $extraArr['travelConfirmation'] = $input;
    try {
        $travelTime = "";
        if ($input == "98") {
            $message = "";
            $message .= "11." . substr(date("d/m l", strtotime("+10 day")), 0, 8) . PHP_EOL;
            $message .= "12." . substr(date("d/m l", strtotime("+13 day")), 0, 8) . PHP_EOL;
            $message .= "13." . substr(date("d/m l", strtotime("+14 day")), 0, 8) . PHP_EOL;
            $message .= "14." . substr(date("d/m l", strtotime("+15 day")), 0, 8) . PHP_EOL;
            $message .= "15." . substr(date("d/m l", strtotime("+16 day")), 0, 8) . PHP_EOL;
            $message .= "16." . substr(date("d/m l", strtotime("+17 day")), 0, 8) . PHP_EOL;
            $message .= "17." . substr(date("d/m l", strtotime("+18 day")), 0, 8) . PHP_EOL;
            $message .= "18." . substr(date("d/m l", strtotime("+19 day")), 0, 8) . PHP_EOL;
            $message .= "0. Back" . PHP_EOL;
            $extraArr['PREVMSG'] = $message . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 104;
        } else if ($input > 1 && $input < 98) {
            $travelTime = backdatetime($input);
        } else if ($input == "1") {
            $travelTime = date("Ymd");
        } else {
            $enterdDate = $input;
            if (strlen($enterdDate) == 4) {
                $year = date("Y");
                flog($flogPath, "++++++++++++++++++++++++++++++ got to the date +++++++++++++++++++++++");
                $month = substr($enterdDate, -2); //get month
                $day = substr($enterdDate, 0, -2); //get day  
                $traveldate = $year . $month . $day;
                $dateObject = DateTime::createFromFormat("Ymd", $traveldate);
                $dateObject = $dateObject->format('Ymd');
                $travelTime = $dateObject;
                flog($flogPath, "TRAVEL DATE2 : " . $travelTime);
            }
        }
        $all_accounts = $redis->get("CITIES");
        $Accounts = explode(',', $all_accounts);
        $fromCityid = "";
        $fromCityName = "";
        $toCityid = "";
        $toCityName = "";
        flog($flogPath, "selectedSource : " . $extraArr['selectedSource']);
        flog($flogPath, "selectedDestination : " . $extraArr['selectedDestination']);


        if (count($Accounts) > 0) {
            foreach ($Accounts as $Account) {
                if (strpos($Account, $extraArr['selectedSource']) !== false) {
                    $cityID = explode(':', $Account);
                    $fromCityid = $cityID[0];
                    $fromCityName = $cityID[1];
                }
                if (strpos($Account, $extraArr['selectedDestination']) !== false) {
                    $cityID = explode(':', $Account);
                    $toCityid = $cityID[0];
                    $toCityName = $cityID[1];
                }
            }
        }

        $extraArr['FROMCITYID'] = $fromCityid;
        $extraArr['TOCITYID'] = $toCityid;


        $url = $base_url . "/inventory?from_city=" . $fromCityid . "&to_city=" . $toCityid . "&outbound_date=" . $travelTime;
        flog($flogPath, "INVENTORY REQUEST : " . $url);
        $request = "";
        $result = quickBusRequestsGet($url, $request);
        $num = 1;

        $extraArr['OUTBOUNDRESULT'] = $result;
        flog($flogPath, "INVENTORY RESULT : " . $result);
        $data = json_decode($result, true);
        $pos = strpos($result, "dep_time_gmt");
        if ($pos !== false) {
            $message = "";
            $date = new DateTime($travelTime);
            $travelTime = strtoupper($date->format('j M'));
            //$message .= "Choose " . $travelTime . "\n\n";
            $message .= "Choose:" . "\n";
            $travel_name = array();
            $travel_id = array();
            $journey_time = array();

            foreach ($data['outbound'] as $item) {
                $travel_name[] = $item['name'];
                $travel_id[] = $item['id'];
                $travel_sno[] = $item['sno'];
                $journey_time[] = $item['dep_time_gmt'];

                $message .= $num . " " . $item['short_name'] . " " . $item['dep_time_gmt'] . " " . $item['user_currency'] . " " . $item['user_fare'] . PHP_EOL;
                $num++;
            }

            $message .= PHP_EOL . "0. Back";
            if (strlen($message) > 141) {

                $first160 = substr($message, 0, 141);
                $second160 = substr($message, 141);


                $extraArr['SECOND160'] = $second160;
                $extraArr['TRAVELNAMES'] = implode(",", $travel_name);
                $extraArr['TRAVELID'] = implode(",", $travel_id);
                $extraArr['TRAVELSNO'] = implode(",", $travel_sno);


                $extraArr['PREVMSG'] = $first160 . PHP_EOL . $message . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 105;
            } else {

                $extraArr['TRAVELNAMES'] = implode(",", $travel_name);
                $extraArr['TRAVELID'] = implode(",", $travel_id);
                $extraArr['TRAVELSNO'] = implode(",", $travel_sno);

                $extraArr['PREVMSG'] = $first160 . PHP_EOL . $message . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 106;
            }
        } else {
            $extraArr['PREVMSG'] = "There are no buses scheduled for the requested journey" . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 104;
        }
    } catch (\Throwable $e) {
        $subject = 'GET TRAVEL TIME ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        flog($flogPath, "travelTime EXCEPTION:" . $e->getMessage());

        $extraArr['PREVMSG'] = 'There was a problem processing your request, Please try again later' . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 104;
    }
}

function backdatetime($input) {
    $date = new DateTime('+' . $input - 1 . ' day');
    $date->format('Ymd');
    $travelTime = $date->format('Ymd');
    return $travelTime;
}

function travelTimeExtra160($input) {//105
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        if ($input !== "98") {
            $result = $extraArr['OUTBOUNDRESULT'];
            $data = json_decode($result, true);
            $outboundnames = explode(',', $extraArr['TRAVELSNO']);
            $index = $input - 1;
            $outboundname = $outboundnames[$index];
            $message = "";
            $board = 0;
            $dropp = 0;
            flog($flogPath, "Boarding Points Results : " . $outboundname);
            //flog($flogPath,  "1 OUT BOUND SERIAL NUMBER : " . $outboundname);
            foreach ($data['outbound'] as $item) {
                if ($item['sno'] == $outboundname) {
                    $extraArr['SHORTNAME'] = $item['short_name'];
                    $extraArr['OP_ID'] = $item['op_id'];
                    $extraArr['IDREQUIRED'] = $item['nationalId_required'];
                    $extraArr['DOMESTICROUTE'] = $item['domestic_route'];
                    foreach ($item['boarding_points'] as $boardingPoint) {
                        if ($boardingPoint['type'] == "board") {
                            if ($board < 1) {
                                $message .= $item['name'] . PHP_EOL;
                                $message .= "KES " . $item['user_fare'] . PHP_EOL;

                                $message .= $boardingPoint['city_name'] . " ->" . $boardingPoint['pickup_point'] . ' ' . $boardingPoint['time'] . PHP_EOL;
                                $board ++;
                            }
                        } else if ($boardingPoint['type'] == "drop") {
                            if ($dropp < 1) {
                                $message .= $boardingPoint['city_name'] . " ->" . $boardingPoint['pickup_point'] . ' ' . $boardingPoint['time'] . PHP_EOL;
                                $dropp ++;
                            }
                        }
                    }
                    $message .= 'Avail seats: ' . $item['available_seats'] . '/' . $item['total_seats'] . PHP_EOL;
                }
            }
            $message .= "1.Confirm" . PHP_EOL;
            $message .= "2.T&Cs" . PHP_EOL;
            $message .= "0. Back";

            $extraArr['PREVMSG'] = $message . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 107;
        } else {
            $extraArr['PREVMSG'] = $extraArr['SECOND160'] . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 106;
        }
    } catch (\Throwable $e) {
        $subject = 'BOARDING POINTS ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        flog($flogPath, "boardingPoints EXCEPTION EXTRA:" . $e->getMessage());
        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function boardingPoints($input) {//106
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $option = $input;
        $optionNum = (int) $option;
        $data = json_decode($extraArr['OUTBOUNDRESULT'], true);
        flog($flogPath, "OUTBOUNDRESULT1" . $extraArr['OUTBOUNDRESULT']);
        $index = $input - 1; //  boardingPoints
        $outboundnames = explode(',', $extraArr['TRAVELSNO']);
        $outboundname = $outboundnames[$index];
        $message = "";
        foreach ($data['outbound'] as $item) {
            if ($item['sno'] == $outboundname) {
                flog($flogPath, "SNO Found" . $item['sno']);
                $extraArr['OP_ID'] = $item['op_id'];
                $extraArr['IDREQUIRED'] = $item['nationalId_required'];
                $extraArr['DOMESTICROUTE'] = $item['domestic_route'];
                $extraArr['SHORTNAME'] = $item['short_name'];
                foreach ($item['boarding_points'] as $boardingPoint) {
                    if ($boardingPoint['type'] == "board") {
                        $message .= $item['name'] . PHP_EOL;
                        $message .= $item['user_fare_formatted'] . PHP_EOL;
                        $message .= $boardingPoint['city_name'] . " ->" . $boardingPoint['pickup_point'] . ' ' . $boardingPoint['time'] . PHP_EOL;
                    } else if ($boardingPoint['type'] == "drop") {
                        $message .= $boardingPoint['city_name'] . " ->" . $boardingPoint['pickup_point'] . ' ' . $boardingPoint['time'] . PHP_EOL;
                    }
                }
                $message .= 'Avail seats: ' . $item['available_seats'] . '/' . $item['total_seats'] . PHP_EOL;
            }
        }
        $message .= "1.Confirm" . PHP_EOL;
        $message .= "2.T&Cs" . PHP_EOL;
        $message .= "0. Back";
        flog($flogPath, "boarding_points Found " . $message);
        $extraArr['PREVMSG'] = $message . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 107;
    } catch (\Throwable $e) {
        $subject = 'BOARDING POINTS ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        flog($flogPath, "boardingPoints EXCEPTION:" . $e->getMessage());

        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function promptPassNumber($input) {//107
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        if ($input == 2) {
            $url = $base_url . "/inventory/operator-info";
            $request = "{
                            \"operatorId\":\"" . $extraArr['OP_ID'] . "\"
                            }";

            flog($flogPath, "operator-info : " . $url);
            flog($flogPath, "operator-info : " . $request);
            //$url = 'https://api2.quickbus.com/v1/flexi/reservation';
            $results = quickBusRequestsPost($url, $request);
            flog($flogPath, "Referral : " . $results);
            $data = json_decode($results, true);
            $us_text = $data['us_text'];


            $extraArr['PREVMSG'] = str_replace('</p>', '', str_replace('<p>', '', $us_text)) . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 108;
        } else {
            $extraArr['PREVMSG'] = 'How many passengers are traveling? (3max)' . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 108;
        }
    } catch (\Throwable $e) {
        $subject = 'PROMPT PASSENGER NUMBER ERROR';
        $message = $e->getMessage();
        flog($flogPath, "promptPassNumber : " . $message);
        email($subject, $message);


        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function checkPassengerNumber($input) { //108
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {

        $numericValue = is_numeric($input);
        $extraArr['INITIALCOUNT'] = $input;

        if ($numericValue) {
            switch ($input) {
                case 3:
                    //proceed to ask for 3 passengers
                    $extraArr['PREVMSG'] = "Main passenger details:\n\nPlease enter full name including title" . PHP_EOL . "e.g. Mr Joe Kimani\n\n000 Exit.\n" . BACKKEY;
                    $message = $extraArr['PREVMSG'];
                    $response['nextLevel'] = 109;
                    break;
                case 2:
                    //proceed to ask for 2 passengers
                    $extraArr['PREVMSG'] = "Main passenger details:\n\nPlease enter full name including title" . PHP_EOL . "e.g. Mr Joe Kimani\n\n000 Exit.\n" . BACKKEY;
                    $message = $extraArr['PREVMSG'];
                    $response['nextLevel'] = 110;
                    break;
                case 1:
                    //proceed to ask for 2 passengers
                    $extraArr['PREVMSG'] = "Main passenger details:\n\nPlease enter full name including title" . PHP_EOL . "e.g. Mr Joe Kimani\n\n000 Exit.\n" . BACKKEY;
                    $message = $extraArr['PREVMSG'];
                    $response['nextLevel'] = 111;
                    break;
                default:
            }
        } else {
            $extraArr['PREVMSG'] = "Enter accurate number less than 3\n\n0 Back\n000 Exit.\n" . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 108;
        }
    } catch (\Throwable $e) {
        $subject = 'PROMPT PASSENGER NUMBER ERROR';
        $message = $e->getMessage();
        flog($flogPath, "promptPassNumber : " . $message);
        email($subject, $message);

        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function secondPassenger($input) {//109
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        //proceed to ask for 2 passengers
        $extraArr['PASSENGERS'] = str_replace('mxmx', ' ', $input);
        $extraArr['PREVMSG'] = "Next passenger details:\n\nPlease enter full name including title" . PHP_EOL . "e.g. Mr Joe Kimani\n\n000 Exit.\n" . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 110;
    } catch (\Throwable $e) {
        $subject = 'PROMPT PASSENGER NUMBER ERROR';
        $message = $e->getMessage();
        flog($flogPath, "promptPassNumber : " . $message);
        email($subject, $message);


        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function firstPassenger() { //110
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {

        $extraArr['PASSENGERS'] = str_replace('mxmx', ' ', $input);
        $extraArr['PREVMSG'] = "Next passenger details:\n\nPlease enter full name including title" . PHP_EOL . "e.g. Mr Joe Kimani\n\n000 Exit.\n" . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 111;
    } catch (\Throwable $e) {
        $subject = 'PROMPT PASSENGER NUMBER ERROR';
        $message = $e->getMessage();
        flog($flogPath, "promptPassNumber : " . $message);
        email($subject, $message);


        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function checkIDOrEmergency($input) { //111
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $extraArr['firstPassenger'] = $input;
    try {
        switch ([$extraArr['IDREQUIRED'], $extraArr['DOMESTICROUTE']]) {
            case ['', 1]:
                //ask for id number
                $extraArr['PREVMSG'] = 'Enter ID Number' . PHP_EOL . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 112;
                break;
            case [1, '']:
                //ask for emergency contact
                $extraArr['PREVMSG'] = 'For safety and legal reasons, please enter an emergency contact number' . PHP_EOL . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 112;
                break;
            case [1, 1]:
                //ask for emergency contact
                $extraArr['PREVMSG'] = "Enter ID Number and emergency contact\n\n0 Back\n000 Exit.\n" . PHP_EOL . BACKKEY;
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 112;
                break;
            default:
        }
    } catch (\Throwable $e) {
        $subject = 'PROMPT PASSENGER NUMBER ERROR';
        $message = $e->getMessage();
        flog($flogPath, "promptPassNumber : " . $message);
        email($subject, $message);

        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function passengerConfirmation($input) { //112
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $extraArr['PASSENGERS' . ':' . 1] = str_replace('mxmx', ' ', $extraArr['firstPassenger']);
    if ($extraArr['INITIALCOUNT'] == 1) {
        $extraArr['PASSENGERS' . ':' . 1] = str_replace('mxmx', ' ', $extraArr['checkPassengerNumber']);
    }
    if ($extraArr['INITIALCOUNT'] == 2) {
        $extraArr['PASSENGERS' . ':' . 2] = str_replace('mxmx', ' ', $extraArr['checkPassengerNumber']);
    }
    try {
        $short_name = $extraArr['SHORTNAME'];
        $gender = "";
        $message = "Passengers:" . PHP_EOL;
        $message .= $extraArr['INITIALCOUNT'] . " Std Tickets-";
        $message .= $short_name . ";" . PHP_EOL . PHP_EOL;
        $num = 1;
        for ($i = 1; $i <= $extraArr['INITIALCOUNT']; $i++) {
            $passengers = $extraArr['PASSENGERS' . ':' . $i];
            if ($passengers !== "") {
                $pass = explode(',', $passengers);
                $totalSum = count($pass);
                $gender = "";
                $name = "";
                $idNumber = "";
                $emergencyContact = "";
                switch ($totalSum) {
                    case 1:
                        $name = $pass[0];
                        $gender = "";
                        $idNumber = "";
                        break;
                    case 2:
                        $name = $pass[0];
                        //$gender = (strtolower($pass[1]) !== "f" || strtolower($pass[1]) !== "m" ? $gender = "M" : $gender = $pass[1]);
                        $gender = "";
                        $idNumber = $pass[1];
                        break;
                    default:
                        $name = $pass[0];
                        //$gender = (strtolower($pass[1]) !== "f" || strtolower($pass[1]) !== "m" ? $gender = "M" : $gender = $pass[1]);
                        $idNumber = $pass[1];
                        $emergencyContact = $pass[2];
                        break;
                }
                // $message .= $name . "," . $gender . "," . $idNumber . PHP_EOL;
                $message .= $name . PHP_EOL;
                $num ++;
            }
        }

        $extraArr['PREVMSG'] = $message . PHP_EOL . "1. Continue" . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 113;
    } catch (\Throwable $e) {
        $subject = 'PASSENGER CONFIRMATION ERROR';
        $message = $e->getMessage();
        flog($flogPath, "PASSENGER CONFIRMATION ERROR : " . $message);
        email($subject, $message);
        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function seats($input) { //113
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        $coupon_code = $extraArr['seats'];
        try {
            $passengerString = "[";
            $data = json_decode($extraArr['OUTBOUNDRESULT'], true);
            $outbounddate = $extraArr['travelConfirmation'];

            flog($flogPath, "outbounddate" . $outbounddate);

            if ($extraArr['travelConfirmation'] == "1") {
                $outbounddate = date("Ymd");
            } else if ($extraArr['travelConfirmation'] > 1 && $extraArr['travelConfirmation'] < 98) {
                $outbounddate = backdatetime($extraArr['travelConfirmation']);
            } else {
                flog($flogPath, "travelConfirmation Date: " . $extraArr['travelConfirmation']);
                $dateObject = DateTime::createFromFormat("dmY", $extraArr['travelConfirmation']);
                $outbounddate = $dateObject->format('Ymd');
            }

            $noofjourneys = $extraArr['No_of_journeys'] == "" ? 0 : $extraArr['No_of_journeys'];
            $flexiOption = "";

            if ($extraArr['flexitravelConfirmation'] !== "") {
                $outboundname = explode(',', $extraArr['TRAVELNAMES'])[0];
                $date = new DateTime('+1 day');
                $date->format('Ymd');
                $outbounddate = $date->format('Ymd');
                $phone = substr($mobileNumber, 0, 1) === '+' ? $mobileNumber : '+' . $mobileNumber;
                $passengerString .= "{\"Name" . "\":\"\",\"Mobile" . "\":\"" . $phone . "\",\"AltMobile" . "\":\"" . $extraArr['promptPassNumber'] . "\",\"Email" . "\":\"\",";
                $passengerString .= "\"Gender" . "\":\"M\",\"Age" . "\":\"\",\"Identity_ID" . "\":\"PASS" . "\",\"Identity_Type" . "\":\"NONE\",\"Country_ID" . "\":\"KENYA\"},";

                $flexiOption = ",\"flexi_ticket_type\":{\"Ticket_type\":\"FlexSpecial100\",\"No_of_journeys\":" . $noofjourneys . "}";
            } else if ($extraArr['flexiValidateVoucher'] !== "") {
                $date = new DateTime('+1 day');
                $date->format('Ymd');
                $outbounddate = $date->format('Ymd');
                $outboundname = explode(',', $extraArr['TRAVELNAMES'])[$input - 1];
                $flexiOption = ",\"flexi_ticket_type\":{\"Ticket_type\":\"FlexSpecial100\",\"No_of_journeys\":" . 1 . "}";
            } else {
                $index = $input - 1;
                $outboundname = explode(',', $extraArr['TRAVELNAMES'])[$index];
            }

            $fromcityserialNo = "";
            $Travel_id = "";
            foreach ($data['outbound'] as $item) {
                if ($item['name'] == $outboundname) {
                    $fromcityserialNo = $item['sno'];
                    $extraArr['CURRENCY'] = $item['user_currency'];
                    $Travel_id = $item['id'];
                    $extraArr['domestic'] = $item['domestic_route'];
                    $extraArr['IDrequred'] = $item['nationalId_required'];
                }
            }
            $passengerArray = Array();

            flog($flogPath, "initial count : " . $extraArr['INITIALCOUNT']);
            flog($flogPath, "outbound_sno : " . urlencode($fromcityserialNo));
            $gender = "";

            $extraArr['PASSENGERS' . ':' . 1] = str_replace('mxmx', ' ', $extraArr['firstPassenger']);
            if ($extraArr['INITIALCOUNT'] == 1) {
                $extraArr['PASSENGERS' . ':' . 1] = str_replace('mxmx', ' ', $extraArr['checkPassengerNumber']);
            }
            if ($extraArr['INITIALCOUNT'] == 2) {
                $extraArr['PASSENGERS' . ':' . 2] = str_replace('mxmx', ' ', $extraArr['checkPassengerNumber']);
            }



            for ($i = 1; $i <= $extraArr['INITIALCOUNT']; $i++) {
                $passengers = $extraArr['PASSENGERS' . ':' . $i];
                flog($flogPath, "available passengers : " . $passengers);
                if ($passengers !== "") {
                    $pass = explode(',', $passengers);
                    $totalSum = count($pass);
                    $gender = "";
                    $name = "";
                    $idNumber = "";

                    switch ($totalSum) {
                        case 1:
                            $name = $pass[0];
                            $gender = "M";
                            $idNumber = "NONE";
                            break;
                        case 2:
                            $name = $pass[0];
                            //$gender = (strtolower($pass[1]) !== "f" || strtolower($pass[1]) !== "m" ? $gender = "M" : $gender = $pass[1]);
                            $gender = "M";
                            $idNumber = $pass[1];
                            break;
                        default:
                            $name = $pass[0];
                            //$gender = (strtolower($pass[1]) !== "f" || strtolower($pass[1]) !== "m" ? $gender = "M" : $gender = $pass[1]);
                            $gender = "M";
                            $idNumber = $pass[1];
                            $emergencyContact = $pass[2];
                            break;
                    }

                    $phone = substr($mobileNumber, 0, 1) === '+' ? $mobileNumber : '+' . $mobileNumber;
                    $passengerString .= "{\"Name" . "\":\"" . $name . "\",\"Mobile" . "\":\"" . $phone . "\",\"AltMobile" . "\":\"" . $extraArr['promptPassNumber'] . "\",\"Email" . "\":\"\",";
                    $passengerString .= "\"Gender" . "\":\"" . strtoupper($gender) . "\",\"Age" . "\":\"\",\"Identity_ID" . "\":\"PASS" . "\",\"Identity_Type" . "\":\"" . $idNumber . "\",\"Country_ID" . "\":\"KENYA\"},";
                    $passengerArray[] = $name . ":" . $gender . ":" . $idNumber;
                }
            }
            $passengerString .= "]";

            $extraArr['PASSENGERARRAY'] = implode(",", $passengerArray);
            $request = "{\"from_city\":\"" . urlencode($extraArr['FROMCITYID']) . "\",\"to_city\":\"" . urlencode($extraArr['TOCITYID']) . "\",\"outbound_date\":\"" . urlencode($outbounddate) . "\",\"outbound_sno\":\"" . urlencode($fromcityserialNo) . "\",\"outbound_travel_id\":" . intval(urlencode($Travel_id)) . ",\"coupon_code\":\"" . $coupon_code . "\"" . $flexiOption . ",\"PassengersInfo\":" . $passengerString . "}";
            $request = str_replace("},]}", "}]}", $request);
            flog($flogPath, "SEATS REQUEST2 : " . $request);
            //$url = 'https://api2.quickbus.com/v1/inventory/confirm-seats';
            $url = $base_url . '/inventory/confirm-seats';
            $results = quickBusRequestsPost($url, $request);
            $data2 = json_decode($results, true);
            flog($flogPath, "SEATS RESPONSE : " . $results);
            $message = "";
            if (strpos($results, "ref") === false) {
                $message = $data2['message'] . " please call Helpline +254 716-292929 for further assistance" . PHP_EOL . PHP_EOL;
                $message .= "0. Back";
                $response = (object) array('id' => 'seatsValidateVoucher', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'seatsValidateVoucher')), 'type' => 'form');
                return $response;
            } else {
                $extraArr['REFFERENCE'] = $data2['ref'];
                if (strpos($results, "reservation_amount") === false) {

                    $payableAmount = $data2['details']['payable_amount'];
                    $totalFare = $data2['details']['total_amount'];
                    $discount = $data2['details']['discount'];
                    $varDiscount = ($discount !== 0 ? $varDiscount = "Discount:" . $discount . PHP_EOL : "");
                    $message .= "Total:" . $totalFare . PHP_EOL;
                    $message .= $varDiscount;
                    $message .= "Make Payment " . $extraArr['CURRENCY'] . " " . $payableAmount . " Select:" . PHP_EOL . PHP_EOL;

                    $extraArr['PAYABLEAMOUNT'] = $payableAmount;
                    $message .= "1. Pay from  " . $mobileNumber . PHP_EOL;
                    $message .= "2. Other Number." . PHP_EOL;
                    $extraArr['SEATSRESPONSE'] = $message;
                    $extraArr['PREVMSG'] = $message . PHP_EOL . "1. Continue" . BACKKEY;
                    $message = $extraArr['PREVMSG'];
                    $response['nextLevel'] = 114;
                } else {
                    $currency = $data2['details']['currency'];
                    $voucher_id = $data2['details']['voucher_id'];
                    $total_fare = $data2['details']['total_fare'];
                    $reservation_amount = $data2['details']['reservation_amount'];
                    $extraArr['CURRENCY'] = $currency;
                    $extraArr['PAYABLEAMOUNT'] = $reservation_amount;
                    $message .= $extraArr['No_of_journeys'] . "-Flexi Ticket" . ($extraArr['No_of_journeys'] > 1 ? "s" : "") . PHP_EOL;
                    $message .= "Locked Price " . $total_fare . PHP_EOL;
                    $message .= "Reservation Amount " . $currency . " " . $reservation_amount . PHP_EOL;
                    $message .= "Amount Due " . $currency . " " . $total_fare . PHP_EOL . PHP_EOL;
                    $message .= "Choose Payment:" . PHP_EOL;
                    $message .= "1. MPESA." . PHP_EOL;
                    $message .= "2. Airtel Money." . PHP_EOL;
                    $extraArr['SEATSRESPONSE'] = $message;
                    $extraArr['PREVMSG'] = $message . PHP_EOL . "1. Continue" . BACKKEY;
                    $message = $extraArr['PREVMSG'];
                    $response['nextLevel'] = 115;
                }
            }
        } catch (\Throwable $e) {
            flog($flogPath, "SEATS REQUEST3 : " . $e->getMessage());
            $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 71;
        }
    } catch (\Throwable $e) {
        flog($flogPath, "SEATS REQUEST4 : " . $e->getMessage());
        $subject = 'CONFIRM SEATS ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function payphone($input) { //114
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        if ($extraArr['NoOfTicketsFlexi'] !== "") {
            $extraArr['PREVMSG'] = "Please select phone number to pay from:" . PHP_EOL . PHP_EOL . PHP_EOL . '1: Pay from ' . $mobile . '?' . PHP_EOL . '2: Other Number' . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 114;
        }
        if ($input === "2") {
            $extraArr['PREVMSG'] = "Please enter the phone number to pay from in the format (254*********)" . PHP_EOL . BACKKEY;
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 116;
        } else {
            $extraArr['PREVMSG'] = "Pay from " . $mobileNumber . "?" . PHP_EOL . PHP_EOL . "1: Accept" . PHP_EOL . "000:Cancel";
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 117;
        }
    } catch (\Throwable $e) {
        $subject = 'PAY PHONE ERROR';
        $message = $e->getMessage();
        email($subject, $message);
        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function otherphonepaymentconfirmation($input) { //116{
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    $extraArr['SELECTEDPHONE'] = $input;
    $extraArr['PHONEOPTION'] = 2;


    $extraArr['PREVMSG'] = "Pay from " . $input . "?" . PHP_EOL . PHP_EOL . "1: Accept" . PHP_EOL . "000:Cancel";
    $message = $extraArr['PREVMSG'];
    $response['nextLevel'] = 117;
}

function payment($input) { //117
    global $extraArr, $flogPath, $message, $response, $mobileNumber, $base_url, $input, $CITIESJSON, $CITIES, $sessionID;
    try {
        if ($extraArr['PHONEOPTION'] == 2) {
            $phone = $extraArr['SELECTEDPHONE'];
        } else {
            $phone = $mobileNumber;
        }
        $phone = "";
        $request = "";
        $phone = substr($phone, 0, 1) === '+' ? $phone : '+' . $phone;
        $request = "{\"ref\":\"" . $extraArr['REFFERENCE'] . "\",\"mobile\":\"" . $phone . "\",\"paymentgateway\":\"mpesa\"}";
        flog($flogPath, "PAYMENT REQUEST : " . $request);
        $url = $base_url . '/payment/init';
        $results = quickBusRequestsPost($url, $request);
        flog($flogPath, "PAYMENT RESPONSE : " . $results);
        $data = json_decode($results, true);
        if ($input == "1") {
            if (strpos($results, "ref") !== false) {
                $extraArr['PREVMSG'] = 'Pay by MPESA popup notification or' . PHP_EOL . 'Paybill:833335' . PHP_EOL . 'Account ' . $data["payload"]["reference"] . PHP_EOL . $extraArr['CURRENCY'] . ' ' . $extraArr['PAYABLEAMOUNT'] . PHP_EOL . 'You have 20 mins to pay, Invoice also sent by SMS to your phone' . PHP_EOL . 'Help call: +254716292929';
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 117;
            } else {
                $extraArr['PREVMSG'] = $data['response'] . PHP_EOL . PHP_EOL . '000. Exit';
                $message = $extraArr['PREVMSG'];
                $response['nextLevel'] = 117;
            }
        } else if ($extraArr['payphone'] == "2") {
            $extraArr['PREVMSG'] = 'Airtel Money is not currently supported, to complete this booking, please call +254716292929 and quote ref ' . $extraArr['REFFERENCE'] . PHP_EOL . PHP_EOL . '000. Exit';
            $message = $extraArr['PREVMSG'];
            $response['nextLevel'] = 117;
        }
    } catch (\Throwable $e) {
        $subject = 'PAYMENTS ERROR';
        $message = $e->getMessage();
        email($subject, $message);


        $extraArr['PREVMSG'] = "There was a problem processing your request, Please try again later" . PHP_EOL . BACKKEY;
        $message = $extraArr['PREVMSG'];
        $response['nextLevel'] = 71;
    }
}

function fetchMenuItems() {
    global $flogPath, $extraArr;
    flog($flogPath, __FUNCTION__ . "() About to fetch menu items ");
    $conn = connectToDb();
    try {
        $query = "SELECT id,name,root,displayCode FROM ussdMenus where disabled = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $extraArr['MENUITEMS'] = $rows;
        return $rows;
    } catch (Exception $ex) {
        flog($flogPath, "Exception " . $ex->getMessage());
    }
}

function updateAccountOTP($msisdn, $status) {
    global $flogPath;
    $conn = connectToDb();

    try {
        $query = "UPDATE jubileeProfiles SET status = '$status' where msisdn = '$msisdn'";
        $conn->exec($query);
        flog($flogPath, "query " . $query);
    } catch (Exception $ex) {
        flog($flogPath, "Exception " . $ex->getMessage());
    }
}

function connectToDb() {
    global $flogPath;
    try {
        $conn = new PDO("mysql:host=" . DBHOST . ";dbname=" . DATABASENAME . "", DBUSERNAME, DBPASSWORD);
// set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//flog($flogPath, "Connected successfully");
        return $conn;
    } catch (PDOException $e) {
        flog($flogPath, "Connection failed: " . $e->getMessage());
    }
}

function encrypt_decrypt($action, $string) {
    $output = false;

    $encrypt_method = "AES-256-CBC";
    $secret_key = 'Stanbic south sudan secret key';
    $secret_iv = 'Stanbic south sudan secret iv';

    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'encrypt') {
        $output_ = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output_);
    } else {
        if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
    }
    return $output;
}

$extra = json_encode($extraArr);
$response['extra'] = $extra;
$response['ussdMessage'] = $message;
$result = json_encode($response);
$SesionResponseDatetime = date('Y-m-d H:i:s');
flog($flogPath, " --Response to the Navigator ::  " . $mobileNumber . " --- " . $response['ussdMessage'] . "   citiEs available " . $CITIES);
flog($flogPath, "-----------------------Response Time::" . $SesionResponseDatetime . " || Phone :: " . $mobileNumber . "--------------------");
output($result);
