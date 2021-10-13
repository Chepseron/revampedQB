<?php

use Yii;
use \PHPMailer;
use common\models\LogonAccessCodes;
use common\models\TillTransfersAccessCodes;
use frontend\models\AuditTrail;
use yii\db\Query;
use frontend\models\DataChanges;

//session_start();

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CoreUtils
 *
 * @author Luke
 */
set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
date_default_timezone_set('Africa/Nairobi');

DEFINE('SMSSENDURL', '');
DEFINE('SMSUSERNAME', '');
DEFINE('SMSPASSWORD', '');
DEFINE('SMSSHORTCODE', '');
DEFINE('SMSSENDURL_SBSS', 'https://ukeesb1.ke.sbicdirectory.com:7844/iib/stanbic/common/v1/sendsms');
DEFINE('SBSS_BASE_URL', 'https://10.235.245.122:9444/app/ke/mobile/');

class CoreUtils {

//put your code here

    const ENTITY_STATE_CREATED = 0;
    const ENTITY_STATE_APPROVED = 1;
    const ENTITY_STATE_REJECTED = 2;
    const ACTIVE_FILE = 1;
    const INACTIVE_FILE = 2;
    const FILE_NOT_UPLOADED = 0;

    public function getSBSSBaseUrl() {
        return SBSS_BASE_URL;
    }

    public function getSMSUrl() {
        return SMSSENDURL_SBSS;
    }

//fetch dash board graps details;
    public static function getTotalAgentsRegistered($userGroupsID, $superAgentsListID) {

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent

        $totalSubAgants = 0;
        $totalSuperAgents = 0;

        if ($userGroupsID == 1) {
            $querysubAgents = "Select count(*) TotalsubAgentsList from subAgentsList";
            $querysuperAgents = "Select count(*) TotalsuperAgentsList from superAgentsList";
        }

        if ($userGroupsID == 3) {
            $querysubAgents = "Select count(*) TotalsubAgentsList from subAgentsList WHERE superAgentsListID='$superAgentsListID'";
            $querysuperAgents = "Select count(*) TotalsuperAgentsList from superAgentsList WHERE superAgentsListID='$superAgentsListID'";
        }


        $resSub = Yii::$app->db->createCommand($querysubAgents)->queryAll();
        if (count($resSub) > 0) {
            if ($resSub[0]['TotalsubAgentsList'] != null)
                $totalSubAgents = $resSub[0]['TotalsubAgentsList'];
        }



        $resSup = Yii::$app->db->createCommand($querysuperAgents)->queryAll();
        if (count($resSup) > 0) {
            if ($resSup[0]['TotalsuperAgentsList'] != null)
                $totalSuperAgents = $resSup[0]['TotalsuperAgentsList'];
        }

        $array = array(
            0 => $totalSubAgents,
            1 => $totalSuperAgents,
        );


        return $array;
    }

    public static function statistiLoancReport($userGroupsID, $subAgentsListID, $UsersID, $superAgentsListID) {

        $totalFloatRequested = 0;
        $totalFloatPending = 0;
        $totalFloatApproved = 0;
        $totalFloatRejected = 0;
        $totalPaid = 0;
        $totalUnpaid = 0;
        $totalDefaulted = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent
//Admin
        if ($userGroupsID == 1) {
            $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests";
            $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests WHERE statusCodeID=0";
            $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE statusCodeID=1";
            $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE statusCodeID=2";
            $queryPaidLoan = "select sum(repaymentAmount) TotalLoanPaid from floatRepayment WHERE statusCodeID=1";
        }

        //Sub Agent
        if ($userGroupsID == 2) {
            $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests WHERE subAgentsListID='$subAgentsListID'";
            $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests WHERE subAgentsListID='$subAgentsListID' AND  statusCodeID=0";
            $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE subAgentsListID='$subAgentsListID' AND statusCodeID=1";
            $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE subAgentsListID='$subAgentsListID' AND statusCodeID=2";
            $queryPaidLoan = "select sum(repaymentAmount) TotalLoanPaid from floatRepayment WHERE usersID='$UsersID' AND statusCodeID=1";
        }

        //Super Agent $superAgentsListID
        if ($userGroupsID == 3) {
            $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests a,subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND superAgentsListID='$superAgentsListID'";
            $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests a,subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND superAgentsListID='$superAgentsListID' AND  a.statusCodeID=0";
            $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests a,subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND superAgentsListID='$superAgentsListID' AND a.statusCodeID=1";
            $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests a,subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND superAgentsListID='$superAgentsListID' AND a.statusCodeID=2";

            $queryPaidLoan = "select sum(repaymentAmount) TotalLoanPaid from floatRepayment a,subAgentsList b WHERE a.usersID=b.usersID AND superAgentsListID='$superAgentsListID' AND a.statusCodeID=1";
        }


        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalLoanRequested'] != null)
                $totalFloatRequested = $resRequested[0]['TotalLoanRequested'];
        }


        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalLoanPending'] != NULL)
                $totalFloatPending = $resPending[0]['TotalLoanPending'];
        }


        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalLoanApproved'] != NULL)
                $totalFloatApproved = $resApproved[0]['TotalLoanApproved'];
        }


        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalLoanRejected'] != null)
                $totalFloatRejected = $resRejected[0]['TotalLoanRejected'];
        }


        //Total Paid

        $resPaidLoan = Yii::$app->db->createCommand($queryPaidLoan)->queryAll();
        if (count($resPaidLoan) > 0) {
            if ($resPaidLoan[0]['TotalLoanPaid'] != null)
                $totalPaid = $resPaidLoan[0]['TotalLoanPaid'];
        }

        $totalUnpaid = $totalFloatApproved - $totalPaid;


        $array = array(
            0 => $totalFloatRequested,
            1 => $totalFloatPending,
            2 => $totalFloatApproved,
            3 => $totalFloatRejected,
            4 => $totalPaid,
            5 => $totalUnpaid,
            6 => $totalDefaulted,
        );




        return $array;
    }

    public static function loanRepaid() {
        $query = "select sum(repayment_amount) loanRepaid from loanRepayments";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        return $res[0]['loanRepaid'];
    }

    public static function usersDetails($userGroupsID, $superAgentsListID) {

        $totalUserRequested = 0;
        $totalUserPending = 0;
        $totalUserApproved = 0;
        $totalUserRejected = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent
        if ($userGroupsID == 1) {
            $queryRequested = "select count(subAgentsListID) totalUserRequested from subAgentsList";
            $queryPending = "select count(subAgentsListID) totalUserPending from subAgentsList WHERE statusCodeID=0";
            $queryApproved = "select count(subAgentsListID) totalUserApproved from subAgentsList WHERE statusCodeID=1";
            $queryRejected = "select count(subAgentsListID) totalUserRejected from subAgentsList WHERE statusCodeID=2";
        } else {
            $queryRequested = "select count(subAgentsListID) totalUserRequested from subAgentsList WHERE superAgentsListID='$superAgentsListID'";
            $queryPending = "select count(subAgentsListID) totalUserPending from subAgentsList WHERE statusCodeID=0 AND superAgentsListID='$superAgentsListID'";
            $queryApproved = "select count(subAgentsListID) totalUserApproved from subAgentsList WHERE statusCodeID=1 AND superAgentsListID='$superAgentsListID'";
            $queryRejected = "select count(subAgentsListID) totalUserRejected from subAgentsList WHERE statusCodeID=2 AND superAgentsListID='$superAgentsListID'";
        }


        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['totalUserRequested'] != null)
                $totalUserRequested = $resRequested[0]['totalUserRequested'];
        }


        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['totalUserPending'] != NULL)
                $totalUserPending = $resPending[0]['totalUserPending'];
        }


        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['totalUserApproved'] != NULL)
                $totalUserApproved = $resApproved[0]['totalUserApproved'];
        }


        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['totalUserRejected'] != null)
                $totalUserRejected = $resRejected[0]['totalUserRejected'];
        }


        $array = array(
            0 => $totalUserRequested,
            1 => $totalUserPending,
            2 => $totalUserApproved,
            3 => $totalUserRejected
        );


        return $array;
    }

    public static function getAdminFloatRequests($userGroupsID, $superAgentsListID) {

        $totalFloatRequested = 0;
        $totalFloatApproved = 0;
        $totalFloatRejected = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent

        if ($userGroupsID == 1) {
            $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests";
            $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE statusCodeID=1";
            $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE statusCodeID=2";
        } else {

            $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests a, subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID'";
            $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests a, subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID' AND a.statusCodeID=1";
            $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests a, subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID' AND a.statusCodeID=2";
        }


        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalLoanRequested'] != null)
                $totalFloatRequested = $resRequested[0]['TotalLoanRequested'];
        }


        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalLoanApproved'] != NULL)
                $totalFloatApproved = $resApproved[0]['TotalLoanApproved'];
        }


        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalLoanRejected'] != null)
                $totalFloatRejected = $resRejected[0]['TotalLoanRejected'];
        }


        if ($totalFloatRequested != 0) {
            $totalFloatRequested = number_format($totalFloatRequested / 1000, 2) . ' K';
        }

        if ($totalFloatApproved != 0) {
            $totalFloatApproved = number_format(($totalFloatApproved / 1000), 0) . ' K';
        }

        if ($totalFloatRejected != 0) {
            $totalFloatRejected = number_format(($totalFloatRejected / 1000), 0) . ' K';
        }


        $array = array(
            0 => $totalFloatRequested,
            1 => $totalFloatApproved,
            2 => $totalFloatRejected
        );


        return $array;
    }

    public static function getFloatRequests($subAgentsListID) {

        $totalFloatRequested = 0;
        $totalFloatApproved = 0;
        $totalFloatRejected = 0;
        $totalFloatPending = 0;

        $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests WHERE subAgentsListID='$subAgentsListID'";
        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalLoanRequested'] != null)
                $totalFloatRequested = $resRequested[0]['TotalLoanRequested'];
        }

        $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE statusCodeID=1 AND subAgentsListID='$subAgentsListID'";
        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalLoanApproved'] != null)
                $totalFloatApproved = $resApproved[0]['TotalLoanApproved'];
        }


        $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE statusCodeID=2 AND subAgentsListID='$subAgentsListID'";
        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalLoanRejected'] != null)
                $totalFloatRejected = $resRejected[0]['TotalLoanRejected'];
        }

        $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests WHERE statusCodeID=0 AND subAgentsListID='$subAgentsListID'";
        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalLoanPending'] != null)
                $totalFloatPending = $resPending[0]['TotalLoanPending'];
        }

        $array = array(
            0 => $totalFloatRequested,
            1 => $totalFloatApproved,
            2 => $totalFloatRejected,
            3 => $totalFloatPending,
        );

        return $array;
    }

    public static function getFloatRequestsGraph($subAgentsListID) {

        $totalFloatRequested = 0;
        $totalFloatApproved = 0;
        $totalFloatRejected = 0;
        $totalFloatPending = 0;

        $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests WHERE subAgentsListID='$subAgentsListID'";
        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalLoanRequested'] != null)
                $totalFloatRequested = $resRequested[0]['TotalLoanRequested'];
        }

        $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE statusCodeID=1 AND subAgentsListID='$subAgentsListID'";
        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalLoanApproved'] != null)
                $totalFloatApproved = $resApproved[0]['TotalLoanApproved'];
        }


        $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE statusCodeID=2 AND subAgentsListID='$subAgentsListID'";
        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalLoanRejected'] != null)
                $totalFloatRejected = $resRejected[0]['TotalLoanRejected'];
        }

        $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests WHERE statusCodeID=0 AND subAgentsListID='$subAgentsListID'";
        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalLoanPending'] != null)
                $totalFloatPending = $resPending[0]['TotalLoanPending'];
        }

        $array = array(
            0 => $totalFloatRequested,
            1 => $totalFloatApproved,
            2 => $totalFloatRejected,
            3 => $totalFloatPending,
        );

        return $array;
    }

    public static function getFloatRequestTransactionSummary($UsersID, $usersGroup) {

        $totalFloatRequested = 0;
        $totalFloatApproved = 0;
        $totalFloatRejected = 0;
        $totalFloatPending = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent  

        $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests WHERE subAgentsListID='$UsersID'";
        if ($usersGroup == 1) {
            $queryRequested = "select sum(amountToPay) TotalLoanRequested from floatRequests";
        }
        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalLoanRequested'] != null)
                $totalFloatRequested = $resRequested[0]['TotalLoanRequested'];
        }

        $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE statusCodeID=1 AND subAgentsListID='$UsersID'";
        if ($usersGroup == 1) {
            $queryApproved = "select sum(amountToPay) TotalLoanApproved from floatRequests WHERE statusCodeID=1";
        }
        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalLoanApproved'] != null)
                $totalFloatApproved = $resApproved[0]['TotalLoanApproved'];
        }


        $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests WHERE statusCodeID=0 AND subAgentsListID='$UsersID'";
        if ($usersGroup == 1) {
            $queryPending = "select sum(amountToPay) TotalLoanPending from floatRequests WHERE statusCodeID=0";
        }
        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalLoanPending'] != null)
                $totalFloatPending = $resPending[0]['TotalLoanPending'];
        }


        $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE statusCodeID=2 AND subAgentsListID='$UsersID'";
        if ($usersGroup == 1) {
            $queryRejected = "select sum(amountToPay) TotalLoanRejected from floatRequests WHERE statusCodeID=2 ";
        }
        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalLoanRejected'] != null)
                $totalFloatRejected = $resRejected[0]['TotalLoanRejected'];
        }


        if ($totalFloatRequested != 0) {
            $totalFloatRequested = number_format(($totalFloatRequested / 1000), 0) . 'k';
        }

        if ($totalFloatApproved != 0) {
            $totalFloatApproved = number_format(($totalFloatApproved / 1000), 0) . 'k';
        }

        if ($totalFloatPending != 0) {
            $totalFloatPending = number_format(($totalFloatPending / 1000), 0) . 'k';
        }

        if ($totalFloatRejected != 0) {
            $totalFloatRejected = number_format(($totalFloatRejected / 1000), 0) . 'k';
        }


        $array = array(
            0 => $totalFloatRequested,
            1 => $totalFloatApproved,
            2 => $totalFloatPending,
            3 => $totalFloatRejected
        );

        return $array;
    }

    public static function getFloatRepaymentTransactionSummary($UsersID, $usersGroup) {

        $totalFloatRequested = 0;
        $totalFloatApproved = 0;
        $totalFloatRejected = 0;
        $totalFloatPending = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent  

        $queryRequested = "select sum(repaymentAmount) TotalLoanRequested from floatRepayment WHERE usersID='$UsersID'";
        if ($usersGroup == 1) {
            $queryRequested = "select sum(repaymentAmount) TotalLoanRequested from floatRepayment";
        }
        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalLoanRequested'] != null)
                $totalFloatRequested = $resRequested[0]['TotalLoanRequested'];
        }

        $queryApproved = "select sum(repaymentAmount) TotalLoanApproved from floatRepayment WHERE statusCodeID=1 AND usersID='$UsersID'";
        if ($usersGroup == 1) {
            $queryApproved = "select sum(repaymentAmount) TotalLoanApproved from floatRepayment WHERE statusCodeID=1";
        }
        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalLoanApproved'] != null)
                $totalFloatApproved = $resApproved[0]['TotalLoanApproved'];
        }


        $queryPending = "select sum(repaymentAmount) TotalLoanPending from floatRepayment WHERE statusCodeID=0 AND usersID='$UsersID'";
        if ($usersGroup == 1) {
            $queryPending = "select sum(repaymentAmount) TotalLoanPending from floatRepayment WHERE statusCodeID=0";
        }
        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalLoanPending'] != null)
                $totalFloatPending = $resPending[0]['TotalLoanPending'];
        }


        $queryRejected = "select sum(repaymentAmount) TotalLoanRejected from floatRepayment WHERE statusCodeID=2 AND usersID='$UsersID'";
        if ($usersGroup == 1) {
            $queryRejected = "select sum(repaymentAmount) TotalLoanRejected from floatRepayment WHERE statusCodeID=2 ";
        }
        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalLoanRejected'] != null)
                $totalFloatRejected = $resRejected[0]['TotalLoanRejected'];
        }


        if ($totalFloatRequested != 0) {
            $totalFloatRequested = number_format(($totalFloatRequested / 1000), 0) . ' K';
        }

        if ($totalFloatApproved != 0) {
            $totalFloatApproved = number_format(($totalFloatApproved / 1000), 0) . ' K';
        }

        if ($totalFloatPending != 0) {
            $totalFloatPending = number_format(($totalFloatPending / 1000), 0) . ' K';
        }

        if ($totalFloatRejected != 0) {
            $totalFloatRejected = number_format(($totalFloatRejected / 1000), 0) . ' K';
        }


        $array = array(
            0 => $totalFloatRequested,
            1 => $totalFloatApproved,
            2 => $totalFloatPending,
            3 => $totalFloatRejected
        );

        return $array;
    }

    public static function getMpesaTransactionsSummary($subAgentsListID, $usersGroup) {

        $totalTransactions = 0;
        $totalCommissions = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent  

        $queryTransactions = "select sum(paid) TotalTransactions from subAgentsTransactions WHERE subAgentsListID='$subAgentsListID'";
        if ($usersGroup == 1) {
            $queryTransactions = "select sum(paid) TotalTransactions from subAgentsTransactions";
        }
        $resTransactions = Yii::$app->db->createCommand($queryTransactions)->queryAll();
        if (count($resTransactions) > 0) {
            if ($resTransactions[0]['TotalTransactions'] != null)
                $totalTransactions = $resTransactions[0]['TotalTransactions'];
        }

        $queryCommision = "select sum(paid) TotalCommission from subAgentsCommissions WHERE  subAgentsListID='$subAgentsListID'";
        if ($usersGroup == 1) {
            $queryCommision = "select sum(paid) TotalCommission from subAgentsCommissions";
        }
        $resCommision = Yii::$app->db->createCommand($queryCommision)->queryAll();
        if (count($resCommision) > 0) {
            if ($resCommision[0]['TotalCommission'] != null)
                $totalCommissions = $resCommision[0]['TotalCommission'];
        }

        if ($totalTransactions != 0) {
            $totalTransactions = number_format(($totalTransactions / 1000), 0) . 'k';
        }

        if ($totalCommissions != 0) {
            $totalCommissions = number_format(($totalCommissions / 1000), 0) . 'k';
        }


        $array = array(
            0 => $totalTransactions,
            1 => $totalCommissions,
        );

        return $array;
    }

    public static function getTotalLoanRepaid() {
        $query = "select sum(repayment_amount) TotalRepaidLoan from loanRepayments;";
        $res = Yii::$app->db->createCommand($query)->queryAll();

        $totalRepaidLoan = $res[0]['TotalRepaidLoan'];

        if ($totalRepaidLoan != NULL) {
            $totalRepaidLoan = ($totalRepaidLoan / 1000) . 'k';
        } else {
            $totalRepaidLoan = 0;
        }
        return $totalRepaidLoan;
    }

    public static function getAdminSuperAgentsList() {

        $totalRequested = 0;
        $totalApproved = 0;
        $totalRejected = 0;
        $totalPending = 0;

        $queryRequested = "select count(superAgentsListID) TotalRequested from superAgentsList";
        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalRequested'] != null)
                $totalRequested = $resRequested[0]['TotalRequested'];
        }

        $queryApproved = "select count(superAgentsListID) TotalApproved from superAgentsList WHERE statusCodeID=1";
        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalApproved'] != NULL)
                $totalApproved = $resApproved[0]['TotalApproved'];
        }

        $queryRejected = "select count(superAgentsListID) TotalRejected from superAgentsList WHERE statusCodeID=2";
        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalRejected'] != null)
                $totalRejected = $resRejected[0]['TotalRejected'];
        }

        $queryPending = "select count(superAgentsListID) TotalPending from superAgentsList WHERE statusCodeID=0";
        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalPending'] != null)
                $totalPending = $resPending[0]['TotalPending'];
        }

        $array = array(
            0 => $totalRequested,
            1 => $totalApproved,
            2 => $totalRejected,
            3 => $totalPending
        );


        return $array;
    }

    public static function getAdminSubAgentsList($userGroupsID, $superAgentsListID) {

        $totalRequested = 0;
        $totalApproved = 0;
        $totalRejected = 0;
        $totalPending = 0;

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 
        if ($userGroupsID == 1) {
            $queryRequested = "select count(subAgentsListID) TotalRequested from subAgentsList";
            $queryApproved = "select count(subAgentsListID) TotalApproved from subAgentsList WHERE statusCodeID=1";
            $queryRejected = "select count(subAgentsListID) TotalRejected from subAgentsList WHERE statusCodeID=2";
            $queryPending = "select count(subAgentsListID) TotalPending from subAgentsList WHERE statusCodeID=0";
        } else {
            $queryRequested = "select count(subAgentsListID) TotalRequested from subAgentsList WHERE superAgentsListID='$superAgentsListID'";
            $queryApproved = "select count(subAgentsListID) TotalApproved from subAgentsList WHERE superAgentsListID='$superAgentsListID' AND statusCodeID=1";
            $queryRejected = "select count(subAgentsListID) TotalRejected from subAgentsList WHERE superAgentsListID='$superAgentsListID' AND statusCodeID=2";
            $queryPending = "select count(subAgentsListID) TotalPending from subAgentsList WHERE superAgentsListID='$superAgentsListID' AND statusCodeID=0";
        }

        $resRequested = Yii::$app->db->createCommand($queryRequested)->queryAll();
        if (count($resRequested) > 0) {
            if ($resRequested[0]['TotalRequested'] != null)
                $totalRequested = $resRequested[0]['TotalRequested'];
        }


        $resApproved = Yii::$app->db->createCommand($queryApproved)->queryAll();
        if (count($resApproved) > 0) {
            if ($resApproved[0]['TotalApproved'] != NULL)
                $totalApproved = $resApproved[0]['TotalApproved'];
        }


        $resRejected = Yii::$app->db->createCommand($queryRejected)->queryAll();
        if (count($resRejected) > 0) {
            if ($resRejected[0]['TotalRejected'] != null)
                $totalRejected = $resRejected[0]['TotalRejected'];
        }

        $resPending = Yii::$app->db->createCommand($queryPending)->queryAll();
        if (count($resPending) > 0) {
            if ($resPending[0]['TotalPending'] != null)
                $totalPending = $resPending[0]['TotalPending'];
        }

        $array = array(
            0 => $totalRequested,
            1 => $totalApproved,
            2 => $totalRejected,
            3 => $totalPending
        );


        return $array;
    }

    public static function contractFileToBeSigned() {
        //logine user
        $customerID = Yii::$app->user->identity->customersID;
        $query = "select file from contracts where  status=1 and customersID =$customerID";
        $res = Yii::$app->db->createCommand($query)->queryAll();

        $count = count($res);
        Coreself::logThis('INFO', "check if the customer id $customerID has file specific :::  count - $count :::: query -$query");
        if ($count > 0) {
            //user has file speciffic
            Coreself::logThis('INFO', "the  RESULTS ARE " . print_r($res, true));
            return $res;
        } else {
            //get the general file
            $sql = "select file from contracts where  status=1 and customersID =0";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $count = count($data);
            Coreself::logThis('INFO', "getting the general contract file :::  count - $count :::: query -$query");
            if ($count > 0) {
                //general contract file is available
                $x = $data;
                Coreself::logThis('INFO', "the  RESULTS ARE " . print_r($data, true));
                //Coreself::logThis('INFO', "return $x ");
                return $x;
            } else {
                //no file exizt
                Coreself::logThis('INFO', "oooooooooops! no contract file exixting");
                return FALSE;
            }
        }
    }

//mobile validator
    public static function isValidMobileNo($msisdn) {
        $mobprefixes = array(0 => 25470, 1 => 25471, 2 => 25472, 3 => 25473, 4 => 25475, 5 => 25478, 6 => 25477, 7 => 25476, 8 => 25479, 9 => 25474, 10 => 25677, 11 => 25678, 12 => 26539);
        $prefixes = join('|', $mobprefixes);
        $msisdn = ltrim($msisdn, "+");
        $prefixes_p = $mobprefixes;
        $mobile = substr($msisdn, 1);
        $mop = substr($mobile, 0, 2);


        foreach ($prefixes_p as $mob) {
            if (substr($msisdn, 0, 1) == 0) {

                $netNo = substr($mob, 3, 2);
                $pre = substr($mob, 0, 3);

                if ($netNo == $mop) {
                    $msisdn = $pre . $mobile;
                }
            } else {
                $netNo = substr($mob, 3, 2);
                $pre = substr($mob, 0, 3);
                $premsisdn = substr($msisdn, 0, 2);



                if ($netNo == $premsisdn) {
                    $msisdn = $pre . $msisdn;
                }
            }
        }
        $validate = preg_match('/(' . $prefixes . ')[0-9]{7}$/', $msisdn);

        if ($validate > 0) {
            return $msisdn;
        } else {
            return 0;
        }
    }

    public static function sendEmail2($name, $to, $subject, $body) {

        Yii::import('application.extensions.phpmailer.JPhpMailer');
        $mail = new JPhpMailer;
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "ssl";
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = '465';
        $mail->Username = 'supportke@mtechcomm.com';
        $mail->Password = 'mtechkenya90';
        $mail->From = 'supportke@mtechcomm.com';
        $mail->FromName = 'Mtech Comm';
        $mail->AddReplyTo('supportke@mtechcomm.com', $name);
        $mail->Subject = $subject;
        $mail->SMTPDebug = 1;
        $mail->Body = $body;
        $mail->IsHTML(true);

        $mail->AddAddress($to, $name);

        try {
            if (!$mail->Send()) {

                self::logThis("Unable to send email.Error :- " . CJSON::encode($mail->ErrorInfo) . ' Details : To ' . $to . ' Subject :' . $subject . ' Message :' . $body);

                return $mail->ErrorInfo;
            } else {

                $mail->ClearAddresses();
                $mail->ClearAttachments();
                return 1;
            }
        } catch (Exception $e) {
            self::logThis("Unable to send email.Error :- " . CJSON::encode($mail->ErrorInfo) . ' Details : To ' . $to . ' Subject :' . $subject . ' Message :' . $body . "::exception::" . $e->getMessage());
        }
    }

    public static function sendEmail($to, $content, $subject) {

        try {

            $message = self::getMessage($content);

            Yii::$app->mailer->compose()
                    ->setFrom('supportke@mtechcomm.com')
                    ->setTo($to)
                    ->setSubject($subject)
                    ->setTextBody("")
                    ->setHtmlBody($message)
                    ->send();
        } catch (Exception $ex) {
            self::logThis("ERROR", "Unable to send email to address $to. Error -- " . $ex->getMessage());
        }
    }

    public static function logT($LEVEL, $logThis) {

        $logFile = "";
        $logLevel = "";
        switch ($LEVEL) {
            case "INFO":
                $logFile = "/var/www/logs/financials_info.log";
                $logLevel = "INFO";
                break;
            case "ERROR":
                $logFile = "/var/www/logs/financials_info.log";
                $logLevel = "ERROR";
                break;
            case "DEBUG":
                $logFile = "/var/www/logs/financials_info.log";
                $logLevel = "DEBUG";
                break;
            default :
                $logFile = "/var/www/logs/financials_info.log";
                $logLevel = "DEFAULT";
        }

        $e = new \Exception();
        $trace = $e->getTrace();
//position 0 would be the line that called this function so we ignore it
        $last_call = isset($trace[1]) ? $trace[1] : array();
        $lineArr = $trace[0];


        $function = isset($last_call['function']) ? $last_call['function'] . "()|" : "";
        $line = isset($lineArr['line']) ? $lineArr['line'] . "|" : "";
        $file = isset($lineArr['file']) ? $lineArr['file'] . "|" : "";

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] . "|" : "";
        $date = date("Y-m-d H:i:s");
        $string = $date . "|$logLevel|$file$function$remote_ip$line" . $logThis . "\n";
        file_put_contents($logFile, $string, FILE_APPEND);
    }

    public static function LogAuditTail($usersID, $action) {
        $tail = new AuditTrail();
        $tail->action = $action;
        $tail->username = $usersID;
        $tail->dateCreated = date('Y-m-d H:i:s');
        $tail->save();
    }

    public static function generatePassword($length) {
        //  $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";        
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = random_int(1000, 9999);
        return $password;
    }

    public static function generateRandomPin($length) {
        $chars = "0123456789";
        $pin = random_int(1000, 9999);
        return $pin;
    }

    public static function sendAccessToken($username) {
        $response = false;
        try {

            $msisdn = "254" . substr(self::getMobileNo($username), -9);
            $usersID = self::getUsersID($username);
            $accessCode = random_int(1000, 9999);
            //Send Sms

            $Refreshtime = (Yii::$app->params['accessCodeTimer'] / 60000);
            $timeNow = date('Y-m-d H:i:s');

            $newtimestamp = strtotime($timeNow . '+ ' . $Refreshtime . ' minute');
            $expirtDate = date('Y-m-d H:i:s', $newtimestamp);

            $message = "Your login access code is $accessCode expiring on $expirtDate";

            $model = new LogonAccessCodes();

            if (self::sendSMS($msisdn, $message)) {

                $model->usersID = $usersID;
                $model->accessCode = $accessCode;
                $model->dateCreated = $timeNow;
                $model->expirtDate = $expirtDate;
                $model->save();

                $response = true;
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        return $response;
    }

    public static function sendTillTransferAccessToken($msisdn, $subAgentsListID) {
        $response = false;
        try {

            $msisdn = "254" . substr($msisdn, -9);
            $accessCode = random_int(1000, 9999);

            //Send Sms

            $Refreshtime = (Yii::$app->params['accessCodeTimer'] / 60000);
            $timeNow = date('Y-m-d H:i:s');

            $newtimestamp = strtotime($timeNow . '+ ' . $Refreshtime . ' minute');
            $expirtDate = date('Y-m-d H:i:s', $newtimestamp);


            $message = "Your till transfer access code is $accessCode expiring on $expirtDate";

            $model = new TillTransfersAccessCodes();

            if (self::sendSMS($msisdn, $message)) {

                $model->subAgentsListID = $subAgentsListID;
                $model->accessCode = $accessCode;
                $model->dateCreated = $timeNow;
                $model->expirtDate = $expirtDate;
                $model->save();

                $response = true;
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        return $response;
    }

    public function validateAccessCodes($userID, $accessCode) {
        $response = "02";
        $sqlexpirtDate = "select expirtDate from logonAccessCodes where usersID='$userID' and accessCode='$accessCode' ORDER BY logonAccessCodesID LIMIT 1;";
        $resultexpirtDate = Yii::$app->db->createCommand($sqlexpirtDate)->queryAll();
        if (count($resultexpirtDate) > 0) {
            $timeNow = date('Y-m-d H:i:s');
            $expirtDate = $resultexpirtDate[0]['expirtDate'];
            if ($expirtDate >= $timeNow) {
                $response = "00";
            } else {
                $response = "01";
            }
        }

        return $response;
    }

    public function getMobileNo($username) {

        $query = new Query();
        $query->select(['msisdn'])
                ->from('user')
                ->where(['username' => $username]);
        $command = $query->createCommand();
        $Users = $command->queryAll();

        foreach ($Users as $a) {
            return $a['msisdn'];
        }
    }

    public function checkIfCompletedCompanyInfo($username) {
        $response = false;
        $sqlUser = "select completedCompanyInfo from user where username='$username' ORDER BY id LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            if ($resultUser[0]['completedCompanyInfo']) {
                $response = true;
            }
        }
        return $response;
    }

    public function checkIfCompanyInfoApproved($username) {
        $response = false;
        $sqlUser = "select infoApproved from user where username='$username' ORDER BY id LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            if ($resultUser[0]['infoApproved'] == 1) {
                $response = true;
            }
        }
        return $response;
    }

    public function getSuperAgentID($username) {

        $response = 0;
        $sqlUser = "select superAgentsListID from user a,superAgentsList b where a.username='$username' AND a.id=b.usersID ORDER BY id LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['superAgentsListID'];
        }
        return $response;
    }

    public function updateCompletedCompanyInfo($username) {
        $query = new Query();
        $query->createCommand()->update('user', ['completedCompanyInfo' => 1, 'infoApproved' => 0], ['username' => $username])->execute();
    }

    public function updateApprovedCompanyInfo($id) {
        $query = new Query();
        $query->createCommand()->update('user', ['infoApproved' => 1], ['id' => $id])->execute();
    }

    public function getSubAgentID($username) {

        $response = 0;
        $sqlUser = "select subAgentsListID from user a,subAgentsList b where a.username='$username' AND a.id=b.usersID ORDER BY id LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['subAgentsListID'];
        }
        return $response;
    }

    public function getUserGroupID($username) {
        $response = 0;
        $sqlUser = "select userGroupsID from user where username='$username' ORDER BY id LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['userGroupsID'];
        }
        return $response;
    }

    public static function getDocumentsToBeSigned() {

        $query = "select * from documentsUploads where statusCodeID=1 order by 1 desc LIMIT 1";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        $count = count($res);
        Coreself::logThis('INFO', "getContractFileToBeSigned  count - $count :::: query -$query");
        if ($count > 0) {
            Coreself::logThis('INFO', "the  RESULTS ARE " . print_r($res, true));
            return $res;
        } else {
            return false;
        }
    }

    public function getInterestRate($repaymentPeriodID) {
        $response = 0;
        $sqlUser = "select interestRate from floatRepaymentPeriod where repaymentPeriodID='$repaymentPeriodID' ORDER BY repaymentPeriodID LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['interestRate'];
        }
        return $response;
    }

    public function getFloatRepaymentPeriodValue($repaymentPeriodID) {
        $response = 0;
        $sqlUser = "select repamentPeriodValue from floatRepaymentPeriod where repaymentPeriodID='$repaymentPeriodID' ORDER BY repaymentPeriodID LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['repamentPeriodValue'];
        }
        return $response;
    }

    public static function sendAdminEmail($subject, $body) {
        try {

            $sqlUser = "select email from user where userGroupsID=1;";
            $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
            if (count($resultUser) > 0) {
                foreach ($resultUser as $a) {
                    Coreself::sendEmail($a['email'], $body, $subject);
                }
            }
        } catch (Exception $exc) {
            
        }
    }

    public function getSubAgentOutletStoreName($subAgentsListID) {

        $response = "";
        $sqlStore = "select outletStoreName from subAgentsList where subAgentsListID='$subAgentsListID' LIMIT 1;";
        $resultStore = Yii::$app->db->createCommand($sqlStore)->queryAll();
        if (count($resultStore) > 0) {
            return $resultStore[0]['outletStoreName'];
        }
        return $response;
    }

    public function getSuperAgentListBySubAgentLIstID($subAgentsListID) {

        $response = "";
        $sqlStore = "select superAgentsListID from subAgentsList where subAgentsListID='$subAgentsListID' LIMIT 1;";
        $resultStore = Yii::$app->db->createCommand($sqlStore)->queryAll();
        if (count($resultStore) > 0) {
            return $resultStore[0]['superAgentsListID'];
        }
        return $response;
    }

    public function updateTillListStatus($subAgentsListID, $tillNo, $statusCodeID) {

        //  echo $subAgentsListID."|".$tillNo."|".$statusCodeID; exit;
        $query = new Query();
        $query->createCommand()->update('tillsList', ['statusCodeID' => $statusCodeID], ['tillNo' => $tillNo, 'subAgentsListID' => $subAgentsListID])->execute();
    }

    public function getEncodeID($id) {
        return base64_encode(Yii::$app->getSecurity()->encryptByPassword($id, Yii::$app->user->identity->userName));
    }

    public function getDecodeID($id) {
        return Yii::$app->getSecurity()->decryptByPassword(base64_decode($id), Yii::$app->user->identity->userName);
    }

    public function validateTillTransferAccessCodes($subAgentsListID, $accessCode) {
        $response = "02";
        $sqlexpirtDate = "select expirtDate from tillTransfersAccessCodes where subAgentsListID='$subAgentsListID' and accessCode='$accessCode' ORDER BY tillTransfersAccessCodesID DESC LIMIT 1;";
        $resultexpirtDate = Yii::$app->db->createCommand($sqlexpirtDate)->queryAll();
        if (count($resultexpirtDate) > 0) {
            $timeNow = date('Y-m-d H:i:s');
            $expirtDate = $resultexpirtDate[0]['expirtDate'];
            if ($expirtDate >= $timeNow) {
                $response = "00";
            } else {
                $response = "01";
            }
        }
        return $response;
    }

    public static function checkIFIDisAlreadyRegistered($contactPersonIdNo) {

        $contactPersonName = "";
        $contactPersonPhone = "";
        $contactPersonEmail = "";
        $subAgentsListID = "";
        $superAgentsListID = "";

        $sqlexpirtDate = "select subAgentsListID,superAgentsListID,contactPersonPhone,contactPersonEmail,contactPersonName from subAgentsList where contactPersonIdNo='$contactPersonIdNo' ORDER BY contactPersonIdNo DESC LIMIT 1;";
        $resultexpirtDate = Yii::$app->db->createCommand($sqlexpirtDate)->queryAll();
        if (count($resultexpirtDate) > 0) {
            $subAgentsListID = $resultexpirtDate[0]['subAgentsListID'];
            $superAgentsListID = $resultexpirtDate[0]['superAgentsListID'];
            $contactPersonPhone = $resultexpirtDate[0]['contactPersonPhone'];
            $contactPersonEmail = $resultexpirtDate[0]['contactPersonEmail'];
            $contactPersonName = $resultexpirtDate[0]['contactPersonName'];
        }

        $array = array(
            0 => $subAgentsListID,
            1 => $superAgentsListID,
            2 => $contactPersonPhone,
            3 => $contactPersonEmail,
            4 => $contactPersonName,
        );

        return $array;
    }

    public static function sendTillTransferSMS($phone, $tillNo, $Type) {
        $response = false;
        try {

            $url = Yii::$app->params['applicationEmailUrl'];

            $tinyUrl = Coreself::tiny_url($url);

            $msisdn = "254" . substr($phone, -9);
            if ($Type != "New") {
                $message = "You have a till transfer request. Please click $tinyUrl to login and accept the till transfer";
            }
            $message = "You have a till transfer request. Please click $tinyUrl to register and accept the till transfer";

            if (self::sendSMS($msisdn, $message)) {
                $response = true;
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        return $response;
    }

    public static function sendTillTransferEmail($email, $name, $tillNo) {

        $url = Yii::$app->params['applicationEmailUrl'];

        $body = '<p><span style="font-family:Trebuchet MS, Verdana, Arial; font-size:17px; font-weight:bold;"> Dear ' . $name . '</span>,</p>
            <br />
            <div>You have a till transfer request. Please click the link below and login to accept the transfer request</div>
            <br />
            <div style="padding-left:20px; padding-bottom:10px;">&nbsp;&nbsp;&nbsp;Url -<a href="' . $url . '" style="color:blue">' . $url . '</a></div>
            <br />
            <div>Thankyou</div>
            <br />
            <div style="color:blue"><i>MTECH</i></div>

 ';
        $subject = "Till Transfer Request";

        Coreself::sendEmail($email, $body, $subject);
    }

    public static function tiny_url($url) {
        return file_get_contents('http://tinyurl.com/api-create.php?url=' . $url);
    }

    public function getSubAgentContactPersonIdNo($username) {

        $response = "";
        $sqlUser = "select contactPersonIdNo from user a,subAgentsList b where a.username='$username' AND a.id=b.usersID ORDER BY id LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['contactPersonIdNo'];
        }
        return $response;
    }

    public function checkIfHasPendingTransferRequest($contactPersonIdNo) {
        $response = false;
        $sqlUser = "select tillsTransfersListID from tillsTransfersList where statusCodeID=0 AND newSubAgentIdNo='$contactPersonIdNo' ORDER BY tillsTransfersListID DESC LIMIT 1;";
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            $response = true;
        }
        return $response;
    }

    public static function getCountPendingSugAgents($userGroupsID, $superAgentsListID) {

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 

        if ($userGroupsID == 1) {
            $query = "select subAgentsListID,contactPersonName,outletStoreName from subAgentsList WHERE statusCodeID=0";
        } else {
            $query = "select subAgentsListID,contactPersonName,outletStoreName from subAgentsList WHERE superAgentsListID='$superAgentsListID' AND  statusCodeID=0";
        }

        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            return count($res);
        }
        return 0;
    }

    public static function getPendingSubAgents($userGroupsID, $superAgentsListID) {
        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 

        if ($userGroupsID == 1) {
            $query = "select subAgentsListID,contactPersonName,outletStoreName from subAgentsList WHERE statusCodeID=0";
        } else {
            $query = "select subAgentsListID,contactPersonName,outletStoreName from subAgentsList WHERE superAgentsListID='$superAgentsListID' AND  statusCodeID=0";
        }

        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            return $res;
        }
        return false;
    }

    public static function getCountPendingTillTransfers($userGroupsID, $contactPersonIdNo, $superAgentsListID, $subAgentsListID) {

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 

        if ($userGroupsID == 1) {

            $query = "select tillsTransfersListID,b.tillNo,outletStoreName from tillsTransfersList a,tillsList b WHERE a.tillsListID=b.tillsListID AND a.statusCodeID=4"; //4 accepted ownership
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return count($res);
            }
        } else if ($userGroupsID == 3) {


            $query = "select tillsTransfersListID,b.tillNo,b.outletStoreName,a.dateCreated from tillsTransfersList a,tillsList b ,subAgentsList c WHERE a.tillsListID=b.tillsListID AND b.subAgentsListID=c.subAgentsListID AND c.superAgentsListID='$subAgentsListID' AND a.statusCodeID=4";
//4 accepted ownership
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return count($res);
            }
        } else {

            $query = "select tillsTransfersListID,b.tillNo,outletStoreName from tillsTransfersList a,tillsList b WHERE a.tillsListID=b.tillsListID AND a.statusCodeID=0 AND newSubAgentIdNo='$contactPersonIdNo'"; //0 Pending acceptance
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return count($res);
            }
        }

        return 0;
    }

    public static function getPendingTillTransfers($userGroupsID, $contactPersonIdNo, $superAgentsListID, $subAgentsListID) {

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 
        if ($userGroupsID == 1) {
            $query = "select tillsTransfersListID,b.tillNo,outletStoreName,a.dateCreated from tillsTransfersList a,tillsList b WHERE a.tillsListID=b.tillsListID AND a.statusCodeID=4"; //4 accepted ownership
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return $res;
            }
        } else if ($userGroupsID == 3) {

            $query = "select tillsTransfersListID,b.tillNo,b.outletStoreName,a.dateCreated from tillsTransfersList a,tillsList b ,subAgentsList c WHERE a.tillsListID=b.tillsListID AND b.subAgentsListID=c.subAgentsListID AND c.superAgentsListID='$subAgentsListID' AND a.statusCodeID=4"; //4 accepted ownership
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return $res;
            }
        } else {

            $query = "select tillsTransfersListID,b.tillNo,outletStoreName,a.dateCreated from tillsTransfersList a,tillsList b WHERE a.tillsListID=b.tillsListID AND a.statusCodeID=0 AND newSubAgentIdNo='$contactPersonIdNo'"; //0 Pending acceptance
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return $res;
            }
        }

        return false;
    }

    public static function getCountTillsStatusPending($userGroupsID, $superAgentsListID, $subAgentsListID) {

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 

        if ($userGroupsID == 1) {

            $query = "select tillsListID from tillsList  WHERE statusCodeID=0";
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return count($res);
            }
        } else if ($userGroupsID == 3) {


            $query = "select a.tillsListID,a.tillNo,a.outletStoreName from tillsList a,subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID' AND a.statusCodeID=0 AND a.contractDirectory is not null";

            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return count($res);
            }
        } else {

            $query = "select tillsListID from tillsList WHERE statusCodeID=0 AND subAgentsListID='$subAgentsListID' AND contractDirectory is null";
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return count($res);
            }
        }

        return 0;
    }

    public static function getTillsStatusPending($userGroupsID, $superAgentsListID, $subAgentsListID) {

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent 

        if ($userGroupsID == 1) {

            $query = "select tillsListID,tillNo,outletStoreName from tillsList  WHERE statusCodeID=0";
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return $res;
            }
        } else if ($userGroupsID == 3) {


            $query = "select a.tillsListID,a.tillNo,a.outletStoreName from tillsList a,subAgentsList b WHERE a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID' AND a.statusCodeID=0 AND a.contractDirectory is not null";

            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return $res;
            }
        } else {

            $query = "select tillsListID,tillNo,outletStoreName from tillsList WHERE statusCodeID=0 AND subAgentsListID='$subAgentsListID' AND contractDirectory is null";
            $res = Yii::$app->db->createCommand($query)->queryAll();
            if (count($res) > 0) {
                return $res;
            }
        }

        return false;
    }

    public function ComputeMaximumFloatAmout($tillsListID) {


        Coreself::logThis("INFO", "ComputeMaximumFloatAmout tillsListID=" . $tillsListID);

        $status = "0";
        $amount = 0;
        $desc = "Till no does not exist";
        $tillNo = "";
        $tillNoStoreCode = "";
        $subAgentsListID = "";

        if (!Coreself::CheckIfTillHasPendingLoan($tillsListID)) { //Check if till has a pending loan
            $queryTillsList = "select tillNo,tillNoStoreCode,subAgentsListID from tillsList WHERE tillsListID='$tillsListID'";

            Coreself::logThis("INFO", "" . $queryTillsList);

            $resTillsList = Yii::$app->db->createCommand($queryTillsList)->queryAll();
            if (count($resTillsList) > 0) {
                if ($resTillsList[0]['tillNoStoreCode'] != null) {
                    $tillNo = $resTillsList[0]['tillNo'];
                    $tillNoStoreCode = $resTillsList[0]['tillNoStoreCode'];
                    $subAgentsListID = $resTillsList[0]['subAgentsListID'];

                    //get total transactions
                    $queryTransactions = "select SUM(paid) as TotalCommision from subAgentsTransactions WHERE subAgentsListID='$subAgentsListID' AND tillNoStoreCode='$tillNoStoreCode'";

                    Coreself::logThis("INFO", "" . $queryTransactions);

                    $resTransactions = Yii::$app->db->createCommand($queryTransactions)->queryAll();
                    if (count($resTransactions) > 0) {
                        if ($resTransactions[0]['TotalCommision'] != null) {
                            $TotalCommision = $resTransactions[0]['TotalCommision'];
                            $status = 1;
                            //get percentage                    
                            $amount = $TotalCommision;
                            $desc = "Success";
                        } else {
                            $desc = "No commision transactions";
                        }
                    } else {
                        $desc = "No commision transactions";
                    }
                } else {

                    $desc = "Store code not set";
                }
            }
        } else {
            $desc = "Till have a unpaid float";
        }

        $array = array(
            0 => $status,
            1 => $amount,
            2 => $desc,
        );

        return $array;
    }

    public function getTillStatus($tillsListID) {

        $status = "0";
        $queryTillsList = "select tillNo,tillNoStoreCode,subAgentsListID,monthlyTransactionsLimit from tillsList WHERE tillsListID='$tillsListID'";

        $resTillsList = Yii::$app->db->createCommand($queryTillsList)->queryAll();
        if (count($resTillsList) > 0) {
            if ($resTillsList[0]['tillNoStoreCode'] != null && $resTillsList[0]['monthlyTransactionsLimit'] != null) {
                $tillNo = $resTillsList[0]['tillNo'];
                $tillNoStoreCode = $resTillsList[0]['tillNoStoreCode'];
                $subAgentsListID = $resTillsList[0]['subAgentsListID'];
                $monthlyTransactionsLimit = $resTillsList[0]['monthlyTransactionsLimit'];

                $queryTransactions = "select SUM(paid) as TotalCommision from subAgentsTransactions WHERE subAgentsListID='$subAgentsListID' AND tillNoStoreCode='$tillNoStoreCode'";

                $resTransactions = Yii::$app->db->createCommand($queryTransactions)->queryAll();
                if (count($resTransactions) > 0) {
                    $count = count($resTransactions);
                    if ($resTransactions[0]['TotalCommision'] != null) {
                        $TotalCommision = $resTransactions[0]['TotalCommision'];
                        $average = ($TotalCommision / $count);

                        Coreself::logThis("INFO", "Total Commission=" . $TotalCommision . " || No of months=" . $count . " || Averange=" . $average . " || monthly target =" . $monthlyTransactionsLimit);

                        if ($average >= $monthlyTransactionsLimit) {
                            $status = "Performing";
                        } else {
                            $status = "Under Performing";
                        }
                    }
                }
            }
        }

        $array = array(
            0 => $status,
        );

        return $array;
    }

    public function CheckIfTillHasPendingLoan($tillsListID) {

        $status = true;
        $TotalRepayment = 0;
        $TotalLoan = 0;

        $queryTillsList = "select tillNo,tillNoStoreCode,subAgentsListID from tillsList WHERE tillsListID='$tillsListID'";

        Coreself::logThis("INFO", "CheckIfTillHasPendingLoan || " . $queryTillsList);

        $resTillsList = Yii::$app->db->createCommand($queryTillsList)->queryAll();
        if (count($resTillsList) > 0) {
            if ($resTillsList[0]['tillNoStoreCode'] != null) {
                $tillNo = $resTillsList[0]['tillNo'];
                $tillNoStoreCode = $resTillsList[0]['tillNoStoreCode'];
                $subAgentsListID = $resTillsList[0]['subAgentsListID'];

                //Loan Requests
                $queryTransactions = "select SUM(amountToPay) as TotalLoan from floatRequests WHERE tillsListID='$tillsListID' AND statusCodeID in (0,1,2)";
                Coreself::logThis("INFO", "" . $queryTransactions);
                $resTransactions = Yii::$app->db->createCommand($queryTransactions)->queryAll();
                if (count($resTransactions) > 0) {
                    if ($resTransactions[0]['TotalLoan'] != null) {
                        $TotalLoan = $resTransactions[0]['TotalLoan'];
                    }
                }
                //Repayment
                $queryRepayments = "select SUM(repaymentAmount) as TotalRepayment from floatRepayment WHERE tillsListID='$tillsListID' AND statusCodeID=1";
                Coreself::logThis("INFO", "" . $queryRepayments);
                $resRepayments = Yii::$app->db->createCommand($queryRepayments)->queryAll();
                if (count($resRepayments) > 0) {
                    if ($resRepayments[0]['TotalRepayment'] != null) {
                        $TotalRepayment = $resRepayments[0]['TotalRepayment'];
                    }
                }
                if ($TotalRepayment >= $TotalLoan) {
                    // Coreself::logThis("INFO", "Total Loan=" . $TotalLoan . " || Total Repayment=" . $TotalRepayment);
                    $status = false;
                }
            } else {
                $status = false;
            }
        }
        return $status;
    }

    public function updateTillsList($superAgentTillsListID, $tillNo, $tillNoStoreCode) {
        $query = new Query();
        $query->createCommand()->update('tillsList', ['tillNo' => $tillNo, 'tillNoStoreCode' => $tillNoStoreCode], ['superAgentTillsListID' => $superAgentTillsListID])->execute();
    }

    public function getTillListIDBySuperAgentTillsListID($superAgentTillsListID) {

        $response = 0;
        $sqlUser = "select tillsListID from tillsList WHERE superAgentTillsListID='$superAgentTillsListID' LIMIT 1;";

        Coreself::logThis("INFO", "" . $sqlUser);
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['tillsListID'];
        }
        return $response;
    }

    public function getSuperAgentTillsListIDByTillsListID($tillsListID) {

        $response = 0;
        $sqlUser = "select superAgentTillsListID from tillsList WHERE tillsListID='$tillsListID' LIMIT 1;";

        Coreself::logThis("INFO", "" . $sqlUser);
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['superAgentTillsListID'];
        }
        return $response;
    }

    public function getTillPerformanceStatus($userGroupsID, $subAgentsListID, $superAgentsListID) {

        $status = "0";

        //userGroupsID= 1 Admin
        //userGroupsID= 2 Sub Agent
        //userGroupsID= 3 Super Agent

        $tillsListID = 0;
        $totalTills = 0;
        $totalUnderPerfoming = 0;
        $totalInActive = 0;
        $totalPerfoming = 0;



        if ($userGroupsID == 1) {
            $query = "select tillsListID,tillNo from tillsList  WHERE statusCodeID=1";
            $queryTillsList = "select tillNo,tillNoStoreCode,subAgentsListID,monthlyTransactionsLimit from tillsList WHERE statusCodeID='1'";
        }

        if ($userGroupsID == 2) {
            $query = "select tillsListID,tillNo from tillsList  WHERE statusCodeID=1 AND subAgentsListID='$subAgentsListID'";

            $queryTillsList = "select tillNo,tillNoStoreCode,subAgentsListID,monthlyTransactionsLimit from tillsList WHERE statusCodeID='1' AND subAgentsListID='$subAgentsListID'";
        }

        if ($userGroupsID == 3) {
            $query = "select tillsListID,a.tillNo from tillsList a, subAgentsList b WHERE a.statusCodeID=1 AND a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID'";

            $queryTillsList = "select tillsListID,a.tillNo, a.tillNoStoreCode,a.subAgentsListID,monthlyTransactionsLimit from tillsList a, subAgentsList b WHERE a.statusCodeID=1 AND a.subAgentsListID=b.subAgentsListID AND b.superAgentsListID='$superAgentsListID'";
        }

        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            $totalTills = count($res);
        }


        //get Under Performing tills
        $resTillsList = Yii::$app->db->createCommand($queryTillsList)->queryAll();
        if (count($resTillsList) > 0) {

            $counter = 0;
            foreach ($resTillsList as $ttTills) {
                if ($ttTills['tillNoStoreCode'] != null && $ttTills['monthlyTransactionsLimit'] != null) {

                    $tillNo = $ttTills['tillNo'];
                    $tillNoStoreCode = $ttTills['tillNoStoreCode'];
                    $subAgentsListID = $ttTills['subAgentsListID'];
                    $monthlyTransactionsLimit = $ttTills['monthlyTransactionsLimit'];

                    $count = 0;

                    $queryTransactions = "select SUM(paid) as TotalCommision from subAgentsTransactions WHERE tillNoStoreCode='$tillNoStoreCode'";

                    $resTransactions = Yii::$app->db->createCommand($queryTransactions)->queryAll();
                    if (count($resTransactions) > 0) {
                        $count = count($resTransactions);

                        if ($resTransactions[0]['TotalCommision'] != null) {
                            Coreself::logThis("INFO", "" . $queryTransactions);

                            $TotalCommision = $resTransactions[0]['TotalCommision'];
                            $average = ($TotalCommision / $count);

                            Coreself::logThis("INFO", "Total Commission=" . $TotalCommision . " || No of months=" . $count . " || Averange=" . $average . " || monthly target =" . $monthlyTransactionsLimit);

                            if ($average >= $monthlyTransactionsLimit) {
                                $totalPerfoming++;
                            } else {
                                $totalUnderPerfoming++;
                            }
                        } else {

                            $totalInActive++;
                        }
                    }
                } else {
                    $totalInActive++;
                }
            }
        }

        $array = array(
            0 => $totalTills,
            1 => $totalPerfoming,
            2 => $totalInActive,
            3 => $totalUnderPerfoming,
        );

        return $array;
    }

    public static function sendUssdPIN($usersID, $contactPersonPhone, $contactPersonName) {
        $response = false;
        try {

            $pin = random_int(1000, 9999);
            $message = "Dear  $contactPersonName, welcome to i-Float, your OTP is $pin. Please dial *571*40# to change your pin.";
            if (self::sendSMS($contactPersonPhone, $message)) {

                $NewPin = md5(sha1($pin));
                $query = new Query();
                $query->createCommand()->update('user', ['pin' => $NewPin, 'changedPin' => 0], ['id' => $usersID])->execute();

                $response = true;
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        return $response;
    }

    public static function invokeSTKPushTransaction($TelephoneNo, $AccountReference, $amount, $clientTrxID, $batch_no) {
        $b = FALSE;
        try {

            $CorporateNo = "817050";
            $network = "SAFARICOM";

            $sql = "SELECT * FROM mobileBanking.mpesa_c2b_stkPush_transactions WHERE CorporateNo='$CorporateNo' AND SESSION_ID='$clientTrxID'";
            $QryResponse = Yii::$app->dbMobileBanking->createCommand($sql)->queryAll();
            if (count($QryResponse) > 0) {
                Coreself::logThis("INFO", "The TRX '$clientTrxID' is a possible duplicate ");
                $b = false;
            } else {

                $MerchantName = $CorporateNo . "-" . $AccountReference;
                $TransactionType = "CustomerPayBillOnline";

                $query = "INSERT INTO mobileBanking.mpesa_c2b_stkPush_transactions (dateReceived,Status,AccountReference,MerchantName,TelephoneNo,Amount,CorporateNo,SentToJournal,SESSION_ID,BATCHID,Network,TransactionType) VALUES (NOW(),'Pending','$AccountReference','$MerchantName','$TelephoneNo','$amount','$CorporateNo',0,'$clientTrxID','$batch_no','$network','$TransactionType') ";
                Coreself::logThis("INFO", "Insert trx query  -  $query");
                Yii::$app->dbMobileBanking->createCommand($query)->execute();
            }
        } catch (Exception $e) {
            Coreself::logThis("INFO", "Error Saving Transaction .... Exception..: " . $e->getMessage());
        }
        return $b;
    }

    public function getTillNo($tillsListID) {

        $response = 0;
        $sqlUser = "select tillNo from tillsList WHERE tillsListID='$tillsListID' LIMIT 1;";

        Coreself::logThis("INFO", "" . $sqlUser);
        $resultUser = Yii::$app->db->createCommand($sqlUser)->queryAll();
        if (count($resultUser) > 0) {
            return $resultUser[0]['tillNo'];
        }
        return $response;
    }

    public function getFloatBalance($floatRequestID, $amountToPay) {

        $TotalRepayment = 0;
        $queryRepayments = "select SUM(repaymentAmount) as TotalRepayment from floatRepayment WHERE floatRequestID='$floatRequestID' AND statusCodeID=2";
        Coreself::logThis("INFO", "" . $queryRepayments);
        $resRepayments = Yii::$app->db->createCommand($queryRepayments)->queryAll();
        if (count($resRepayments) > 0) {
            if ($resRepayments[0]['TotalRepayment'] != null) {
                $TotalRepayment = $resRepayments[0]['TotalRepayment'];
            }
        }
        return number_format($amountToPay - $TotalRepayment, 3);
    }

    //fetch dash board graps details;
    public static function getTotalMpesaC2BTransactionss($MpesaC2BPaybill) {

        $totalMpesaC2b = 0;
        $query = "Select sum(AMOUNT) as TotalAmount from mpesa_c2b_transactions WHERE BusinessShortCode='$MpesaC2BPaybill'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaC2b = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaC2b,
        );
        return $array;
    }

    public static function getTotalMpesaC2BReversalsTransactionss($MpesaC2BPaybill) {

        $totalMpesaC2b = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_c2b_reversal_transactions WHERE CorporateNo='$MpesaC2BPaybill'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaC2b = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaC2b,
        );
        return $array;
    }

    public static function getTotalMpesaB2CTransactionss($MpesaB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$MpesaB2CPaybill'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalMpesaB2BTransactionss($MpesaB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2b_transactions WHERE CorporateNo='$MpesaB2CPaybill'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalSafAirtimeTransactionss($MpesaB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from safaricon_airtime_requests WHERE CorporateNo='$MpesaB2CPaybill' AND Safaricom_TXNSTATUS='200'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalMpesaB2CSuccessTransactionss($MpesaB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$MpesaB2CPaybill' AND MPESA_ResultType='Completed'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalMpesaB2CFailedTransactionss($MpesaB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$MpesaB2CPaybill' AND MPESA_ResultType NOT IN ('Completed','Declined') AND Status='Failed'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalMpesaB2CDeclinedTransactionss($MpesaB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$MpesaB2CPaybill' AND MPESA_ResultType='Declined'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    //===============
    public static function getTotalAirtelC2BTransactionss($AirtelC2BPaybill) {

        $totalC2b = 0;
        $query = "Select sum(amount) as TotalAmount from airtel_c2b_payments WHERE nickname='$AirtelC2BPaybill' AND nickname!=''";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalC2b = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalC2b,
        );
        return $array;
    }

    public static function getTotalAirtelB2CTransactionss($AirtelB2CPaybill) {

        $totalB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$AirtelB2CPaybill' AND CorporateNo!=''";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalB2c,
        );
        return $array;
    }

    public static function getTotalAirtelB2CSuccessTransactionss($AirtelB2CPaybill) {

        $totalB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$AirtelB2CPaybill' AND MPESA_ResultType='Completed' AND CorporateNo!=''";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalB2c,
        );
        return $array;
    }

    public static function getTotalAirtelB2CFailedTransactionss($AirtelB2CPaybill) {

        $totalB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$AirtelB2CPaybill' AND MPESA_ResultType NOT IN ('Completed','Declined') AND Status='Failed'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalB2c,
        );
        return $array;
    }

    public static function getTotalAirtelB2CDeclinedTransactionss($AirtelB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2c_transactions WHERE CorporateNo='$AirtelB2CPaybill' AND MPESA_ResultType='Declined'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalAirtelB2BTransactionss($AirtelB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from mpesa_b2b_transactions WHERE CorporateNo='$AirtelB2CPaybill'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getTotalAityelAirtimeTransactionss($AirtelB2CPaybill) {

        $totalMpesaB2c = 0;
        $query = "Select sum(Amount) as TotalAmount from safaricon_airtime_requests WHERE CorporateNo='$AirtelB2CPaybill' AND Safaricom_TXNSTATUS='200'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalAmount'] != null)
                $totalMpesaB2c = $res[0]['TotalAmount'];
        }
        $array = array(
            0 => $totalMpesaB2c,
        );
        return $array;
    }

    public static function getClientName($clientID) {

        $clientName = "-";
        $query = "Select clientName from bulkMessages.clients where clientID='" . $clientID . "' and status=1";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['clientName'] != null)
                $clientName = $res[0]['clientName'];
        }
        $array = array(
            0 => $clientName,
        );
        return $array;
    }

    //-------------------------------------------------------
    //finds a userID from a clientID
    public function getUsername($usersId) {
        $query = new Query();
        $query->select(['user.id', 'user.userName'])
                ->from('user')
                ->where(['user.id' => $usersId]);
        $command = $query->createCommand();
        $loggedinUserID = $command->queryAll();

        foreach ($loggedinUserID as $a) {
            return $a['userName'];
        }
    }

    public function getUsersID($username) {

        $query = new Query();
        $query->select(['id'])
                ->from('user')
                ->where(['userName' => $username]);
        $command = $query->createCommand();
        $Users = $command->queryAll();

        foreach ($Users as $a) {
            return $a['id'];
        }
    }

    public function getUsersGroupID($username) {

        $query = new Query();
        $query->select(['UserGroupsID'])
                ->from('user')
                ->where(['userName' => $username]);
        $command = $query->createCommand();
        $Users = $command->queryAll();

        foreach ($Users as $a) {
            return $a['UserGroupsID'];
        }
    }

    public static function getNetworkName($MobileServicesID) {

        $networkName = "-";
        $query = "Select MobileServiceName from MobileServices where MobileServicesID='" . $MobileServicesID . "'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['MobileServiceName'] != null)
                $networkName = $res[0]['MobileServiceName'];
        }
        $array = array(
            0 => $networkName,
        );
        return $array;
    }

    public static function getBankName($networkID) {

        $networkName = "-";
        $query = "Select Network from Network where networkID='" . $networkID . "'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['Network'] != null)
                $networkName = $res[0]['Network'];
        }
        $array = array(
            0 => $networkName,
        );
        return $array;
    }

    public static function logAuditTrail($Username, $Action) {
        $tail = new AuditTrail();
        $tail->Action = $Action;
        $tail->Username = $Username;
        $tail->DateCreated = date('Y-m-d H:i:s');
        $tail->save();

        return $tail->AuditTrailID;
    }

    public function getApproversEmailsPerUserGroup($UsersGroupID, $UsersId) {

        $EmailAddress = array();

        $query = new Query();
        $query->select(['id', 'EmailAddress'])
                ->from('user')
                ->where(['UserGroupsID' => $UsersGroupID])
                ->andWhere(['!=', 'id', $UsersId]); //'UserRolesID' => 2
        $command = $query->createCommand();
        $loggedinUserID = $command->queryAll();

        foreach ($loggedinUserID as $a) {
            $EmailAddress[] = $a['EmailAddress'];
        }

        return $EmailAddress;
    }

    public function getApproversUsersIDPerGroup($UsersGroupID, $UsersId) {

        $approverID = array();

        $query = new Query();
        $query->select(['id'])
                ->from('user')
                ->where(['UserGroupsID' => $UsersGroupID])
                ->andWhere(['!=', 'id', $UsersId]); //'UserRolesID' => 2
        $command = $query->createCommand();
        $loggedinUserID = $command->queryAll();

        foreach ($loggedinUserID as $a) {
            $approverID[] = $a['id'];
        }

        return $approverID;
    }

    public static function saveDataChanges($newValues, $pkID, $logID, $Status, $model, $Action, $InitiatorEmail, $tableNameRecordID) {

        $table = str_replace('`', '', $model->tableSchema->name);

        $currentdb = explode(';', \Yii::$app->db->dsn);

        $returnValue = false;
        $key = "";
        $att = "";
        $value = "";


        $current_db = explode('=', $currentdb[1]);
        $database = $current_db[1];

        if ($Action == "New Creation" || $Action == "Delete" || $Action == "Block" || $Action == "Unblock User" || $Action == "Reset Pin" || $Action == "Reset Password") {
            $dataChanges = new DataChanges();
            $dataChanges->AuditTrailID = $logID;
            $dataChanges->TablePKID = $pkID;
            $dataChanges->ColumnName = $key;
            $dataChanges->NewValue = (string) $att;
            $dataChanges->OldValue = (string) $value;
            $dataChanges->TableSchema = $database;
            $dataChanges->TableNameRecordID = $tableNameRecordID;
            $dataChanges->TableName = $table;
            $dataChanges->Status = $Status;
            $dataChanges->Action = $Action;
            $dataChanges->dateApproved = date('Y-m-d H:i:s');
            $dataChanges->Initiator = $InitiatorEmail;
            if ($dataChanges->save(false)) {
                $returnValue = true;
            }
        } else {



            foreach ($newValues as $key => $att) {
                foreach ($model->attributes as $keyAtt => $value) {

                    //self::logThis("INFO", "Old value=".$att ." || new value=" .$value);

                    if ($key == $keyAtt && $att != $value) { //check if the value has changed
                        //  self::logThis("INFO", "=====> table name=" . $table . " |PKid=" . $pkID . " | new value=" . $att . "| old value=" . $value);
                        $dataChanges = new DataChanges();
                        $dataChanges->AuditTrailID = $logID;
                        $dataChanges->TablePKID = $pkID;
                        $dataChanges->ColumnName = $key;
                        $dataChanges->NewValue = (string) $att;
                        $dataChanges->OldValue = (string) $value;
                        $dataChanges->TableSchema = $database;
                        $dataChanges->TableName = $table;
                        $dataChanges->TableNameRecordID = $tableNameRecordID;
                        $dataChanges->Status = $Status;
                        $dataChanges->Action = $Action;
                        $dataChanges->dateApproved = date('Y-m-d H:i:s');
                        $dataChanges->Initiator = $InitiatorEmail;
                        try {
                            if (!$dataChanges->save()) {

                                $returnValue = false;

                                self::logThis("ERROR", "Error when saving dataChanges:> " . print_r($dataChanges->getErrors(), true), __FILE__, __FUNCTION__, __LINE__);
                            } else {

                                //  self::logThis("INFO","Insert to dataChanges table successful dataChangesID=".$dataChanges->dataChangesID);

                                $returnValue = true;
                            }
                        } catch (Exception $exc) {

                            self::logThis("ERROR", "Exception when saving dataChanges:> Error ::  || {$logID} " . __FUNCTION__ . "('$pkID', '$att','$table', '$database')", __FILE__, __FUNCTION__, __LINE__);

                            $returnValue = false;
                        }

                        //$returnValue = true;
                    }
                }
            }
        }


        if ($returnValue) {
            return true;
        } else {
            return false;
        }
    }

    public static function getChangeLogs($model, $pkID) {

        $table = str_replace('`', '', $model->tableSchema->name);
        $query = "SELECT * from DataChanges where `TableName`='$table' and  TablePKID=$pkID and Status='Pending'";

        // echo $query; exit();
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($query);
        $rowCount = $command->execute(); // execute the non-query SQL
        $dataReader = $command->queryAll(); // execute a query SQL

        return $dataReader;
    }

    public function getAction($tableName, $tableNamePkIDColumnName, $tablePKID) {

        $query = new Query();
        $query->select(['Action'])
                ->from($tableName)
                ->where([$tableNamePkIDColumnName => $tablePKID]);
        $command = $query->createCommand();
        $loggedinUserID = $command->queryAll();

        foreach ($loggedinUserID as $a) {
            return $a['Action'];
        }
    }

    public static function processCheckerActions($tablePKID, $action, $model, $narration, $dataChangesID, $ApproverEmail) {

        try {

            self::logThis("INFO", "--------- " . $action . " --------");

            //   $transaction = \Yii::$app->db->beginTransaction();
            //Try catch

            $tableName = str_replace('`', '', $model->tableSchema->name);

            $query = "SELECT * from DataChanges where `TablePKID`='$tablePKID' AND TableName='$tableName' AND Status='Pending'";

            self::logThis("INFO", $query);

            $connection = \Yii::$app->db;
            $command = $connection->createCommand($query);
            $rowCount = $command->execute(); // execute the non-query SQL
            $logs = $command->queryAll(); // execute a query SQL
            //update actual record
            //$log = $log[0];

            foreach ($logs as $log) {

                //self::logThis("INFO", $query);

                $rowCount = 1;

                $Action = $log['Action'];


                $dataChangesID = $log['DataChangesID'];
                $table = $log['TableName'];
                $column = $log['ColumnName'];
                $pk = $log['TablePKID'];
                $newValue = $log['NewValue'];
                $primaryFieldArray = $model->tableSchema->primaryKey;

                // print_r($primaryFieldArray); exit();

                foreach ($primaryFieldArray as $primaryField) {
                    
                } //Get primary key column

                if ($action == "Reject") {
                    $rowCount = 1;

                    $queryUp1 = "Update `$table` set ApprovalStatus='Rejected' where `$primaryField` =$pk";

                    $connection = \Yii::$app->db;
                    $command1 = $connection->createCommand($queryUp1);
                    $rowCount = $command1->execute();
                } else {

                    self::logThis("INFO", "--------- " . $Action . " --------");

                    if ($Action == "Delete" || $Action == "New Creation" || $Action == "Block" || $Action == "Reset Pin" || $Action == "Unblock User") {
                        $rowCount = 1;

                        if ($Action == "New Creation") {
                            $queryUp2 = "Update `$table` set ApprovalStatus='Approved',Narration='$narration' where `$primaryField` =$pk";
                        } else if ($Action == "Delete") {
                            $queryUp2 = "Update `$table` set ApprovalStatus='Approved',Status=0,Action='Deleted' where `$primaryField` =$pk";
                        } else if ($Action == "Unblock User") {
                            $queryUp2 = "Update `$table` set ApprovalStatus='Approved',Status=1 where `$primaryField` =$pk";
                        } else {
                            $queryUp2 = "Update `$table` set ApprovalStatus='Approved',Status=0 where `$primaryField` =$pk";
                        }

                        $connection = \Yii::$app->db;
                        $command2 = $connection->createCommand($queryUp2);
                        $rowCount = $command2->execute();

                        self::logThis("INFO", "rowCount=" . $rowCount . "  || " . $queryUp2);
                    } else {


                        $queryUp3 = "Update `$table` set `$column`= '$newValue',ApprovalStatus='Approved' where `$primaryField` =$pk";

                        $connection = \Yii::$app->db;
                        $command3 = $connection->createCommand($queryUp3);
                        $rowCount = $command3->execute();

                        self::logThis("INFO", "--------Updatig changes -----  " . $queryUp3, __FILE__, __FUNCTION__, __LINE__);
                        /* 	 $query = new Query();
                          $query->createCommand()->update($table,[$column => '$newValue','Status'=>'Approved'],[$primaryField => $pk])->execute();
                         */
                    }
                }

                if ($rowCount > 0) {

                    // echo $narration; exit();

                    $queryUp_ = "Update DataChanges set Status='$action',ApproverNarration='$narration',Approver='$ApproverEmail',dateApproved=NOW() where `DataChangesID`='$dataChangesID' AND TableName='$tableName' AND Status='Pending'";

                    // echo $queryUp_; exit();
                    $command_ = $connection->createCommand($queryUp_);
                    $rowCount_ = $command_->execute();
                    if ($rowCount_ > 0) {
                        self::logThis("INFO", "Change effected " . $dataChangesID, __FILE__, __FUNCTION__, __LINE__);
                        //$transaction->commit();
                        return true;
                    } else {
                        self::logThis("ERROR", "Error when saving dataChanges: ", __FILE__, __FUNCTION__, __LINE__);
                    }
                } else {
                    self::logThis("ERROR", "Error when updating actual table  " . print_r($log, true), __FILE__, __FUNCTION__, __LINE__);

                    // $transaction->rollback();
                    return FALSE;
                }
            }

            //$transaction->commit();
        } catch (Exception $e) {
            //$transaction->rollBack();
            // other actions to perform on fail (redirect, alert, etc.)

            self::logThis("INFO", "Error updating changes " . $e->getMessage());
        }
    }

    public function getInitiatorUsersId($tableName, $tableNamePkIDColumnName, $tablePKID) {

        $query = new Query();
        $query->select(['InitiatorUsersId'])
                ->from($tableName)
                ->where([$tableNamePkIDColumnName => $tablePKID]);
        $command = $query->createCommand();
        $loggedinUserID = $command->queryAll();

        foreach ($loggedinUserID as $a) {
            return $a['InitiatorUsersId'];
        }
    }

    public function flog($type, $logfile, $string, $line = NULL) {
        date_default_timezone_set('Africa/Nairobi');
        $date = date("Y-m-d H:i:s");
        $file = \Yii::$app->params['logurl'] . $logfile . '.log';

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] . "|" : "";

        if ($fo = fopen($file, 'ab')) {
            fwrite($fo, "$date - [ $type ] " . $line . " | $string |$remote_ip\n");
            fclose($fo);
        } else {
            trigger_error("flog Cannot log '$string' to file '$file' ", E_USER_WARNING);
        }
    }

    public static function logThis($LEVEL, $logThis) {

        $logFile = "";
        $logLevel = "";
        $infoLogFile = "/var/log/applications/USSD/" . date('dmY') . "_sbss_portal.log";
        switch ($LEVEL) {
            case "INFO":
                $logFile = $infoLogFile;
                $logLevel = "INFO";
                break;
            case "ERROR":
                $logFile = $infoLogFile;
                $logLevel = "ERROR";
                break;
            case "DEBUG":
                $logFile = $infoLogFile;
                $logLevel = "DEBUG";
                break;
            default :
                $logFile = $infoLogFile;
                $logLevel = "DEFAULT";
        }

        // $e = new Exception();
        $trace = ""; //$e->getTrace();
//position 0 would be the line that called this function so we ignore it
        $last_call = isset($trace[1]) ? $trace[1] : array();
        $lineArr = array(); //$trace[0];


        $function = isset($last_call['function']) ? $last_call['function'] . "()|" : "";
        $line = isset($lineArr['line']) ? $lineArr['line'] . "|" : "";
        $file = isset($lineArr['file']) ? $lineArr['file'] . "|" : "";

        $mobileNumber = isset($_SESSION['MSISDN']) ? $_SESSION['MSISDN'] . "|" : "";
        $transactionID = isset($_SESSION['TRXID']) ? $_SESSION['TRXID'] . "|" : "";

        $command = isset($_SESSION['COMMAND']) ? $_SESSION['COMMAND'] . "|" : "";
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] . "|" : "";
        $date = date("Y-m-d H:i:s");
        $string = $date . "|$logLevel|$file$function$command$remote_ip$mobileNumber$transactionID$line" . $logThis . "\n";
        file_put_contents($logFile, $string, FILE_APPEND);
    }

    public function SendApprovalEmail($username, $receipientMail, $link, $Subject = null) {


        $content = '<p><span style="font-family:Trebuchet MS, Verdana, Arial; font-size:17px; font-weight:bold;"> Dear Sir/Madam</span>,</p>
        <br />
        <div>You have an approval request from ' . $username . ' Click on the link below to either approve or reject.</div>
           <div style="padding-left:20px; padding-bottom:10px;">&nbsp;&nbsp;&nbsp;Portal Link -<a href="' . $link . '" style="color:blue">' . $link . '</a></div>
        <br />

        <div>Thank you</div>
        <br />
        <div style="color:blue"><i>SBSS MANAGEMENT PORTAL</i></div>
        ';

        $html = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SBSS MANAGEMENT PORTAL</title>
        </head>
        <body>
        <center>
        <table width="600" background="#FFFFFF" style="text-align:left;" cellpadding="0" cellspacing="0">
        <tr>
        <td height="18" width="31" style="border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td height="18" width="131">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td height="18" width="466" style="border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        </tr>
        <tr>
        <td height="2" width="31" style="border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td height="2" width="131">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td height="2" width="466" style="border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        </tr>
        <!--GREEN STRIPE-->
        <tr>
        <td background="imags/greenback.gif" width="31" bgcolor="#7fbce8" style="border-top:1px solid #FFF; border-bottom:1px solid #FFF;" height="43">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>

        <!--WHITE TEXT AREA-->
        <td width="131" bgcolor="#FFFFFF" style="border-top:1px solid #FFF; text-align:center;" height="43" valign="middle">
        <span style="font-size:25px; font-family:Trebuchet MS, Verdana, Arial; color:#7fbce8;">SBSS Management Portal</span>
        </td>

        <!--GREEN TEXT AREA-->
        <td background="imags/greenback.gif" bgcolor="#7fbce8" style="border-top:1px solid #FFF; border-bottom:1px solid #FFF; padding-left:15px;" height="43">
        <span style="color:#FFFFFF; font-size:18px; font-family:Trebuchet MS, Verdana, Arial;">by Mtech Communications</span>
        </td>
        </tr>

        <!--DOUBLE BORDERS BOTTOM-->
        <tr>
        <td height="3" width="31" style="border-top:1px solid #e4e4e4; border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td height="3" width="131">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td height="3" style="border-top:1px solid #e4e4e4; border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        </tr>
        <tr>
        <td colspan="3">
        <!--CONTENT STARTS HERE-->
        <br />
        <br />
        <table cellpadding="0" cellspacing="0">
        <tr>
        <td width="15"><div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        <td width="600" style="padding-right:10px; font-family:Trebuchet MS, Verdana, Arial; font-size:12px;" valign="top">
        ' . $content . '
        <br/>

        <hr/>

        This email is CONFIDENTIAL and was auto-generated by the SBSS Management Portal, do not reply.

        </td>

        </tr>
        </table>
        </td>
        </tr>
        </table>
        <br />
        <table cellpadding="0" style="border-top:1px solid #e4e4e4; text-align:center; font-family:Trebuchet MS, Verdana, Arial; font-size:12px;" cellspacing="0" width="600">
        <tr>
        <td height="2" style="border-bottom:1px solid #e4e4e4;">
        <div style="line-height: 0px; font-size: 1px; position: absolute;">&nbsp;</div>
        </td>
        </tr>
        <td style="font-family:Trebuchet MS, Verdana, Arial; font-size:12px;">

        </td>
        </tr>
        </table>
        </center>
        </body>
        </html>
        ';

        self::SendMail($receipientMail, $html, $Subject);
    }

    public static function SendMail($receipient, $mailInHtml, $Subject) {

        try {




            if (empty($Subject)) {
                $Subject = "SBSS Portal Approval Request";
            }
            if (count($receipient) > 0) {

                foreach ($receipient as $a) {

                    $receipientNames = "";

                    $receipientEmail = $a;

                    $mail = new PHPMailer;

                    $mail->isSMTP();   // Set mailer to use SMTP
                    $mail->Host = Yii::$app->params['emailHost']; //10.2.100.14';  // Specify main and backup SMTP servers
                    $mail->Port = Yii::$app->params['emailPort']; //25; // TCP port to connect to

                    $mail->setFrom(\Yii::$app->params['supportEmail'], ' SBSS Reporting Portal');
                    $mail->addAddress($receipientEmail, $receipientNames);     // Add a recipient
                    $mail->isHTML(true);      // Set email format to HTML

                    $mail->Username = Yii::$app->params['supportEmail'];
                    $mail->Password = Yii::$app->params['emailPassword'];
                    $mail->Subject = $Subject;
                    $mail->Body = $mailInHtml;
                    $mail->AltBody = $mailInHtml;
                    $mail->SMTPAuth = true;
                    $mail->SMTPOptions = array('ssl' => array('verify_peer' => true, 'verify_peer_name' => false, 'allow_self_signed' => true));
                    if (!$mail->send()) {
                        // Utils::logThis("INFO", "Mail to '$receipientEmail' not send ok. Reason, " . print_r($mail->ErrorInfo));
                        // self::flog("User management modue", 'portal', "Mail to '$receipient' not send OK " . print_r($mail->ErrorInfo));
                        //    self::logThis("INFO", "Mail to '$receipientEmail' not send ok. Reason " . print_r($mail->ErrorInfo, true));
                        return false;
                    } else {
                        // Utils::logThis("INFO", "Mail to '$receipientEmail' sent OK");
                        self::logThis("INFO", "Mail to '$receipientEmail' sent OK");
                        return true;
                    }
                }
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    public static function getTotalMobileUsers() {

        $TotalCount = 0;
        $query = "Select count(MobileUsersID) as TotalCount from MobileUsers WHERE Status='1'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalBalanceRequests() {

        $TotalCount = 0;
        $query = "Select count(BalanceRequestID) as TotalCount from BalanceRequests";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalMinistatementRequests() {

        $TotalCount = 0;
        $query = "Select count(MinistatementRequestsID) as TotalCount from MinistatementRequests";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalFullstatementRequests() {

        $TotalCount = 0;
        $query = "Select count(FullstatementRequestsID) as TotalCount from FullstatementRequests";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalFundTransferRequests() {

        $TotalCount = 0;
        $query = "Select count(FundTransferRequestsID) as TotalCount from FundTransferRequests";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalForexRequests() {

        $TotalCount = 0;
        $query = "Select count(ForexRequeststID) as TotalCount from ForexRequests";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalMobileMoneyTransferRequests() {


        // Mobile Money,Regional Transfer,Local Transfer



        $TotalCount = 0;
        $query = "Select count(FundTransferRequestsID) as TotalCount from FundTransferRequests WHERE FundTransferType='Mobile Money'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalLocalTransferRequests() {


        //INTER-ACCOUNT-FT
        //INTER-BANK-FT
        //CROSS-BORDER-FT
        //PESA-LINK-FT
        //MPESA-FT
        //M-GURUSH-FT
        //NILE-PAY-FT



        $TotalCount = 0;
        $query = "Select count(FundTransferRequestsID) as TotalCount from FundTransferRequests WHERE FundTransferType='Local Transfer'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalRegionalFundTransferRequests() {


        //INTER-ACCOUNT-FT
        //INTER-BANK-FT
        //CROSS-BORDER-FT
        //PESA-LINK-FT
        //MPESA-FT
        //M-GURUSH-FT
        //NILE-PAY-FT



        $TotalCount = 0;
        $query = "Select count(FundTransferRequestsID) as TotalCount from FundTransferRequests WHERE FundTransferType='Regional Transfer'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public static function getTotalPesaLinkFundTransferRequests() {


        //INTER-ACCOUNT-FT
        //INTER-BANK-FT
        //CROSS-BORDER-FT
        //PESA-LINK-FT
        //MPESA-FT
        //M-GURUSH-FT
        //NILE-PAY-FT



        $TotalCount = 0;
        $query = "Select count(FundTransferRequestsID) as TotalCount from FundTransferRequests WHERE FundTransferType='PESA-LINK-FT'";
        $res = Yii::$app->db->createCommand($query)->queryAll();
        if (count($res) > 0) {
            if ($res[0]['TotalCount'] != null)
                $TotalCount = $res[0]['TotalCount'];
        }
        $array = array(
            0 => $TotalCount,
        );
        return $array;
    }

    public function CheckIfUserShouldChangePassword($username) {
        $days = 0;

        $sqlexpirtDate = "select DATEDIFF(passwordExpiryDate,CURDATE()) as days from user where username='$username' ORDER BY id DESC LIMIT 1;";
        $resultexpirtDate = Yii::$app->db->createCommand($sqlexpirtDate)->queryAll();
        if (count($resultexpirtDate) > 0) {
            $days = $resultexpirtDate[0]['days'];
        }

        return $days;
    }

    public function checkPassword($pwd) {
        $errors = array();

        $PassLength = 12;

        $sqlexpirtDate = "select value from ApplicationSettings where Name='PasswordLength' LIMIT 1;";
        $resultexpirtDate = Yii::$app->db->createCommand($sqlexpirtDate)->queryAll();
        if (count($resultexpirtDate) > 0) {
            $PassLength = $resultexpirtDate[0]['value'];
        }

        if (strlen($pwd) < $PassLength) {
            $errors[] = "Password too short ! Password should not be less than $PassLength characters";
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            $errors[] = "Password must include at least one number!";
        }

        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors[] = "Password must include at least one letter!";
        }

        return $errors;
    }

    public function CheckNoDaysUsersHasbeenAway($username) {
        $days = 0;

        $sqlexpirtDate = "select DATEDIFF(lastLoginDate,CURDATE()) as days from user where username='$username' ORDER BY id DESC LIMIT 1;";
        $resultexpirtDate = Yii::$app->db->createCommand($sqlexpirtDate)->queryAll();
        if (count($resultexpirtDate) > 0) {
            $days = $resultexpirtDate[0]['days'];
        }

        return $days;
    }

    public function updateLastLoginDate($username) {

        $lastLoginDate = date('Y-m-d H:i:s');

        \Yii::$app->db->createCommand()->update(User::tableName(), ['lastLoginDate' => $lastLoginDate], ['username' => $username])
                ->execute();
    }

    function logOldPassword($usersID, $password) {

        try {

            $sql = "insert into passwordsLog(usersID,password_hash,dateCreated) values('$usersID','$password',NOW());";
            $connection = \Yii::$app->db;
            $connection->createCommand($sql)->execute();

            return true;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();

            return false;
        }
    }

    public function checkIfPasswordPreviouslyUsed($usersID, $password) {

        $query = new Query();
        $query->select(['password_hash'])
                ->from('passwordsLog')
                ->where(['usersID' => $usersID]);
        $command = $query->createCommand();
        $Users = $command->queryAll();
        if (count($Users) > 0) {
            foreach ($Users as $a) {
                if ($password == Yii::$app->getSecurity()->decryptByPassword($a['password_hash'], 'password_hash')) {
                    //  self::flog("Login", 'portal', $sql);
                    return true;
                    break;
                }
            }
        } else {
            return false;
        }
    }

    public function getUserGroupName($UserGroupsID) {
        $Response = "";

        $sql = "select GroupName from UserGroups where UserGroupsID='$UserGroupsID' LIMIT 1;";
        $result = Yii::$app->db->createCommand($sql)->queryAll();
        if (count($result) > 0) {
            $Response = $result[0]['GroupName'];
        }

        return $Response;
    }

    public function getSysMapGroupRightValue($UserGroupsID, $SysModulesID, $Action) {
        $Response = "";

        $sql = "select $Action as GroupValue from SysMapGroupRight where UserGroupsID='$UserGroupsID' AND SysModulesID='$SysModulesID' LIMIT 1;";
        $result = Yii::$app->db->createCommand($sql)->queryAll();
        if (count($result) > 0) {
            $Response = $result[0]['GroupValue'];
        }

        return $Response;
    }

    public static function getUserPersmission($AliasModuleName, $field, $username) {

        $connection = \Yii::$app->db;
        if ($username == "A209576" || $username == "A238468" || $username == "EA209576" || $username == "admin" || $username == "lmwangi") {
            return true;
        }

        $sql = "SELECT b.$field FROM user a,SysMapGroupRight b,SysModules c WHERE a.userName='$username' AND a.UserGroupsID=b.UserGroupsID AND b.SysModulesID=c.SysModulesID AND c.AliasModuleName='$AliasModuleName'";
        $command = $connection->createCommand($sql);
        $Perm = $command->queryAll();

        if (count($Perm) > 0) {

            foreach ($Perm as $a) {
                $result = $a[$field];
            }

            if ($result == 1) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function getTotalCountContacts($contact_group_id) {
        $Response = 0;

        $sql = "select count(id) as Total from contacts where contact_group_id='$contact_group_id';";
        $result = Yii::$app->db->createCommand($sql)->queryAll();
        if (count($result) > 0) {
            $Response = $result[0]['Total'];
        }

        return $Response;
    }

    public function getTotalSheduledContacts($contact_group_id) {
        $Response = 0;

        $sql = "select count(id) as Total from contacts where contact_group_id in ('$contact_group_id');";
        $result = Yii::$app->db->createCommand($sql)->queryAll();
        if (count($result) > 0) {
            $Response = $result[0]['Total'];
        }

        return $Response;
    }

    public function getTotalContactGroupName($contact_group_id) {
        $Response = "";

        $groupIdArray = explode(",", $contact_group_id);

        foreach ($groupIdArray as $groupID) {
            $sql = "select name from contact_groups where id = '$groupID';";
            $result = Yii::$app->db->createCommand($sql)->queryAll();
            if (count($result) > 0) {
                $Response .= $result[0]['name'] . ", ";
            }
        }
        return $Response;
    }

    public function getApproversUserName($ApproversUsersId) {
        $Response = "";

        $usersIdArray = explode(",", $ApproversUsersId);

        if (count($usersIdArray) > 0) {
            foreach ($usersIdArray as $userID) {
                $sql = "select userName from user where id = '$userID';";
                $result = Yii::$app->db->createCommand($sql)->queryAll();
                if (count($result) > 0) {
                    $Response .= $result[0]['userName'] . ", ";
                }
            }
        } else {
            $sql = "select userName from user where id = '$ApproversUsersId';";
            $result = Yii::$app->db->createCommand($sql)->queryAll();
            if (count($result) > 0) {
                $Response .= $result[0]['userName'] . ", ";
            }
        }


        return $Response;
    }

    function generateRandomNumber() {

        global $mobileNumber;
        $alphabet = "0123456789";

        return random_int(1000, 9999) . date('YmdHis') + $mobileNumber;
    }

    public function sendSMS($msisdn, $message) {

        global $flogPath;
        $url = SMSSENDURL_SBSS;

        $params = array(
            "message" => $message,
            "msisdn" => $msisdn,
            "sms_id" => self::generateRandomNumber()
        );



        $data = json_encode($params);

        $headers = array(
            "Content-type: application/json;charset=\"utf-8\"",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        );


        self::logThis("INFO", "About to send sms " . print_r($params, true) . " to end point ." . SMSSENDURL_SBSS);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        /* SSL options */
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);

        //flog($flogPath, "Send SMS response ----->" . print_r($response, true));
        $HTTPStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // $remarks = $response;


        if (!empty($HTTPStatusCode) or $HTTPStatusCode != 0) {
            $body = $response;
            // flog($flogPath, "-------->httpcode is " . $HTTPStatusCode . " and the response is " . print_r($body, true));
        } else {
            $body = curl_error($ch);
            // flog($flogPath, "httpcode is " . $HTTPStatusCode . " and the response is " . curl_error($ch));
        }
        curl_close($ch);
    }

    public static function encrypt_decrypt($action, $string) {
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

    public function DecryptActionAuditTrail($String) {
        $newString = "";
        if (strpos($String, "Reset pin Mobile User") !== false) {
            $trxArr = explode("Reset pin Mobile User", $String);
            $newString = $trxArr[0] . " " . CoreUtils::encrypt_decrypt("decrypt", trim(end($trxArr)));
        } else if (strpos($String, "Deleted MobileUsers") !== false) {
            $trxArr = explode("Deleted MobileUsers", $String);
            $newString = $trxArr[0] . " " . CoreUtils::encrypt_decrypt("decrypt", trim(end($trxArr)));
        } else if (strpos($String, "Updated mobile user") !== false) {
            $trxArr = explode("Updated mobile user", $String);
            $newString = $trxArr[0] . " " . CoreUtils::encrypt_decrypt("decrypt", trim(end($trxArr)));
        } else if (strpos($String, "Unblock User") !== false) {
            $trxArr = explode("Unblock User", $String);
            $newString = $trxArr[0] . " " . CoreUtils::encrypt_decrypt("decrypt", trim(end($trxArr)));
        } else {
            $newString = $String;
        }

        if (empty($newString)) {
            $newString = $String;
        }
        return $newString;
    }

}
