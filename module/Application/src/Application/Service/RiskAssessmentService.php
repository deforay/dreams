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
              $alertContainer->msg = 'Error-Oops, something went wrong!!';
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
              $alertContainer->msg = 'Error-Oops, something went wrong!!';
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
                    $keyArray = array('0'=>'','1'=>'husband','2'=>'exhusband','3'=>'boyfriend','4'=>'stranger','88'=>'dk','99'=>'r','2222'=>'rna');
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $addedDate = '';
                        if(isset($aRow['added_on']) && $aRow['added_on']!= null && trim($aRow['added_on'])!= '' && $aRow['added_on']!= '0000-00-00 00:00:00'){
                            $addedDateArray = explode(' ',$aRow['added_on']);
                            $addedDate = $common->humanDateFormat($addedDateArray[0]).' '.$addedDateArray[1];
                        }
	                $updatedDate = '';
                        if(isset($aRow['updated_on']) && $aRow['updated_on']!= null && trim($aRow['updated_on'])!= '' && $aRow['updated_on']!= '0000-00-00 00:00:00'){
                            $updatedDateArray = explode(' ',$aRow['updated_on']);
                            $updatedDate = $common->humanDateFormat($updatedDateArray[0]).' '.$updatedDateArray[1];
                        }
                        $interviewDate = '';
                        if(isset($aRow['interview_date']) && $aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
                            $interviewDate = $common->humanDateFormat($aRow['interview_date']);
                        }
                        //participant age from anc
                        $participantAge = '';
                        if($aRow['participant_age']!= null && trim($aRow['participant_age'])!= ''){
                            if($aRow['participant_age'][0] == '@'){
                                $participantAge = substr($aRow['participant_age'],1);
                            }else if($aRow['participant_age'] == 88){
                                $participantAge = 888;
                            }else if($aRow['participant_age'] == 99){
                                $participantAge = 777;
                            }else if($aRow['participant_age'] == 2222 || $aRow['participant_age'] == 'not applicable'){
                                $participantAge = 999;
                            }
                        }
                        //patient occupation
                        $occupation = 'other';
                        $occupationOther = '';
                        if($aRow['occupation_code']!= 1111){
                           $occupation = (isset($aRow['occupationName']))?strtolower($aRow['occupationName']):'';
                        }else{
                           $occupationOther = (isset($aRow['occupationName']))?strtolower($aRow['occupationName']):'';
                        }
                        //patient schooling details
                        $hasPatientEverAttendedSchool = '';
                        if($aRow['has_patient_ever_attended_school']!= null && trim($aRow['has_patient_ever_attended_school'])!= ''){
                            if($aRow['has_patient_ever_attended_school'] == 1){
                                $hasPatientEverAttendedSchool = "yes";
                            }else if($aRow['has_patient_ever_attended_school'] == 2){
                                $hasPatientEverAttendedSchool = "no";
                            }else if($aRow['has_patient_ever_attended_school'] == 88){
                                $hasPatientEverAttendedSchool = "dk";
                            }else if($aRow['has_patient_ever_attended_school'] == 99){
                                $hasPatientEverAttendedSchool = "r";
                            }else if($aRow['has_patient_ever_attended_school'] == 2222){
                                $hasPatientEverAttendedSchool = "rna";
                            }
                        }
                        $degree = '';
                        if($aRow['patient_degree']!= null && trim($aRow['patient_degree'])!= ''){
                            if($aRow['patient_degree'] == 1){
                                $degree = "primary/vocational";
                            }else if($aRow['patient_degree'] == 2){
                                $degree = "secondary";
                            }else if($aRow['patient_degree'] == 3){
                                $degree = "university/college";
                            }else if($aRow['patient_degree'] == 88){
                                $degree = "don't know";
                            }else if($aRow['patient_degree'] == 99){
                                $degree = "refused";
                            }else if($aRow['patient_degree'] == 2222){
                                $degree = "response not available";
                            }
                        }
                        $isParticipantAttendingSchoolNow = '';
                        if($aRow['is_participant_attending_school_now']!= null && trim($aRow['is_participant_attending_school_now'])!= ''){
                            if($aRow['is_participant_attending_school_now'] == 1){
                                $isParticipantAttendingSchoolNow = "yes";
                            }else if($aRow['is_participant_attending_school_now'] == 2){
                                $isParticipantAttendingSchoolNow = "no";
                            }else if($aRow['is_participant_attending_school_now'] == 88){
                                $isParticipantAttendingSchoolNow = "dk";
                            }else if($aRow['is_participant_attending_school_now'] == 99){
                                $isParticipantAttendingSchoolNow = "r";
                            }else if($aRow['is_participant_attending_school_now'] == 2222){
                                $isParticipantAttendingSchoolNow = "rna";
                            }
                        }
                        //marital status
                        $patientEverBeenMarried = '';
                        if($aRow['patient_ever_been_married']!= null && trim($aRow['patient_ever_been_married'])!= ''){
                            if($aRow['patient_ever_been_married'] == 1){
                                $patientEverBeenMarried = "yes";
                            }else if($aRow['patient_ever_been_married'] == 2){
                                $patientEverBeenMarried = "no";
                            }else if($aRow['patient_ever_been_married'] == 88){
                                $patientEverBeenMarried = "dk";
                            }else if($aRow['patient_ever_been_married'] == 99){
                                $patientEverBeenMarried = "r";
                            }else if($aRow['patient_ever_been_married'] == 2222){
                                $patientEverBeenMarried = "rna";
                            }
                        }
                        $ageAtFirstMarriage = '';
                        if($aRow['age_at_first_marriage']!= null && trim($aRow['age_at_first_marriage'])!= ''){
                            if($aRow['age_at_first_marriage'][0] == '@'){
                                $ageAtFirstMarriage = substr($aRow['age_at_first_marriage'],1);
                            }else if($aRow['age_at_first_marriage'] == 88){
                                $ageAtFirstMarriage = 888;
                            }else if($aRow['age_at_first_marriage'] == 99){
                                $ageAtFirstMarriage = 777;
                            }else if($aRow['age_at_first_marriage'] == 2222 || $aRow['age_at_first_marriage'] == 'not applicable'){
                                $ageAtFirstMarriage = 999;
                            }
                        }
                        $patientEverBeenWidowed = '';
                        if($aRow['patient_ever_been_widowed']!= null && trim($aRow['patient_ever_been_widowed'])!= ''){
                            if($aRow['patient_ever_been_widowed'] == 1){
                                $patientEverBeenWidowed = "yes";
                            }else if($aRow['patient_ever_been_widowed'] == 2){
                                $patientEverBeenWidowed = "no";
                            }else if($aRow['patient_ever_been_widowed'] == 88){
                                $patientEverBeenWidowed = "dk";
                            }else if($aRow['patient_ever_been_widowed'] == 99){
                                $patientEverBeenWidowed = "r";
                            }else if($aRow['patient_ever_been_widowed'] == 2222){
                                $patientEverBeenWidowed = "rna";
                            }
                        }
                        $maritalStatus = '';
                        if($aRow['current_marital_status']!= null && trim($aRow['current_marital_status'])!= ''){
                            if($aRow['current_marital_status'] == 1){
                                $maritalStatus = "married/cohabiting";
                            }else if($aRow['current_marital_status'] == 2){
                                $maritalStatus = "never married/cohabiting";
                            }else if($aRow['current_marital_status'] == 3){
                                $maritalStatus = "widowed";
                            }else if($aRow['current_marital_status'] == 4){
                                $maritalStatus = "separated";
                            }else if($aRow['current_marital_status'] == 5){
                                $maritalStatus = "divorced";
                            }else if($aRow['current_marital_status'] == 88){
                                $maritalStatus = "don't know";
                            }else if($aRow['current_marital_status'] == 99){
                                $maritalStatus = "refused";
                            }else if($aRow['current_marital_status'] == 2222){
                                $maritalStatus = "response not available";
                            }
                        }
                        //patient HIV test result
                        $hasPatientEverBeenTestedforHIV = '';
                        if($aRow['has_patient_ever_been_tested_for_HIV']!= null && trim($aRow['has_patient_ever_been_tested_for_HIV'])!= ''){
                            if($aRow['has_patient_ever_been_tested_for_HIV'] == 1){
                                $hasPatientEverBeenTestedforHIV = "yes";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 2){
                                $hasPatientEverBeenTestedforHIV = "no";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 88){
                                $hasPatientEverBeenTestedforHIV = "dk";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 99){
                                $hasPatientEverBeenTestedforHIV = "r";
                            }else if($aRow['has_patient_ever_been_tested_for_HIV'] == 2222){
                                $hasPatientEverBeenTestedforHIV = "rna";
                            }
                        }
                        $timeofMostRecentHIVTest = '';
                        if($aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= ''){
                            if($aRow['time_of_last_HIV_test'] == 1){
                                $timeofMostRecentHIVTest = "<3 mos ago";
                            }else if($aRow['time_of_last_HIV_test'] == 2){
                                $timeofMostRecentHIVTest = "3-6 mos ago";
                            }else if($aRow['time_of_last_HIV_test'] == 3){
                                $timeofMostRecentHIVTest = "7-12 mos ago";
                            }else if($aRow['time_of_last_HIV_test'] == 4){
                                $timeofMostRecentHIVTest = ">12 mos ago";
                            }else if($aRow['time_of_last_HIV_test'] == 88){
                                $timeofMostRecentHIVTest = "don't know";
                            }else if($aRow['time_of_last_HIV_test'] == 99){
                                $timeofMostRecentHIVTest = "refused";
                            }else if($aRow['time_of_last_HIV_test'] == 2222){
                                $timeofMostRecentHIVTest = "response not available";
                            }
                        }
                        $resultofMostRecentHIVTest = '';
                        if($aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= ''){
                            if($aRow['last_HIV_test_status'] == 1){
                                $resultofMostRecentHIVTest = "didnotreceive";
                            }else if($aRow['last_HIV_test_status']== 2){
                                $resultofMostRecentHIVTest = "HIVpos";
                            }else if($aRow['last_HIV_test_status'] == 3){
                                $resultofMostRecentHIVTest = "HIVneg";
                            }else if($aRow['last_HIV_test_status'] == 4){
                                $resultofMostRecentHIVTest = "indeterminate";
                            }else if($aRow['last_HIV_test_status'] == 88){
                                $resultofMostRecentHIVTest = "dk";
                            }else if($aRow['last_HIV_test_status'] == 99){
                                $resultofMostRecentHIVTest = "r";
                            }else if($aRow['last_HIV_test_status'] == 2222){
                                $resultofMostRecentHIVTest = "rna";
                            }
                        }
                        $placeofLastHIVTest = '';
                        if($aRow['place_of_last_HIV_test']!= null && trim($aRow['place_of_last_HIV_test'])!= ''){
                            if($aRow['place_of_last_HIV_test'] == 1){
                                $placeofLastHIVTest = "facility";
                            }else if($aRow['place_of_last_HIV_test']== 2){
                                $placeofLastHIVTest = "community";
                            }else if($aRow['place_of_last_HIV_test'] == 88){
                                $placeofLastHIVTest = "dk";
                            }else if($aRow['place_of_last_HIV_test'] == 99){
                                $placeofLastHIVTest = "r";
                            }else if($aRow['place_of_last_HIV_test'] == 2222){
                                $placeofLastHIVTest = "rna";
                            }
                        }
                        //patient sexual activity/sexual transmitted infections
                        $ageAtVeryFirstSex = '';
                        if($aRow['age_at_very_first_sex']!= null && trim($aRow['age_at_very_first_sex'])!= ''){
                            if($aRow['age_at_very_first_sex'][0] == '@'){
                                $ageAtVeryFirstSex = substr($aRow['age_at_very_first_sex'],1);
                            }else if($aRow['age_at_very_first_sex'] == 88){
                                $ageAtVeryFirstSex = 888;
                            }else if($aRow['age_at_very_first_sex'] == 99){
                                $ageAtVeryFirstSex = 777;
                            }else if($aRow['age_at_very_first_sex'] == 2222 || $aRow['age_at_very_first_sex'] == 'not applicable'){
                                $ageAtVeryFirstSex = 999;
                            }
                        }
                        $reasonforVeryFirstSex = '';
                        if($aRow['reason_for_very_first_sex']!= null && trim($aRow['reason_for_very_first_sex'])!= ''){
                            if($aRow['reason_for_very_first_sex'] == 1){
                                $reasonforVeryFirstSex = "wanted to";
                            }else if($aRow['reason_for_very_first_sex'] == 2){
                                $reasonforVeryFirstSex = "forced to";
                            }else if($aRow['reason_for_very_first_sex'] == 88){
                                $reasonforVeryFirstSex = "don't know";
                            }else if($aRow['reason_for_very_first_sex'] == 99){
                                $reasonforVeryFirstSex = "refused";
                            }else if($aRow['reason_for_very_first_sex'] == 2222){
                                $reasonforVeryFirstSex = "response not available";
                            }
                        }
                        $totalNoofSexualPartners = '';
                        if($aRow['no_of_sexual_partners']!= null && trim($aRow['no_of_sexual_partners'])!= ''){
                            if($aRow['no_of_sexual_partners'][0] == '@'){
                                $totalNoofSexualPartners = substr($aRow['no_of_sexual_partners'],1);
                            }else if($aRow['no_of_sexual_partners'] == 88){
                                $totalNoofSexualPartners = 888;
                            }else if($aRow['no_of_sexual_partners'] == 99){
                                $totalNoofSexualPartners = 777;
                            }else if($aRow['no_of_sexual_partners'] == 2222 || $aRow['no_of_sexual_partners'] == 'not applicable'){
                                $totalNoofSexualPartners = 999;
                            }
                        }
                        $noofSexualPartnersinLastSixMonths = '';
                        if($aRow['no_of_sexual_partners_in_last_six_months']!= null && trim($aRow['no_of_sexual_partners_in_last_six_months'])!= ''){
                            if($aRow['no_of_sexual_partners_in_last_six_months'][0] == '@'){
                                $noofSexualPartnersinLastSixMonths = substr($aRow['no_of_sexual_partners_in_last_six_months'],1);
                            }else if($aRow['no_of_sexual_partners_in_last_six_months'] == 88){
                                $noofSexualPartnersinLastSixMonths = 888;
                            }else if($aRow['no_of_sexual_partners_in_last_six_months'] == 99){
                                $noofSexualPartnersinLastSixMonths = 777;
                            }else if($aRow['no_of_sexual_partners_in_last_six_months'] == 2222 || $aRow['no_of_sexual_partners_in_last_six_months'] == 'not applicable'){
                                $noofSexualPartnersinLastSixMonths = 999;
                            }
                        }
                        $partnerHIVStatus = '';
                        if($aRow['partner_HIV_test_status']!= null && trim($aRow['partner_HIV_test_status'])!= ''){
                            if($aRow['partner_HIV_test_status'] == 1){
                                $partnerHIVStatus = "HIVpos";
                            }else if($aRow['partner_HIV_test_status'] == 2){
                                $partnerHIVStatus = "HIVneg";
                            }else if($aRow['partner_HIV_test_status'] == 3){
                                $partnerHIVStatus = "nosexptnr";
                            }else if($aRow['partner_HIV_test_status'] == 88){
                                $partnerHIVStatus = "dk";
                            }else if($aRow['partner_HIV_test_status'] == 99){
                                $partnerHIVStatus = "r";
                            }else if($aRow['partner_HIV_test_status'] == 2222){
                                $partnerHIVStatus = "rna";
                            }
                        }
                        $ageofMainSexualpartneratLastBirthday = '';
                        if($aRow['age_of_main_sexual_partner_at_last_birthday']!= null && trim($aRow['age_of_main_sexual_partner_at_last_birthday'])!= ''){
                            if($aRow['age_of_main_sexual_partner_at_last_birthday'][0] == '@'){
                                $ageofMainSexualpartneratLastBirthday = substr($aRow['age_of_main_sexual_partner_at_last_birthday'],1);
                            }else if($aRow['age_of_main_sexual_partner_at_last_birthday'] == 88){
                                $ageofMainSexualpartneratLastBirthday = 888;
                            }else if($aRow['age_of_main_sexual_partner_at_last_birthday'] == 99){
                                $ageofMainSexualpartneratLastBirthday = 777;
                            }else if($aRow['age_of_main_sexual_partner_at_last_birthday'] == 2222 || $aRow['age_of_main_sexual_partner_at_last_birthday'] == 'not applicable'){
                                $ageofMainSexualpartneratLastBirthday = 999;
                            }
                        }
                        $ageDiffofMainSexualPartner = '';
                        if($aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= ''){
                            if($aRow['age_diff_of_main_sexual_partner'] == 1){
                                $ageDiffofMainSexualPartner = "<5yrs older";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 2){
                                $ageDiffofMainSexualPartner = "5-10yrs older";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 3){
                                $ageDiffofMainSexualPartner = ">10yrs older";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 4){
                                $ageDiffofMainSexualPartner = "same age";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 5){
                                $ageDiffofMainSexualPartner = "<5yrs younger";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 6){
                                $ageDiffofMainSexualPartner = "5-10yrs younger";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 7){
                                $ageDiffofMainSexualPartner = ">10yrs younger";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 88){
                                $ageDiffofMainSexualPartner = "don't know";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 99){
                                $ageDiffofMainSexualPartner = "refused";
                            }else if($aRow['age_diff_of_main_sexual_partner'] == 2222){
                                $ageDiffofMainSexualPartner = "response not available";
                            }
                        }
                        $isPartnerCircumcised = '';
                        if($aRow['is_partner_circumcised']!= null && trim($aRow['is_partner_circumcised'])!= ''){
                            if($aRow['is_partner_circumcised'] == 1){
                                $isPartnerCircumcised = "yes";
                            }else if($aRow['is_partner_circumcised'] == 2){
                                $isPartnerCircumcised = "no";
                            }else if($aRow['is_partner_circumcised'] == 88){
                                $isPartnerCircumcised = "dk";
                            }else if($aRow['is_partner_circumcised'] == 99){
                                $isPartnerCircumcised = "r";
                            }else if($aRow['is_partner_circumcised'] == 2222){
                                $isPartnerCircumcised = "rna";
                            }
                        }
                        $circumcision = '';
                        if($aRow['circumcision']!= null && trim($aRow['circumcision'])!= ''){
                            if($aRow['circumcision'] == 1){
                                $circumcision = "medical";
                            }else if($aRow['circumcision'] == 2){
                                $circumcision = "traditional";
                            }else if($aRow['circumcision'] == 88){
                                $circumcision = "dk";
                            }else if($aRow['circumcision'] == 99){
                                $circumcision = "r";
                            }else if($aRow['circumcision'] == 2222){
                                $circumcision = "rna";
                            }
                        }
                        $hasPatientEverReceivedGiftforSex = '';
                        if($aRow['has_patient_ever_received_gift_for_sex']!= null && $aRow['has_patient_ever_received_gift_for_sex']!= 'not applicable'){
                            if($aRow['has_patient_ever_received_gift_for_sex'] == 1){
                                $hasPatientEverReceivedGiftforSex = "yes";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 2){
                                $hasPatientEverReceivedGiftforSex = "no";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 88){
                                $hasPatientEverReceivedGiftforSex = "dk";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 99){
                                $hasPatientEverReceivedGiftforSex = "r";
                            }else if($aRow['has_patient_ever_received_gift_for_sex'] == 2222){
                                $hasPatientEverReceivedGiftforSex = "rna";
                            }
                        }
                        $mostRecentTimeofReceivingGiftforSex = '';
                        if($aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= ''){
                            if($aRow['last_time_of_receiving_gift_for_sex'] == 1){
                                $mostRecentTimeofReceivingGiftforSex = "<6 mos ago";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 2){
                                $mostRecentTimeofReceivingGiftforSex = "6-12 mos ago";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 3){
                                $mostRecentTimeofReceivingGiftforSex = ">12 mos ago";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 88){
                                $mostRecentTimeofReceivingGiftforSex = "don't know";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 99){
                                $mostRecentTimeofReceivingGiftforSex = "refused";
                            }else if($aRow['last_time_of_receiving_gift_for_sex'] == 2222){
                                $mostRecentTimeofReceivingGiftforSex = "response not available";
                            }
                        }
                        $noofTimesBeenPregnant = '';
                        if($aRow['no_of_times_been_pregnant']!= null && trim($aRow['no_of_times_been_pregnant'])!= ''){
                            if($aRow['no_of_times_been_pregnant'][0] == '@'){
                                $noofTimesBeenPregnant = substr($aRow['no_of_times_been_pregnant'],1);
                            }else if($aRow['no_of_times_been_pregnant'] == 88){
                                $noofTimesBeenPregnant = 888;
                            }else if($aRow['no_of_times_been_pregnant'] == 99){
                                $noofTimesBeenPregnant = 777;
                            }else if($aRow['no_of_times_been_pregnant'] == 2222 || $aRow['no_of_times_been_pregnant'] == 'not applicable'){
                                $noofTimesBeenPregnant = 999;
                            }
                        }
                        $hasPatientWantedtoGetPregnant = '';
                        if($aRow['has_patient_wanted_to_get_pregnant']!= null && $aRow['has_patient_wanted_to_get_pregnant']!= 'not applicable'){
                            if($aRow['has_patient_wanted_to_get_pregnant'] == 1){
                                $hasPatientWantedtoGetPregnant = "yes";
                            }else if($aRow['has_patient_wanted_to_get_pregnant'] == 2){
                                $hasPatientWantedtoGetPregnant = "no";
                            }else if($aRow['has_patient_wanted_to_get_pregnant'] == 88){
                                $hasPatientWantedtoGetPregnant = "dk";
                            }else if($aRow['has_patient_wanted_to_get_pregnant'] == 99){
                                $hasPatientWantedtoGetPregnant = "r";
                            }else if($aRow['has_patient_wanted_to_get_pregnant'] == 2222){
                                $hasPatientWantedtoGetPregnant = "rna";
                            }
                        }
	                $hasPatientWantedtoHaveaBabyLater = '';
                        if($aRow['has_patient_wanted_to_have_a_baby_later']!= null && $aRow['has_patient_wanted_to_have_a_baby_later']!= 'not applicable'){
                            if($aRow['has_patient_wanted_to_have_a_baby_later'] == 1){
                                $hasPatientWantedtoHaveaBabyLater = "wanted baby later";
                            }else if($aRow['has_patient_wanted_to_have_a_baby_later'] == 2){
                                $hasPatientWantedtoHaveaBabyLater = "did not want any";
                            }else if($aRow['has_patient_wanted_to_have_a_baby_later'] == 88){
                                $hasPatientWantedtoHaveaBabyLater = "dk";
                            }else if($aRow['has_patient_wanted_to_have_a_baby_later'] == 99){
                                $hasPatientWantedtoHaveaBabyLater = "r";
                            }else if($aRow['has_patient_wanted_to_have_a_baby_later'] == 2222){
                                $hasPatientWantedtoHaveaBabyLater = "rna";
                            }
                        }
                        $noofTimesCondomUsedBeforePregnancy = '';
                        if($aRow['no_of_times_condom_used_before_pregnancy']!= null && trim($aRow['no_of_times_condom_used_before_pregnancy'])!= ''){
                            if($aRow['no_of_times_condom_used_before_pregnancy'] == 1){
                                $noofTimesCondomUsedBeforePregnancy = "always";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 2){
                                $noofTimesCondomUsedBeforePregnancy = "sometimes";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 3){
                                $noofTimesCondomUsedBeforePregnancy = "never";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 88){
                                $noofTimesCondomUsedBeforePregnancy = "dk";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 99){
                                $noofTimesCondomUsedBeforePregnancy = "r";
                            }else if($aRow['no_of_times_condom_used_before_pregnancy'] == 2222){
                                $noofTimesCondomUsedBeforePregnancy = "rna";
                            }
                        }
                        $noofTimesCondomUsedAfterPregnancy = '';
                        if($aRow['no_of_times_condom_used_after_pregnancy']!= null && trim($aRow['no_of_times_condom_used_after_pregnancy'])!= ''){
                            if($aRow['no_of_times_condom_used_after_pregnancy'] == 1){
                                $noofTimesCondomUsedAfterPregnancy = "always";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 2){
                                $noofTimesCondomUsedAfterPregnancy = "sometimes";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 3){
                                $noofTimesCondomUsedAfterPregnancy = "never";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 88){
                                $noofTimesCondomUsedAfterPregnancy = "dk";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 99){
                                $noofTimesCondomUsedAfterPregnancy = "r";
                            }else if($aRow['no_of_times_condom_used_after_pregnancy'] == 2222){
                                $noofTimesCondomUsedAfterPregnancy = "rna";
                            }
                        }
                        $hasPatientHadPaininLowerAbdomen = '';
                        if($aRow['has_patient_had_pain_in_lower_abdomen']!= null && trim($aRow['has_patient_had_pain_in_lower_abdomen'])!= ''){
                            if($aRow['has_patient_had_pain_in_lower_abdomen'] == 1){
                                $hasPatientHadPaininLowerAbdomen = "yes";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 2){
                                $hasPatientHadPaininLowerAbdomen = "no";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 88){
                                $hasPatientHadPaininLowerAbdomen = "dk";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 99){
                                $hasPatientHadPaininLowerAbdomen = "r";
                            }else if($aRow['has_patient_had_pain_in_lower_abdomen'] == 2222){
                                $hasPatientHadPaininLowerAbdomen = "rna";
                            }
                        }
                        $hasPatientBeenTreatedforLowerAbdomenPain = '';
                        if($aRow['has_patient_been_treated_for_lower_abdomen_pain']!= null && trim($aRow['has_patient_been_treated_for_lower_abdomen_pain'])!= ''){
                            if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 1){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "yes";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 2){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "no";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 88){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "dk";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 99){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "r";
                            }else if($aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 2222){
                                $hasPatientBeenTreatedforLowerAbdomenPain = "rna";
                            }
                        }
                        $hasPatientEverBeenTreatedforSyphilis = '';
                        if($aRow['has_patient_ever_been_treated_for_syphilis']!= null && trim($aRow['has_patient_ever_been_treated_for_syphilis'])!= ''){
                            if($aRow['has_patient_ever_been_treated_for_syphilis'] == 1){
                                $hasPatientEverBeenTreatedforSyphilis = "yes";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 2){
                                $hasPatientEverBeenTreatedforSyphilis = "no";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 88){
                                $hasPatientEverBeenTreatedforSyphilis = "dk";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 99){
                                $hasPatientEverBeenTreatedforSyphilis = "r";
                            }else if($aRow['has_patient_ever_been_treated_for_syphilis'] == 2222){
                                $hasPatientEverBeenTreatedforSyphilis = "rna";
                            }
                        }
                        $hasPatientEverReceivedVaccinetoPreventCervicalCancer = '';
                        if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer']!= null && trim($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'])!= ''){
                            if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 1){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "yes";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 2){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "no";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 88){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "dk";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 99){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "r";
                            }else if($aRow['has_patient_ever_received_vaccine_to_prevent_cervical_cancer'] == 2222){
                                $hasPatientEverReceivedVaccinetoPreventCervicalCancer = "rna";
                            }
                        }
                        //alcohol and drug use
                        $patientHadDrinkwithAlcoholinLastSixMonths = '';
                        if($aRow['patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['patient_had_drink_with_alcohol_in_last_six_months'])!= ''){
                            if($aRow['patient_had_drink_with_alcohol_in_last_six_months'][0] == '@'){
                                $patientHadDrinkwithAlcoholinLastSixMonths = substr($aRow['patient_had_drink_with_alcohol_in_last_six_months'],1);
                            }else if($aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 88){
                                $patientHadDrinkwithAlcoholinLastSixMonths = 888;
                            }else if($aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 99){
                                $patientHadDrinkwithAlcoholinLastSixMonths = 777;
                            }else if($aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 2222 || $aRow['patient_had_drink_with_alcohol_in_last_six_months'] == 'not applicable'){
                                $patientHadDrinkwithAlcoholinLastSixMonths = 999;
                            }
                        }
                        $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = '';
                        if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']!= null && trim($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'])!= ''){
                            if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 1){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "never";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 2){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "monthly";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 3){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "weekly";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 4){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "daily";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 88){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "dk";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 99){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "r";
                            }else if($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 2222){
                                $hasPatientOftenHad4rmoreDrinkswithAlcoholonOneOccasion = "rna";
                            }
                        }
                        $hasPatientEverTriedRecreationalDrugs = '';
                        if($aRow['has_patient_ever_tried_recreational_drugs']!= null && trim($aRow['has_patient_ever_tried_recreational_drugs'])!= ''){
                            if($aRow['has_patient_ever_tried_recreational_drugs'] == 1){
                                $hasPatientEverTriedRecreationalDrugs = "yes";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 2){
                                $hasPatientEverTriedRecreationalDrugs = "no";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 88){
                                $hasPatientEverTriedRecreationalDrugs = "dk";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 99){
                                $hasPatientEverTriedRecreationalDrugs = "r";
                            }else if($aRow['has_patient_ever_tried_recreational_drugs'] == 2222){
                                $hasPatientEverTriedRecreationalDrugs = "rna";
                            }
                        }
                        $hasPatientHadRecreationalDrugsInLastSixMonths = '';
                        $recreationalDrugs = '';
                        if($aRow['has_patient_had_recreational_drugs_in_last_six_months']!= null && trim($aRow['has_patient_had_recreational_drugs_in_last_six_months'])!= ''){
                            $recreationaldata = json_decode($aRow['has_patient_had_recreational_drugs_in_last_six_months'],true);
                            $hasHadinLastSixMonths = (isset($recreationaldata['has_had_in_last_six_months']))?$recreationaldata['has_had_in_last_six_months']:'';
                            $recreationalDrugs = (isset($recreationaldata['drugs']))?$recreationaldata['drugs']:'';
                            if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 1){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "yes";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 2){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "no";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 88){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "dk";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 99){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "r";
                            }else if(trim($hasHadinLastSixMonths)!= '' && (int)$hasHadinLastSixMonths == 2222){
                                $hasPatientHadRecreationalDrugsInLastSixMonths = "rna";
                            }
                        }
                        //abuse
                        //patient abused by
                        $hasPatientEverBeenAbusedBySomeone = '';
                        $patientAbusedBy = '';
                        $patientAbusedByOther = '';
                        $patientAbusedByInNoofTimes = '';
                        if($aRow['has_patient_ever_been_abused_by_someone']!= null && trim($aRow['has_patient_ever_been_abused_by_someone'])!= ''){
                            $patientAbusedBydata = json_decode($aRow['has_patient_ever_been_abused_by_someone'],true);
                            $hasPatientAbusedBy = (isset($patientAbusedBydata['ever_abused']))?(int)$patientAbusedBydata['ever_abused']:'';
                            if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 1){
                                $hasPatientEverBeenAbusedBySomeone = "yes";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2){
                                $hasPatientEverBeenAbusedBySomeone = "no";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 88){
                                $hasPatientEverBeenAbusedBySomeone = "dk";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 99){
                                $hasPatientEverBeenAbusedBySomeone = "r";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2222){
                                $hasPatientEverBeenAbusedBySomeone = "rna";
                            }
                            
                            if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused'])!= '' && $patientAbusedBydata['who_abused'][0] == '@'){
                                $patientAbusedBy = 'other';
                                $patientAbusedByOther = substr($patientAbusedBydata['who_abused'],1);
                            }else if(isset($patientAbusedBydata['who_abused']) && $patientAbusedBydata['who_abused']!= null && trim($patientAbusedBydata['who_abused'])!= 'not applicable'){
                                $abusedppl = explode(',',$patientAbusedBydata['who_abused']);
                                $abusedGroup = array();
                                for($i=0;$i<count($abusedppl);$i++){
                                    $abusedGroup[] = $keyArray[$abusedppl[$i]];
                                }
                                $patientAbusedBy = str_replace(',',', ',implode(',',$abusedGroup));
                            }
                            
                            if(isset($patientAbusedBydata['no_of_times']) && trim($patientAbusedBydata['no_of_times'])!= '' && $patientAbusedBydata['no_of_times'][0] == '@'){
                                $patientAbusedByInNoofTimes = substr($patientAbusedBydata['no_of_times'],1);
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 88){
                               $patientAbusedByInNoofTimes = 888; 
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 99){
                               $patientAbusedByInNoofTimes = 777;
                            }else if(isset($patientAbusedBydata['no_of_times']) && ($patientAbusedBydata['no_of_times'] == 2222 || $patientAbusedBydata['no_of_times'] == 'not applicable')){
                               $patientAbusedByInNoofTimes = 999;
                            }
                        }
                        //patient hurt by someone within last year
                        $hasPatientHurtBySomeoneWithinLastYear = '';
                        $patientHurtByWithinLastYear = '';
                        $patientHurtByWithinLastYearByOther = '';
                        $patientHurtByInNoofTimes = '';
                        if($aRow['has_patient_ever_been_hurt_by_someone_within_last_year']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'])!= ''){
                            $patientHurtBydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'],true);
                            $hasPatientHurtByWithinLastYear = (isset($patientHurtBydata['ever_hurt']))?(int)$patientHurtBydata['ever_hurt']:'';
                            if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 1){
                                $hasPatientHurtBySomeoneWithinLastYear = "yes";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2){
                                $hasPatientHurtBySomeoneWithinLastYear = "no";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 88){
                                $hasPatientHurtBySomeoneWithinLastYear = "dk";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 99){
                                $hasPatientHurtBySomeoneWithinLastYear = "r";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2222){
                                $hasPatientHurtBySomeoneWithinLastYear = "rna";
                            }
                           
                            if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt'])!= '' && $patientHurtBydata['who_hurt'][0] == '@'){
                                $patientHurtByWithinLastYear = 'other';
                                $patientHurtByWithinLastYearByOther = substr($patientHurtBydata['who_hurt'],1);
                            }else if(isset($patientHurtBydata['who_hurt']) && $patientHurtBydata['who_hurt']!= null && trim($patientHurtBydata['who_hurt'])!= 'not applicable'){
                                $hurtedppl = explode(',',$patientHurtBydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByWithinLastYear = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            
                            if(isset($patientHurtBydata['no_of_times']) && trim($patientHurtBydata['no_of_times'])!='' && $patientHurtBydata['no_of_times'][0] == '@'){
                                $patientHurtByInNoofTimes = substr($patientHurtBydata['no_of_times'],1);
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 88){
                               $patientHurtByInNoofTimes = 888; 
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 99){
                               $patientHurtByInNoofTimes = 777;
                            }else if(isset($patientHurtBydata['no_of_times']) && ($patientHurtBydata['no_of_times'] == 2222 || $patientHurtBydata['no_of_times'] == 'not applicable')){
                               $patientHurtByInNoofTimes = 999;
                            }
                        }
                        //patient hurt by someone during pregnancy
                        $hasPatientHurtBySomeoneDuringPregnancy = '';
                        $patientHurtByDuringPregnancy = '';
                        $patientHurtByOtherDuringPregnancy = '';
                        $patientHurtByDuringPregnancyInNoofTimes = '';
                        if(isset($hasPatientHurtByWithinLastYear) && trim($hasPatientHurtByWithinLastYear) == 1 && $aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'])!= ''){
                            $patientHurtByDuringPregnancydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'],true);
                            $hasPatientHurtByDuringPregnancy = (isset($patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']))?(int)$patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']:'';
                            if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 1){
                                $hasPatientHurtBySomeoneDuringPregnancy = "yes";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2){
                                $hasPatientHurtBySomeoneDuringPregnancy = "no";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 88){
                                $hasPatientHurtBySomeoneDuringPregnancy = "dk";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 99){
                                $hasPatientHurtBySomeoneDuringPregnancy = "r";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2222){
                                $hasPatientHurtBySomeoneDuringPregnancy = "rna";
                            }
                            
                            if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt'])!= '' && $patientHurtByDuringPregnancydata['who_hurt'][0] == '@'){
                                $patientHurtByDuringPregnancy = 'other';
                                $patientHurtByOtherDuringPregnancy = substr($patientHurtByDuringPregnancydata['who_hurt'],1);
                            }else if(isset($patientHurtByDuringPregnancydata['who_hurt']) && $patientHurtByDuringPregnancydata['who_hurt']!= null && trim($patientHurtByDuringPregnancydata['who_hurt'])!= 'not applicable'){
                                $hurtedppl = explode(',',$patientHurtByDuringPregnancydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByDuringPregnancy = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            
                            if(isset($patientHurtByDuringPregnancydata['no_of_times']) && trim($patientHurtByDuringPregnancydata['no_of_times'])!='' && $patientHurtByDuringPregnancydata['no_of_times'][0] == '@'){
                                $patientHurtByDuringPregnancyInNoofTimes = substr($patientHurtByDuringPregnancydata['no_of_times'],1);
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 88){
                               $patientHurtByDuringPregnancyInNoofTimes = 888; 
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 99){
                               $patientHurtByDuringPregnancyInNoofTimes = 777;
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && ($patientHurtByDuringPregnancydata['no_of_times'] == 2222 || $patientHurtByDuringPregnancydata['no_of_times'] == 'not applicable')){
                               $patientHurtByDuringPregnancyInNoofTimes = 999;
                            }
                        }
                        //patient forced for sex within last year
                        $hasPatientForcedforSexBySomeoneWithinLastYear = '';
                        $patientForcedforSexBy = '';
                        $patientForcedforSexByOther = '';
                        $patientForcedforSexInNoofTimes = '';
                        if($aRow['has_patient_ever_been_forced_for_sex_within_last_year']!= null && trim($aRow['has_patient_ever_been_forced_for_sex_within_last_year'])!= ''){
                            $patientForcedforSexdata = json_decode($aRow['has_patient_ever_been_forced_for_sex_within_last_year'],true);
                            $hasPatientForcedforSexWithinLastYear = (isset($patientForcedforSexdata['ever_forced_for_sex']))?(int)$patientForcedforSexdata['ever_forced_for_sex']:'';
                            if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 1){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "yes";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "no";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 88){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "dk";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 99){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "r";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2222){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "rna";
                            }
                            
                            if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced'])!= '' && $patientForcedforSexdata['who_forced'][0] == '@'){
                                $patientForcedforSexBy = 'other';
                                $patientForcedforSexByOther = substr($patientForcedforSexdata['who_forced'],1);
                            }else if(isset($patientForcedforSexdata['who_forced']) && $patientForcedforSexdata['who_forced']!= null && trim($patientForcedforSexdata['who_forced'])!= 'not applicable'){
                                $forcedbyppl = explode(',',$patientForcedforSexdata['who_forced']);
                                $forcedbyGroup = array();
                                for($i=0;$i<count($forcedbyppl);$i++){
                                    $forcedbyGroup[] = $keyArray[$forcedbyppl[$i]];
                                }
                                $patientForcedforSexBy = str_replace(',',', ',implode(',',$forcedbyGroup));
                            }
                            
                            if(isset($patientForcedforSexdata['no_of_times']) && trim($patientForcedforSexdata['no_of_times'])!='' && $patientForcedforSexdata['no_of_times'][0] == '@'){
                                $patientForcedforSexInNoofTimes = substr($patientForcedforSexdata['no_of_times'],1);
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 88){
                               $patientForcedforSexInNoofTimes = 888;
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 99){
                               $patientForcedforSexInNoofTimes = 777;
                            }else if(isset($patientForcedforSexdata['no_of_times']) && ($patientForcedforSexdata['no_of_times'] == 2222 || $patientForcedforSexdata['no_of_times'] == 'not applicable')){
                               $patientForcedforSexInNoofTimes = 999;
                            }
                        }
                        $hasPatientAfraidofAnyone = '';
                        $patientAfraidofAnyone = '';
                        $patientAfraidofOther = '';
                        if($aRow['form_version'] == 2){
                            if($aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                                $patientAfraidofPeople = explode(',',$aRow['is_patient_afraid_of_anyone']);
                                $patientAfraidofGroup = array();
                                for($i=0;$i<count($patientAfraidofPeople);$i++){
                                    $patientAfraidofGroup[] = $keyArray[$patientAfraidofPeople[$i]];
                                }
                                $patientAfraidofAnyone = str_replace(',',', ',implode(',',$patientAfraidofGroup));
                            }else if($aRow['patient_afraid_of_other']!= null && trim($aRow['patient_afraid_of_other'])!= ''){
                               $patientAfraidofAnyone = 'other';
                               $patientAfraidofOther = $aRow['patient_afraid_of_other'];
                            }
                        }else{
                            if($aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                                if($aRow['is_patient_afraid_of_anyone'] == 1){
                                    $hasPatientAfraidofAnyone = "yes";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 2){
                                    $hasPatientAfraidofAnyone = "no";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 88){
                                    $hasPatientAfraidofAnyone = "dk";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 99){
                                    $hasPatientAfraidofAnyone = "r";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 2222){
                                    $hasPatientAfraidofAnyone = "rna";
                                }
                            }
                        }
                        //Go girls teen club
	                //club participation
                        $hasPatientEverParticipatedInaClupforAdolescents = '';
                        if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents']!= null && trim($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'])!= ''){
                            if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'] == 1){
                                $hasPatientEverParticipatedInaClupforAdolescents = "yes a go girls club";
                            }else if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'] == 2){
                                $hasPatientEverParticipatedInaClupforAdolescents = "yes a different club";
                            }else if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'] == 3){
                                $hasPatientEverParticipatedInaClupforAdolescents = "no";
                            }else if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'] == 88){
                                $hasPatientEverParticipatedInaClupforAdolescents = "don't know";
                            }else if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'] == 99){
                                $hasPatientEverParticipatedInaClupforAdolescents = "refused";
                            }else if($aRow['has_patient_ever_participated_in_a_clup_for_adolescents'] == 2222){
                                $hasPatientEverParticipatedInaClupforAdolescents = "response not available";
                            }
                        }
                        $timeofPatientParticipateinClub = '';
                        if($aRow['time_of_patient_participate_in_club']!= null && trim($aRow['time_of_patient_participate_in_club'])!= ''){
                            if($aRow['time_of_patient_participate_in_club'] == 1){
                                $timeofPatientParticipateinClub = "currently participating";
                            }else if($aRow['time_of_patient_participate_in_club'] == 2){
                                $timeofPatientParticipateinClub = "<3 mos ago";
                            }else if($aRow['time_of_patient_participate_in_club'] == 3){
                                $timeofPatientParticipateinClub = "3-6 mos ago";
                            }else if($aRow['time_of_patient_participate_in_club'] == 4){
                                $timeofPatientParticipateinClub = "7-12 mos ago";
                            }else if($aRow['time_of_patient_participate_in_club'] == 5){
                                $timeofPatientParticipateinClub = "12 mos ago";
                            }else if($aRow['time_of_patient_participate_in_club'] == 88){
                                $timeofPatientParticipateinClub = "don't know";
                            }else if($aRow['time_of_patient_participate_in_club'] == 99){
                                $timeofPatientParticipateinClub = "refused";
                            }else if($aRow['time_of_patient_participate_in_club'] == 2222){
                                $timeofPatientParticipateinClub = "response not available";
                            }
                        }
                        $hasPatientEverParticipatedinMothersGroup = '';
                        if($aRow['has_patient_ever_participated_in_mothers_group']!= null && trim($aRow['has_patient_ever_participated_in_mothers_group'])!= ''){
                            if($aRow['has_patient_ever_participated_in_mothers_group'] == 1){
                                $hasPatientEverParticipatedinMothersGroup = "yes";
                            }else if($aRow['has_patient_ever_participated_in_mothers_group'] == 2){
                                $hasPatientEverParticipatedinMothersGroup = "no";
                            }else if($aRow['has_patient_ever_participated_in_mothers_group'] == 88){
                                $hasPatientEverParticipatedinMothersGroup = "dk";
                            }else if($aRow['has_patient_ever_participated_in_mothers_group'] == 99){
                                $hasPatientEverParticipatedinMothersGroup = "r";
                            }else if($aRow['has_patient_ever_participated_in_mothers_group'] == 2222){
                                $hasPatientEverParticipatedinMothersGroup = "rna";
                            }
                        }
                        $row = array();
                        $row[] = $aRow['anc_site_code'].'-'.$aRow['anc_site_name'];
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = (isset($aRow['age']) && (int)$aRow['age'] > 0)?$aRow['age']:'';
                        $row[] = $aRow['interviewer_name'];
                        $row[] = $aRow['anc_patient_id'];
                        $row[] = $interviewDate;
                        //$row[] = (isset($aRow['has_participant_received_dreams_services']) && $aRow['has_participant_received_dreams_services']!= null && trim($aRow['has_participant_received_dreams_services'])!= '')?$aRow['has_participant_received_dreams_services']:'';
                        $row[] = $participantAge;
                        $row[] = $occupation;
                        $row[] = $occupationOther;
                        $row[] = $hasPatientEverAttendedSchool;
                        $row[] = $degree;
                        $row[] = $isParticipantAttendingSchoolNow;
                        $row[] = $patientEverBeenMarried;
                        $row[] = $ageAtFirstMarriage;
                        $row[] = $patientEverBeenWidowed;
                        $row[] = $maritalStatus;
                        $row[] = $hasPatientEverBeenTestedforHIV;
                        $row[] = $timeofMostRecentHIVTest;
                        $row[] = $resultofMostRecentHIVTest;
                        $row[] = $placeofLastHIVTest;
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
                        $row[] = $hasPatientWantedtoGetPregnant;
                        $row[] = $hasPatientWantedtoHaveaBabyLater;
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
                        $row[] = $recreationalDrugs;
                        $row[] = $hasPatientEverBeenAbusedBySomeone;
                        $row[] = $patientAbusedBy;
                        $row[] = $patientAbusedByOther;
                        $row[] = $patientAbusedByInNoofTimes;
                        $row[] = $hasPatientHurtBySomeoneWithinLastYear;
                        $row[] = $patientHurtByWithinLastYear;
                        $row[] = $patientHurtByWithinLastYearByOther;
                        $row[] = $patientHurtByInNoofTimes;
                        $row[] = $hasPatientHurtBySomeoneDuringPregnancy;
                        $row[] = $patientHurtByDuringPregnancy;
                        $row[] = $patientHurtByOtherDuringPregnancy;
                        $row[] = $patientHurtByDuringPregnancyInNoofTimes;
                        $row[] = $hasPatientForcedforSexBySomeoneWithinLastYear;
                        $row[] = $patientForcedforSexBy;
                        $row[] = $patientForcedforSexByOther;
                        $row[] = $patientForcedforSexInNoofTimes;
                        $row[] = $hasPatientAfraidofAnyone;
                        $row[] = $patientAfraidofAnyone;
                        $row[] = $patientAfraidofOther;
                        $row[] = $hasPatientEverParticipatedInaClupforAdolescents;
                        $row[] = $timeofPatientParticipateinClub;
                        $row[] = $hasPatientEverParticipatedinMothersGroup;
                        $row[] = $aRow['comment'];
                        $row[] = $addedDate;
                        $row[] = (isset($aRow['addedBy']))?ucwords($aRow['addedBy']):'';
                        $row[] = $updatedDate;
                        $row[] = (isset($aRow['updatedBy']))?ucwords($aRow['updatedBy']):'';
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
                    //Merge section
                    $sheet->mergeCells('A1:A3');
                    $sheet->mergeCells('B1:B3');
                    $sheet->mergeCells('C1:C3');
                    $sheet->mergeCells('D1:D3');
                    $sheet->mergeCells('E1:E3');
                    $sheet->mergeCells('F1:F3');
                    
                    $sheet->mergeCells('G1:P2');
                    
                    $sheet->mergeCells('Q1:T2');
                    
                    $sheet->mergeCells('U1:AN2');
                    
                    $sheet->mergeCells('AO1:AS2');
                    
                    $sheet->mergeCells('AT1:BL1');
                    $sheet->mergeCells('AT2:AW2');
                    $sheet->mergeCells('AX2:BA2');
                    $sheet->mergeCells('BB2:BE2');
                    $sheet->mergeCells('BF2:BI2');
                    $sheet->mergeCells('BK2:BL2');
                    
                    $sheet->mergeCells('BM1:BO2');
                    
                    $sheet->mergeCells('BP1:BP3');
                    $sheet->mergeCells('BQ1:BQ3');
                    $sheet->mergeCells('BR1:BR3');
                    $sheet->mergeCells('BS1:BS3');
                    $sheet->mergeCells('BT1:BT3');
                    
                    //Label section
                    $sheet->setCellValue('A1', html_entity_decode('ANC Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Age from Lab Request ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Interviewer Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Interview Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    //$sheet->setCellValue('F1', html_entity_decode('Has Participant received DREAMS services ?', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G1', html_entity_decode('Demographic Characteristics ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G3', html_entity_decode('What is your age? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H3', html_entity_decode('What kind of work/occupation do you do most of the time? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I3', html_entity_decode('If other, then specify ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J3', html_entity_decode('Have you ever attended school? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K3', html_entity_decode('What was the highest level of education that you completed or are attending now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L3', html_entity_decode('Are you attending school now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M3', html_entity_decode('Have you ever been married or lived with a partner in a union as if married? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N3', html_entity_decode('How old were you when you first got married or lived with a partner in a union? (in years)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O3', html_entity_decode('Have you ever been widowed? That is, did a spouse ever pass away while you were still married or living with them? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P3', html_entity_decode('What is your marital status now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('Q1', html_entity_decode('HIV Testing History ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q3', html_entity_decode('[BEFORE TODAY] Have you ever been tested for HIV? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R3', html_entity_decode('When was the most recent time you were tested for HIV? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S3', html_entity_decode('What was the result of that HIV test? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T3', html_entity_decode('Where were you tested for HIV in the community or at a facility? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('U1', html_entity_decode('Sexual Activity and History of Sexually Transmitted Infections ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U3', html_entity_decode('How old were you when you had sexual intercourse for the very first time? (in years) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V3', html_entity_decode('The first time you had sex, was it because you wanted to or because you were forced to? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W3', html_entity_decode('How many different people have you had sexual intercourse with in your entire life? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X3', html_entity_decode('How many different people have you had sex with in the last 6 months? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y3', html_entity_decode('What is the HIV status of your spouse/main sexual partner\'s (person with whom you have sexual intercourse most frequently)? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z3', html_entity_decode('How old was your spouse/main sexual partner on his last birthday? (in years) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA3', html_entity_decode('Is the age of your spouse/main sexual partner older, younger, or the same age as you? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB3', html_entity_decode('Is your spouse/main sexual partner circumcised? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC3', html_entity_decode('What type of circumcision? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD3', html_entity_decode('Have you ever received money/gifts in exchange for sex? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AE3', html_entity_decode('When did you last receive money/gifts in exchange for sex? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AF3', html_entity_decode('How many times have you been pregnant? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AG3', html_entity_decode('When you got pregnant this time, did you want to get pregnant? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AH3', html_entity_decode('Did you want to have a baby later on or did you not want any(more) children? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AI3', html_entity_decode('Before becoming pregnant this time, in the past year how frequently did you use a condom when having sexual intercourse? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AJ3', html_entity_decode('Since becoming pregnant this time, how frequently have you used a condom when having sexual intercourse? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AK3', html_entity_decode('In the past year, did you have symptoms such as abnormal genital discharge, sores in your genital area, pain during urination, or pain in your lower abdomen? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AL3', html_entity_decode('Were you treated for these symptoms? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AM3', html_entity_decode('Have you ever been diagnosed or treated for syphilis? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AN3', html_entity_decode('Have you ever received a vaccine (HPV vaccine) to prevent cervical cancer, which is caused by a common virus that can be passed through sexual contact? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AO1', html_entity_decode('Alcohol and drug use ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AO3', html_entity_decode('During the past 6 months, on how many days did you have at least one drink containing alcohol? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AP3', html_entity_decode('How often do you have 4 or more drinks with alcohol on one occasion? (e.g. within 2 hours) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AQ3', html_entity_decode('Have you ever tried recreational drugs? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AR3', html_entity_decode('In the last 6 months, have you taken any recreational drugs? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AS3', html_entity_decode('If yes, then specify drug(s) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AT1', html_entity_decode('Abuse ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AT2', html_entity_decode('Have you ever been emotionally or physically abused by your partner or your loved one? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AT3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AU3', html_entity_decode('Abused by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AV3', html_entity_decode('Abused by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AW3', html_entity_decode('No.of times abused ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AX2', html_entity_decode('Within the last year, have you ever been hit, slapped, kicked, or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AX3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AY3', html_entity_decode('Hurted by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AZ3', html_entity_decode('Hurted by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BA3', html_entity_decode('No.of times hurted ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BB2', html_entity_decode('Since you\'ve been pregnant have you been slapped, kicked or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BB3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BC3', html_entity_decode('Hurted by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BD3', html_entity_decode('Hurted by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BE3', html_entity_decode('No.of times hurted ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BF2', html_entity_decode('Within the last year, has anyone forced you to have sexual activities? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BF3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BG3', html_entity_decode('Forced by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BH3', html_entity_decode('Forced by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BI3', html_entity_decode('No.of times forced ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BJ2', html_entity_decode('Are you afraid of your partner or anyone listed above? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BJ3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BK2', html_entity_decode('Are you afraid of any of the following people? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BK3', html_entity_decode('Afraid of', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BL3', html_entity_decode('Afraid of other ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BM1', html_entity_decode('Go Girls Teen Club ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BM3', html_entity_decode('Have you ever participated in a club for adolescents? If YES, was it a Go Girls! club or a different club? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BN3', html_entity_decode('When did you participate in the club? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BO3', html_entity_decode('Have you ever participated in a mother\'s group? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BP1', html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BQ1', html_entity_decode('Added Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BR1', html_entity_decode('Added by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BS1', html_entity_decode('Last Updated Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BT1', html_entity_decode('Last Updated by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    //Style section
                    $sheet->getStyle('A1:A3')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B3')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C3')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D3')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E3')->applyFromArray($styleArray);
                    $sheet->getStyle('F1:F3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('G1:N2')->applyFromArray($styleArray);
                    $sheet->getStyle('G3')->applyFromArray($styleArray);
                    $sheet->getStyle('H3')->applyFromArray($styleArray);
                    $sheet->getStyle('I3')->applyFromArray($styleArray);
                    $sheet->getStyle('J3')->applyFromArray($styleArray);
                    $sheet->getStyle('K3')->applyFromArray($styleArray);
                    $sheet->getStyle('L3')->applyFromArray($styleArray);
                    $sheet->getStyle('M3')->applyFromArray($styleArray);
                    $sheet->getStyle('N3')->applyFromArray($styleArray);
                    $sheet->getStyle('O3')->applyFromArray($styleArray);
                    $sheet->getStyle('P3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('Q1:T2')->applyFromArray($styleArray);
                    $sheet->getStyle('Q3')->applyFromArray($styleArray);
                    $sheet->getStyle('R3')->applyFromArray($styleArray);
                    $sheet->getStyle('S3')->applyFromArray($styleArray);
                    $sheet->getStyle('T3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('U1:AN2')->applyFromArray($styleArray);
                    $sheet->getStyle('U3')->applyFromArray($styleArray);
                    $sheet->getStyle('V3')->applyFromArray($styleArray);
                    $sheet->getStyle('W3')->applyFromArray($styleArray);
                    $sheet->getStyle('X3')->applyFromArray($styleArray);
                    $sheet->getStyle('Y3')->applyFromArray($styleArray);
                    $sheet->getStyle('Z3')->applyFromArray($styleArray);
                    $sheet->getStyle('AA3')->applyFromArray($styleArray);
                    $sheet->getStyle('AB3')->applyFromArray($styleArray);
                    $sheet->getStyle('AC3')->applyFromArray($styleArray);
                    $sheet->getStyle('AD3')->applyFromArray($styleArray);
                    $sheet->getStyle('AE3')->applyFromArray($styleArray);
                    $sheet->getStyle('AF3')->applyFromArray($styleArray);
                    $sheet->getStyle('AG3')->applyFromArray($styleArray);
                    $sheet->getStyle('AH3')->applyFromArray($styleArray);
                    $sheet->getStyle('AI3')->applyFromArray($styleArray);
                    $sheet->getStyle('AJ3')->applyFromArray($styleArray);
                    $sheet->getStyle('AK3')->applyFromArray($styleArray);
                    $sheet->getStyle('AL3')->applyFromArray($styleArray);
                    $sheet->getStyle('AM3')->applyFromArray($styleArray);
                    $sheet->getStyle('AN3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('AO1:AS2')->applyFromArray($styleArray);
                    $sheet->getStyle('AO3')->applyFromArray($styleArray);
                    $sheet->getStyle('AP3')->applyFromArray($styleArray);
                    $sheet->getStyle('AQ3')->applyFromArray($styleArray);
                    $sheet->getStyle('AR3')->applyFromArray($styleArray);
                    $sheet->getStyle('AS3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('AT1:BL1')->applyFromArray($styleArray);
                    $sheet->getStyle('AT2:AW2')->applyFromArray($styleArray);
                    $sheet->getStyle('AT3')->applyFromArray($styleArray);
                    $sheet->getStyle('AU3')->applyFromArray($styleArray);
                    $sheet->getStyle('AV3')->applyFromArray($styleArray);
                    $sheet->getStyle('AW3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('AX2:BA2')->applyFromArray($styleArray);
                    $sheet->getStyle('AX3')->applyFromArray($styleArray);
                    $sheet->getStyle('AY3')->applyFromArray($styleArray);
                    $sheet->getStyle('AZ3')->applyFromArray($styleArray);
                    $sheet->getStyle('BA3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BB2:BE2')->applyFromArray($styleArray);
                    $sheet->getStyle('BB3')->applyFromArray($styleArray);
                    $sheet->getStyle('BC3')->applyFromArray($styleArray);
                    $sheet->getStyle('BD3')->applyFromArray($styleArray);
                    $sheet->getStyle('BE3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BF2:BI2')->applyFromArray($styleArray);
                    $sheet->getStyle('BF3')->applyFromArray($styleArray);
                    $sheet->getStyle('BG3')->applyFromArray($styleArray);
                    $sheet->getStyle('BH3')->applyFromArray($styleArray);
                    $sheet->getStyle('BI3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BJ2')->applyFromArray($styleArray);
                    $sheet->getStyle('BJ3')->applyFromArray($styleArray);
                    $sheet->getStyle('BK2:BL2')->applyFromArray($styleArray);
                    $sheet->getStyle('BK3')->applyFromArray($styleArray);
                    $sheet->getStyle('BL3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BM1:BO2')->applyFromArray($styleArray);
                    $sheet->getStyle('BM3')->applyFromArray($styleArray);
                    $sheet->getStyle('BN3')->applyFromArray($styleArray);
                    $sheet->getStyle('BO3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BP1:BP3')->applyFromArray($styleArray);
                    $sheet->getStyle('BQ1:BQ3')->applyFromArray($styleArray);
                    $sheet->getStyle('BR1:BR3')->applyFromArray($styleArray);
                    $sheet->getStyle('BS1:BS3')->applyFromArray($styleArray);
                    $sheet->getStyle('BT1:BT3')->applyFromArray($styleArray);
                    
                    $currentRow = 4;
                    $sheet->setCellValue('A'.$currentRow, html_entity_decode('ANCsite', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B'.$currentRow, html_entity_decode('PatientBarcodeID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C'.$currentRow, html_entity_decode('age', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D'.$currentRow, html_entity_decode('interviewer', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E'.$currentRow, html_entity_decode('ancID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F'.$currentRow, html_entity_decode('interviewdate', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G'.$currentRow, html_entity_decode('participantage', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H'.$currentRow, html_entity_decode('occupation', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I'.$currentRow, html_entity_decode('occupothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J'.$currentRow, html_entity_decode('education', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K'.$currentRow, html_entity_decode('educationlvl', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L'.$currentRow, html_entity_decode('isparticipantattendingschoolnow', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M'.$currentRow, html_entity_decode('evermarried', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N'.$currentRow, html_entity_decode('agemarried', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O'.$currentRow, html_entity_decode('everwidow', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P'.$currentRow, html_entity_decode('maritalstatus', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('Q'.$currentRow, html_entity_decode('everHIVtest', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R'.$currentRow, html_entity_decode('timelastHIVtest', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S'.$currentRow, html_entity_decode('resultlastHIVtest', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T'.$currentRow, html_entity_decode('placeoflastHIVtest', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('U'.$currentRow, html_entity_decode('agefirstsex', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V'.$currentRow, html_entity_decode('forcedfirstsex', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W'.$currentRow, html_entity_decode('sexptnrlife', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X'.$currentRow, html_entity_decode('sexptnr6m', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y'.$currentRow, html_entity_decode('ptnrHIVstat', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z'.$currentRow, html_entity_decode('ptnrage', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA'.$currentRow, html_entity_decode('ptnragedif', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB'.$currentRow, html_entity_decode('circumcised', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC'.$currentRow, html_entity_decode('circumtype', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD'.$currentRow, html_entity_decode('gifts4sex', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AE'.$currentRow, html_entity_decode('gifts4sexlast', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AF'.$currentRow, html_entity_decode('pregnancies', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AG'.$currentRow, html_entity_decode('haspatientwantedtogetpregnant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AH'.$currentRow, html_entity_decode('haspatientwantedtohaveababylater', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AI'.$currentRow, html_entity_decode('condomyr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AJ'.$currentRow, html_entity_decode('condompreg', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AK'.$currentRow, html_entity_decode('STIsym', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AL'.$currentRow, html_entity_decode('STItreat', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AM'.$currentRow, html_entity_decode('syphilis', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AN'.$currentRow, html_entity_decode('HPVvaccine', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AO'.$currentRow, html_entity_decode('alcdays6m', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AP'.$currentRow, html_entity_decode('alcbingefreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AQ'.$currentRow, html_entity_decode('everdrugs', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AR'.$currentRow, html_entity_decode('drugs6m', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AS'.$currentRow, html_entity_decode('drugtype', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AT'.$currentRow, html_entity_decode('everabuse', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AU'.$currentRow, html_entity_decode('everabuseby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AV'.$currentRow, html_entity_decode('everabusebyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AW'.$currentRow, html_entity_decode('everabusefreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AX'.$currentRow, html_entity_decode('abuselastyr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AY'.$currentRow, html_entity_decode('abuselastyrby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AZ'.$currentRow, html_entity_decode('abuselastyrbyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BA'.$currentRow, html_entity_decode('abuselastyrfreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BB'.$currentRow, html_entity_decode('abusepreg', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BC'.$currentRow, html_entity_decode('abusepregby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BD'.$currentRow, html_entity_decode('abusepregbyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BE'.$currentRow, html_entity_decode('abusepregfreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BF'.$currentRow, html_entity_decode('forcedsexyr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BG'.$currentRow, html_entity_decode('forcedsexyrby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BH'.$currentRow, html_entity_decode('forcedsexbyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BI'.$currentRow, html_entity_decode('forcedsexyrfreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BJ'.$currentRow, html_entity_decode('afraid', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BK'.$currentRow, html_entity_decode('patientafraidofanyone', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BL'.$currentRow, html_entity_decode('patientafraidofother', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BM'.$currentRow, html_entity_decode('haspatienteverparticipatedinaclupforadolescents', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BN'.$currentRow, html_entity_decode('timeofpatientparticipateinclub', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BO'.$currentRow, html_entity_decode('haspatienteverparticipatedinmothersgroup', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('BP'.$currentRow, html_entity_decode('comments ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BQ'.$currentRow, html_entity_decode('adddate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BR'.$currentRow, html_entity_decode('addby ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BS'.$currentRow, html_entity_decode('update ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BT'.$currentRow, html_entity_decode('updateby ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->getStyle('A'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('B'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('C'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('D'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('E'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('F'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('G'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('H'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('I'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('J'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('K'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('L'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('M'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('N'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('O'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('P'.$currentRow)->applyFromArray($styleArray);
                    
                    
                    $sheet->getStyle('Q'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('R'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('S'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('T'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('U'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('V'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('W'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('X'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Y'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Z'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AA'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AB'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AC'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AD'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AE'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AF'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AG'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AH'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AI'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AJ'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AK'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AL'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AM'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AN'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('AO'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AP'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AQ'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AR'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AS'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('AT'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AU'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AV'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AW'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('AX'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AY'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AZ'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BA'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BB'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BC'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BD'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BE'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BF'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BG'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BH'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BI'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BJ'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BK'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BL'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BM'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BN'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BO'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('BP'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BQ'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BR'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BS'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BT'.$currentRow)->applyFromArray($styleArray);
                    
                    $currentRow = 5;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
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
                    return "na";
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
				   ->join(array('anc_r_r' => 'anc_rapid_recency'), "anc_r_r.assessment_id=r_a.assessment_id",array('anc_rapid_recency_id','has_patient_had_rapid_recency_test','control_line','HIV_diagnostic_line','recency_line'),'left');
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
                        $addedDate = '';
                        if(isset($aRow['added_on']) && $aRow['added_on']!= null && trim($aRow['added_on'])!= '' && $aRow['added_on']!= '0000-00-00 00:00:00'){
                            $addedDateArray = explode(' ',$aRow['added_on']);
                            $addedDate = $common->humanDateFormat($addedDateArray[0]).' '.$addedDateArray[1];
                        }
	                $updatedDate = '';
                        if(isset($aRow['updated_on']) && $aRow['updated_on']!= null && trim($aRow['updated_on'])!= '' && $aRow['updated_on']!= '0000-00-00 00:00:00'){
                            $updatedDateArray = explode(' ',$aRow['updated_on']);
                            $updatedDate = $common->humanDateFormat($updatedDateArray[0]).' '.$updatedDateArray[1];
                        }
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
                        $row[] = $addedDate;
                        $row[] = (isset($aRow['addedBy']))?ucwords($aRow['addedBy']):'';
                        $row[] = $updatedDate;
                        $row[] = (isset($aRow['updatedBy']))?ucwords($aRow['updatedBy']):'';
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
                    $sheet->setCellValue('F1', html_entity_decode('Added Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G1', html_entity_decode('Added by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H1', html_entity_decode('Last Updated Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I1', html_entity_decode('Last Updated by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                   
                    $sheet->getStyle('A1')->applyFromArray($styleArray);
                    $sheet->getStyle('B1')->applyFromArray($styleArray);
                    $sheet->getStyle('C1')->applyFromArray($styleArray);
                    $sheet->getStyle('D1')->applyFromArray($styleArray);
                    $sheet->getStyle('E1')->applyFromArray($styleArray);
                    $sheet->getStyle('F1')->applyFromArray($styleArray);
                    $sheet->getStyle('G1')->applyFromArray($styleArray);
                    $sheet->getStyle('H1')->applyFromArray($styleArray);
                    $sheet->getStyle('I1')->applyFromArray($styleArray);
                    
                    $currentRow = 2;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
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
                    return "na";
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
                    $keyArray = array('0'=>'','1'=>'husband','2'=>'exhusband','3'=>'boyfriend','4'=>'stranger','88'=>'dk','99'=>'r','2222'=>'rna');
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $addedDate = '';
                        if(isset($aRow['added_on']) && $aRow['added_on']!= null && trim($aRow['added_on'])!= '' && $aRow['added_on']!= '0000-00-00 00:00:00'){
                            $addedDateArray = explode(' ',$aRow['added_on']);
                            $addedDate = $common->humanDateFormat($addedDateArray[0]).' '.$addedDateArray[1];
                        }
	                $updatedDate = '';
                        if(isset($aRow['updated_on']) && $aRow['updated_on']!= null && trim($aRow['updated_on'])!= '' && $aRow['updated_on']!= '0000-00-00 00:00:00'){
                            $updatedDateArray = explode(' ',$aRow['updated_on']);
                            $updatedDate = $common->humanDateFormat($updatedDateArray[0]).' '.$updatedDateArray[1];
                        }
                        $interviewDate = '';
                        if(isset($aRow['interview_date']) && $aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
                            $interviewDate = $common->humanDateFormat($aRow['interview_date']);
                        }
                        //abuse
                        //patient abused by
                        $hasPatientEverBeenAbusedBySomeone = '';
                        $patientAbusedBy = '';
                        $patientAbusedByOther = '';
                        $patientAbusedByInNoofTimes = '';
                        if($aRow['has_patient_ever_been_abused_by_someone']!= null && trim($aRow['has_patient_ever_been_abused_by_someone'])!= ''){
                            $patientAbusedBydata = json_decode($aRow['has_patient_ever_been_abused_by_someone'],true);
                            $hasPatientAbusedBy = (isset($patientAbusedBydata['ever_abused']))?(int)$patientAbusedBydata['ever_abused']:'';
                            if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 1){
                                $hasPatientEverBeenAbusedBySomeone = "yes";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2){
                                $hasPatientEverBeenAbusedBySomeone = "no";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 88){
                                $hasPatientEverBeenAbusedBySomeone = "dk";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 99){
                                $hasPatientEverBeenAbusedBySomeone = "r";
                            }else if(trim($hasPatientAbusedBy)!= '' && (int)$hasPatientAbusedBy == 2222){
                                $hasPatientEverBeenAbusedBySomeone = "rna";
                            }
                            
                            if(isset($patientAbusedBydata['who_abused']) && trim($patientAbusedBydata['who_abused'])!= '' && $patientAbusedBydata['who_abused'][0] == '@'){
                                $patientAbusedBy = 'other';
                                $patientAbusedByOther = substr($patientAbusedBydata['who_abused'],1);
                            }else if(isset($patientAbusedBydata['who_abused']) && $patientAbusedBydata['who_abused']!= null && trim($patientAbusedBydata['who_abused'])!= 'not applicable'){
                                $abusedppl = explode(',',$patientAbusedBydata['who_abused']);
                                $abusedGroup = array();
                                for($i=0;$i<count($abusedppl);$i++){
                                    $abusedGroup[] = $keyArray[$abusedppl[$i]];
                                }
                                $patientAbusedBy = str_replace(',',', ',implode(',',$abusedGroup));
                            }
                            
                            if(isset($patientAbusedBydata['no_of_times']) && trim($patientAbusedBydata['no_of_times'])!= '' && $patientAbusedBydata['no_of_times'][0] == '@'){
                                $patientAbusedByInNoofTimes = substr($patientAbusedBydata['no_of_times'],1);
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 88){
                               $patientAbusedByInNoofTimes = 888; 
                            }else if(isset($patientAbusedBydata['no_of_times']) && $patientAbusedBydata['no_of_times'] == 99){
                               $patientAbusedByInNoofTimes = 777;
                            }else if(isset($patientAbusedBydata['no_of_times']) && ($patientAbusedBydata['no_of_times'] == 2222 || $patientAbusedBydata['no_of_times'] == 'not applicable')){
                               $patientAbusedByInNoofTimes = 999;
                            }
                        }
                        //patient hurt by someone within last year
                        $hasPatientHurtBySomeoneWithinLastYear = '';
                        $patientHurtByWithinLastYear = '';
                        $patientHurtByWithinLastYearByOther = '';
                        $patientHurtByInNoofTimes = '';
                        if($aRow['has_patient_ever_been_hurt_by_someone_within_last_year']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'])!= ''){
                            $patientHurtBydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'],true);
                            $hasPatientHurtByWithinLastYear = (isset($patientHurtBydata['ever_hurt']))?(int)$patientHurtBydata['ever_hurt']:'';
                            if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 1){
                                $hasPatientHurtBySomeoneWithinLastYear = "yes";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2){
                                $hasPatientHurtBySomeoneWithinLastYear = "no";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 88){
                                $hasPatientHurtBySomeoneWithinLastYear = "dk";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 99){
                                $hasPatientHurtBySomeoneWithinLastYear = "r";
                            }else if(trim($hasPatientHurtByWithinLastYear)!= '' && (int)$hasPatientHurtByWithinLastYear == 2222){
                                $hasPatientHurtBySomeoneWithinLastYear = "rna";
                            }
                           
                            if(isset($patientHurtBydata['who_hurt']) && trim($patientHurtBydata['who_hurt'])!= '' && $patientHurtBydata['who_hurt'][0] == '@'){
                                $patientHurtByWithinLastYear = 'other';
                                $patientHurtByWithinLastYearByOther = substr($patientHurtBydata['who_hurt'],1);
                            }else if(isset($patientHurtBydata['who_hurt']) && $patientHurtBydata['who_hurt']!= null && trim($patientHurtBydata['who_hurt'])!= 'not applicable'){
                                $hurtedppl = explode(',',$patientHurtBydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByWithinLastYear = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            
                            if(isset($patientHurtBydata['no_of_times']) && trim($patientHurtBydata['no_of_times'])!= '' && $patientHurtBydata['no_of_times'][0] == '@'){
                                $patientHurtByInNoofTimes = substr($patientHurtBydata['no_of_times'],1);
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 88){
                               $patientHurtByInNoofTimes = 888; 
                            }else if(isset($patientHurtBydata['no_of_times']) && $patientHurtBydata['no_of_times'] == 99){
                               $patientHurtByInNoofTimes = 777;
                            }else if(isset($patientHurtBydata['no_of_times']) && ($patientHurtBydata['no_of_times'] == 2222 || $patientHurtBydata['no_of_times'] == 'not applicable')){
                               $patientHurtByInNoofTimes = 999;
                            }
                        }
                        //patient hurt by someone during pregnancy
                        $hasPatientHurtBySomeoneDuringPregnancy = '';
                        $patientHurtByDuringPregnancy = '';
                        $patientHurtByOtherDuringPregnancy = '';
                        $patientHurtByDuringPregnancyInNoofTimes = '';
                        if(isset($hasPatientHurtByWithinLastYear) && trim($hasPatientHurtByWithinLastYear) == 1 && $aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'])!= ''){
                            $patientHurtByDuringPregnancydata = json_decode($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'],true);
                            $hasPatientHurtByDuringPregnancy = (isset($patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']))?(int)$patientHurtByDuringPregnancydata['ever_hurt_by_during_pregnancy']:'';
                            if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 1){
                                $hasPatientHurtBySomeoneDuringPregnancy = "yes";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2){
                                $hasPatientHurtBySomeoneDuringPregnancy = "no";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 88){
                                $hasPatientHurtBySomeoneDuringPregnancy = "dk";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 99){
                                $hasPatientHurtBySomeoneDuringPregnancy = "r";
                            }else if(trim($hasPatientHurtByDuringPregnancy)!= '' && (int)$hasPatientHurtByDuringPregnancy == 2222){
                                $hasPatientHurtBySomeoneDuringPregnancy = "rna";
                            }
                            
                            if(isset($patientHurtByDuringPregnancydata['who_hurt']) && trim($patientHurtByDuringPregnancydata['who_hurt'])!= '' && $patientHurtByDuringPregnancydata['who_hurt'][0] == '@'){
                                $patientHurtByDuringPregnancy = 'other';
                                $patientHurtByOtherDuringPregnancy = substr($patientHurtByDuringPregnancydata['who_hurt'],1);
                            }else if(isset($patientHurtByDuringPregnancydata['who_hurt']) && $patientHurtByDuringPregnancydata['who_hurt']!= null && trim($patientHurtByDuringPregnancydata['who_hurt'])!= 'not applicable'){
                                $hurtedppl = explode(',',$patientHurtByDuringPregnancydata['who_hurt']);
                                $hurtedGroup = array();
                                for($i=0;$i<count($hurtedppl);$i++){
                                    $hurtedGroup[] = $keyArray[$hurtedppl[$i]];
                                }
                                $patientHurtByDuringPregnancy = str_replace(',',', ',implode(',',$hurtedGroup));
                            }
                            
                            if(isset($patientHurtByDuringPregnancydata['no_of_times']) && trim($patientHurtByDuringPregnancydata['no_of_times'])!= '' && $patientHurtByDuringPregnancydata['no_of_times'][0] == '@'){
                                $patientHurtByDuringPregnancyInNoofTimes = substr($patientHurtByDuringPregnancydata['no_of_times'],1);
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 88){
                               $patientHurtByDuringPregnancyInNoofTimes = 888; 
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && $patientHurtByDuringPregnancydata['no_of_times'] == 99){
                               $patientHurtByDuringPregnancyInNoofTimes = 777;
                            }else if(isset($patientHurtByDuringPregnancydata['no_of_times']) && ($patientHurtByDuringPregnancydata['no_of_times'] == 2222 || $patientHurtByDuringPregnancydata['no_of_times'] == 'not applicable')){
                               $patientHurtByDuringPregnancyInNoofTimes = 999;
                            }
                        }
                        //patient forced for sex within last year
                        $hasPatientForcedforSexBySomeoneWithinLastYear = '';
                        $patientForcedforSexBy = '';
                        $patientForcedforSexByOther = '';
                        $patientForcedforSexInNoofTimes = '';
                        if($aRow['has_patient_ever_been_forced_for_sex_within_last_year']!= null && trim($aRow['has_patient_ever_been_forced_for_sex_within_last_year'])!= ''){
                            $patientForcedforSexdata = json_decode($aRow['has_patient_ever_been_forced_for_sex_within_last_year'],true);
                            $hasPatientForcedforSexWithinLastYear = (isset($patientForcedforSexdata['ever_forced_for_sex']))?(int)$patientForcedforSexdata['ever_forced_for_sex']:'';
                            if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 1){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "yes";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "no";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 88){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "dk";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 99){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "r";
                            }else if(trim($hasPatientForcedforSexWithinLastYear)!= '' && (int)$hasPatientForcedforSexWithinLastYear == 2222){
                                $hasPatientForcedforSexBySomeoneWithinLastYear = "rna";
                            }
                            
                            if(isset($patientForcedforSexdata['who_forced']) && trim($patientForcedforSexdata['who_forced'])!= '' && $patientForcedforSexdata['who_forced'][0] == '@'){
                                $patientForcedforSexBy = 'other';
                                $patientForcedforSexByOther = substr($patientForcedforSexdata['who_forced'],1);
                            }else if(isset($patientForcedforSexdata['who_forced']) && $patientForcedforSexdata['who_forced']!= null && trim($patientForcedforSexdata['who_forced'])!= 'not applicable'){
                                $forcedbyppl = explode(',',$patientForcedforSexdata['who_forced']);
                                $forcedbyGroup = array();
                                for($i=0;$i<count($forcedbyppl);$i++){
                                    $forcedbyGroup[] = $keyArray[$forcedbyppl[$i]];
                                }
                                $patientForcedforSexBy = str_replace(',',', ',implode(',',$forcedbyGroup));
                            }
                            
                            if(isset($patientForcedforSexdata['no_of_times']) && trim($patientForcedforSexdata['no_of_times'])!= '' && $patientForcedforSexdata['no_of_times'][0] == '@'){
                                $patientForcedforSexInNoofTimes = substr($patientForcedforSexdata['no_of_times'],1);
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 88){
                               $patientForcedforSexInNoofTimes = 888;
                            }else if(isset($patientForcedforSexdata['no_of_times']) && $patientForcedforSexdata['no_of_times'] == 99){
                               $patientForcedforSexInNoofTimes = 777;
                            }else if(isset($patientForcedforSexdata['no_of_times']) && ($patientForcedforSexdata['no_of_times'] == 2222 || $patientForcedforSexdata['no_of_times'] == 'not applicable')){
                               $patientForcedforSexInNoofTimes = 999;
                            }
                        }
                        $hasPatientAfraidofAnyone = '';
                        $patientAfraidofAnyone = '';
                        $patientAfraidofOther = '';
                        if($aRow['form_version'] == 2){
                            if($aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                                $patientAfraidofPeople = explode(',',$aRow['is_patient_afraid_of_anyone']);
                                $patientAfraidofGroup = array();
                                for($i=0;$i<count($patientAfraidofPeople);$i++){
                                    $patientAfraidofGroup[] = $keyArray[$patientAfraidofPeople[$i]];
                                }
                                $patientAfraidofAnyone = str_replace(',',', ',implode(',',$patientAfraidofGroup));
                            }else if($aRow['patient_afraid_of_other']!= null && trim($aRow['patient_afraid_of_other'])!= ''){
                               $patientAfraidofAnyone = 'other';
                               $patientAfraidofOther = $aRow['patient_afraid_of_other'];
                            }
                        }else{
                            if($aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                                if($aRow['is_patient_afraid_of_anyone'] == 1){
                                    $hasPatientAfraidofAnyone = "yes";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 2){
                                    $hasPatientAfraidofAnyone = "no";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 88){
                                    $hasPatientAfraidofAnyone = "dk";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 99){
                                    $hasPatientAfraidofAnyone = "r";
                                }else if($aRow['is_patient_afraid_of_anyone'] == 2222){
                                    $hasPatientAfraidofAnyone = "rna";
                                }
                            }
                        }
                        $row = array();
                        $row[] = $aRow['anc_site_code'].'-'.$aRow['anc_site_name'];
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = (isset($aRow['age']) && (int)$aRow['age'] > 0)?$aRow['age']:'';
                        $row[] = $aRow['interviewer_name'];
                        $row[] = $aRow['anc_patient_id'];
                        $row[] = $interviewDate;
                        $row[] = $hasPatientEverBeenAbusedBySomeone;
                        $row[] = $patientAbusedBy;
                        $row[] = $patientAbusedByOther;
                        $row[] = $patientAbusedByInNoofTimes;
                        $row[] = $hasPatientHurtBySomeoneWithinLastYear;
                        $row[] = $patientHurtByWithinLastYear;
                        $row[] = $patientHurtByWithinLastYearByOther;
                        $row[] = $patientHurtByInNoofTimes;
                        $row[] = $hasPatientHurtBySomeoneDuringPregnancy;
                        $row[] = $patientHurtByDuringPregnancy;
                        $row[] = $patientHurtByOtherDuringPregnancy;
                        $row[] = $patientHurtByDuringPregnancyInNoofTimes;
                        $row[] = $hasPatientForcedforSexBySomeoneWithinLastYear;
                        $row[] = $patientForcedforSexBy;
                        $row[] = $patientForcedforSexByOther;
                        $row[] = $patientForcedforSexInNoofTimes;
                        $row[] = $hasPatientAfraidofAnyone;
                        $row[] = $patientAfraidofAnyone;
                        $row[] = $patientAfraidofOther;
                        $row[] = $aRow['comment'];
                        $row[] = $addedDate;
                        $row[] = (isset($aRow['addedBy']))?ucwords($aRow['addedBy']):'';
                        $row[] = $updatedDate;
                        $row[] = (isset($aRow['updatedBy']))?ucwords($aRow['updatedBy']):'';
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
                    $sheet->mergeCells('A1:A3');
                    $sheet->mergeCells('B1:B3');
                    $sheet->mergeCells('C1:C3');
                    $sheet->mergeCells('D1:D3');
                    $sheet->mergeCells('E1:E3');
                    $sheet->mergeCells('F1:F3');
                    
                    $sheet->mergeCells('G1:Y1');
                    $sheet->mergeCells('G2:J2');
                    $sheet->mergeCells('K2:N2');
                    $sheet->mergeCells('O2:R2');
                    $sheet->mergeCells('S2:V2');
                    $sheet->mergeCells('X2:Y2');
                    
                    $sheet->mergeCells('Z1:Z3');
                    $sheet->mergeCells('AA1:AA3');
                    $sheet->mergeCells('AB1:AB3');
                    $sheet->mergeCells('AC1:AC3');
                    $sheet->mergeCells('AD1:AD3');
                    
                    $sheet->setCellValue('A1', html_entity_decode('ANC Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Age from Lab Request ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Interviewer Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Interview Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G1', html_entity_decode('Abuse ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G2', html_entity_decode('Have you ever been emotionally or physically abused by your partner or your loved one? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H3', html_entity_decode('Abused by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I3', html_entity_decode('Abused by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J3', html_entity_decode('No.of times abused ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('K2', html_entity_decode('Within the last year, have you ever been hit, slapped, kicked, or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L3', html_entity_decode('Hurted by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M3', html_entity_decode('Hurted by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N3', html_entity_decode('No.of times hurted ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('O2', html_entity_decode('Since you\'ve been pregnant have you been slapped, kicked or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P3', html_entity_decode('Hurted by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q3', html_entity_decode('Hurted by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R3', html_entity_decode('No.of times hurted ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('S2', html_entity_decode('Within the last year, has anyone forced you to have sexual activities? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T3', html_entity_decode('Forced by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U3', html_entity_decode('Forced by other', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V3', html_entity_decode('No.of times forced ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('W2', html_entity_decode('Are you afraid of your partner or anyone listed above? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W3', html_entity_decode('Yes/No ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('X2', html_entity_decode('Are you afraid of any of the following people? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X3', html_entity_decode('Afraid of', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y3', html_entity_decode('Afraid of other ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('Z1', html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA1', html_entity_decode('Added Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB1', html_entity_decode('Added by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC1', html_entity_decode('Last Updated Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD1', html_entity_decode('Last Updated by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->getStyle('A1:A3')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B3')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C3')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D3')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E3')->applyFromArray($styleArray);
                    $sheet->getStyle('F1:F3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('G1:Y1')->applyFromArray($styleArray);
                    $sheet->getStyle('G2:J2')->applyFromArray($styleArray);
                    $sheet->getStyle('G3')->applyFromArray($styleArray);
                    $sheet->getStyle('H3')->applyFromArray($styleArray);
                    $sheet->getStyle('I3')->applyFromArray($styleArray);
                    $sheet->getStyle('J3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('K2:N2')->applyFromArray($styleArray);
                    $sheet->getStyle('K3')->applyFromArray($styleArray);
                    $sheet->getStyle('L3')->applyFromArray($styleArray);
                    $sheet->getStyle('M3')->applyFromArray($styleArray);
                    $sheet->getStyle('N3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('O2:R2')->applyFromArray($styleArray);
                    $sheet->getStyle('O3')->applyFromArray($styleArray);
                    $sheet->getStyle('P3')->applyFromArray($styleArray);
                    $sheet->getStyle('Q3')->applyFromArray($styleArray);
                    $sheet->getStyle('R3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('S2:V2')->applyFromArray($styleArray);
                    $sheet->getStyle('S3')->applyFromArray($styleArray);
                    $sheet->getStyle('T3')->applyFromArray($styleArray);
                    $sheet->getStyle('U3')->applyFromArray($styleArray);
                    $sheet->getStyle('V3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('W2')->applyFromArray($styleArray);
                    $sheet->getStyle('W3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('X2')->applyFromArray($styleArray);
                    $sheet->getStyle('X3')->applyFromArray($styleArray);
                    $sheet->getStyle('Y3')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('Z1:Z3')->applyFromArray($styleArray);
                    $sheet->getStyle('AA1:AA3')->applyFromArray($styleArray);
                    $sheet->getStyle('AB1:AB3')->applyFromArray($styleArray);
                    $sheet->getStyle('AC1:AC3')->applyFromArray($styleArray);
                    $sheet->getStyle('AD1:AD3')->applyFromArray($styleArray);
                    
                    $currentRow = 4;
                    $sheet->setCellValue('A'.$currentRow, html_entity_decode('ANCsite', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B'.$currentRow, html_entity_decode('PatientBarcodeID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C'.$currentRow, html_entity_decode('age', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D'.$currentRow, html_entity_decode('interviewer', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E'.$currentRow, html_entity_decode('ancID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F'.$currentRow, html_entity_decode('interviewdate', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G'.$currentRow, html_entity_decode('everabuse', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H'.$currentRow, html_entity_decode('everabuseby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I'.$currentRow, html_entity_decode('everabusebyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J'.$currentRow, html_entity_decode('everabusefreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('K'.$currentRow, html_entity_decode('abuselastyr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L'.$currentRow, html_entity_decode('abuselastyrby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M'.$currentRow, html_entity_decode('abuselastyrbyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N'.$currentRow, html_entity_decode('abuselastyrfreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('O'.$currentRow, html_entity_decode('abusepreg', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P'.$currentRow, html_entity_decode('abusepregby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q'.$currentRow, html_entity_decode('abusepregbyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R'.$currentRow, html_entity_decode('abusepregfreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('S'.$currentRow, html_entity_decode('forcedsexyr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T'.$currentRow, html_entity_decode('forcedsexyrby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U'.$currentRow, html_entity_decode('forcedsexbyothr', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V'.$currentRow, html_entity_decode('forcedsexyrfreq', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('W'.$currentRow, html_entity_decode('afraid', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X'.$currentRow, html_entity_decode('patientafraidofanyone', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y'.$currentRow, html_entity_decode('patientafraidofother', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('Z'.$currentRow, html_entity_decode('comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA'.$currentRow, html_entity_decode('adddate', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB'.$currentRow, html_entity_decode('addby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC'.$currentRow, html_entity_decode('update', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD'.$currentRow, html_entity_decode('updateby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->getStyle('A'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('B'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('C'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('D'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('E'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('F'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('G'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('H'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('I'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('J'.$currentRow)->applyFromArray($styleArray);

                    $sheet->getStyle('K'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('L'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('M'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('N'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('O'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('P'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Q'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('R'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('S'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('T'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('U'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('V'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('W'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('X'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Y'.$currentRow)->applyFromArray($styleArray);
                    
                    $sheet->getStyle('Z'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AA'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AB'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AC'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AD'.$currentRow)->applyFromArray($styleArray);
                    
                    $currentRow = 5;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
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
                    return "na";
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