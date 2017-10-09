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
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $interviewDate = '';
                        if(isset($aRow['interview_date']) && $aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
                            $interviewDate = $common->humanDateFormat($aRow['interview_date']);
                        }
                        $patientDegree = '';
                        if(isset($aRow['patient_degree']) && $aRow['patient_degree']!= null && trim($aRow['patient_degree'])!= '' && $aRow['patient_degree']=='noedu'){
                            $patientDegree = 'No Education';
                        }else if(isset($aRow['patient_degree']) && $aRow['patient_degree']!= null && trim($aRow['patient_degree'])!= '' && $aRow['patient_degree']=='primary'){
                            $patientDegree = 'Primary/Post-Primary/Vocational';
                        }else if(isset($aRow['patient_degree']) && $aRow['patient_degree']!= null && trim($aRow['patient_degree'])!= '' && $aRow['patient_degree']=='secondary'){
                            $patientDegree = 'Secondary/Post-Secondary';
                        }else if(isset($aRow['patient_degree']) && $aRow['patient_degree']!= null && trim($aRow['patient_degree'])!= '' && $aRow['patient_degree']=='university'){
                            $patientDegree = 'University/College';
                        }
                        $ageAtFirstMarriage = '';
                        if(isset($aRow['age_at_first_marriage']) && $aRow['age_at_first_marriage']!= null && trim($aRow['age_at_first_marriage'])!= '' && $aRow['age_at_first_marriage']=='dontknow'){
                            $ageAtFirstMarriage = 'Don\'t Know';
                        }else if(isset($aRow['age_at_first_marriage']) && $aRow['age_at_first_marriage']!= null && trim($aRow['age_at_first_marriage'])!= '' && $aRow['age_at_first_marriage']=='refused'){
                           $ageAtFirstMarriage = 'Refused'; 
                        }else if(isset($aRow['age_at_first_marriage']) && $aRow['age_at_first_marriage']!= null && trim($aRow['age_at_first_marriage'])!= '' && $aRow['age_at_first_marriage'] >1){
                           $ageAtFirstMarriage = $aRow['age_at_first_marriage'].' Years';
                        }else if(isset($aRow['age_at_first_marriage']) && $aRow['age_at_first_marriage']!= null && trim($aRow['age_at_first_marriage'])!= '' && $aRow['age_at_first_marriage'] == 1){
                           $ageAtFirstMarriage = $aRow['age_at_first_marriage'].' Year';
                        }
                        $everBeenWidowed = '';
                        if(isset($aRow['patient_ever_been_widowed']) && $aRow['patient_ever_been_widowed']!= null && trim($aRow['patient_ever_been_widowed'])!= '' && $aRow['patient_ever_been_widowed']=='dontknow'){
                            $everBeenWidowed = 'Don\'t Know';
                        }else if(isset($aRow['patient_ever_been_widowed']) && $aRow['patient_ever_been_widowed']!= null && trim($aRow['patient_ever_been_widowed'])!= ''){
                           $everBeenWidowed = ucwords($aRow['patient_ever_been_widowed']);
                        }
                        $currentMaritalStatus = '';
                        if(isset($aRow['current_marital_status']) && $aRow['current_marital_status']!= null && trim($aRow['current_marital_status'])!= '' && $aRow['current_marital_status']=='married'){
                            $currentMaritalStatus = 'Married/Cohabiting';
                        }else if(isset($aRow['current_marital_status']) && $aRow['current_marital_status']!= null && trim($aRow['current_marital_status'])!= '' && $aRow['current_marital_status']=='nevermarried'){
                           $currentMaritalStatus = 'Never Married/Cohabiting';
                        }else if(isset($aRow['current_marital_status']) && $aRow['current_marital_status']!= null && trim($aRow['current_marital_status'])!= '' && $aRow['current_marital_status']=='dontknow'){
                           $currentMaritalStatus = 'Don\'t Know';
                        }else if(isset($aRow['current_marital_status']) && $aRow['current_marital_status']!= null && trim($aRow['current_marital_status'])!= ''){
                           $currentMaritalStatus = ucfirst($aRow['current_marital_status']);
                        }
                        $timeOfLastHIVTest = '';
                        if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='nevertested'){
                           $timeOfLastHIVTest = 'Never Tested'; 
                        }else if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='lt3months'){
                           $timeOfLastHIVTest = '< 3 Months Ago'; 
                        }else if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='3to6months'){
                           $timeOfLastHIVTest = '3-6 Months Ago'; 
                        }else if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='7to12months'){
                           $timeOfLastHIVTest = '7-12 Months Ago'; 
                        }else if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='gt12months'){
                           $timeOfLastHIVTest = '> 12 months'; 
                        }else if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='dontknow'){
                           $timeOfLastHIVTest = 'Don\'t Know';
                        }else if(isset($aRow['time_of_last_HIV_test']) && $aRow['time_of_last_HIV_test']!= null && trim($aRow['time_of_last_HIV_test'])!= '' && $aRow['time_of_last_HIV_test']=='refused'){
                           $timeOfLastHIVTest = 'Refused';
                        }
                        $resultofLastHIVTest = '';
                        if(isset($aRow['last_HIV_test_status']) && $aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= '' && $aRow['last_HIV_test_status']=='didnotreceive'){
                            $resultofLastHIVTest = 'I Did Not Receive Result';
                        }else if(isset($aRow['last_HIV_test_status']) && $aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= '' && $aRow['last_HIV_test_status']=='hivpos'){
                            $resultofLastHIVTest = 'HIV+';
                        }else if(isset($aRow['last_HIV_test_status']) && $aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= '' && $aRow['last_HIV_test_status']=='hivneg'){
                            $resultofLastHIVTest = 'HIV-';
                        }else if(isset($aRow['last_HIV_test_status']) && $aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= '' && $aRow['last_HIV_test_status']=='dontknow'){
                            $resultofLastHIVTest = 'Don\'t Know';
                        }else if(isset($aRow['last_HIV_test_status']) && $aRow['last_HIV_test_status']!= null && trim($aRow['last_HIV_test_status'])!= ''){
                            $resultofLastHIVTest = ucfirst($aRow['last_HIV_test_status']);
                        }
                        $partnerHIVTestStatus = '';
                        if(isset($aRow['partner_HIV_test_status']) && $aRow['partner_HIV_test_status']!= null && trim($aRow['partner_HIV_test_status'])!= '' && $aRow['partner_HIV_test_status']=='hivpos'){
                            $partnerHIVTestStatus = 'HIV+';
                        }else if(isset($aRow['partner_HIV_test_status']) && $aRow['partner_HIV_test_status']!= null && trim($aRow['partner_HIV_test_status'])!= '' && $aRow['partner_HIV_test_status']=='hivneg'){
                            $partnerHIVTestStatus = 'HIV-';
                        }else if(isset($aRow['partner_HIV_test_status']) && $aRow['partner_HIV_test_status']!= null && trim($aRow['partner_HIV_test_status'])!= '' && $aRow['partner_HIV_test_status']=='idonotknow'){
                            $partnerHIVTestStatus = 'I Do Not Know';
                        }else if(isset($aRow['partner_HIV_test_status']) && $aRow['partner_HIV_test_status']!= null && trim($aRow['partner_HIV_test_status'])!= '' && $aRow['partner_HIV_test_status']=='refused'){
                            $partnerHIVTestStatus = 'Refused';
                        }
                        $ageAtVeryFirstSex = '';
                        if(isset($aRow['age_at_very_first_sex']) && $aRow['age_at_very_first_sex']!= null && trim($aRow['age_at_very_first_sex'])!= '' && $aRow['age_at_very_first_sex'] > 1){
                            $ageAtVeryFirstSex = $aRow['age_at_very_first_sex'].' Years';
                        }else if(isset($aRow['age_at_very_first_sex']) && $aRow['age_at_very_first_sex']!= null && trim($aRow['age_at_very_first_sex'])!= '' && $aRow['age_at_very_first_sex'] == 1){
                            $ageAtVeryFirstSex = $aRow['age_at_very_first_sex'].' Year';
                        }
                        $reasonForVeryFirstSex = '';
                        if(isset($aRow['reason_for_very_first_sex']) && $aRow['reason_for_very_first_sex']!= null && trim($aRow['reason_for_very_first_sex'])!= '' && $aRow['reason_for_very_first_sex'] == 'wantedto'){
                          $reasonForVeryFirstSex = 'Wanted To';  
                        }else if(isset($aRow['reason_for_very_first_sex']) && $aRow['reason_for_very_first_sex']!= null && trim($aRow['reason_for_very_first_sex'])!= '' && $aRow['reason_for_very_first_sex'] == 'forcedto'){
                          $reasonForVeryFirstSex = 'Forced To';  
                        }else if(isset($aRow['reason_for_very_first_sex']) && $aRow['reason_for_very_first_sex']!= null && trim($aRow['reason_for_very_first_sex'])!= '' && $aRow['reason_for_very_first_sex'] == 'dontknow'){
                          $reasonForVeryFirstSex = 'Don\'t Know';  
                        }else if(isset($aRow['reason_for_very_first_sex']) && $aRow['reason_for_very_first_sex']!= null && trim($aRow['reason_for_very_first_sex'])!= '' && $aRow['reason_for_very_first_sex'] == 'refused'){
                          $reasonForVeryFirstSex = 'Refused';
                        }
                        $noofSexualPartners = '';
                        if(isset($aRow['no_of_sexual_partners']) && $aRow['no_of_sexual_partners']!= null && trim($aRow['no_of_sexual_partners'])!= '' && $aRow['no_of_sexual_partners'] > 1){
                            $noofSexualPartners = $aRow['no_of_sexual_partners'].' Persons';
                        }else if(isset($aRow['no_of_sexual_partners']) && $aRow['no_of_sexual_partners']!= null && trim($aRow['no_of_sexual_partners'])!= '' && $aRow['no_of_sexual_partners'] == 1){
                            $noofSexualPartners = $aRow['no_of_sexual_partners'].' Person';
                        }else if(isset($aRow['no_of_sexual_partners']) && $aRow['no_of_sexual_partners']!= null && trim($aRow['no_of_sexual_partners'])!= '' && $aRow['no_of_sexual_partners'] == 'dontknow'){
                            $noofSexualPartners = 'Don\'t Know';
                        }else if(isset($aRow['no_of_sexual_partners']) && $aRow['no_of_sexual_partners']!= null && trim($aRow['no_of_sexual_partners'])!= '' && $aRow['no_of_sexual_partners'] == 'refused'){
                            $noofSexualPartners = 'Refused';
                        }
                        $noofSexualPartnersInLastSixMonths = '';
                        if(isset($aRow['no_of_sexual_partners_in_last_six_months']) && $aRow['no_of_sexual_partners_in_last_six_months']!= null && trim($aRow['no_of_sexual_partners_in_last_six_months'])!= '' && $aRow['no_of_sexual_partners_in_last_six_months'] > 1){
                            $noofSexualPartnersInLastSixMonths = $aRow['no_of_sexual_partners_in_last_six_months'].' Persons';
                        }else if(isset($aRow['no_of_sexual_partners_in_last_six_months']) && $aRow['no_of_sexual_partners_in_last_six_months']!= null && trim($aRow['no_of_sexual_partners_in_last_six_months'])!= '' && $aRow['no_of_sexual_partners_in_last_six_months'] == 1){
                            $noofSexualPartnersInLastSixMonths = $aRow['no_of_sexual_partners_in_last_six_months'].' Person';
                        }else if(isset($aRow['no_of_sexual_partners_in_last_six_months']) && $aRow['no_of_sexual_partners_in_last_six_months']!= null && trim($aRow['no_of_sexual_partners_in_last_six_months'])!= '' && $aRow['no_of_sexual_partners_in_last_six_months'] == 'dontknow'){
                            $noofSexualPartnersInLastSixMonths = 'Don\'t Know';
                        }else if(isset($aRow['no_of_sexual_partners_in_last_six_months']) && $aRow['no_of_sexual_partners_in_last_six_months']!= null && trim($aRow['no_of_sexual_partners_in_last_six_months'])!= '' && $aRow['no_of_sexual_partners_in_last_six_months'] == 'refused'){
                            $noofSexualPartnersInLastSixMonths = 'Refused';
                        }
                        $ageofMainSexualPartnerAtLastBirthday = '';
                        if(isset($aRow['age_of_main_sexual_partner_at_last_birthday']) && $aRow['age_of_main_sexual_partner_at_last_birthday']!= null && trim($aRow['age_of_main_sexual_partner_at_last_birthday'])!= '' && $aRow['age_of_main_sexual_partner_at_last_birthday'] > 1){
                            $ageofMainSexualPartnerAtLastBirthday = $aRow['age_of_main_sexual_partner_at_last_birthday'].' Years';
                        }else if(isset($aRow['age_of_main_sexual_partner_at_last_birthday']) && $aRow['age_of_main_sexual_partner_at_last_birthday']!= null && trim($aRow['age_of_main_sexual_partner_at_last_birthday'])!= '' && $aRow['age_of_main_sexual_partner_at_last_birthday'] == 1){
                            $ageofMainSexualPartnerAtLastBirthday = $aRow['age_of_main_sexual_partner_at_last_birthday'].' Year';
                        }else if(isset($aRow['age_of_main_sexual_partner_at_last_birthday']) && $aRow['age_of_main_sexual_partner_at_last_birthday']!= null && trim($aRow['age_of_main_sexual_partner_at_last_birthday'])!= '' && $aRow['age_of_main_sexual_partner_at_last_birthday'] == 'dontknow'){
                            $ageofMainSexualPartnerAtLastBirthday = 'Don\'t Know';
                        }else if(isset($aRow['age_of_main_sexual_partner_at_last_birthday']) && $aRow['age_of_main_sexual_partner_at_last_birthday']!= null && trim($aRow['age_of_main_sexual_partner_at_last_birthday'])!= '' && $aRow['age_of_main_sexual_partner_at_last_birthday'] == 'refused'){
                            $ageofMainSexualPartnerAtLastBirthday = 'Refused';
                        }
                        $ageDiffofMainSexualPartner = '';
                        if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == 'lt5yearolder'){
                            $ageDiffofMainSexualPartner = '< 5 Years Older';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == '5to10yearolder'){
                            $ageDiffofMainSexualPartner = '5-10 Years Older';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == '10yearsolder'){
                            $ageDiffofMainSexualPartner = '>10 Years Older';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == 'sameage'){
                            $ageDiffofMainSexualPartner = 'Same Age';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == 'lt5yearyounger'){
                            $ageDiffofMainSexualPartner = '< 5 Years Younger';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == '5to10yearyounger'){
                            $ageDiffofMainSexualPartner = '5-10 Years Younger';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == 'gt10yearyounger'){
                            $ageDiffofMainSexualPartner = '>10 Years Younger';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == 'dontknow'){
                            $ageDiffofMainSexualPartner = 'Don\'t Know';
                        }else if(isset($aRow['age_diff_of_main_sexual_partner']) && $aRow['age_diff_of_main_sexual_partner']!= null && trim($aRow['age_diff_of_main_sexual_partner'])!= '' && $aRow['age_diff_of_main_sexual_partner'] == 'refused'){
                            $ageDiffofMainSexualPartner = 'Refused';
                        }
                        $isPatientCircumcised = '';
                        if(isset($aRow['is_partner_circumcised']) && $aRow['is_partner_circumcised']!= null && trim($aRow['is_partner_circumcised'])!= '' && $aRow['is_partner_circumcised'] == 'dontknow'){
                            $isPatientCircumcised = 'Don\'t Know';
                        }else if(isset($aRow['is_partner_circumcised']) && $aRow['is_partner_circumcised']!= null && trim($aRow['is_partner_circumcised'])!= ''){
                            $isPatientCircumcised = ucwords($aRow['is_partner_circumcised']);
                        }
                        $lastTimeofReceivingGiftForSex = '';
                        if(isset($aRow['last_time_of_receiving_gift_for_sex']) && $aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= '' && $aRow['last_time_of_receiving_gift_for_sex'] == 'lt6months'){
                            $lastTimeofReceivingGiftForSex = '< 6 Months Ago';
                        }else if(isset($aRow['last_time_of_receiving_gift_for_sex']) && $aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= '' && $aRow['last_time_of_receiving_gift_for_sex'] == '6to12months'){
                            $lastTimeofReceivingGiftForSex = '6-12 Months Ago';
                        }else if(isset($aRow['last_time_of_receiving_gift_for_sex']) && $aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= '' && $aRow['last_time_of_receiving_gift_for_sex'] == 'gt12months'){
                            $lastTimeofReceivingGiftForSex = '> 12 Months Ago';
                        }else if(isset($aRow['last_time_of_receiving_gift_for_sex']) && $aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= '' && $aRow['last_time_of_receiving_gift_for_sex'] == 'dontknow'){
                            $lastTimeofReceivingGiftForSex = 'Don\'t Know';
                        }else if(isset($aRow['last_time_of_receiving_gift_for_sex']) && $aRow['last_time_of_receiving_gift_for_sex']!= null && trim($aRow['last_time_of_receiving_gift_for_sex'])!= ''){
                           $lastTimeofReceivingGiftForSex = ucwords($aRow['last_time_of_receiving_gift_for_sex']);
                        }
                        $noofTimesBeenPregnant = '';
                        if(isset($aRow['no_of_times_been_pregnant']) && $aRow['no_of_times_been_pregnant']!= null && trim($aRow['no_of_times_been_pregnant'])!= '' && $aRow['no_of_times_been_pregnant']>1){
                           $noofTimesBeenPregnant = $aRow['no_of_times_been_pregnant'].' Times';
                        }else if(isset($aRow['no_of_times_been_pregnant']) && $aRow['no_of_times_been_pregnant']!= null && trim($aRow['no_of_times_been_pregnant'])!= '' && $aRow['no_of_times_been_pregnant']==1){
                           $noofTimesBeenPregnant = $aRow['no_of_times_been_pregnant'].' Time';
                        }
                        $noofTimesCondomUsedBeforePregnancy = '';
                        if(isset($aRow['no_of_times_condom_used_before_pregnancy']) && $aRow['no_of_times_condom_used_before_pregnancy']!= null && trim($aRow['no_of_times_condom_used_before_pregnancy'])!= '' && $aRow['no_of_times_condom_used_before_pregnancy'] == 'dontknow'){
                           $noofTimesCondomUsedBeforePregnancy = 'Don\'t Know';
                        }else if(isset($aRow['no_of_times_condom_used_before_pregnancy']) && $aRow['no_of_times_condom_used_before_pregnancy']!= null && trim($aRow['no_of_times_condom_used_before_pregnancy'])!= ''){
                           $noofTimesCondomUsedBeforePregnancy = ucwords($aRow['no_of_times_condom_used_before_pregnancy']);
                        }
                        $noofTimesCondomUsedAfterPregnancy = '';
                        if(isset($aRow['no_of_times_condom_used_after_pregnancy']) && $aRow['no_of_times_condom_used_after_pregnancy']!= null && trim($aRow['no_of_times_condom_used_after_pregnancy'])!= '' && $aRow['no_of_times_condom_used_after_pregnancy'] == 'dontknow'){
                           $noofTimesCondomUsedAfterPregnancy = 'Don\'t Know';
                        }else if(isset($aRow['no_of_times_condom_used_after_pregnancy']) && $aRow['no_of_times_condom_used_after_pregnancy']!= null && trim($aRow['no_of_times_condom_used_after_pregnancy'])!= ''){
                           $noofTimesCondomUsedAfterPregnancy = ucwords($aRow['no_of_times_condom_used_after_pregnancy']);
                        }
                        $hasPatientHadPainInLowerAbdomen = '';
                        if(isset($aRow['has_patient_had_pain_in_lower_abdomen']) && $aRow['has_patient_had_pain_in_lower_abdomen']!= null && trim($aRow['has_patient_had_pain_in_lower_abdomen'])!= '' && $aRow['has_patient_had_pain_in_lower_abdomen'] == 'dontknow'){
                           $hasPatientHadPainInLowerAbdomen = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_had_pain_in_lower_abdomen']) && $aRow['has_patient_had_pain_in_lower_abdomen']!= null && trim($aRow['has_patient_had_pain_in_lower_abdomen'])!= ''){
                           $hasPatientHadPainInLowerAbdomen = ucwords($aRow['has_patient_had_pain_in_lower_abdomen']);
                        }
                        $hasPatientBeenTreatedForLowerAbdomenPain = '';
                        if(isset($aRow['has_patient_been_treated_for_lower_abdomen_pain']) && $aRow['has_patient_been_treated_for_lower_abdomen_pain']!= null && trim($aRow['has_patient_been_treated_for_lower_abdomen_pain'])!= '' && $aRow['has_patient_been_treated_for_lower_abdomen_pain'] == 'dontknow'){
                           $hasPatientBeenTreatedForLowerAbdomenPain = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_been_treated_for_lower_abdomen_pain']) && $aRow['has_patient_been_treated_for_lower_abdomen_pain']!= null && trim($aRow['has_patient_been_treated_for_lower_abdomen_pain'])!= ''){
                           $hasPatientBeenTreatedForLowerAbdomenPain = ucwords($aRow['has_patient_been_treated_for_lower_abdomen_pain']);
                        }
                        $hasPatientEverBeenTreatedForSyphilis = '';
                        if(isset($aRow['has_patient_ever_been_treated_for_syphilis']) && $aRow['has_patient_ever_been_treated_for_syphilis']!= null && trim($aRow['has_patient_ever_been_treated_for_syphilis'])!= '' && $aRow['has_patient_ever_been_treated_for_syphilis'] == 'dontknow'){
                           $hasPatientEverBeenTreatedForSyphilis = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_ever_been_treated_for_syphilis']) && $aRow['has_patient_ever_been_treated_for_syphilis']!= null && trim($aRow['has_patient_ever_been_treated_for_syphilis'])!= ''){
                           $hasPatientEverBeenTreatedForSyphilis = ucwords($aRow['has_patient_ever_been_treated_for_syphilis']);
                        }
                        $hasPatientEverReceivedVaccineToPreventHPV = '';
                        if(isset($aRow['has_patient_ever_received_vaccine_to_prevent_HPV']) && $aRow['has_patient_ever_received_vaccine_to_prevent_HPV']!= null && trim($aRow['has_patient_ever_received_vaccine_to_prevent_HPV'])!= '' && $aRow['has_patient_ever_received_vaccine_to_prevent_HPV'] == 'dontknow'){
                           $hasPatientEverReceivedVaccineToPreventHPV = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_ever_received_vaccine_to_prevent_HPV']) && $aRow['has_patient_ever_received_vaccine_to_prevent_HPV']!= null && trim($aRow['has_patient_ever_received_vaccine_to_prevent_HPV'])!= ''){
                           $hasPatientEverReceivedVaccineToPreventHPV = ucwords($aRow['has_patient_ever_received_vaccine_to_prevent_HPV']);
                        }
                        $hasPatientHadDrinkWithAlcoholInLastSixMonths = '';
                        if(isset($aRow['has_patient_had_drink_with_alcohol_in_last_six_months']) && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['has_patient_had_drink_with_alcohol_in_last_six_months'])!= '' && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'] > 1){
                           $hasPatientHadDrinkWithAlcoholInLastSixMonths = $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'].' Days';
                        }else if(isset($aRow['has_patient_had_drink_with_alcohol_in_last_six_months']) && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['has_patient_had_drink_with_alcohol_in_last_six_months'])!= '' && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'] == 1){
                           $hasPatientHadDrinkWithAlcoholInLastSixMonths = $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'].' Day'; 
                        }else if(isset($aRow['has_patient_had_drink_with_alcohol_in_last_six_months']) && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['has_patient_had_drink_with_alcohol_in_last_six_months'])!= '' && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'] == 'havenotdrink'){
                           $hasPatientHadDrinkWithAlcoholInLastSixMonths = 'Haven\'t Had a Drink';
                        }else if(isset($aRow['has_patient_had_drink_with_alcohol_in_last_six_months']) && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['has_patient_had_drink_with_alcohol_in_last_six_months'])!= '' && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'] == 'dontknow'){
                           $hasPatientHadDrinkWithAlcoholInLastSixMonths = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_had_drink_with_alcohol_in_last_six_months']) && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months']!= null && trim($aRow['has_patient_had_drink_with_alcohol_in_last_six_months'])!= '' && $aRow['has_patient_had_drink_with_alcohol_in_last_six_months'] == 'refusedtoanswer'){
                           $hasPatientHadDrinkWithAlcoholInLastSixMonths = 'Refused To Answer';
                        }
                        $hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion = '';
                        if(isset($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']) && $aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']!= null && trim($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'])!= '' && $aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 'daily'){
                           $hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion = 'Daily or On Most Days of The Week';
                        }else if(isset($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']) && $aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']!= null && trim($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'])!= '' && $aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'] == 'dontknow'){
                           $hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']) && $aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']!= null && trim($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'])!= ''){
                           $hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion = ucwords($aRow['has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion']);
                        }
                        $hasPatientEverTriedRecreaionalDrugs = '';
                        if(isset($aRow['has_patient_ever_tried_recreational_drugs']) && $aRow['has_patient_ever_tried_recreational_drugs']!= null && trim($aRow['has_patient_ever_tried_recreational_drugs'])!= '' && $aRow['has_patient_ever_tried_recreational_drugs'] == 'dontknow'){
                           $hasPatientEverTriedRecreaionalDrugs = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_ever_tried_recreational_drugs']) && $aRow['has_patient_ever_tried_recreational_drugs']!= null && trim($aRow['has_patient_ever_tried_recreational_drugs'])!= ''){
                           $hasPatientEverTriedRecreaionalDrugs = ucwords($aRow['has_patient_ever_tried_recreational_drugs']);
                        }
                        $hasPatientHadRecreaionalDrugsInLastSixMonths = '';
                        if(isset($aRow['has_patient_had_recreational_drugs_in_last_six_months']) && $aRow['has_patient_had_recreational_drugs_in_last_six_months']!= null && trim($aRow['has_patient_had_recreational_drugs_in_last_six_months'])!= '' && $aRow['has_patient_had_recreational_drugs_in_last_six_months'] == 'dontknow'){
                           $hasPatientHadRecreaionalDrugsInLastSixMonths = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_had_recreational_drugs_in_last_six_months']) && $aRow['has_patient_had_recreational_drugs_in_last_six_months']!= null && trim($aRow['has_patient_had_recreational_drugs_in_last_six_months'])!= ''){
                           $hasPatientHadRecreaionalDrugsInLastSixMonths = ucwords($aRow['has_patient_had_recreational_drugs_in_last_six_months']);
                        }
                        $hasPatientEverBeenAbusedBySomeone = '';
                        if(isset($aRow['has_patient_ever_been_abused_by_someone']) && $aRow['has_patient_ever_been_abused_by_someone']!= null && trim($aRow['has_patient_ever_been_abused_by_someone'])!= '' && $aRow['has_patient_ever_been_abused_by_someone'] == 'dontknow'){
                           $hasPatientEverBeenAbusedBySomeone = 'Don\'t Know';
                        }else if(isset($aRow['has_patient_ever_been_abused_by_someone']) && $aRow['has_patient_ever_been_abused_by_someone']!= null && trim($aRow['has_patient_ever_been_abused_by_someone'])!= ''){
                           $hasPatientEverBeenAbusedBySomeone = ucwords($aRow['has_patient_ever_been_abused_by_someone']);
                        }
                        $hasPatientHurtBy = '';
                        $patientHurtBy = '';
                        $patientHurtByInNoofTimes = '';
                        if(isset($aRow['has_patient_ever_been_hurt_by_someone_within_last_year']) && $aRow['has_patient_ever_been_hurt_by_someone_within_last_year']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'])!= ''){
                            $hasPatientEverBeenHurtBySomeoneWithinLastYear = json_decode($aRow['has_patient_ever_been_hurt_by_someone_within_last_year'],true);
                            $hasPatientHurtBy = (isset($hasPatientEverBeenHurtBySomeoneWithinLastYear['has_patient_hurt_by']) && trim($hasPatientEverBeenHurtBySomeoneWithinLastYear['has_patient_hurt_by'])== 'dontknow')?'Don\'t Know':ucwords($hasPatientEverBeenHurtBySomeoneWithinLastYear['has_patient_hurt_by']);
                            $patientHurtBy = (isset($hasPatientEverBeenHurtBySomeoneWithinLastYear['patient_hurt_by']) && trim($hasPatientEverBeenHurtBySomeoneWithinLastYear['patient_hurt_by'])!= '')?", ".ucwords($hasPatientEverBeenHurtBySomeoneWithinLastYear['patient_hurt_by']):'';
                            $patientHurtByInNoofTimes = (isset($hasPatientEverBeenHurtBySomeoneWithinLastYear['no_of_times']) && trim($hasPatientEverBeenHurtBySomeoneWithinLastYear['no_of_times'])!= '')?", ".$hasPatientEverBeenHurtBySomeoneWithinLastYear['no_of_times']." times":'';
                        }
                        $hasPatientHurtBySomeoneDuringPregnancy = '';
                        $patientHurtBySomeoneDuringPregnancy = '';
                        if(isset($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy']) && $aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy']!= null && trim($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'])!= ''){
                            $hasPatientEverBeenHurtBySomeoneDuringPregnancy = json_decode($aRow['has_patient_ever_been_hurt_by_someone_during_pregnancy'],true);
                            $hasPatientHurtBySomeoneDuringPregnancy = (isset($hasPatientEverBeenHurtBySomeoneDuringPregnancy['has_patient_hurt_by_someone_during_pregnancy']) && trim($hasPatientEverBeenHurtBySomeoneDuringPregnancy['has_patient_hurt_by_someone_during_pregnancy'])== 'dontknow')?'Don\'t Know':ucwords($hasPatientEverBeenHurtBySomeoneDuringPregnancy['has_patient_hurt_by_someone_during_pregnancy']);
                            $patientHurtBySomeoneDuringPregnancy = (isset($hasPatientEverBeenHurtBySomeoneDuringPregnancy['patient_hurt_by_someone_during_pregnancy']) && trim($hasPatientEverBeenHurtBySomeoneDuringPregnancy['patient_hurt_by_someone_during_pregnancy'])!= '')?", ".ucwords($hasPatientEverBeenHurtBySomeoneDuringPregnancy['patient_hurt_by_someone_during_pregnancy']):'';
                        }
                        $hasPatientForcedForSex = '';
                        $patientForcedForSex = '';
                        $patientForcedForSexInNoofTimes = '';
                        if(isset($aRow['has_patient_ever_been_forced_for_sex_within_last_year']) && $aRow['has_patient_ever_been_forced_for_sex_within_last_year']!= null && trim($aRow['has_patient_ever_been_forced_for_sex_within_last_year'])!= ''){
                            $hasPatientEverBeenForcedForSexWithinLastYear = json_decode($aRow['has_patient_ever_been_forced_for_sex_within_last_year'],true);
                            $hasPatientForcedForSex = (isset($hasPatientEverBeenForcedForSexWithinLastYear['has_patient_forced_for_sex']) && trim($hasPatientEverBeenForcedForSexWithinLastYear['has_patient_forced_for_sex'])== 'dontknow')?'Don\'t Know':ucwords($hasPatientEverBeenForcedForSexWithinLastYear['has_patient_forced_for_sex']);
                            $patientForcedForSex = (isset($hasPatientEverBeenForcedForSexWithinLastYear['patient_forced_by']) && trim($hasPatientEverBeenForcedForSexWithinLastYear['patient_forced_by'])!= '')?", ".ucwords($hasPatientEverBeenForcedForSexWithinLastYear['patient_forced_by']):'';
                            $patientForcedForSexInNoofTimes = (isset($hasPatientEverBeenForcedForSexWithinLastYear['no_of_times']) && trim($hasPatientEverBeenForcedForSexWithinLastYear['no_of_times'])!= '')?", ".$hasPatientEverBeenForcedForSexWithinLastYear['no_of_times']." times":'';
                        }
                        $isPatientAfraidofAnyone = '';
                        if(isset($aRow['is_patient_afraid_of_anyone']) && $aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= '' && $aRow['is_patient_afraid_of_anyone'] == 'dontknow'){
                           $isPatientAfraidofAnyone = 'Don\'t Know';
                        }else if(isset($aRow['is_patient_afraid_of_anyone']) && $aRow['is_patient_afraid_of_anyone']!= null && trim($aRow['is_patient_afraid_of_anyone'])!= ''){
                           $isPatientAfraidofAnyone = ucwords($aRow['is_patient_afraid_of_anyone']);
                        }
                        $row = array();
                        $row[] = $aRow['anc_site_code'].'-'.ucwords($aRow['anc_site_name']);
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = ucwords($aRow['interviewer_name']);
                        $row[] = $aRow['anc_patient_id'];
                        $row[] = $interviewDate;
                        $row[] = (isset($aRow['has_participant_received_dreams_services']) && $aRow['has_participant_received_dreams_services']!= null && trim($aRow['has_participant_received_dreams_services'])!= '')?ucfirst($aRow['has_participant_received_dreams_services']):'';
                        $row[] = (isset($aRow['occupationName']))?ucwords($aRow['occupationName']):'';
                        $row[] = $patientDegree;
                        $row[] = (isset($aRow['patient_ever_been_married']) && $aRow['patient_ever_been_married']!= null && trim($aRow['patient_ever_been_married'])!= '')?ucfirst($aRow['patient_ever_been_married']):'';
                        $row[] = $ageAtFirstMarriage;
                        $row[] = $everBeenWidowed;
                        $row[] = $currentMaritalStatus;
                        $row[] = $timeOfLastHIVTest;
                        $row[] = $resultofLastHIVTest;
                        $row[] = $partnerHIVTestStatus;
                        $row[] = $ageAtVeryFirstSex;
                        $row[] = $reasonForVeryFirstSex;
                        $row[] = $noofSexualPartners;
                        $row[] = $noofSexualPartnersInLastSixMonths;
                        $row[] = $ageofMainSexualPartnerAtLastBirthday;
                        $row[] = $ageDiffofMainSexualPartner;
                        $row[] = $isPatientCircumcised;
                        $row[] = $lastTimeofReceivingGiftForSex;
                        $row[] = $noofTimesBeenPregnant;
                        $row[] = $noofTimesCondomUsedBeforePregnancy;
                        $row[] = $noofTimesCondomUsedAfterPregnancy;
                        $row[] = $hasPatientHadPainInLowerAbdomen;
                        $row[] = $hasPatientBeenTreatedForLowerAbdomenPain;
                        $row[] = $hasPatientEverBeenTreatedForSyphilis;
                        $row[] = $hasPatientEverReceivedVaccineToPreventHPV;
                        $row[] = $hasPatientHadDrinkWithAlcoholInLastSixMonths;
                        $row[] = $hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion;
                        $row[] = $hasPatientEverTriedRecreaionalDrugs;
                        $row[] = $hasPatientHadRecreaionalDrugsInLastSixMonths;
                        $row[] = ucwords($aRow['recreational_drugs']);
                        $row[] = $hasPatientEverBeenAbusedBySomeone;
                        $row[] = $hasPatientHurtBy.$patientHurtBy.$patientHurtByInNoofTimes;
                        $row[] = $hasPatientHurtBySomeoneDuringPregnancy.$patientHurtBySomeoneDuringPregnancy;
                        $row[] = $hasPatientForcedForSex.$patientForcedForSex.$patientForcedForSexInNoofTimes;
                        $row[] = $isPatientAfraidofAnyone;
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
                    $sheet->mergeCells('G1:L1');
                    $sheet->mergeCells('M1:O1');
                    $sheet->mergeCells('P1:AD1');
                    $sheet->mergeCells('AE1:AI1');
                    $sheet->mergeCells('AJ1:AN1');
                    $sheet->mergeCells('AO1:AO2');
                    
                    $sheet->setCellValue('A1', html_entity_decode('ANC Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Interviewer Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Interview Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Participant received DREAMS services ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G1', html_entity_decode('Demographic Characteristics ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G2', html_entity_decode('What kind of work/occupation do you do most of the time? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H2', html_entity_decode('What was the highest level of school that you completed or are attending now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I2', html_entity_decode('Have you ever been married or lived with a partner in a union as if married? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J2', html_entity_decode('How old were you when you first got married or lived with a partner in a union? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K2', html_entity_decode('Have you ever been widowed? That is, did a spouse ever pass away while you were still married or living with them? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L2', html_entity_decode('What is your marital status now? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('M1', html_entity_decode('HIV Testing History ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M2', html_entity_decode('When was the most recent time you were tested for HIV? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N2', html_entity_decode('What was the result of that HIV test? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O2', html_entity_decode('What is your spouse/main sexual partner\'s HIV status? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('P1', html_entity_decode('Sexual Activity and History of Sexually Transmitted Infections ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P2', html_entity_decode('How old were you when you had sexual intercourse for the very first time? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q2', html_entity_decode('The first time you had sex, was it because you wanted to or because you were forced to? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R2', html_entity_decode('How many different people have you had sexual intercourse with in your entire life? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S2', html_entity_decode('How many different people have you had sex with in the last 6 months? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T2', html_entity_decode('How old was your main sexual partner on his last birthday? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U2', html_entity_decode('Is the age of your main sexual partner older, younger, or the same age as you? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V2', html_entity_decode('Is your main sexual partner circumcised? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W2', html_entity_decode('When did you last receive money/gifts in exchange for sex? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X2', html_entity_decode('How many times have you been pregnant? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y2', html_entity_decode('Before becoming pregnant this time, in the past year how frequently did you use a condom when having sexual intercourse? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z2', html_entity_decode('Since becoming pregnant this time, how frequently have you used a condom when having sexual intercourse? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA2', html_entity_decode('In the past year, did you have symptoms such as genital discharge, sores in your genital area, pain during urination, or pain in your lower abdomen? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB2', html_entity_decode('If yes, were you treated for these symptoms? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC2', html_entity_decode('Have you ever been diagnosed or treated for syphilis? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD2', html_entity_decode('Have you ever received a vaccine to prevent human papillomavirus (HPV), a common virus that be passed through sexual contact? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AE1', html_entity_decode('Alcohol and drug use ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AE2', html_entity_decode('During the past 6 months, on how many days did you have at least one drink containing alcohol? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AF2', html_entity_decode('How often do you have 4 or more drinks with alcohol on one occasion? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AG2', html_entity_decode('Have you ever tried recreational drugs? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AH2', html_entity_decode('In the last 6 months, have you taken any recreational drugs? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AI2', html_entity_decode('Recreational drugs', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AJ1', html_entity_decode('Abuse ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AJ2', html_entity_decode('Have you ever been emotionally or physically abused by your partner or someone important to you? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AK2', html_entity_decode('Within the last year, have you ever been hit, slapped, kicked, or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AL2', html_entity_decode('Since you\'ve been pregnant have you been slapped, kicked or otherwise physically hurt by someone? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AM2', html_entity_decode('Within the last year, has anyone forced you to have sexual activities? ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AN2', html_entity_decode('Are you afraid of your partner or anyone listed above?', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('AO1', html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B2')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C2')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D2')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E2')->applyFromArray($styleArray);
                    $sheet->getStyle('F1:K1')->applyFromArray($styleArray);
                    $sheet->getStyle('G1:G1')->applyFromArray($styleArray);
                    $sheet->getStyle('G2')->applyFromArray($styleArray);
                    $sheet->getStyle('H2')->applyFromArray($styleArray);
                    $sheet->getStyle('I2')->applyFromArray($styleArray);
                    $sheet->getStyle('J2')->applyFromArray($styleArray);
                    $sheet->getStyle('K2')->applyFromArray($styleArray);
                    $sheet->getStyle('L2')->applyFromArray($styleArray);
                    $sheet->getStyle('M1:O1')->applyFromArray($styleArray);
                    $sheet->getStyle('M2')->applyFromArray($styleArray);
                    $sheet->getStyle('N2')->applyFromArray($styleArray);
                    $sheet->getStyle('O2')->applyFromArray($styleArray);
                    $sheet->getStyle('P1:AC1')->applyFromArray($styleArray);
                    $sheet->getStyle('P2')->applyFromArray($styleArray);
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
                    $sheet->getStyle('AE1:AI1')->applyFromArray($styleArray);
                    $sheet->getStyle('AE2')->applyFromArray($styleArray);
                    $sheet->getStyle('AF2')->applyFromArray($styleArray);
                    $sheet->getStyle('AG2')->applyFromArray($styleArray);
                    $sheet->getStyle('AH2')->applyFromArray($styleArray);
                    $sheet->getStyle('AI2')->applyFromArray($styleArray);
                    $sheet->getStyle('AJ1:AN1')->applyFromArray($styleArray);
                    $sheet->getStyle('AJ2')->applyFromArray($styleArray);
                    $sheet->getStyle('AK2')->applyFromArray($styleArray);
                    $sheet->getStyle('AL2')->applyFromArray($styleArray);
                    $sheet->getStyle('AM2')->applyFromArray($styleArray);
                    $sheet->getStyle('AN2')->applyFromArray($styleArray);
                    $sheet->getStyle('AO1:AO2')->applyFromArray($styleArray);
                    
                    $currentRow = 3;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            
                            if($colNo > 40){
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
                                   ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation'),'left')
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
}