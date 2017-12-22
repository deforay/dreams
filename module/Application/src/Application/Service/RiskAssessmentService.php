<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use PHPExcel;


class RiskAssessmentService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function getOccupationTypes(){
        $occupationTypeDb = $this->sm->get('OccupationTypeTable');
        return $occupationTypeDb->fetchOccupationTypes();
    }
    
    public function addRiskAssessment($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
           $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
           $result = $clinicRiskAssessmentDb->addRiskAssessmentDetails($params);
           if($result>0){
            $adapter->commit();
              $alertContainer->msg = 'Risk Assessment form submitted successfully';
           }else{
              $alertContainer->msg = 'OOPS..';
           }
        }
        catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
        }
    }
    
    public function getAllRiskAssessment($parameters){
        $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
        return $clinicRiskAssessmentDb->fetchAllRiskAssessment($parameters);
    }
    
    public function getRiskAssessment($riskAssessmentId){
        $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
        return $clinicRiskAssessmentDb->fetchRiskAssessment($riskAssessmentId);
    }
    
     public function updateRiskAssessment($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
           $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
           $result = $clinicRiskAssessmentDb->updateRiskAssessmentDetails($params);
           if($result>0){
            $adapter->commit();
              $alertContainer->msg = 'Risk Assessment form updated successfully';
           }else{
              $alertContainer->msg = 'OOPS..';
           }
        }
        catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
        }
    }
    
    public function exportRiskAssessmentInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->riskAssessmentQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->riskAssessmentQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $keyArray = array('0'=>'','1'=>'Husband - 1','2'=>'Ex-Husband - 2','3'=>'Boyfriend - 3','4'=>'Stranger - 4','88'=>'Don\'t Know - 88','99'=>'Refused - 99','2222'=>'Response Not Available - 2222');
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $interviewDate = '';
                        if(isset($aRow['interview_date']) && $aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
                            $interviewDate = $common->humanDateFormat($aRow['interview_date']);
                        }
                        //patient occupation
                        $occupation = (isset($aRow['occupationName']))?ucwords($aRow['occupationName']).' - '.$aRow['occupation_code']:'';
                        //patient schooling details
                        $hasPatientEverAttendedSchool = '';
                        if($aRow['has_patient_ever_attended_school']!= null && trim($aRow['has_patient_ever_attended_school'])!= ''){
                            if($aRow['has_patient_ever_attended_school'] == 1){
                                $hasPatientEverAttendedSchool = "Yes - 1";
                            }else if($aRow['has_patient_ever_attended_school'] == 2){
                                $hasPatientEverAttendedSchool = "No - 2";
                            }else if($aRow['has_patient_ever_attended_school'] == 88){
                                $hasPatientEverAttendedSchool = "Don't Know - 88";
                            }else if($aRow['has_patient_ever_attended_school'] == 99){
                                $hasPatientEverAttendedSchool = "Refused - 99";
                            }else if($aRow['has_patient_ever_attended_school'] == 2222){
                                $hasPatientEverAttendedSchool = "Response Not Available - 2222";
                            }
                        }
                        $degree = '';
                        if($aRow['patient_degree']!= null && trim($aRow['patient_degree'])!= ''){
                            if($aRow['patient_degree'] == 1){
                                $degree = "Primary/Vocational - 1";
                            }else if($aRow['patient_degree'] == 2){
                                $degree = "Secondary - 2";
                            }else if($aRow['patient_degree'] == 3){
                                $degree = "University/College - 3";
                            }else if($aRow['patient_degree'] == 88){
                                $degree = "Don't know - 88";
                            }else if($aRow['patient_degree'] == 99){
                                $degree = "Refused - 99";
                            }else if($aRow['patient_degree'] == 2222){
                                $degree = "Response Not Available - 2222";
                            }else{
                                $degree = ucwords($aRow['patient_degree']);
                            }
                        }
                        //marital status
                        $patientEverBeenMarried = '';
                        if($aRow['patient_ever_been_married']!= null && trim($aRow['patient_ever_been_married'])!= ''){
                            if($aRow['patient_ever_been_married'] == 1){
                                $patientEverBeenMarried = "Yes - 1";
                            }else if($aRow['patient_ever_been_married'] == 2){
                                $patientEverBeenMarried = "No - 2";
                            }else if($$aRow['patient_ever_been_married'] == 88){
                                $patientEverBeenMarried = "Don't Know - 88";
                            }else if($aRow['patient_ever_been_married'] == 99){
                                $patientEverBeenMarried = "Refused - 99";
                            }else if($aRow['patient_ever_been_married'] == 2222){
                                $patientEverBeenMarried = "Response Not Available - 2222";
                            }
                        }
                        $ageAtFirstMarriage = '';
                        if($aRow['age_at_first_marriage']!= null && trim($aRow['age_at_first_marriage'])!= ''){
                            if($aRow['age_at_first_marriage'][0] == '@'){
                                $ageAtFirstMarriage = substr($aRow['age_at_first_marriage'],1).' Year(s)';
                            }else if($aRow['age_at_first_marriage'] == 88){
                                $ageAtFirstMarriage = "Don't Know - 88";
                            }else if($aRow['age_at_first_marriage'] == 99){
                                $ageAtFirstMarriage = "Refused - 99";
                            }else if($aRow['age_at_first_marriage'] == 2222){
                                $ageAtFirstMarriage = "Response Not Available - 2222";
                            }else{
                                $ageAtFirstMarriage = ucwords($aRow['age_at_first_marriage']);
                            }
                        }
                        $patientEverBeenWidowed = '';
                        if($aRow['patient_ever_been_widowed']!= null && trim($aRow['patient_ever_been_widowed'])!= ''){
                            if($aRow['patient_ever_been_widowed'] == 1){
                                $patientEverBeenWidowed = "Yes - 1";
                            }else if($aRow['patient_ever_been_widowed'] == 2){
                                $patientEverBeenWidowed = "No - 2";
                            }else if($aRow['patient_ever_been_widowed'] == 88){
                                $patientEverBeenWidowed = "Don't Know - 88";
                            }else if($aRow['patient_ever_been_widowed'] == 99){
                                $patientEverBeenWidowed = "Refused - 99";
                            }else if($aRow['patient_ever_been_widowed'] == 2222){
                                $patientEverBeenWidowed = "Response Not Available - 2222";
                            }else{
                                $patientEverBeenWidowed = ucwords($aRow['patient_ever_been_widowed']);
                            }
                        }
                        $maritalStatus = '';
                        if($aRow['current_marital_status']!= null && trim($aRow['current_marital_status'])!= ''){
                            if($aRow['current_marital_status'] == 1){
                                $maritalStatus = "Married/Cohabiting - 1";
                            }else if($aRow['current_marital_status'] == 2){
                                $maritalStatus = "Never Married/Cohabiting - 2";
                            }else if($aRow['current_marital_status'] == 3){
                                $maritalStatus = "Widowed - 3";
                            }else if($aRow['current_marital_status'] == 4){
                                $maritalStatus = "Separated - 4";
                            }else if($aRow['current_marital_status'] == 5){
                                $maritalStatus = "Divorced - 5";
                            }else if($aRow['current_marital_status'] == 88){
                                $maritalStatus = "Don't Know - 88";
                            }else if($aRow['current_marital_status'] == 99){
                                $maritalStatus = "Refused - 99";
                            }else if($aRow['current_marital_status'] == 2222){
                                $maritalStatus = "Response Not Available - 2222";
                            }else{
                                $maritalStatus = ucwords($aRow['current_marital_status']);
                            }
                        }
                        //patient HIV test result
                        $hasPatientEverBeenTestedforHIV = '';
                        if($aRow['has_patient_ever_been_tested_for_HIV']!= null && trim($aRow['has_patient_ever_been_tested_for_HIV'])!= ''){
                            if($aRow['has_patient_ever_been_tested_for_HIV'] == 1){
                                $hasPatientEverBeenTestedforHIV = "Yes - 1";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 2){
                                $hasPatientEverBeenTestedforHIV = "No - 2";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 88){
                                $hasPatientEverBeenTestedforHIV = "Don't Know - 88";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 99){
                                $hasPatientEverBeenTestedforHIV = "Refused - 99";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 2222){
                                $hasPatientEverBeenTestedforHIV = "Response Not Available - 2222";
                            }
                        }
                        $timeofMostRecentHIVTest = '';
                        if($aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= ''){
                            if($aRow['time_of_last_HIV_test'] == 1){
                                $timeofMostRecentHIVTest = "< 3 Months Ago - 1";
                            }else if($aRow['time_of_last_HIV_test'] == 2){
                                $timeofMostRecentHIVTest = "3-6 Months Ago - 2";
                            }else if($aRow['time_of_last_HIV_test'] == 3){
                                $timeofMostRecentHIVTest = "7-12 Months Ago - 3";
                            }else if($aRow['time_of_last_HIV_test'] == 4){
                                $timeofMostRecentHIVTest = "> 12 months - 4";
                            }else if($aRow['time_of_last_HIV_test'] == 88){
                                $timeofMostRecentHIVTest = "Don't Know - 88";
                            }else if($aRow['time_of_last_HIV_test'] == 99){
                                $timeofMostRecentHIVTest = "Refused - 99";
                            }else if($aRow['time_of_last_HIV_test'] == 2222){
                                $timeofMostRecentHIVTest = "Response Not Available - 2222";
                            }else{
                                $timeofMostRecentHIVTest = ucwords($aRow['time_of_last_HIV_test']);
                            }
                        }
                        $resultofMostRecentHIVTest = '';
                        if($aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= ''){
                            if($aRow['last_HIV_test_status'] == 1){
                                $resultofMostRecentHIVTest = "I Did Not Receive Result - 1";
                            }else if($aRow['last_HIV_test_status']== 2){
                                $resultofMostRecentHIVTest = "HIV Positive - 2";
                            }else if($aRow['last_HIV_test_status'] == 3){
                                $resultofMostRecentHIVTest = "HIV Negative - 3";
                            }else if($aRow['last_HIV_test_status'] == 4){
                                $resultofMostRecentHIVTest = "Indeterminate - 4";
                            }else if($aRow['last_HIV_test_status'] == 88){
                                $resultofMostRecentHIVTest = "Don't Know - 88";
                            }else if($aRow['last_HIV_test_status'] == 99){
                                $resultofMostRecentHIVTest = "Refused - 99";
                            }else if($aRow['last_HIV_test_status'] == 2222){
                                $resultofMostRecentHIVTest = "Response Not Available - 2222";
                            }else{
                                $resultofMostRecentHIVTest = ucwords($aRow['last_HIV_test_status']);
                            }
                        }
                        //patient sexual activity/sexual transmitted infections
                        $ageAtVeryFirstSex = '';
                        if($aRow['age_at_very_first_sex']!= null && trim($aRow['age_at_very_first_sex'])!= ''){
                            if($aRow['age_at_very_first_sex'][0] == '@'){
                                $ageAtVeryFirstSex = substr($aRow['age_at_very_first_sex'],1).' Year(s)';
                            }else if($aRow['age_at_very_first_sex'] == 88){
                                $ageAtVeryFirstSex = "Don't Know - 88";
                            }else if($aRow['age_at_very_first_sex'] == 99){
                                $ageAtVeryFirstSex = "Refused - 99";
                            }else if($aRow['age_at_very_first_sex'] == 2222){
                                $ageAtVeryFirstSex = "Response Not Available - 2222";
                            }
                        }
                        $reasonforVeryFirstSex = '';
                        if($aRow['reason_for_very_first_sex']!= null && trim($aRow['reason_for_very_first_sex'])!= ''){
                            if($aRow['reason_for_very_first_sex'] == 1){
                                $reasonforVeryFirstSex = "Wanted To - 1";
                            }else if($aRow['reason_for_very_first_sex'] == 2){
                                $reasonforVeryFirstSex = "Forced To - 2";
                            }else if($aRow['reason_for_very_first_sex'] == 88){
                                $reasonforVeryFirstSex = "Don't Know - 88";
                            }else if($aRow['reason_for_very_first_sex'] == 99){
                                $reasonforVeryFirstSex = "Refused - 99";
                            }else if($aRow['reason_for_very_first_sex'] == 2222){
                                $reasonforVeryFirstSex = "Response Not Available - 2222";
                            }
                        }
                        $totalNoofSexualPartners = '';
                        if($aRow['no_of_sexual_partners']!= null && trim($aRow['no_of_sexual_partners'])!= ''){
                            if($aRow['no_of_sexual_partners'][0] == '@'){
                                $totalNoofSexualPartners = substr($aRow['no_of_sexual_partners'],1).' Person(s)';
                            }else if($aRow['no_of_sexual_partners'] == 88){
                                $totalNoofSexualPartners = "Don't Know - 88";
                            }else if($aRow['no_of_sexual_partners'] == 99){
                                $totalNoofSexualPartners = "Refused - 99";
                            }else if($aRow['no_of_sexual_partners'] == 2222){
                                $totalNoofSexualPartners = "Response Not Available - 2222";
                            }
                        }
                        $noofSexualPartnersinLastSixMonths = '';
                        if($aRow['no_of_sexual_partners_in_last_six_months']!= null && trim($aRow['no_of_sexual_partners_in_last_six_months'])!= ''){
                            if($aRow['no_of_sexual_partners_in_last_six_months'][0] == '@'){
                                $noofSexualPartnersinLastSixMonths = substr($aRow['no_of_sexual_partners_in_last_six_months'],1).' Person(s)';
                            }else if($aRow['no_of_sexual_partners_in_last_six_months'] == 88){
                                $noofSexualPartnersinLastSixMonths = "Don't Know - 88";
                            }else if($aRow['no_of_sexual_partners_in_last_six_months'] == 99){
                                $noofSexualPartnersinLastSixMonths = "Refused - 99";
                            }else if($aRow['no_of_sexual_partners_in_last_six_months'] == 2222){
                                $noofSexualPartnersinLastSixMonths = "Response Not Available - 2222";
                            }
                        }
                        $partnerHIVStatus = '';
                        if($aRow['partner_HIV_test_status']!= null && trim($aRow['partner_HIV_test_status'])!= ''){
                            if($aRow['partner_HIV_test_status'] == 1){
                                $partnerHIVStatus = "HIV Positive - 1";
                            }else if($aRow['partner_HIV_test_status'] == 2){
                                $partnerHIVStatus = "HIV Negative - 2";
                            }else if($aRow['partner_HIV_test_status'] == 3){
                                $partnerHIVStatus = "No Main Sexual Partner - 3";
                            }else if($aRow['partner_HIV_test_status'] == 88){
                                $partnerHIVStatus = "Don't Know - 88";
                            }else if($aRow['partner_HIV_test_status'] == 99){
                                $partnerHIVStatus = "Refused - 99";
                            }else if($aRow['partner_HIV_test_status'] == 2222){
                                $partnerHIVStatus = "Response Not Available - 2222";
                            }
                        }
                        $ageofMainSexualpartneratLastBirthday = '';
                        if($aRow['age_of_main_sexual_partner_at_last_birthday']!= null && trim($aRow['age_of_main_sexual_partner_at_last_birthday'])!= ''){
                            if($aRow['age_of_main_sexual_partner_at_last_birthday'][0] == '@'){
                                $ageofMainSexualpartneratLastBirthday = substr($aRow['age_of_main_sexual_partner_at_last_birthday'],1).' Year(s)';
                            }else if($aRow['age_of_main_sexual_partner_at_last_birthday'] == 88){
                                $ageofMainSexualpartneratLastBirthday = "Don't Know - 88";
                            }else if($aRow['age_of_main_sexual_partner_at_last_birthday'] == 99){
                                $ageofMainSexualpartneratLastBirthday = "Refused - 99";
                            }else if($aRow['age_of_main_sexual_partner_at_last_birthday'] == 2222){
                                $ageofMainSexualpartneratLastBirthday = "Response Not Available - 2222";
                            }else{
                                $ageofMainSexualpartneratLastBirthday = ucwords($aRow['age_of_main_sexual_partner_at_last_birthday']);
                            }
                        }
                        $ageDiffofMainSexualPartner = '';
                        if($aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= ''){
                            if($aRow['age_diff_of_main_sexual_partner'] == 1){
                                $ageDiffofMainSexualPartner = "< 5 Years Older - 1";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 2){
                                $ageDiffofMainSexualPartner = "5-10 Years Older - 2";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 3){
                                $ageDiffofMainSexualPartner = ">10 Years Older - 3";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 4){
                                $ageDiffofMainSexualPartner = "Same Age - 4";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 5){
                                $ageDiffofMainSexualPartner = "< 5 Years Younger - 5";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 6){
                                $ageDiffofMainSexualPartner = "5-10 Years Younger - 6";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 7){
                                $ageDiffofMainSexualPartner = ">10 Years Younger - 7";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 88){
                                $ageDiffofMainSexualPartner = "Don't Know - 88";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 99){
                                $ageDiffofMainSexualPartner = "Refused - 99";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 2222){
                                $ageDiffofMainSexualPartner = "Response Not Available - 2222";
                            }else{
                                $ageDiffofMainSexualPartner = ucwords($aRow['age_diff_of_main_sexual_partner']);
                            }
                        }
                        $isPartnerCircumcised = '';
                        if($aRow['is_partner_circumcised']!= null && trim($aRow['is_partner_circumcised'])!= ''){
                            if($aRow['is_partner_circumcised'] == 1){
                                $isPartnerCircumcised = "Yes - 1";
                            }else if($aRow['is_partner_circumcised'] == 2){
                                $isPartnerCircumcised = "No - 2";
                            }else if($aRow['is_partner_circumcised'] == 88){
                                $isPartnerCircumcised = "Don't Know - 88";
                            }else if($aRow['is_partner_circumcised'] == 99){
                                $isPartnerCircumcised = "Refused - 99";
                            }else if($aRow['is_partner_circumcised'] == 2222){
                                $isPartnerCircumcised = "Response Not Available - 2222";
                            }else{
                                $isPartnerCircumcised = ucwords($aRow['is_partner_circumcised']);
                            }
                        }
                        $circumcision = '';
                        if($aRow['circumcision']!= null && trim($aRow['circumcision'])!= ''){
                            if($aRow['circumcision'] == 1){
                                $circumcision = "Medical Circumcision - 1";
                            }else if($aRow['circumcision'] == 2){
                                $circumcision = "Traditional Circumcision - 2";
                            }else if($aRow['circumcision'] == 88){
                                $circumcision = "Don't Know - 88";
                            }else if($aRow['circumcision'] == 99){
                                $circumcision = "Refused - 99";
                            }else if($aRow['circumcision'] == 2222){
                                $circumcision = "Response Not Available - 2222";
                            }else{
                                $circumcision = ucwords($aRow['circumcision']);
                            }
                        }
                        $hasPatientEverReceivedGiftforSex = '';
                        if($aRow['has_patient_ever_received_gift_for_sex']!= null && trim($aRow['has_patient_ever_received_gift_for_sex'])!= '' && $aRow['has_patient_ever_received_gift_for_sex']!= 'not applicable'){
                            if($aRow['has_patient_ever_received_gift_for_sex'] == 1){
                                $hasPatientEverReceivedGiftforSex = "Yes - 1";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 2){
                                $hasPatientEverReceivedGiftforSex = "No - 2";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 88){
                                $hasPatientEverReceivedGiftforSex = "Don't Know - 88";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 99){
                                $hasPatientEverReceivedGiftforSex = "Refused - 99";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 2222){
                                $hasPatientEverReceivedGiftforSex = "Response Not Available - 2222";
                            }else{
                                $hasPatientEverReceivedGiftforSex = ucwords($aRow['has_patient_ever_received_gift_for_sex']);
                            }
                        }
                        $mostRecentTimeofReceivingGiftforSex = '';
                        if($aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= ''){
                            if($aRow['last_time_of_receiving_gift_for_sex'] == 1){
                                $mostRecentTimeofReceivingGiftforSex = "< 6 Months Ago - 1";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 2){
                                $mostRecentTimeofReceivingGiftforSex = "6-12 Months Ago - 2";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 3){
                                $mostRecentTimeofReceivingGiftforSex = "> 12 Months Ago - 3";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 88){
                                $mostRecentTimeofReceivingGiftforSex = "Don't Know - 88";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 99){
                                $mostRecentTimeofReceivingGiftforSex = "Refused - 99";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 2222){
                                $mostRecentTimeofReceivingGiftforSex = "Response Not Available - 2222";
                            }else{
                                $mostRecentTimeofReceivingGiftforSex = ucwords($aRow['last_time_of_receiving_gift_for_sex']);
                            }
                        }
                        $noofTimesBeenPregnant = '';
                        if($aRow['no_of_times_been_pregnant']!= null && trim($aRow['no_of_times_been_pregnant'])!= '' && $aRow['no_of_times_been_pregnant']!= 'not applicable'){
                            if($aRow['no_of_times_been_pregnant'][0] == '@'){
                                $noofTimesBeenPregnant = substr($aRow['no_of_times_been_pregnant'],1).' Time(s)';
                            }else if($aRow['no_of_times_been_pregnant'] == 88){
                                $noofTimesBeenPregnant = "Don't Know - 88";
                            }else if($aRow['no_of_times_been_pregnant'] == 99){
                                $noofTimesBeenPregnant = "Refused - 99";
                            }else if($aRow['no_of_times_been_pregnant'] == 2222){
                                $noofTimesBeenPregnant = "Response Not Available - 2222";
                            }else{
                                $noofTimesBeenPregnant = ucwords($aRow['no_of_times_been_pregnant']);
                            }
                        }
                        $noofTimesCondomUsedBeforePregnancy = '';
                        if($aRow['no_of_times_condom_used_before_pregnancy']!= null && trim($aRow['no_of_times_condom_used_before_pregnancy'])!= '' && $aRow['no_of_times_condom_used_before_pregnancy']!= 'not applicable'){
                            if($aRow['no_of_times_condom_used_before_pregnancy'] == 1){
                                $noofTimesCondomUsedBeforePregnancy = "Always - 1";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 2){
                                $noofTimesCondomUsedBeforePregnancy = "Sometimes - 2";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 3){
                                $noofTimesCondomUsedBeforePregnancy = "Never - 3";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 88){
                                $noofTimesCondomUsedBeforePregnancy = "Don't Know - 88";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 99){
                                $noofTimesCondomUsedBeforePregnancy = "Refused - 99";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 2222){
                                $noofTimesCondomUsedBeforePregnancy = "Response Not Available - 2222";
                            }else{
                                $noofTimesCondomUsedBeforePregnancy = ucwords($aRow['no_of_times_condom_used_before_pregnancy']);
                            }
                        }
                        $noofTimesCondomUsedAfterPregnancy = '';
                        if($aRow['no_of_times_condom_used_after_pregnancy']!= null && trim($aRow['no_of_times_condom_used_after_pregnancy'])!= '' && $aRow['no_of_times_condom_used_after_pregnancy']!= 'not applicable'){
                            if($aRow['no_of_times_condom_used_after_pregnancy'] == 1){
                                $noofTimesCondomUsedAfterPregnancy = "Always - 1";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 2){
                                $noofTimesCondomUsedAfterPregnancy = "Sometimes - 2";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 3){
                                $noofTimesCondomUsedAfterPregnancy = "Never - 3";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 88){
                                $noofTimesCondomUsedAfterPregnancy = "Don't Know - 88";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 99){
                                $noofTimesCondomUsedAfterPregnancy = "Refused - 99";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 2222){
                                $noofTimesCondomUsedAfterPregnancy = "Response Not Available - 2222";
                            }else{
                                $noofTimesCondomUsedAfterPregnancy = ucwords($aRow['no_of_times_condom_used_after_pregnancy']);
                            }
                        }
                        $hasPatientHadPaininLowerAbdomen = '';
                        if($aRow['has_patient_had_pain_in_lower_abdomen']!= null && trim($aRow['has_patient_had_pain_in_lower_abdomen'])!= ''){
                            if($aRow['has_patient_had_pain_in_lower_abdomen'] == 1){
                                $hasPatientHadPaininLowerAbdomen = "Yes - 1";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 2){
                                $hasPatientHadPaininLowerAbdomen = "No - 2";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 88){
                                $hasPatientHadPaininLowerAbdomen = "Don't Know - 88";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 99){
                                $hasPatientHadPaininLowerAbdomen = "Refused - 99";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 2222){
                                $hasPatientHadPaininLowerAbdomen = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientBeenTreatedforLowerAbdomenPain = '';
                        if($aRow['has_patient_been_treated_for_lower_abdomen_pain']!= null && trim($aRow['has_patient_been_treated_for_lower_abdomen_pain'])!= ''){
                            if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 1){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "Yes - 1";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 2){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "No - 2";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 88){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "Don't Know - 88";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 99){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "Refused - 99";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 2222){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "Response Not Available - 2222";
                            }else{
                                $hasPatientBeenTreatedforLowerAbdomenPain = ucwords($aRow['has_patient_been_treated_for_lower_abdomen_pain']);
                            }
                        }
                        $hasPatientEverBeenTreatedforSyphilis = '';
                        if($aRow['has_patient_ever_been_treated_for_syphilis']!= null && trim($aRow['has_patient_ever_been_treated_for_syphilis'])!= ''){
                            if($aRow['has_patient_ever_been_treated_for_syphilis'] == 1){
                                $hasPatientEverBeenTreatedforSyphilis = "Yes - 1";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 2){
                                $hasPatientEverBeenTreatedforSyphilis = "No - 2";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 88){
                                $hasPatientEverBeenTreatedforSyphilis = "Don't Know - 88";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 99){
                                $hasPatientEverBeenTreatedforSyphilis = "Refused - 99";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 2222){
                                $hasPatientEverBeenTreatedforSyphilis = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientEverReceivedVaccinetoPreventCervicalCancer = '';
                        if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer']!= null && trim($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'])!= ''){
                            if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 1){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "Yes - 1";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 2){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "No - 2";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 88){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "Don't Know - 88";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 99){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "Refused - 99";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 2222){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "Response Not Available - 2222";
                            }
                        }
                        //alcohol and drug use
                        $patientHadDrinkwithAlcoholinLastSixMonths = '';
                        if($aRow['patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['patient_had_drink_with_alcohol_in_last_six_months'])!= ''){
                            if($aRow['patient_had_drink_with_alcohol_in_last_six_months'][0] == '@'){
                                $patientHadDrinkwithAlcoholinLastSixMonths = substr($aRow['patient_had_drink_with_alcohol_in_last_six_months'],1).' Day(s)';
                            }else if($aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 88){
                                $patientHadDrinkwithAlcoholinLastSixMonths = "Don't Know - 88";
                            }else if($aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 99){
                                $patientHadDrinkwithAlcoholinLastSixMonths = "Refused - 99";
                            }else if($aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 2222){
                                $patientHadDrinkwithAlcoholinLastSixMonths = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = '';
                        if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']!= null && trim($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'])!= ''){
                            if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 1){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Never - 1";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 2){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Monthly - 2";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 3){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Weekly - 3";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 4){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Daily or On Most Days of The Week - 4";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 88){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Don't Know - 88";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 99){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Refused - 99";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 2222){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientEverTriedRecreationalDrugs = '';
                        if($aRow['has_patient_ever_tried_recreational_drugs']!= null && trim($aRow['has_patient_ever_tried_recreational_drugs'])!= ''){
                            if($aRow['has_patient_ever_tried_recreational_drugs'] == 1){
                                $hasPatientEverTriedRecreationalDrugs = "Yes - 1";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 2){
                                $hasPatientEverTriedRecreationalDrugs = "No - 2";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 88){
                                $hasPatientEverTriedRecreationalDrugs = "Don't Know - 88";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 99){
                                $hasPatientEverTriedRecreationalDrugs = "Refused - 99";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 2222){
                                $hasPatientEverTriedRecreationalDrugs = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientHadRecreationalDrugsInLastSixMonths = '';
                        if($aRow['has_patient_had_recreational_drugs_in_last_six_months']!= null && trim($aRow['has_patient_had_recreational_drugs_in_last_six_months'])!= ''){
                            $recreationaldata = json_decode($aRow['has_patient_had_recreational_drugs_in_last_six_months'],true);
                            $hasHadinLastSixMonths = (isset($recreationaldata['has_had_in_last_six_months']))?$recreationaldata['has_had_in_last_six_months']:'';
                            $recreationalDrugs = (isset($recreationaldata['drugs']))?$recreationaldata['drugs']:'';
                            if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 1){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "Yes - 1,&nbsp;&nbsp;Drug(s) - ".ucwords($recreationalDrugs);
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 2){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "No - 2";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 88){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "Don't Know - 88";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 99){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "Refused - 99";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 2222){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "Response Not Available - 2222";
                            }else if(trim($hasHadinLastSixMonths)!= ''){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = ucwords($hasHadinLastSixMonths);
                            }
                        }
                        //abuse
                        //patient abused by
                        $hasPatientEverBeenAbusedBySomeone = '';
                        if($aRow['has_patient_ever_been_abused_by_someone']!= null && trim($aRow['has_patient_ever_been_abused_by_someone'])!= ''){
                            $patientAbusedBydata = json_decode($aRow['has_patient_ever_been_abused_by_someone'],true);
                            $hasPatientAbusedBy = (isset($patientAbusedBydata['ever_abused']))?(int)$patientAbusedBydata['ever_abused']:'';
                            if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'][0] == '@'){
                                $patientAbusedByInNoofTimes = substr($patientAbusedBydata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 88){
                               $patientAbusedByInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 99){
                               $patientAbusedByInNoofTimes = 'Refused - 99';
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 2222){
                               $patientAbusedByInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientAbusedBydata['no_of_times']) && trim($patientAbusedBydata['no_of_times'])!= ''){
                               $patientAbusedByInNoofTimes = ucwords($patientAbusedBydata['no_of_times']);
                            }
                            if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused'])!= '' && $patientAbusedBydata['who_abused'][0] == '@'){
                                $patientAbusedBy = substr($patientAbusedBydata['who_abused'],1);
                            }else if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused']) == 'not applicable'){
                                $patientAbusedBy = 'Not Applicable';
                            }else if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused'])!= ''){
                                $abusedppl = explode(',',$patientAbusedBydata['who_abused']);
                                $abusedGroup = array();
                                for($i=0;$i<count($abusedppl);$i++){
                                    $abusedGroup[] = $keyArray[$abusedppl[$i]];
                                }
                                $patientAbusedBy = str_replace(',',', ',implode(',',$abusedGroup));
                            }
                            if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 1){
                                $hasPatientEverBeenAbusedBySomeone = "Yes - 1,&nbsp;&nbsp;Abused by - ".$patientAbusedBy."&nbsp;&nbsp;No.of times abused - ".$patientAbusedByInNoofTimes;
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2){
                                $hasPatientEverBeenAbusedBySomeone = "No - 2";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 88){
                                $hasPatientEverBeenAbusedBySomeone = "Don't Know - 88";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 99){
                                $hasPatientEverBeenAbusedBySomeone = "Refused - 99";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2222){
                                $hasPatientEverBeenAbusedBySomeone = "Response Not Available - 2222";
                            }
                        }
                        //patient hurt by someone within last year
                        $hasPatientHurtBySomeoneWithinLastYear = '';
                        if($aRow['has_patient_ever_been_hurt_by_someone_within_last_year']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'])!= ''){
                            $patientHurtBydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'],true);
                            $hasPatientHurtByWithinLastYear = (isset($patientHurtBydata['ever_hurt']))?(int)$patientHurtBydata['ever_hurt']:'';
                            if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'][0] == '@'){
                                $patientHurtByInNoofTimes = substr($patientHurtBydata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 88){
                               $patientHurtByInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 99){
                               $patientHurtByInNoofTimes = 'Refused - 99';
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 2222){
                               $patientHurtByInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientHurtBydata['no_of_times']) && trim($patientHurtBydata['no_of_times'])!= ''){
                               $patientHurtByInNoofTimes = ucwords($patientHurtBydata['no_of_times']);
                            }
                            if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt'])!= '' && $patientHurtBydata['who_hurt'][0] == '@'){
                                $patientHurtByWithinLastYear = substr($patientHurtBydata['who_hurt'],1);
                            }else if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt']) == 'not applicable'){
                                $patientHurtByWithinLastYear = 'Not Applicable';
                            }else if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt'])!= ''){
                                $hurtedppl = explode(',',$patientHurtBydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByWithinLastYear = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 1){
                                $hasPatientHurtBySomeoneWithinLastYear = "Yes - 1,&nbsp;&nbsp;Hurt by - ".$patientHurtByWithinLastYear."&nbsp;&nbsp;No.of times hurted - ".$patientHurtByInNoofTimes;
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2){
                                $hasPatientHurtBySomeoneWithinLastYear = "No - 2";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 88){
                                $hasPatientHurtBySomeoneWithinLastYear = "Don't Know - 88";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 99){
                                $hasPatientHurtBySomeoneWithinLastYear = "Refused - 99";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2222){
                                $hasPatientHurtBySomeoneWithinLastYear = "Response Not Available - 2222";
                            }
                        }
                        //patient hurt by someone during pregnancy
                        $hasPatientHurtBySomeoneDuringPregnancy = 'Not Applicable';;
                        if(isset($hasPatientHurtByWithinLastYear) && trim($hasPatientHurtByWithinLastYear) == 1 && $aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'])!= ''){
                            $patientHurtByDuringPregnancydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'],true);
                            $hasPatientHurtByDuringPregnancy = (isset($patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']))?(int)$patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']:'';
                            if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'][0] == '@'){
                                $patientHurtByDuringPregnancyInNoofTimes = substr($patientHurtByDuringPregnancydata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 88){
                               $patientHurtByDuringPregnancyInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 99){
                               $patientHurtByDuringPregnancyInNoofTimes = 'Refused - 99';
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 2222){
                               $patientHurtByDuringPregnancyInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && trim($patientHurtByDuringPregnancydata['no_of_times'])!= ''){
                               $patientHurtByDuringPregnancyInNoofTimes = ucwords($patientHurtByDuringPregnancydata['no_of_times']);
                            }
                            if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt'])!= '' && $patientHurtByDuringPregnancydata['who_hurt'][0] == '@'){
                                $patientHurtByDuringPregnancy = substr($patientHurtByDuringPregnancydata['who_hurt'],1);
                            }else if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt']) == 'not applicable'){
                                $patientHurtByDuringPregnancy = 'Not Applicable';
                            }else if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt'])!= ''){
                                $hurtedppl = explode(',',$patientHurtByDuringPregnancydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByDuringPregnancy = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 1){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Yes - 1,&nbsp;&nbsp;Hurt by - ".$patientHurtByDuringPregnancy."&nbsp;&nbsp;No.of times hurted - ".$patientHurtByDuringPregnancyInNoofTimes;
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2){
                                $hasPatientHurtBySomeoneDuringPregnancy = "No - 2";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 88){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Don't Know - 88";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 99){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Refused - 99";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2222){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Response Not Available - 2222";
                            }
                        }
                        //patient forced for sex within last year
                        $hasPatientForcedforSexBySomeoneWithinLastYear = '';
                        if($aRow['has_patient_ever_been_forced_for_sex_within_last_year']!= null && trim($aRow['has_patient_ever_been_forced_for_sex_within_last_year'])!= ''){
                            $patientForcedforSexdata = json_decode($aRow['has_patient_ever_been_forced_for_sex_within_last_year'],true);
                            $hasPatientForcedforSexWithinLastYear = (isset($patientForcedforSexdata['ever_forced_for_sex']))?(int)$patientForcedforSexdata['ever_forced_for_sex']:'';
                            if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'][0] == '@'){
                                $patientForcedforSexInNoofTimes = substr($patientForcedforSexdata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 88){
                               $patientForcedforSexInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 99){
                               $patientForcedforSexInNoofTimes = 'Refused - 99';
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 2222){
                               $patientForcedforSexInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientForcedforSexdata['no_of_times']) && trim($patientForcedforSexdata['no_of_times'])!= ''){
                               $patientForcedforSexInNoofTimes = ucwords($patientForcedforSexdata['no_of_times']);
                            }
                            if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced'])!= '' && $patientForcedforSexdata['who_forced'][0] == '@'){
                                $patientForcedforSexBy = substr($patientForcedforSexdata['who_forced'],1);
                            }else if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced']) == 'not applicable'){
                                $patientForcedforSexBy = 'Not Applicable';
                            }else if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced'])!= ''){
                                $forcedbyppl = explode(',',$patientForcedforSexdata['who_forced']);
                                $forcedbyGroup = array();
                                for($i=0;$i<count($forcedbyppl);$i++){
                                    $forcedbyGroup[] = $keyArray[$forcedbyppl[$i]];
                                }
                                $patientForcedforSexBy = str_replace(',',', ',implode(',',$forcedbyGroup));
                            }
                            if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 1){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Yes - 1,&nbsp;&nbsp;Forced by - ".$patientForcedforSexBy."&nbsp;&nbsp;No.of times forced - ".$patientForcedforSexInNoofTimes;
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "No - 2";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 88){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Don't Know - 88";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 99){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Refused - 99";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2222){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientAfraidofAnyone = '';
                        if($aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                            if($aRow['is_patient_afraid_of_anyone'] == 1){
                                $hasPatientAfraidofAnyone = "Yes - 1";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 2){
                                $hasPatientAfraidofAnyone = "No - 2";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 88){
                                $hasPatientAfraidofAnyone = "Don't Know - 88";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 99){
                                $hasPatientAfraidofAnyone = "Refused - 99";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 2222){
                                $hasPatientAfraidofAnyone = "Response Not Available - 2222";
                            }
                        }
                        $row = array();
                        $row[] = $aRow['anc_site_code'].'-'.ucwords($aRow['anc_site_name']);
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = ucwords($aRow['interviewer_name']);
                        $row[] = $aRow['anc_patient_id'];
                        $row[] = $interviewDate;
                        $row[] = (isset($aRow['age']) && (int)$aRow['age'] > 0)?$aRow['age']:'';
                        //$row[] = (isset($aRow['has_participant_received_dreams_services']) && $aRow['has_participant_received_dreams_services']!= null && trim($aRow['has_participant_received_dreams_services'])!= '')?ucfirst($aRow['has_participant_received_dreams_services']):'';
                        $row[] = $occupation;
                        $row[] = $hasPatientEverAttendedSchool;
                        $row[] = $degree;
                        $row[] = $patientEverBeenMarried;
                        $row[] = $ageAtFirstMarriage;
                        $row[] = $patientEverBeenWidowed;
                        $row[] = $maritalStatus;
                        $row[] = $hasPatientEverBeenTestedforHIV;
                        $row[] = $timeofMostRecentHIVTest;
                        $row[] = $resultofMostRecentHIVTest;
                        $row[] = $ageAtVeryFirstSex;
                        $row[] = $reasonforVeryFirstSex;
                        $row[] = $totalNoofSexualPartners;
                        $row[] = $noofSexualPartnersinLastSixMonths;
                        $row[] = $partnerHIVStatus;
                        $row[] = $ageofMainSexualpartneratLastBirthday;
                        $row[] = $ageDiffofMainSexualPartner;
                        $row[] = $isPartnerCircumcised;
                        $row[] = $circumcision;
                        $row[] = $hasPatientEverReceivedGiftforSex;
                        $row[] = $mostRecentTimeofReceivingGiftforSex;
                        $row[] = $noofTimesBeenPregnant;
                        $row[] = $noofTimesCondomUsedBeforePregnancy;
                        $row[] = $noofTimesCondomUsedAfterPregnancy;
                        $row[] = $hasPatientHadPaininLowerAbdomen;
                        $row[] = $hasPatientBeenTreatedforLowerAbdomenPain;
                        $row[] = $hasPatientEverBeenTreatedforSyphilis;
                        $row[] = $hasPatientEverReceivedVaccinetoPreventCervicalCancer;
                        $row[] = $patientHadDrinkwithAlcoholinLastSixMonths;
                        $row[] = $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion;
                        $row[] = $hasPatientEverTriedRecreationalDrugs;
                        $row[] = $hasPatientHadRecreationalDrugsInLastSixMonths;
                        $row[] = $hasPatientEverBeenAbusedBySomeone;
                        $row[] = $hasPatientHurtBySomeoneWithinLastYear;
                        $row[] = $hasPatientHurtBySomeoneDuringPregnancy;
                        $row[] = $hasPatientForcedforSexBySomeoneWithinLastYear;
                        $row[] = $hasPatientAfraidofAnyone;
                        $row[] = ucfirst($aRow['comment']);
                        $output[] = $row;
                    }
                    $styleArray = array(
                        'font' => array(
                            'size' => 12,
                            'bold' => true,
                        ),
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    $borderStyle = array(
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    
                    $sheet->mergeCells('A1:A2');
                    $sheet->mergeCells('B1:B2');
                    $sheet->mergeCells('C1:C2');
                    $sheet->mergeCells('D1:D2');
                    $sheet->mergeCells('E1:E2');
                    $sheet->mergeCells('F1:F2');
                    $sheet->mergeCells('G1:M1');
                    $sheet->mergeCells('N1:P1');
                    $sheet->mergeCells('Q1:AH1');
                    $sheet->mergeCells('AI1:AL1');
                    $sheet->mergeCells('AM1:AQ1');
                    $sheet->mergeCells('AR1:AR2');
                    
                    $sheet->setCellValue('A1', html_entity_decode('ANC Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Interviewer Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Interview Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Age from Lab Request ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    //$sheet->setCellValue('F1', html_entity_decode('Has Participant received DREAMS services ?', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G1', html_entity_decode('Demographic Characteristics ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G2', html_entity_decode('What kind of work/occupation do you do most of the time? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H2', html_entity_decode('Have you ever attended school? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I2', html_entity_decode('What was the highest level of education that you completed or are attending now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J2', html_entity_decode('Have you ever been married or lived with a partner in a union as if married? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K2', html_entity_decode('How old were you when you first got married or lived with a partner in a union? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L2', html_entity_decode('Have you ever been widowed? That is, did a spouse ever pass away while you were still married or living with them? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M2', html_entity_decode('What is your marital status now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('N1', html_entity_decode('HIV Testing History ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N2', html_entity_decode('Have you ever been tested for HIV? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O2', html_entity_decode('When was the most recent time you were tested for HIV? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P2', html_entity_decode('What was the result of that HIV test? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('Q1', html_entity_decode('Sexual Activity and History of Sexually Transmitted Infections ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q2', html_entity_decode('How old were you when you had sexual intercourse for the very first time? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R2', html_entity_decode('The first time you had sex, was it because you wanted to or because you were forced to? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S2', html_entity_decode('How many different people have you had sexual intercourse with in your entire life? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T2', html_entity_decode('How many different people have you had sex with in the last 6 months? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U2', html_entity_decode('What is the HIV status of your spouse/main sexual partner\'s (person with whom you have sexual intercourse most frequently)? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V2', html_entity_decode('How old was your spouse/main sexual partner on his last birthday? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W2', html_entity_decode('Is the age of your spouse/main sexual partner older, younger, or the same age as you? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X2', html_entity_decode('Is your spouse/main sexual partner circumcised? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y2', html_entity_decode('What type of circumcision? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z2', html_entity_decode('Have you ever received money/gifts in exchange for sex? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA2', html_entity_decode('When did you last receive money/gifts in exchange for sex? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB2', html_entity_decode('How many times have you been pregnant? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC2', html_entity_decode('Before becoming pregnant this time, in the past year how frequently did you use a condom when having sexual intercourse? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD2', html_entity_decode('Since becoming pregnant this time, how frequently have you used a condom when having sexual intercourse? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AE2', html_entity_decode('In the past year, did you have symptoms such as genital discharge, sores in your genital area, pain during urination, or pain in your lower abdomen? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AF2', html_entity_decode('Were you treated for these symptoms? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AG2', html_entity_decode('Have you ever been diagnosed or treated for syphilis? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AH2', html_entity_decode('Have you ever received a vaccine (HPV vaccine) to prevent cervical cancer, which is caused by a common virus that can be passed through sexual contact? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AI1', html_entity_decode('Alcohol and drug use ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AI2', html_entity_decode('During the past 6 months, on how many days did you have at least one drink containing alcohol? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AJ2', html_entity_decode('How often do you have 4 or more drinks with alcohol on one occasion? (e.g. within 2 hours) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AK2', html_entity_decode('Have you ever tried recreational drugs? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AL2', html_entity_decode('In the last 6 months, have you taken any recreational drugs? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AM1', html_entity_decode('Abuse ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AM2', html_entity_decode('Have you ever been emotionally or physically abused by your partner or your loved one? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AN2', html_entity_decode('Within the last year, have you ever been hit, slapped, kicked, or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AO2', html_entity_decode('Since you\'ve been pregnant have you been slapped, kicked or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AP2', html_entity_decode('Within the last year, has anyone forced you to have sexual activities? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AQ2', html_entity_decode('Are you afraid of your partner or anyone listed above? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AR1', html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B2')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C2')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D2')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E2')->applyFromArray($styleArray);
                    $sheet->getStyle('F1:F2')->applyFromArray($styleArray);
                    $sheet->getStyle('G1:M1')->applyFromArray($styleArray);
                    $sheet->getStyle('G2')->applyFromArray($styleArray);
                    $sheet->getStyle('H2')->applyFromArray($styleArray);
                    $sheet->getStyle('I2')->applyFromArray($styleArray);
                    $sheet->getStyle('J2')->applyFromArray($styleArray);
                    $sheet->getStyle('K2')->applyFromArray($styleArray);
                    $sheet->getStyle('L2')->applyFromArray($styleArray);
                    $sheet->getStyle('M2')->applyFromArray($styleArray);
                    $sheet->getStyle('N1:P1')->applyFromArray($styleArray);
                    $sheet->getStyle('N2')->applyFromArray($styleArray);
                    $sheet->getStyle('O2')->applyFromArray($styleArray);
                    $sheet->getStyle('P2')->applyFromArray($styleArray);
                    $sheet->getStyle('Q1:AH1')->applyFromArray($styleArray);
                    $sheet->getStyle('Q2')->applyFromArray($styleArray);
                    $sheet->getStyle('R2')->applyFromArray($styleArray);
                    $sheet->getStyle('S2')->applyFromArray($styleArray);
                    $sheet->getStyle('T2')->applyFromArray($styleArray);
                    $sheet->getStyle('U2')->applyFromArray($styleArray);
                    $sheet->getStyle('V2')->applyFromArray($styleArray);
                    $sheet->getStyle('W2')->applyFromArray($styleArray);
                    $sheet->getStyle('X2')->applyFromArray($styleArray);
                    $sheet->getStyle('Y2')->applyFromArray($styleArray);
                    $sheet->getStyle('Z2')->applyFromArray($styleArray);
                    $sheet->getStyle('AA2')->applyFromArray($styleArray);
                    $sheet->getStyle('AB2')->applyFromArray($styleArray);
                    $sheet->getStyle('AC2')->applyFromArray($styleArray);
                    $sheet->getStyle('AD2')->applyFromArray($styleArray);
                    $sheet->getStyle('AE2')->applyFromArray($styleArray);
                    $sheet->getStyle('AF2')->applyFromArray($styleArray);
                    $sheet->getStyle('AG2')->applyFromArray($styleArray);
                    $sheet->getStyle('AH2')->applyFromArray($styleArray);
                    $sheet->getStyle('AI1:AL1')->applyFromArray($styleArray);
                    $sheet->getStyle('AI2')->applyFromArray($styleArray);
                    $sheet->getStyle('AJ2')->applyFromArray($styleArray);
                    $sheet->getStyle('AK2')->applyFromArray($styleArray);
                    $sheet->getStyle('AL2')->applyFromArray($styleArray);
                    $sheet->getStyle('AM1:AQ1')->applyFromArray($styleArray);
                    $sheet->getStyle('AM2')->applyFromArray($styleArray);
                    $sheet->getStyle('AN2')->applyFromArray($styleArray);
                    $sheet->getStyle('AO2')->applyFromArray($styleArray);
                    $sheet->getStyle('AP2')->applyFromArray($styleArray);
                    $sheet->getStyle('AQ2')->applyFromArray($styleArray);
                    $sheet->getStyle('AR1:AR2')->applyFromArray($styleArray);
                    
                    $currentRow = 3;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            
                            if($colNo > 43){
                                break;
                            }
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                      $currentRow++;
                    }
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = 'RISK-ASSESSMENT-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "";
                }
            }catch (Exception $exc) {
                error_log("RISK-ASSESSMENT-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function generateRiskAssessmentPdf($params){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $aQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                                   ->join(array('anc' => 'anc_site'), "anc.anc_site_id=r_a.anc",array('anc_site_name'))
                                   ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation','occupation_code'))
				   ->join(array('anc_r_r' => 'anc_rapid_recency'), "anc_r_r.assessment_id=r_a.assessment_id",array('anc_rapid_recency_id','has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line'),'left');
        if(count($params['assessment'])>0){
            $assessmentArray = array();
            for($i=0;$i<count($params['assessment']);$i++){
                $assessmentArray[] = base64_decode($params['assessment'][$i]);
            }
           $aQuery = $aQuery->where('r_a.assessment_id IN ("' . implode('", "', $assessmentArray) . '")');
        }
        $aQueryStr = $sql->getSqlStringForSqlObject($aQuery);
      return $dbAdapter->query($aQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function lockRiskAssessment($params){
        $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
       return $clinicRiskAssessmentDb->lockRiskAssessmentDetails($params);
    }
    
    public function unlockRiskAssessment($params){
        $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
       return $clinicRiskAssessmentDb->unlockRiskAssessmentDetails($params);
    }
    
    public function getANCAsanteResults($parameters){
        $ancRapidRecencyDb = $this->sm->get('AncRapidRecencyTable');
       return $ancRapidRecencyDb->fetchANCAsanteResults($parameters);
    }
    
    public function exportAsanteResultInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->ancAsanteResultQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->ancAsanteResultQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $ancHIVVerificationClassification = '-';
                        $ancRecencyVerificationClassification = '-';
                        if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line']) == 'positive'){
                            $ancHIVVerificationClassification = 'Present';
                        }else if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line']) == 'negative'){
                            $ancHIVVerificationClassification = 'Absent';
                        }else if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line']) == 'invalid') {
                            $ancHIVVerificationClassification = 'Invalid';
                        }
                        //if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line'])!= 'negative'){
                            if(isset($aRow['recency_line']) && trim($aRow['recency_line']) == 'recent'){
                                $ancRecencyVerificationClassification = 'Absent';
                            }else if(isset($aRow['recency_line']) && trim($aRow['recency_line']) == 'long term'){
                                $ancRecencyVerificationClassification = 'Present';
                            }else if(isset($aRow['recency_line']) && trim($aRow['recency_line']) == 'invalid') {
                                $ancRecencyVerificationClassification = 'Invalid';
                            }
                        //}
                        $row = array();
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = ucwords($aRow['anc_site_name']);
                        $row[] = (isset($aRow['location_name']))?ucwords($aRow['location_name']):'';
                        $row[] = $ancHIVVerificationClassification;
                        $row[] = $ancRecencyVerificationClassification;
                        $output[] = $row;
                    }
                    
                    $styleArray = array(
                        'font' => array(
                            'size' => 12,
                            'bold' => true,
                        ),
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    $borderStyle = array(
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    
                    $sheet->setCellValue('A1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('ANC Site ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('District Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('ANC Positive Verification Line ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('ANC Long Term Line ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                   
                    $sheet->getStyle('A1')->applyFromArray($styleArray);
                    $sheet->getStyle('B1')->applyFromArray($styleArray);
                    $sheet->getStyle('C1')->applyFromArray($styleArray);
                    $sheet->getStyle('D1')->applyFromArray($styleArray);
                    $sheet->getStyle('E1')->applyFromArray($styleArray);
                    
                    $currentRow = 2;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            if($colNo > 4){
                                break;
                            }
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                      $currentRow++;
                    }
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = 'ANC-ASANTE-RESULT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "";
                }
            }catch (Exception $exc) {
                error_log("ANC-ASANTE-RESULT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getBehaviourDataReportingWeeklyDetails($params){
        $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
      return $clinicRiskAssessmentDb->fetchBehaviourDataReportingWeeklyDetails($params);
    }
    
    public function exportIPVReportInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->riskAssessmentQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQuery = $queryContainer->riskAssessmentQuery;
                $sQuery = $sQuery->where('(r_a.has_patient_ever_been_abused_by_someone like \'{"ever_abused":"1"%\' OR r_a.has_patient_ever_been_hurt_by_someone_within_last_year like \'{"ever_hurt":"1"%\' OR r_a.has_patient_ever_been_hurt_by_someone_during_pregnancy like \'{"ever_hurt_by_during_pregnancy":"1"%\' OR r_a.has_patient_ever_been_forced_for_sex_within_last_year like \'{"ever_forced_for_sex":"1"%\' OR r_a.is_patient_afraid_of_anyone = 1)');
                $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $keyArray = array('0'=>'','1'=>'Husband - 1','2'=>'Ex-Husband - 2','3'=>'Boyfriend - 3','4'=>'Stranger - 4','88'=>'Don\'t Know - 88','99'=>'Refused - 99','2222'=>'Response Not Available - 2222');
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $interviewDate = '';
                        if(isset($aRow['interview_date']) && $aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
                            $interviewDate = $common->humanDateFormat($aRow['interview_date']);
                        }
                        //abuse
                        //patient abused by
                        $hasPatientEverBeenAbusedBySomeone = '';
                        if($aRow['has_patient_ever_been_abused_by_someone']!= null && trim($aRow['has_patient_ever_been_abused_by_someone'])!= ''){
                            $patientAbusedBydata = json_decode($aRow['has_patient_ever_been_abused_by_someone'],true);
                            $hasPatientAbusedBy = (isset($patientAbusedBydata['ever_abused']))?(int)$patientAbusedBydata['ever_abused']:'';
                            if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'][0] == '@'){
                                $patientAbusedByInNoofTimes = substr($patientAbusedBydata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 88){
                               $patientAbusedByInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 99){
                               $patientAbusedByInNoofTimes = 'Refused - 99';
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 2222){
                               $patientAbusedByInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientAbusedBydata['no_of_times']) && trim($patientAbusedBydata['no_of_times'])!= ''){
                               $patientAbusedByInNoofTimes = ucwords($patientAbusedBydata['no_of_times']);
                            }
                            if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused'])!= '' && $patientAbusedBydata['who_abused'][0] == '@'){
                                $patientAbusedBy = substr($patientAbusedBydata['who_abused'],1);
                            }else if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused']) == 'not applicable'){
                                $patientAbusedBy = 'Not Applicable';
                            }else if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused'])!= ''){
                                $abusedppl = explode(',',$patientAbusedBydata['who_abused']);
                                $abusedGroup = array();
                                for($i=0;$i<count($abusedppl);$i++){
                                    $abusedGroup[] = $keyArray[$abusedppl[$i]];
                                }
                                $patientAbusedBy = str_replace(',',', ',implode(',',$abusedGroup));
                            }
                            if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 1){
                                $hasPatientEverBeenAbusedBySomeone = "Yes - 1,&nbsp;&nbsp;Abused by - ".$patientAbusedBy."&nbsp;&nbsp;No.of times abused - ".$patientAbusedByInNoofTimes;
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2){
                                $hasPatientEverBeenAbusedBySomeone = "No - 2";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 88){
                                $hasPatientEverBeenAbusedBySomeone = "Don't Know - 88";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 99){
                                $hasPatientEverBeenAbusedBySomeone = "Refused - 99";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2222){
                                $hasPatientEverBeenAbusedBySomeone = "Response Not Available - 2222";
                            }
                        }
                        //patient hurt by someone within last year
                        $hasPatientHurtBySomeoneWithinLastYear = '';
                        if($aRow['has_patient_ever_been_hurt_by_someone_within_last_year']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'])!= ''){
                            $patientHurtBydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'],true);
                            $hasPatientHurtByWithinLastYear = (isset($patientHurtBydata['ever_hurt']))?(int)$patientHurtBydata['ever_hurt']:'';
                            if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'][0] == '@'){
                                $patientHurtByInNoofTimes = substr($patientHurtBydata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 88){
                               $patientHurtByInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 99){
                               $patientHurtByInNoofTimes = 'Refused - 99';
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 2222){
                               $patientHurtByInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientHurtBydata['no_of_times']) && trim($patientHurtBydata['no_of_times'])!= ''){
                               $patientHurtByInNoofTimes = ucwords($patientHurtBydata['no_of_times']);
                            }
                            if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt'])!= '' && $patientHurtBydata['who_hurt'][0] == '@'){
                                $patientHurtByWithinLastYear = substr($patientHurtBydata['who_hurt'],1);
                            }else if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt']) == 'not applicable'){
                                $patientHurtByWithinLastYear = 'Not Applicable';
                            }else if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt'])!= ''){
                                $hurtedppl = explode(',',$patientHurtBydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByWithinLastYear = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 1){
                                $hasPatientHurtBySomeoneWithinLastYear = "Yes - 1,&nbsp;&nbsp;Hurt by - ".$patientHurtByWithinLastYear."&nbsp;&nbsp;No.of times hurted - ".$patientHurtByInNoofTimes;
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2){
                                $hasPatientHurtBySomeoneWithinLastYear = "No - 2";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 88){
                                $hasPatientHurtBySomeoneWithinLastYear = "Don't Know - 88";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 99){
                                $hasPatientHurtBySomeoneWithinLastYear = "Refused - 99";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2222){
                                $hasPatientHurtBySomeoneWithinLastYear = "Response Not Available - 2222";
                            }
                        }
                        //patient hurt by someone during pregnancy
                        $hasPatientHurtBySomeoneDuringPregnancy = 'Not Applicable';;
                        if(isset($hasPatientHurtByWithinLastYear) && trim($hasPatientHurtByWithinLastYear) == 1 && $aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'])!= ''){
                            $patientHurtByDuringPregnancydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'],true);
                            $hasPatientHurtByDuringPregnancy = (isset($patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']))?(int)$patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']:'';
                            if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'][0] == '@'){
                                $patientHurtByDuringPregnancyInNoofTimes = substr($patientHurtByDuringPregnancydata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 88){
                               $patientHurtByDuringPregnancyInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 99){
                               $patientHurtByDuringPregnancyInNoofTimes = 'Refused - 99';
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 2222){
                               $patientHurtByDuringPregnancyInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && trim($patientHurtByDuringPregnancydata['no_of_times'])!= ''){
                               $patientHurtByDuringPregnancyInNoofTimes = ucwords($patientHurtByDuringPregnancydata['no_of_times']);
                            }
                            if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt'])!= '' && $patientHurtByDuringPregnancydata['who_hurt'][0] == '@'){
                                $patientHurtByDuringPregnancy = substr($patientHurtByDuringPregnancydata['who_hurt'],1);
                            }else if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt']) == 'not applicable'){
                                $patientHurtByDuringPregnancy = 'Not Applicable';
                            }else if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt'])!= ''){
                                $hurtedppl = explode(',',$patientHurtByDuringPregnancydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByDuringPregnancy = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 1){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Yes - 1,&nbsp;&nbsp;Hurt by - ".$patientHurtByDuringPregnancy."&nbsp;&nbsp;No.of times hurted - ".$patientHurtByDuringPregnancyInNoofTimes;
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2){
                                $hasPatientHurtBySomeoneDuringPregnancy = "No - 2";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 88){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Don't Know - 88";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 99){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Refused - 99";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2222){
                                $hasPatientHurtBySomeoneDuringPregnancy = "Response Not Available - 2222";
                            }
                        }
                        //patient forced for sex within last year
                        $hasPatientForcedforSexBySomeoneWithinLastYear = '';
                        if($aRow['has_patient_ever_been_forced_for_sex_within_last_year']!= null && trim($aRow['has_patient_ever_been_forced_for_sex_within_last_year'])!= ''){
                            $patientForcedforSexdata = json_decode($aRow['has_patient_ever_been_forced_for_sex_within_last_year'],true);
                            $hasPatientForcedforSexWithinLastYear = (isset($patientForcedforSexdata['ever_forced_for_sex']))?(int)$patientForcedforSexdata['ever_forced_for_sex']:'';
                            if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'][0] == '@'){
                                $patientForcedforSexInNoofTimes = substr($patientForcedforSexdata['no_of_times'],1).' Time(s)';
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 88){
                               $patientForcedforSexInNoofTimes = 'Don\'t Know - 88'; 
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 99){
                               $patientForcedforSexInNoofTimes = 'Refused - 99';
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 2222){
                               $patientForcedforSexInNoofTimes = 'Response Not Available - 2222';
                            }else if(isset($patientForcedforSexdata['no_of_times']) && trim($patientForcedforSexdata['no_of_times'])!= ''){
                               $patientForcedforSexInNoofTimes = ucwords($patientForcedforSexdata['no_of_times']);
                            }
                            if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced'])!= '' && $patientForcedforSexdata['who_forced'][0] == '@'){
                                $patientForcedforSexBy = substr($patientForcedforSexdata['who_forced'],1);
                            }else if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced']) == 'not applicable'){
                                $patientForcedforSexBy = 'Not Applicable';
                            }else if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced'])!= ''){
                                $forcedbyppl = explode(',',$patientForcedforSexdata['who_forced']);
                                $forcedbyGroup = array();
                                for($i=0;$i<count($forcedbyppl);$i++){
                                    $forcedbyGroup[] = $keyArray[$forcedbyppl[$i]];
                                }
                                $patientForcedforSexBy = str_replace(',',', ',implode(',',$forcedbyGroup));
                            }
                            if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 1){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Yes - 1,&nbsp;&nbsp;Forced by - ".$patientForcedforSexBy."&nbsp;&nbsp;No.of times forced - ".$patientForcedforSexInNoofTimes;
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "No - 2";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 88){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Don't Know - 88";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 99){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Refused - 99";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2222){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "Response Not Available - 2222";
                            }
                        }
                        $hasPatientAfraidofAnyone = '';
                        if($aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                            if($aRow['is_patient_afraid_of_anyone'] == 1){
                                $hasPatientAfraidofAnyone = "Yes - 1";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 2){
                                $hasPatientAfraidofAnyone = "No - 2";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 88){
                                $hasPatientAfraidofAnyone = "Don't Know - 88";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 99){
                                $hasPatientAfraidofAnyone = "Refused - 99";
                            }else if($aRow['is_patient_afraid_of_anyone'] == 2222){
                                $hasPatientAfraidofAnyone = "Response Not Available - 2222";
                            }
                        }
                        $row = array();
                        $row[] = $aRow['anc_site_code'].'-'.ucwords($aRow['anc_site_name']);
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = ucwords($aRow['interviewer_name']);
                        $row[] = $aRow['anc_patient_id'];
                        $row[] = $interviewDate;
                        $row[] = (isset($aRow['age']) && (int)$aRow['age'] > 0)?$aRow['age']:'';
                        $row[] = $hasPatientEverBeenAbusedBySomeone;
                        $row[] = $hasPatientHurtBySomeoneWithinLastYear;
                        $row[] = $hasPatientHurtBySomeoneDuringPregnancy;
                        $row[] = $hasPatientForcedforSexBySomeoneWithinLastYear;
                        $row[] = $hasPatientAfraidofAnyone;
                        $row[] = ucfirst($aRow['comment']);
                        $output[] = $row;
                    }
                    $styleArray = array(
                        'font' => array(
                            'size' => 12,
                            'bold' => true,
                        ),
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    $borderStyle = array(
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    $sheet->mergeCells('A1:A2');
                    $sheet->mergeCells('B1:B2');
                    $sheet->mergeCells('C1:C2');
                    $sheet->mergeCells('D1:D2');
                    $sheet->mergeCells('E1:E2');
                    $sheet->mergeCells('F1:F2');
                    
                    $sheet->mergeCells('G1:K1');
                    
                    $sheet->mergeCells('L1:L2');
                    
                    $sheet->setCellValue('A1', html_entity_decode('ANC Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Interviewer Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Interview Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Age from Lab Request ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G1', html_entity_decode('Abuse ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G2', html_entity_decode('Have you ever been emotionally or physically abused by your partner or your loved one? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H2', html_entity_decode('Within the last year, have you ever been hit, slapped, kicked, or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I2', html_entity_decode('Since you\'ve been pregnant have you been slapped, kicked or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J2', html_entity_decode('Within the last year, has anyone forced you to have sexual activities? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K2', html_entity_decode('Are you afraid of your partner or anyone listed above? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('L1', html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B2')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C2')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D2')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E2')->applyFromArray($styleArray);
                    $sheet->getStyle('F1:F2')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('G1:K1')->applyFromArray($styleArray);
                    $sheet->getStyle('G2')->applyFromArray($styleArray);
                    $sheet->getStyle('H2')->applyFromArray($styleArray);
                    $sheet->getStyle('I2')->applyFromArray($styleArray);
                    $sheet->getStyle('J2')->applyFromArray($styleArray);
                    $sheet->getStyle('K2')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('L1:L2')->applyFromArray($styleArray);
                    
                    $currentRow = 3;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            
                            if($colNo > 12){
                                break;
                            }
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                      $currentRow++;
                    }
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = 'IPV-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "";
                }
            }catch (Exception $exc) {
                error_log("IPV-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            } 
        }else{
            return "";
        }
    }
}