<?php

include "configs.php";

session_start();



function writeToFile($level, $message, $fileName = NULL) {

    global $mobileNumber;

    date_default_timezone_set('Africa/Nairobi');
    $return = false;
    global $defaultLogPath;
    if ($fileName and strlen(trim($fileName)) > 3) {
        $file = trim($fileName);
    } else {
        switch ($level) {
            case 0;
                $file = date('dmY') . "_INFO.log";
                break;
            case 1;
                $file = date('dmY') . "_INFO.log";
                break;

            default:
                $file = $level;
        }
    }
    $date = date("Y-M-d-H:i:s");
    $filename = $defaultLogPath . $file;
    $script = $_SERVER["PHP_SELF"];

    /*
      newaddition 03/10/19 */
    $e = new Exception();
    $trace = $e->getTrace();
//position 0 would be the line that called this function so we ignore it
    $last_call = isset($trace[1]) ? $trace[1] : array();
    $lineArr = $trace[0];


    $function = isset($last_call['function']) ? $last_call['function'] . "()|" : "";
    $line = isset($lineArr['line']) ? $lineArr['line'] . "|" : "";
    $file = isset($lineArr['file']) ? $lineArr['file'] . "|" : "";

    /* end of new addition */
    $data = $date . " | $file$function$line |--- $mobileNumber --- " . $message . "\n";
    $fileWrite = file_put_contents($filename, $data, FILE_APPEND);
    /*  if ($fileWrite) {
      $return = true;
      } */
    return $return;
}

function generateSessionID() {
    $sessionID = random_int(1000, 9999);
    return $sessionID;
}

function getSessionInfo($dbConnect, $mobileNumbervalue, $sessionIDvalue, $ussdCodevalue = 'not_set', $imsivalue = "", $networkvalue = "") {
    $responseTime = 300; /* in seconds */

    $mobileNumber = filter_var(trim($mobileNumbervalue), FILTER_SANITIZE_STRING);
    $sessionID = filter_var(trim($sessionIDvalue), FILTER_SANITIZE_STRING);
    $ussdCode = filter_var(trim($ussdCodevalue), FILTER_SANITIZE_STRING);
    $imsi = filter_var(trim($imsivalue), FILTER_SANITIZE_STRING);
    $network = filter_var(trim($networkvalue), FILTER_SANITIZE_STRING);

    $response = array(
        "status" => null,
        "sessionID" => NULL,
        "state" => null,
        "url" => null,
        "sessionActivity" => null,
    );

    $sql = "select sessionID,url,sessionActivity from sessions where sessionID=:sessionID and  mobileNumber=:mobileNumber and status=0 order by sessionID desc limit 1";
    try {
        $STH = $dbConnect->prepare($sql);

        $values = [
            'sessionID' => $sessionID,
            'mobileNumber' => $mobileNumber,
        ];
        $STH->execute($values);
# setting the fetch mode
        $STH->setFetchMode(PDO::FETCH_OBJ);
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage());
        return false;
    }

    if ($STH->rowCount() > 0) {
        $row = $STH->fetch();

        $response['sessionID'] = $row->sessionID;
        $response['url'] = $row->url;
        $response['sessionActivity'] = $row->sessionActivity;
//update the time accessed
        $sql = "update sessions set dateModified=now() where sessionID=:sessionID";
        try {
            $STH = $dbConnect->prepare($sql);

            $values = [
                'sessionID' => $response['sessionID']
            ];
            $STH->execute($values);
        } catch (Exception $ex) {
            
        }
        $response['state'] = "EXISTS";
        $response['status'] = true;
    } else { //create the session
//  $response['sessionID'] = generateSessionID();
        if (strlen($sessionID) > 3) {
            $response['sessionID'] = $sessionID;
        } else {
            $response['sessionID'] = generateSessionID();
        }
        $response['state'] = "NEW";
        $response['status'] = true;
//save the sessionID
        $sql = "insert into sessions (sessionID, mobileNumber, dateCreated,dateModified,ussdCode,imsi,network) values (:sessionID, :mobileNumber, now(),now(),:ussdCode,:imsi,:network)";
        try {
            $STH = $dbConnect->prepare($sql);

            $values = [
                'sessionID' => $response['sessionID'],
                'mobileNumber' => $mobileNumber,
                'ussdCode' => $ussdCode,
                'imsi' => $imsi,
                'network' => $network,
            ];
            $STH->execute($values);
        } catch (Exception $ex) {
            writeToFile(1, "(" . __FUNCTION__ . ")" . " --- didnt get a result::" . $ex->getMessage());
            $response['status'] = false;
        }
    }
    writeToFile(1, "(" . __FUNCTION__ . ")" . " the reponse " . serialize($response));
    return $response;
}

function getUssdUrl($DBH, $ussdCode) {

    $sql = "select url from shortCodeSettings where shortCode like :ussdCode limit 1";

    try {
        $STH = $DBH->prepare($sql);
        $values = [
            'ussdCode' => $ussdCode
        ];
        $STH->execute($values);
    } catch (Exception $ex) {
        writeToFile(0, "Exception ::" . $ex->getMessage());
        return false;
    }
    $STH->setFetchMode(PDO::FETCH_OBJ);

    if ($STH->rowCount() > 0) {
        $row = $STH->fetch();

        $urls = $row->url;
        $url = filter_var($urls, FILTER_SANITIZE_STRING);

        return $url;
    }
    return false;
}

function saveNextLevel($mobileNumbervalue, $nextLevelvalue, $sessionIDvalue, $extravalue) { //first get whatever is in the nextlevel, set it to the current Level
    global $db_connect;
    $currentLevelString = "";

    $mobileNumber = filter_var(trim($mobileNumbervalue), FILTER_SANITIZE_STRING);
    $sessionID = filter_var(trim($sessionIDvalue), FILTER_SANITIZE_STRING);
    $nextLevel = filter_var(trim($nextLevelvalue), FILTER_SANITIZE_STRING);
    // $extra = filter_var(trim($extravalue), FILTER_SANITIZE_STRING);
    $extra = trim($extravalue);

    $sql = "select nextLevel from sessionnav where mobileNumber=:mobileNumber and sessionID=:sessionID";

    try {
        $STH = $db_connect->prepare($sql);

        $values = [
            'mobileNumber' => $mobileNumber,
            'sessionID' => $sessionID,
        ];
        $STH->execute($values);
# setting the fetch mode
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage());
        return;
    }
    $STH->setFetchMode(PDO::FETCH_OBJ);
    if ($STH->rowCount() > 0) {
        $row = $STH->fetch();
        $currentLevel = $row->nextLevel;

        $values = array(
            'nextLevel' => $nextLevel,
            'mobileNumber' => $mobileNumber,
            'sessionID' => $sessionID,
        );
        if (trim($currentLevel) != "") {
            $currentLevelString = " , currentLevel=:currentLevel";
            $values['currentLevel'] = $currentLevel;
        }
    }

    if (trim($extra) == "") {
        $sql = "update sessionnav set nextLevel=:nextLevel $currentLevelString where mobileNumber=:mobileNumber and sessionID=:sessionID";
    } else {
        $sql = "update sessionnav set nextLevel=:nextLevel $currentLevelString, extra=:extra where mobileNumber=:mobileNumber and sessionID=:sessionID";
        $values['extra'] = $extra;
    }
    try {
        $STH = $db_connect->prepare($sql);

        $STH->execute($values);
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage() . "::Query::" . $sql . "::values::" . serialize($values));
        return false;
    }


    return true;
}

function getNextUssdLevel($sessionIDvalue, $mobileNumbervalue) {
    global $db_connect;

    $mobileNumber = filter_var(trim($mobileNumbervalue), FILTER_SANITIZE_STRING);
    $sessionID = filter_var(trim($sessionIDvalue), FILTER_SANITIZE_STRING);

    $sql = "select nextLevel,extra from sessionnav where mobileNumber=:mobileNumber and sessionID=:sessionID limit 1";

    try {
        $STH = $db_connect->prepare($sql);

        $values = [
            'mobileNumber' => $mobileNumber,
            'sessionID' => $sessionID,
        ];
        $STH->execute($values);
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage());
        return false;
    }

    $STH->setFetchMode(PDO::FETCH_OBJ);
    if ($STH->rowCount() > 0) {
        $row = $STH->fetch();
        $nextLevel = $row->nextLevel;
        $extra = $row->extra;
        $return = array(
            'nextLevel' => $nextLevel,
            'extra' => $extra
        );
//return $nextLevel;
        return $return;
    }
    return false;
}

function postPayload($url, $payload) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    if (!$result) { //some error occured
        writeToFile(0, "(" . __FUNCTION__ . ")" . "error when posting the payload to ussd script '$url' error " . curl_error($ch) . " the erro " . serialize(curl_error($ch)));
    }
    curl_close($ch);
    return $result;
}

function createSessionNav($mobileNumbervalue, $sessionIDvalue, $ussdLevelvalue) {
    global $db_connect;

    $mobileNumber = filter_var(trim($mobileNumbervalue), FILTER_SANITIZE_STRING);
    $sessionID = filter_var(trim($sessionIDvalue), FILTER_SANITIZE_STRING);
    $ussdLevel = filter_var(trim($ussdLevelvalue), FILTER_SANITIZE_STRING);

    $sql = "insert into sessionnav (sessionID, mobileNumber, currentLevel, nextLevel, dateCreated, dateModified) values(:sessionID, :mobileNumber, :ussdLevel, 0, now(), now())";
    try {
        $STH = $db_connect->prepare($sql);

        $values = [
            'sessionID' => $sessionID,
            'mobileNumber' => $mobileNumber,
            'ussdLevel' => $ussdLevel,
        ];
        $STH->execute($values);
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage());
        return false;
    }
    return true;
}

function closeSession($sessionIDvalue, $mobileNumbervalue) {

    global $db_connect;

    $mobileNumber = filter_var(trim($mobileNumbervalue), FILTER_SANITIZE_STRING);
    $sessionID = filter_var(trim($sessionIDvalue), FILTER_SANITIZE_STRING);

    $sql = "update sessions set status=1 where sessionID=:sessionID and mobileNumber=:mobileNumber and status=0";
    try {
        $STH = $db_connect->prepare($sql);

        $values = [
            'sessionID' => $sessionID,
            'mobileNumber' => $mobileNumber,
        ];
        $STH->execute($values);
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage());
        return false;
    }
    return true;
}

function output($string) {
//  writeToFile(22,"the string to echo $string");
    echo $string;
}

function logSessionHop() {
    
}

function mobileNumberValidator($MSISDN) {

    writeToFile(1, "(" . __FUNCTION__ . ")" . " Mobile Number validator- $MSISDN -- length " . strlen($MSISDN) . " prefix " . substr($MSISDN, 0, 3));
    if (is_numeric($MSISDN) and ( strlen($MSISDN) == 10) and ( substr($MSISDN, 0, 3) == "077" or substr($MSISDN, 0, 3) == "020") or substr($MSISDN, 0, 5) == "25420" or substr($MSISDN, 0, 5) == "25472" or substr($MSISDN, 0, 5) == "25471" or substr($MSISDN, 0, 5) == "25470" or substr($MSISDN, 0, 5) == "25478" or substr($MSISDN, 0, 5) == "25473" or substr($MSISDN, 0, 3) == "072" or substr($MSISDN, 0, 3) == "071" or substr($MSISDN, 0, 3) == "070" or substr($MSISDN, 0, 3) == "078" or substr($MSISDN, 0, 3) == "073") {
        return trim($MSISDN);
    } else
        return false;
}

function flog($flogPath, $string) {
    global $mobileNumber;
    $e = new Exception();
    $trace = $e->getTrace();
//position 0 would be the line that called this function so we ignore it
    $last_call = isset($trace[1]) ? $trace[1] : array();
    $lineArr = $trace[0];


    $function = isset($last_call['function']) ? $last_call['function'] . "()|" : "";
    $line = isset($lineArr['line']) ? $lineArr['line'] . "|" : "";
    $file = isset($lineArr['file']) ? $lineArr['file'] . "|" : "";

    $date = date('Y-m-d H:i:s');
    $mnumber = strlen($mobileNumber) > 1 ? $mobileNumber . "|" : "";
    $string = $date . " |$mnumber$function$line" . $string . "\n";

    $file = file_put_contents($flogPath, $string, FILE_APPEND);
}

function updateSessionURL($sessionIDvalue, $mobileNumbervalue, $urlvalue) {
    global $db_connect;

    $mobileNumber = filter_var(trim($mobileNumbervalue), FILTER_SANITIZE_STRING);
    $sessionID = filter_var(trim($sessionIDvalue), FILTER_SANITIZE_STRING);
    $url = filter_var(trim($urlvalue), FILTER_SANITIZE_STRING);

    $sql = "update sessions set url=:url where sessionID=:sessionID and  mobileNumber=:mobileNumber limit 1";
    writeToFile(1, "(" . __FUNCTION__ . ") query == $sql");

    try {
        $STH = $db_connect->prepare($sql);

        $values = [
            'url' => $url,
            'sessionID' => $sessionID,
            'mobileNumber' => $mobileNumber,
        ];
        $STH->execute($values);
    } catch (Exception $ex) {
        writeToFile(0, "Exception::" . $ex->getMessage());
        return false;
    }


    return true;
}

function updateSessionDetails($sessionID, $currentLevel, $nextLevel, $ussdMessage, $currentSessionActivity, $userInput, $closeSession) {

    global $dbHost, $dbUser, $DBpassword, $ussdDataBaseName;
    try {
        $DBH = new PDO("mysql:host=$dbHost;dbname=$ussdDataBaseName", $dbUser, $DBpassword);
        $DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $newJsonActivity = json_decode($currentSessionActivity, true);
        if (!$newJsonActivity) {
//probably no data saved in the sessionActivity Column Yet
            $newJsonActivity = array();
        }
        $newJsonActivity[] = array("CL" => $currentLevel, 'NL' => $nextLevel, 'MSG' => $ussdMessage, 'INPUT' => $userInput, 'CLOSE' => $closeSession);

        $sql = "update sessions set sessionActivity=:sessionActivity where sessionID=:sessionID";

        $STH = $DBH->prepare($sql);
        $values = [
            'sessionActivity' => json_encode($newJsonActivity),
            'sessionID' => $sessionID
        ];
        $STH->execute($values);

        writeToFile(1, "sessionID '$sessionID' updated with sessionHop activity " . json_encode($newJsonActivity));
    } catch (Exception $ex) {
        writeToFile(1, "Exeption " . $ex->getMessage() . ":line:" . $ex->getLine());
    }
}

?>
