<?php

/* $dbHost = "10.235.6.138";
  $dbUser = "root";
  $DBpassword = "y9af4Qauh6fC8YD2G"; //y9af4Qauh6fC8YD2G
 */


$dbHost = "localhost:3307";
$dbUser = "root";
$DBpassword = "root"; //y9af4Qauh6fC8YD2G


$ussdDataBaseName = "sbss_mobilebanking";
$defaultLogPath = "C:/xampp/htdocs/QB2/QBtest/app/";
$defaultCode = "*544#";
$logLevel = 10;
$allowedAPiIPs = array('192.168.0.', '192.168.0.');


$default_error_message = '{"END":true,"USSDMESSAGE":"Sorry, we could not complete your request. Our esteemed engineers are working on this"}';
$ussd_code_not_exist = '{"END":true,"USSDMESSAGE":"Service not found"}';
//$sbss_base_url = "https://skedpg01.stanbicbank.co.ke:9045/mtechussd/app/ssbmobile/";
$sbss_base_url = "https://skedpg01.stanbicbank.co.ke:9045/app/ssbmobile/";
$sbss_sms_url = "https://ukeesb1.ke.sbicdirectory.com:7844/iib/stanbic/common/v1/sendsms";
$ip_not_allowed_error_message = '{"END":true,"USSDMESSAGE":"Sorry, we could not complete your request. Please contact the bank."}';
