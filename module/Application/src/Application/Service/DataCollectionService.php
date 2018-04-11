<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;
use PHPExcel;
use PHPExcel_Worksheet;


class DataCollectionService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function addDataCollection($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
           $dataCollectionDb = $this->sm->get('DataCollectionTable');
           $result = $dataCollectionDb->addDataCollectionDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'Lab Data with Patient Barcode ID '.$params['patientBarcodeId'].' has been added successfully.';
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
    
    public function getAllDataCollections($parameters){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchAllDataCollections($parameters);
    }
    
    public function getDataCollection($dataCollectionId){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchDataCollection($dataCollectionId);
    }
    
    public function updateDataCollection($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
           $dataCollectionDb = $this->sm->get('DataCollectionTable');
           $result = $dataCollectionDb->updateDataCollectionDetails($params);
           if($result>0){
               $adapter->commit();
               $alertContainer->msg = 'Lab Data with Patient Barcode ID '.$params['patientBarcodeId'].' has been updated successfully.';
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
    
    public function lockDataCollection($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->lockDataCollectionDetails($params);
    }
    
    public function unlockDataCollection($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->unlockDataCollectionDetails($params);
    }
    
    public function autoDataLockAfterLogin(){
        $loginContainer = new Container('user');
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
        $clinicRiskAssessmentDb = $this->sm->get('ClinicRiskAssessmentTable');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $global = array();
        $globalConfigQuery = $sql->select()->from(array('conf' => 'global_config'));
        $globalConfigQueryStr = $sql->getSqlStringForSqlObject($globalConfigQuery);
        $globalConfigResult = $dbAdapter->query($globalConfigQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        for ($i = 0; $i < sizeof($globalConfigResult); $i++) {
           $global[$globalConfigResult[$i]['name']] = $globalConfigResult[$i]['value'];
        }
        //lab data start
        $lockHour = '+72 hours';//default lock-hour
        //set lock-hour
        if(isset($global['locking_data_after_login']) && (int)$global['locking_data_after_login'] > 0){
            $lockHour = '+'.(int)$global['locking_data_after_login'].' hours';
        }
        
        $dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                                   ->columns(array('data_collection_id','added_on'))
                                   ->where(array('da_c.added_by'=>$loginContainer->userId,'da_c.status'=> 1));
        $dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
        $dataCollectionResult = $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(count($dataCollectionResult)>0){
            $now = date("Y-m-d H:i:s");
            foreach($dataCollectionResult as $dataCollection){
               $newDate = date("Y-m-d H:i:s", strtotime($dataCollection['added_on'] . $lockHour));
               if($newDate <= $now){
                   $params = array();
                   $params['dataCollectionId'] = base64_encode($dataCollection['data_collection_id']);
                   $dataCollectionDb->lockDataCollectionDetails($params);
               }
            }
        }
        //lab data end
        //clinic data start
        $lockHour = '+48 hours';//default lock-hour
        //set clinic data's lock-hour
        if(isset($global['locking_clinic_data_after_login']) && (int)$global['locking_clinic_data_after_login'] > 0){
            $lockHour = '+'.(int)$global['locking_clinic_data_after_login'].' hours';
        }
        
        $clinicDataCollectionQuery = $sql->select()->from(array('cl_da_c' => 'clinic_data_collection'))
                                   ->columns(array('cl_data_collection_id','added_on'))
                                   ->where(array('cl_da_c.added_by'=>$loginContainer->userId,'cl_da_c.status'=>1));
        $clinicDataCollectionQueryStr = $sql->getSqlStringForSqlObject($clinicDataCollectionQuery);
        $clinicDataCollectionResult = $dbAdapter->query($clinicDataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(count($clinicDataCollectionResult)>0){
            $now = date("Y-m-d H:i:s");
            foreach($clinicDataCollectionResult as $clinicDataCollection){
               $newDate = date("Y-m-d H:i:s", strtotime($clinicDataCollection['added_on'] . $lockHour));
               if($newDate <=$now){
                   $params = array();
                   $params['clinicDataCollectionId'] = base64_encode($clinicDataCollection['cl_data_collection_id']);
                   $clinicDataCollectionDb->lockClinicDataCollectionDetails($params);
               }
            }
        }
        //clinic data end
        //clinic risk assessment data start
        $lockHour = '+48 hours';//default data lock-hour
        //set lock-hour
        if(isset($global['locking_risk_assessment_data_after_login']) && (int)$global['locking_risk_assessment_data_after_login'] > 0){
            $lockHour = '+'.(int)$global['locking_risk_assessment_data_after_login'].' hours';
        }
        $riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                                   ->columns(array('assessment_id','added_on'))
                                   ->where(array('r_a.added_by'=>$loginContainer->userId,'r_a.status'=>1));
        $riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($riskAssessmentQuery);
        $riskAssessmentResult = $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(count($riskAssessmentResult)>0){
            $now = date("Y-m-d H:i:s");
            foreach($riskAssessmentResult as $riskAssessment){
               $newDate = date("Y-m-d H:i:s", strtotime($riskAssessment['added_on'] . $lockHour));
               if($newDate <=$now){
                   $params = array();
                   $params['assessment'] = base64_encode($riskAssessment['assessment_id']);
                   $clinicRiskAssessmentDb->lockRiskAssessmentDetails($params);
               }
            }
        }
        //clinic risk assessment data end
      return true;
    }
    
    public function requestForUnlockDataCollection($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->requestForUnlockDataCollectionDetails($params);
    }
    
    public function generateDataCollectionResultPdf($params){
        $queryContainer = new Container('query');
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        if(isset($params['dataCollection']) && count($params['dataCollection']) > 0){
            $dQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                      ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
                      ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
                      ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
                      ->join(array('l_d' => 'location_details'), "l_d.location_id=f.district",array('location_name'),'left')
                      ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code','rejection_name'=>'rejection_reason'),'left');
            $dataCollectionArray = array();
            for($i=0;$i<count($params['dataCollection']);$i++){
                $dataCollectionArray[] = base64_decode($params['dataCollection'][$i]);
            }
            $dQuery = $dQuery->where('da_c.data_collection_id IN ("' . implode('", "', $dataCollectionArray) . '")');
        }else if($params['frmSrc'] == 'l_l_r_r'){
            $dQuery = $queryContainer->labRecencyResultQuery;
        }else{
           return array(); 
        }
        $dQueryStr = $sql->getSqlStringForSqlObject($dQuery);
        $dResult = $dbAdapter->query($dQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(isset($dResult) && count($dResult) >0){
            if(!isset($params['printSrc'])){
                $params['printSrc'] = '';
            }
            foreach($dResult as $row){
                $dataCollectionDb->updateResultPrintStatus($row['data_collection_id'],$params['printSrc']);
            }
        }
      return $dResult;
    }
    
    public function exportDataCollectionInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        $facilityDb = $this->sm->get('FacilityTable');
        $name = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'LAB-LOGBOOK--':'LAB-DATA-REPORT--';
        $sQuery = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$queryContainer->logbookQuery:$queryContainer->dataCollectionQuery;
        if(isset($sQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $labs = '-';
                    $receiptDateatLab = '-';
                    $resultReported = 'Completed Tests, Pending Tests';
                    $headerRow = 1;
                    if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                        $headerRow = 4;
                        //filter content
                        //set labs
                        $facilities = $facilityDb->fetchActivefacilities('extract-excel',$params['countryId']);
                        if(isset($params['labName']) && trim($params['labName'])!= ''){
                            $labs = $params['labName'];
                        }else if(isset($facilities) && count($facilities) > 0){
                            $allLabs = array();
                            foreach($facilities as $facility){
                                $allLabs[] = ' '.ucwords($facility['facility_name']);
                            }
                            $labs = implode(',',$allLabs);
                        }
                        //set receipt date at lab
                        if(trim($params['date'])!= ''){
                            $receiptDateatLab = $params['date'];
                        }
                        //set result reported
                        if(trim($params['status']) == 'completed'){
                            $resultReported = 'Completed Tests';
                        }else if(trim($params['status']) == 'pending'){
                            $resultReported = 'Pending Tests';
                        }
                    }
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $row = array();
                        $ancSiteDistrict = '';
                        if(isset($aRow['anc_site_district']) && $aRow['anc_site_district']!= null && trim($aRow['anc_site_district'])!= ''){
                           $ancSiteDistrict = ucwords($aRow['anc_site_district']);
                        }
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
                        $specimenCollectedDate = '';
                        if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
                            $specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
                        }
                        $specimenPickedUpDateAtAnc = '';
                        if(isset($aRow['specimen_picked_up_date_at_anc']) && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
                            $specimenPickedUpDateAtAnc = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
                        }
                        $receiptDateAtCentralLab = '';
                        if(isset($aRow['receipt_date_at_central_lab']) && trim($aRow['receipt_date_at_central_lab'])!= '' && $aRow['receipt_date_at_central_lab']!= '0000-00-00'){
                            $receiptDateAtCentralLab = $common->humanDateFormat($aRow['receipt_date_at_central_lab']);
                        }
                        $testCompletionDate = '';
                        if(isset($aRow['date_of_test_completion']) && trim($aRow['date_of_test_completion'])!= '' && $aRow['date_of_test_completion']!= '0000-00-00'){
                            $testCompletionDate = $common->humanDateFormat($aRow['date_of_test_completion']);
                        }
                        $resultDispatchedDateToClinic = '';
                        if(isset($aRow['result_dispatched_date_to_clinic']) && trim($aRow['result_dispatched_date_to_clinic'])!= '' && $aRow['result_dispatched_date_to_clinic']!= '0000-00-00'){
                            $resultDispatchedDateToClinic = $common->humanDateFormat($aRow['result_dispatched_date_to_clinic']);
                        }
                        $dateResultReturnedatClinic = '';
                        $timeResultReturnedatClinic = '';
                        if(isset($aRow['date_result_returned_clinic']) && trim($aRow['date_result_returned_clinic'])!= '' && $aRow['date_result_returned_clinic']!= '0000-00-00 00:00:00'){
                            $dateArray = explode(" ",$aRow['date_result_returned_clinic']);
                            $dateResultReturnedatClinic = $common->humanDateFormat($dateArray[0]);
                            $timeResultReturnedatClinic = $dateArray[1];
                        }
                        $dateReturnedtoParticipant = '';
                        $timeReturnedtoParticipant = '';
                        if(isset($aRow['date_returned_to_participant']) && trim($aRow['date_returned_to_participant'])!= '' && $aRow['date_returned_to_participant']!= '0000-00-00 00:00:00'){
                            $dateArray = explode(" ",$aRow['date_returned_to_participant']);
                            $dateReturnedtoParticipant = $common->humanDateFormat($dateArray[0]);
                            $timeReturnedtoParticipant = $dateArray[1];
                        }
                        $rejectionCode = '';
                        if(isset($aRow['rejection_code']) && (int)$aRow['rejection_code'] > 1){
                            $rejectionCode = $aRow['rejection_code'];
                        }
                        $recencyInfection = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
                        
                        //$hIVRNAResult = '';
                        //if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
                        //    $hIVRNAResult = 'High Viral Load';
                        //}else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
                        //    $hIVRNAResult = 'Low Viral Load';
                        //}
                        $finalLagRecencyInfection = '';
                        if(isset($aRow['recent_infection']) && $aRow['recent_infection'] != null){
                            if($aRow['recent_infection'] == 'yes'){
                                $finalLagRecencyInfection = 'Recent';
                            }else if($aRow['recent_infection'] == 'no'){
                                $finalLagRecencyInfection = 'Long Term';
                            }else{
                                $finalLagRecencyInfection = 'Incomplete';
                            }
                        }
                        $rapidRecencyAssay = '';
                        $diagnosisReaderLogVal = '';
                        $rapidRecencyAssayDuration = '';
                        $recencyReaderLogVal = '';
                        if(trim($aRow['asante_rapid_recency_assy'])!= ''){
                            $asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
                            if(isset($asanteRapidRecencyAssy['rrdt'])){
                                $rapidRecencyAssay = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?ucwords($asanteRapidRecencyAssy['rrdt']['assay']):'';
                                $diagnosisReaderLogVal = (isset($asanteRapidRecencyAssy['rrdt']['reader']))?$asanteRapidRecencyAssy['rrdt']['reader']:'';
                            }if(isset($asanteRapidRecencyAssy['rrr'])){
                                $rapidRecencyAssayDuration = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
                                $recencyReaderLogVal = (isset($asanteRapidRecencyAssy['rrr']['reader']))?$asanteRapidRecencyAssy['rrr']['reader']:'';
                            }
                        }
                        $sampleType = '';
                        if($aRow['specimen_type'] == 1){
                           $sampleType = 'Venous';
                        }else if($aRow['specimen_type'] == 2){
                           $sampleType = 'Plasma'; 
                        }else if($aRow['specimen_type'] == 3){
                           $sampleType = 'DBS';
                        }
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$receiptDateAtCentralLab:$aRow['patient_barcode_id'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['patient_barcode_id']:$specimenCollectedDate;
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$specimenCollectedDate:ucwords($aRow['anc_site_name']);
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?ucwords($aRow['anc_site_name']):$aRow['anc_site_code'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['anc_site_code']:$aRow['enc_anc_patient_id'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['enc_anc_patient_id']:'';
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'':$aRow['age'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['age']:$aRow['gestational_age'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['gestational_age']:$specimenPickedUpDateAtAnc;
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$specimenPickedUpDateAtAnc:ucwords($aRow['facility_name']);
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?ucwords($aRow['facility_name']):$aRow['facility_code'];
                        //$row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['facility_code']:$aRow['lab_specimen_id'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['facility_code']:$rejectionCode;
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$rejectionCode:$receiptDateAtCentralLab;
                        $row[] = ucwords($aRow['lab_tech_name']);
                        $row[] = $testCompletionDate;
                        $row[] = $resultDispatchedDateToClinic;
                        $row[] = $aRow['hiv_rna'];
                        $row[] = $aRow['final_lag_avidity_odn'];
                        $row[] = $recencyInfection;
                        $row[] = $finalLagRecencyInfection;
                        $row[] = $diagnosisReaderLogVal;
                        $row[] = $rapidRecencyAssay;
                        $row[] = $recencyReaderLogVal;
                        $row[] = $rapidRecencyAssayDuration;
                        $row[] = ucfirst($aRow['comments']);
                        $row[] = $addedDate;
                        $row[] = (isset($aRow['addedBy']))?ucwords($aRow['addedBy']):'';
                        $row[] = $updatedDate;
                        $row[] = (isset($aRow['updatedBy']))?ucwords($aRow['updatedBy']):'';
                        if(!isset($params['frmSrc'])){
                            $row[] = $sampleType;
                            $row[] = ucwords($aRow['test_status_name']);
                        }
                        $row[] = $dateResultReturnedatClinic;
                        $row[] = $timeResultReturnedatClinic;
	                $row[] = $dateReturnedtoParticipant;
                        $row[] = $timeReturnedtoParticipant;
                        if(!isset($params['countryId']) || trim($params['countryId']) == ''){
                            $row[] = ucfirst($aRow['country_name']);
                        }
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                            $row[] = ''; //manager's approval
                        }
                        $row[] = $ancSiteDistrict;
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
                    $redTxtArray = array(
                        'font' => array(
                            'color' => array('rgb' => 'F44336')
                        )
                    );
                    $blueTxtArray = array(
                        'font' => array(
                            'color' => array('rgb' => '3792a8')
                        )
                    );
                    $yellowTxtArray = array(
                        'fill' => array(
                            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'FFFF00')
                        )
                    );
                    $titleTxtArray = array(
                        'font' => array(
                            'size' => 22
                        )
                    );
                    $labelArray = array(
                        'font' => array(
                            'bold' => true,
                        ),
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        )
                    );
                    $wrapTxtArray = array(
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        )
                    );
                    $lagAssayArray = array(
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
                        ),
                        'fill' => array(
                            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => '9bc2e6')
                        )
                    );
                    $rapidRecencyAssayArray = array(
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
                        ),
                        'fill' => array(
                            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'ffe699')
                        )
                    );
                    if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                      $sheet->mergeCells('R3:T3');
                      $sheet->mergeCells('U3:X3');
                      
                      $sheet->setCellValue('A1', html_entity_decode('Logbook for Recency Test ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('A2', html_entity_decode('Lab Site/Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('B2', html_entity_decode($labs, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('A3', html_entity_decode('Receipt Date at Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('B3', html_entity_decode($receiptDateatLab, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('C3', html_entity_decode('Result Reported ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('D3', html_entity_decode($resultReported, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('R3', html_entity_decode('LAg Assay ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('U3', html_entity_decode('Rapid Recency Assay ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'B'.$headerRow:'A'.$headerRow, html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'C'.$headerRow:'B'.$headerRow, html_entity_decode('Specimen Collection Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'D'.$headerRow:'C'.$headerRow, html_entity_decode('ANC Site ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'E'.$headerRow:'D'.$headerRow, html_entity_decode('ANC Site Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'F'.$headerRow:'E'.$headerRow, html_entity_decode('Encrypted ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'G'.$headerRow:'F'.$headerRow, html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'H'.$headerRow:'G'.$headerRow, html_entity_decode('Age ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'I'.$headerRow:'H'.$headerRow, html_entity_decode('Gestation Age (Weeks) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'J'.$headerRow:'I'.$headerRow, html_entity_decode('Specimen Picked Up Date at ANC ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'K'.$headerRow:'J'.$headerRow, html_entity_decode('Lab/Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'L'.$headerRow:'K'.$headerRow, html_entity_decode('Lab/Facility Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    //$sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'M'.$headerRow:'L'.$headerRow, html_entity_decode('Lab Specimen ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'M'.$headerRow:'L'.$headerRow, html_entity_decode('Rejection Code At Central Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'A'.$headerRow:'M'.$headerRow, html_entity_decode('Receipt Date at Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N'.$headerRow, html_entity_decode('Lab Tech. Name/ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O'.$headerRow, html_entity_decode('Date of Test Completion', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P'.$headerRow, html_entity_decode('Result Dispatched Date to Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q'.$headerRow, html_entity_decode('HIV RNA (cp/ml)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R'.$headerRow, html_entity_decode('LAg Avidity ODn ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    //$sheet->setCellValue('S'.$headerRow, html_entity_decode('HIV RNA > 1000', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S'.$headerRow, html_entity_decode('LAg Recency by ODn', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T'.$headerRow, html_entity_decode('LAg Recency (Odn+VL)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U'.$headerRow, html_entity_decode('Asante Positive Verification Line Reader Value (log10)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V'.$headerRow, html_entity_decode('Asante Positive Verification Line (Visual)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W'.$headerRow, html_entity_decode('Asante Long Term Line Reader Value (log10)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X'.$headerRow, html_entity_decode('Asante Long Term Line (Visual)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y'.$headerRow, html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z'.$headerRow, html_entity_decode('Added Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA'.$headerRow, html_entity_decode('Added by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB'.$headerRow, html_entity_decode('Last Updated Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC'.$headerRow, html_entity_decode('Last Updated by', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    if(!isset($params['frmSrc'])){
                        $sheet->setCellValue('AD'.$headerRow, html_entity_decode('Specimen Type', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AE'.$headerRow, html_entity_decode('Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AF'.$headerRow, html_entity_decode('Date Result Returned at Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AG'.$headerRow, html_entity_decode('Time Result Returned at Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AH'.$headerRow, html_entity_decode('Date Returned to Participant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AI'.$headerRow, html_entity_decode('Time Returned to Participant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }else{
                        $sheet->setCellValue('AD'.$headerRow, html_entity_decode('Date Result Returned at Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AE'.$headerRow, html_entity_decode('Time Result Returned at Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AF'.$headerRow, html_entity_decode('Date Returned to Participant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AG'.$headerRow, html_entity_decode('Time Returned to Participant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    if(!isset($params['countryId']) || trim($params['countryId']) == ''){
                        $cellName = (!isset($params['frmSrc']))?'AJ':'AH';
                        $sheet->setCellValue($cellName.$headerRow, html_entity_decode('Country', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->setCellValue('AI'.$headerRow, html_entity_decode('Manager\'s Approval', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                          $sheet->setCellValue('AJ'.$headerRow, html_entity_decode('ANC District', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING); 
                        }else{
                          $sheet->setCellValue('AK'.$headerRow, html_entity_decode('ANC District', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                    }else{
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->setCellValue('AH'.$headerRow, html_entity_decode('Manager\'s Approval', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                          $sheet->setCellValue('AI'.$headerRow, html_entity_decode('ANC District', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);  
                        }else{
                          $sheet->setCellValue('AJ'.$headerRow, html_entity_decode('ANC District', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);  
                        }
                    }
                    
                    if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                      $sheet->getRowDimension(1)->setRowHeight(-1);
                      //$sheet->getRowDimension(2)->setRowHeight(-1);
                      //$sheet->getStyle('B2')->getAlignment()->setWrapText(true);
                      //$sheet->getStyle('D2')->getAlignment()->setWrapText(true);
                      
                      $sheet->getStyle('A1')->applyFromArray($titleTxtArray);
                      $sheet->getStyle('A2')->applyFromArray($labelArray);
                      //$sheet->getStyle('B2')->applyFromArray($wrapTxtArray);
                      //$sheet->getStyle('D2')->applyFromArray($wrapTxtArray);
                      $sheet->getStyle('A3')->applyFromArray($labelArray);
                      $sheet->getStyle('C3')->applyFromArray($labelArray);
                      $sheet->getStyle('R3:T3')->applyFromArray($lagAssayArray);
                      $sheet->getStyle('U3:X3')->applyFromArray($rapidRecencyAssayArray);
                    }
                    $sheet->getStyle('A'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('B'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('C'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('D'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('E'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('F'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('G'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('H'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('I'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('J'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('K'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('L'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('M'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('N'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('O'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('P'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Q'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('R'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('S'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('T'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('U'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('V'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('W'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('X'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Y'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('Z'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AA'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AB'.$headerRow)->applyFromArray($styleArray);
                    $sheet->getStyle('AC'.$headerRow)->applyFromArray($styleArray);
                    if(!isset($params['frmSrc'])){
                        $sheet->getStyle('AD'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AE'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AF'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AG'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AH'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AI'.$headerRow)->applyFromArray($styleArray);
                    }else{
                        $sheet->getStyle('AD'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AE'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AF'.$headerRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AG'.$headerRow)->applyFromArray($styleArray);
                    }
                    if(!isset($params['countryId']) || trim($params['countryId']) == ''){
                        $cellName = (!isset($params['frmSrc']))?'AJ':'AH';
                        $sheet->getStyle($cellName.$headerRow)->applyFromArray($styleArray);
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                         $sheet->getStyle('AI'.$headerRow)->applyFromArray($styleArray);
                         $sheet->getStyle('AJ'.$headerRow)->applyFromArray($styleArray);
                        }else{
                          $sheet->getStyle('AK'.$headerRow)->applyFromArray($styleArray);
                        }
                    }else{
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->getStyle('AH'.$headerRow)->applyFromArray($styleArray);
                          $sheet->getStyle('AI'.$headerRow)->applyFromArray($styleArray);
                        }else{
                          $sheet->getStyle('AJ'.$headerRow)->applyFromArray($styleArray);  
                        }
                    }
                    
                    $currentRow = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?5:2;
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'B'.$currentRow:'A'.$currentRow, html_entity_decode('PatientBarcodeID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'C'.$currentRow:'B'.$currentRow, html_entity_decode('speccollectdate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'D'.$currentRow:'C'.$currentRow, html_entity_decode('ANCSite', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'E'.$currentRow:'D'.$currentRow, html_entity_decode('ANCSiteCode', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'F'.$currentRow:'E'.$currentRow, html_entity_decode('EncryptedANCPatientID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'G'.$currentRow:'F'.$currentRow, html_entity_decode('ANCPatientID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'H'.$currentRow:'G'.$currentRow, html_entity_decode('Age', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'I'.$currentRow:'H'.$currentRow, html_entity_decode('GestationAgeWeeks ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'J'.$currentRow:'I'.$currentRow, html_entity_decode('specpickeddate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'K'.$currentRow:'J'.$currentRow, html_entity_decode('labfacility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'L'.$currentRow:'K'.$currentRow, html_entity_decode('labcode ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'M'.$currentRow:'L'.$currentRow, html_entity_decode('rejectsatellite ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'A'.$currentRow:'M'.$currentRow, html_entity_decode('labrecdate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N'.$currentRow, html_entity_decode('labtech ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O'.$currentRow, html_entity_decode('testdate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P'.$currentRow, html_entity_decode('resultdispatchdate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q'.$currentRow, html_entity_decode('hivrna ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R'.$currentRow, html_entity_decode('lagodn ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S'.$currentRow, html_entity_decode('lagrecent ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T'.$currentRow, html_entity_decode('ritarecent ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U'.$currentRow, html_entity_decode('asanteHIVposvalue ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V'.$currentRow, html_entity_decode('asanteHIVpos ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W'.$currentRow, html_entity_decode('asanteLTvalue ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X'.$currentRow, html_entity_decode('asanteLTvisual ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y'.$currentRow, html_entity_decode('comments ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z'.$currentRow, html_entity_decode('adddate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA'.$currentRow, html_entity_decode('addby ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB'.$currentRow, html_entity_decode('update ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC'.$currentRow, html_entity_decode('updateby ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    if(!isset($params['frmSrc'])){
                        $sheet->setCellValue('AD'.$currentRow, html_entity_decode('spectype ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AE'.$currentRow, html_entity_decode('status ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AF'.$currentRow, html_entity_decode('dateresultreturnedclinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AG'.$currentRow, html_entity_decode('timeresultreturnedclinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AH'.$currentRow, html_entity_decode('datereturnedtoparticipant ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AI'.$currentRow, html_entity_decode('timereturnedtoparticipant ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }else{
                        $sheet->setCellValue('AD'.$currentRow, html_entity_decode('dateresultreturnedclinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AE'.$currentRow, html_entity_decode('timeresultreturnedclinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AF'.$currentRow, html_entity_decode('datereturnedtoparticipant ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AG'.$currentRow, html_entity_decode('timereturnedtoparticipant ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    if(!isset($params['countryId']) || trim($params['countryId']) == ''){
                        $cellName = (!isset($params['frmSrc']))?'AJ':'AH';
                        $sheet->setCellValue($cellName.$currentRow, html_entity_decode('Country', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                           $sheet->setCellValue('AI'.$currentRow, html_entity_decode('ManagersApproval', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                           $sheet->setCellValue('AJ'.$currentRow, html_entity_decode('ancdistrict', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }else{
                           $sheet->setCellValue('AK'.$currentRow, html_entity_decode('ancdistrict', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                    }else{
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->setCellValue('AH'.$currentRow, html_entity_decode('ManagersApproval', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                          $sheet->setCellValue('AI'.$currentRow, html_entity_decode('ancdistrict', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }else{
                          $sheet->setCellValue('AJ'.$currentRow, html_entity_decode('ancdistrict', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                    }
                    
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
                    if(!isset($params['frmSrc'])){
                        $sheet->getStyle('AD'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AE'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AF'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AG'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AH'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AI'.$currentRow)->applyFromArray($styleArray);
                    }else{
                        $sheet->getStyle('AD'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AE'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AF'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AG'.$currentRow)->applyFromArray($styleArray);
                    }
                    if(!isset($params['countryId']) || trim($params['countryId']) == ''){
                      $cellName = (!isset($params['frmSrc']))?'AJ':'AH';  
                      $sheet->getStyle($cellName.$currentRow)->applyFromArray($styleArray);
                      if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                        $sheet->getStyle('AI'.$currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('AJ'.$currentRow)->applyFromArray($styleArray);
                      }else{
                        $sheet->getStyle('AK'.$currentRow)->applyFromArray($styleArray);
                      }
                    }else{
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->getStyle('AH'.$currentRow)->applyFromArray($styleArray);
                          $sheet->getStyle('AI'.$currentRow)->applyFromArray($styleArray);
                        }else{
                          $sheet->getStyle('AJ'.$currentRow)->applyFromArray($styleArray);  
                        }
                    }
                    $currentRow = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?6:3;
                    foreach ($output as $rowData) {
                        $rejection_code = '';
                        $lag = '';
                        $labHIVV = '';
                        $labHIVR = '';
                        $colNo = 0;
                        $status = '';
                        $lastCol = (count($rowData)-1);
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            if(!isset($params['frmSrc']) && $colNo == 11){ $rejection_code = $value; }
                            if(isset($params['frmSrc']) && $colNo == 12){ $rejection_code = $value; }
                            if($colNo == 19){ $lag = $value; }
                            if($colNo == 21){ $labHIVV = $value; }
                            if($colNo == 23){ $labHIVR = $value; }
                            if($colNo == 30){ $status = $value; }
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            if($colNo > ($lastCol-1)){
                                if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                                    $lastColName = (!isset($params['frmSrc']))?'AK':'AJ';
                                }else{
                                    $lastColName = (!isset($params['frmSrc']))?'AJ':'AI';
                                }
                                if(trim($rejection_code)!= '' && $rejection_code > 1){
                                    $sheet->getStyle('A'.$currentRow.':'.$lastColName.$currentRow)->applyFromArray($blueTxtArray);
                                }else{
                                    if(!isset($params['frmSrc']) && $status == 'Incomplete'){
                                       $sheet->getStyle('A'.$currentRow.':'.$lastColName.$currentRow)->applyFromArray($yellowTxtArray);
                                    } 
                                    if($labHIVV =='Absent' || ($lag == 'Long Term' && $labHIVR == 'Absent') || ($lag == 'Recent' && $labHIVR == 'Present')){
                                      $sheet->getStyle('A'.$currentRow.':'.$lastColName.$currentRow)->applyFromArray($redTxtArray);
                                    }
                                }
                            }
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                      $currentRow++;
                    }
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = $name . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "na";
                }
            }catch (Exception $exc) {
                error_log($name . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getAllDataExtractions($parameters){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchAllDataExtractions($parameters);
    }
    
    public function getSearchableDataCollection($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchSearchableDataCollection($params);
    }
    
    public function getDashboardDetails($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchDashboardDetails($params);
    }
    
    public function getCountriesLabAncDetails($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchCountriesLabAncDetails($params);
    }
    
    public function getAllLabLogbook($parameters){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fecthAllLabLogbook($parameters);
    }
    
    public function getLogbookResult($params){
        $queryContainer = new Container('query');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $logbookQueryStr = $sql->getSqlStringForSqlObject($queryContainer->logbookQuery);
      return $dbAdapter->query($logbookQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getActiveAncFormFields(){
        $ancFormDb = $this->sm->get('AncFormTable');
        return $ancFormDb->fetchActiveAncFormFields();
    }
    
    public function addClinicDataCollection($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
           $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
           $result = $clinicDataCollectionDb->addClinicDataCollectionDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'ANC Data Reporting added successfully.';
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
    
    public function getAllClinicDataCollections($parameters){
        $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
        return $clinicDataCollectionDb->fetchAllClinicDataCollections($parameters);
    }
    
    public function getClinicDataCollection($clinicDataCollectionId){
        $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
        return $clinicDataCollectionDb->fetchClinicDataCollection($clinicDataCollectionId);
    }
    
    public function updateClinicDataCollection($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
           $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
           $result = $clinicDataCollectionDb->updateClinicDataCollectionDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'ANC Data Reporting updated successfully.';
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
    
    public function getAllClinicalDataExtractions($parameters){
        $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
        return $clinicDataCollectionDb->fetchAllClinicalDataExtractions($parameters);
    }
    
    public function exportClinicDataCollectionInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->clinicDataCollectionQuery)){
            try{
                $ancFormDb = $this->sm->get('AncFormTable');
                $ancFormFields = $ancFormDb->fetchActiveAncFormFields();
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->clinicDataCollectionQuery);
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
                        $row = array();
                        $ancSiteDistrict = '';
                        if(isset($aRow['anc_site_district']) && $aRow['anc_site_district']!= null && trim($aRow['anc_site_district'])!= ''){
                           $ancSiteDistrict = ucwords($aRow['anc_site_district']);
                        }
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
                        $reportingMonth = '';
                        $reportingYear = '';
                        if(isset($aRow['reporting_month_year']) && trim($aRow['reporting_month_year'])!= ''){
                            $reportingMonthYearArray = explode('/',$aRow['reporting_month_year']);
                            $reportingMonth = $reportingMonthYearArray[0];
                            $reportingYear = $reportingMonthYearArray[1];
                        }
                        $dateofSupportVisit = '';
                        if($aRow['date_of_support_visit']!= null && trim($aRow['date_of_support_visit'])!= '' && $aRow['date_of_support_visit']!= '0000-00-00'){
                            $dateofSupportVisit = $common->humanDateFormat($aRow['date_of_support_visit']);
                        }
                        $row[] = ucwords($aRow['anc_site_name']);
                        $row[] = $aRow['anc_site_code'];
                        $row[] = ucfirst($reportingMonth);
                        $row[] = $reportingYear;
                        $row[] = $dateofSupportVisit;
                        if($params['countryId'] == ''){
                          $row[] = ucwords($aRow['country_name']);
                        }
                        foreach($ancFormFields as $key=>$value){
                            //for new fields
                            $col0Val = '0';
                            $col1Val = '0';
                            $col2Val = '0';
                            $col3Val = '0';
                            $col4Val = '0';
                            if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                                $fields = json_decode($aRow['characteristics_data'],true);
                                foreach($fields as $fieldName=>$fieldValue){
                                    if($key == $fieldName){
                                        //for existing fields
                                        foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                            $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                                           if($characteristicsName =='age_lt_15'){
                                              $col0Val = $characteristicsValue;
                                           }elseif($characteristicsName =='age_15_to_19'){
                                              $col1Val = $characteristicsValue;
                                           }elseif($characteristicsName =='age_20_to_24'){
                                              $col2Val = $characteristicsValue;
                                           }elseif($characteristicsName =='age_unknown'){
                                              $col3Val = $characteristicsValue;
                                           }elseif($characteristicsName =='total'){
                                              $col4Val = $characteristicsValue;
                                           }
                                        }
                                    }
                                }
                            }
                          if($value == 'yes'){
                            $row[] = $col0Val;
                            $row[] = $col1Val;
                            $row[] = $col2Val;
                            $row[] = $col3Val;
                          }
                          $row[] = $col4Val;
                        }
                        $row[] = ucfirst($aRow['comments']);
                        $row[] = $addedDate;
                        $row[] = (isset($aRow['addedBy']))?ucwords($aRow['addedBy']):'';
                        $row[] = $updatedDate;
                        $row[] = (isset($aRow['updatedBy']))?ucwords($aRow['updatedBy']):'';
                        $row[] = $ancSiteDistrict;
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
                    if($params['countryId'] == ''){
                      $sheet->mergeCells('F1:F2');
                    }
                     
                    $e1 = ($params['countryId'] == '')?6:5;
                    foreach($ancFormFields as $key=>$value){
                        $e2 = ($value == 'yes')?$e1+4:$e1;
                        if($value == 'yes'){
                            $startCell = $sheet->getCellByColumnAndRow($e1, 1)->getColumn();
                            $endCell = $sheet->getCellByColumnAndRow($e2, 1)->getColumn();
                            $sheet->mergeCells($startCell.'1:'.$endCell.'1');
                        }
                      $e1 = $e2;
                      $e1++;
                    }
                    $cellName = $sheet->getCellByColumnAndRow($e1, 1)->getColumn();
                    $sheet->mergeCells($cellName.'1:'.$cellName.'2');
                    $cellName = $sheet->getCellByColumnAndRow($e1+1, 1)->getColumn();
                    $sheet->mergeCells($cellName.'1:'.$cellName.'2');
                    $cellName = $sheet->getCellByColumnAndRow($e1+2, 1)->getColumn();
                    $sheet->mergeCells($cellName.'1:'.$cellName.'2');
                    $cellName = $sheet->getCellByColumnAndRow($e1+3, 1)->getColumn();
                    $sheet->mergeCells($cellName.'1:'.$cellName.'2');
                    $cellName = $sheet->getCellByColumnAndRow($e1+4, 1)->getColumn();
                    $sheet->mergeCells($cellName.'1:'.$cellName.'2');
                    $cellName = $sheet->getCellByColumnAndRow($e1+5, 1)->getColumn();
                    $sheet->mergeCells($cellName.'1:'.$cellName.'2');
                    
                    $sheet->setCellValue('A1', html_entity_decode('Clinic Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Clinic ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Month ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Year ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Support Visit Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    if($params['countryId'] == ''){
                       $sheet->setCellValue('F1', html_entity_decode('Country ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $a1 = ($params['countryId'] == '')?6:5;
                    foreach($ancFormFields as $key=>$value){
                        $columnTitle = ucfirst(str_replace("_"," ",$key));
                        $columnTitle = str_replace("No","No.",$columnTitle);
                        $cellName = $sheet->getCellByColumnAndRow($a1, 1)->getColumn();
                        $sheet->setCellValue($cellName.'1', html_entity_decode($columnTitle, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        if($value == 'yes'){
                            $subCellOne = $sheet->getCellByColumnAndRow($a1, 2)->getColumn();
                            $subCellTwo = $sheet->getCellByColumnAndRow($a1+1, 2)->getColumn();
                            $subCellThree = $sheet->getCellByColumnAndRow($a1+2, 2)->getColumn();
                            $subCellFour = $sheet->getCellByColumnAndRow($a1+3, 2)->getColumn();
                            $subCellFive = $sheet->getCellByColumnAndRow($a1+4, 2)->getColumn();
                            $sheet->setCellValue($subCellOne.'2', html_entity_decode('Age < 15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            $sheet->setCellValue($subCellTwo.'2', html_entity_decode('Age 15-19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            $sheet->setCellValue($subCellThree.'2', html_entity_decode('Age 20-24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            $sheet->setCellValue($subCellFour.'2', html_entity_decode('Age Unknown', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            $sheet->setCellValue($subCellFive.'2', html_entity_decode('Total', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }else{
                            $subCellOne = $sheet->getCellByColumnAndRow($a1, 2)->getColumn();
                            $sheet->setCellValue($subCellOne.'2', html_entity_decode('Total', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                      if($value == 'yes'){ $a1+=4; }
                      $a1++;
                    }
                    $cellName = $sheet->getCellByColumnAndRow($a1, 1)->getColumn();
                    $sheet->setCellValue($cellName.'1', html_entity_decode('Comments ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $cellName = $sheet->getCellByColumnAndRow($a1+1, 1)->getColumn();
                    $sheet->setCellValue($cellName.'1', html_entity_decode('Added Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $cellName = $sheet->getCellByColumnAndRow($a1+2, 1)->getColumn();
                    $sheet->setCellValue($cellName.'1', html_entity_decode('Added by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $cellName = $sheet->getCellByColumnAndRow($a1+3, 1)->getColumn();
                    $sheet->setCellValue($cellName.'1', html_entity_decode('Last Updated Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $cellName = $sheet->getCellByColumnAndRow($a1+4, 1)->getColumn();
                    $sheet->setCellValue($cellName.'1', html_entity_decode('Last Updated by ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $cellName = $sheet->getCellByColumnAndRow($a1+5, 1)->getColumn();
                    $sheet->setCellValue($cellName.'1', html_entity_decode('ANC District ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B2')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C2')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D2')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E2')->applyFromArray($styleArray);
                    if($params['countryId'] == ''){
                       $sheet->getStyle('F1:F2')->applyFromArray($styleArray);
                    }
                    $f1 = ($params['countryId'] == '')?6:5;
                    foreach($ancFormFields as $key=>$value){
                        $f2 = ($value == 'yes')?$f1+4:$f1;
                        if($value == 'yes'){
                            $startCell = $sheet->getCellByColumnAndRow($f1, 1)->getColumn();
                            $endCell = $sheet->getCellByColumnAndRow($f2, 1)->getColumn();
                            $subCellone = $sheet->getCellByColumnAndRow($f1, 2)->getColumn();
                            $subCellTwo = $sheet->getCellByColumnAndRow($f1+1, 2)->getColumn();
                            $subCellThree = $sheet->getCellByColumnAndRow($f1+2, 2)->getColumn();
                            $subCellFour = $sheet->getCellByColumnAndRow($f1+3, 2)->getColumn();
                            $subCellFive = $sheet->getCellByColumnAndRow($f1+4, 2)->getColumn();
                            
                            $sheet->getStyle($startCell.'1:'.$endCell.'1')->applyFromArray($styleArray);
                            $sheet->getStyle($subCellone.'2')->applyFromArray($styleArray);
                            $sheet->getStyle($subCellTwo.'2')->applyFromArray($styleArray);
                            $sheet->getStyle($subCellThree.'2')->applyFromArray($styleArray);
                            $sheet->getStyle($subCellFour.'2')->applyFromArray($styleArray);
                            $sheet->getStyle($subCellFive.'2')->applyFromArray($styleArray);
                        }else{
                           $startCell = $sheet->getCellByColumnAndRow($f1, 1)->getColumn();
                           $sheet->getStyle($startCell.'1')->applyFromArray($styleArray);
                           $subCellone = $sheet->getCellByColumnAndRow($f1, 2)->getColumn();
                           $sheet->getStyle($subCellone.'2')->applyFromArray($styleArray);
                        }
                      $f1 = $f2;
                      $f1++;
                    }
                    $cellName = $sheet->getCellByColumnAndRow($f1, 1)->getColumn();
                    $sheet->getStyle($cellName.'1:'.$cellName.'2')->applyFromArray($styleArray);
                    $cellName = $sheet->getCellByColumnAndRow($f1+1, 1)->getColumn();
                    $sheet->getStyle($cellName.'1:'.$cellName.'2')->applyFromArray($styleArray);
                    $cellName = $sheet->getCellByColumnAndRow($f1+2, 1)->getColumn();
                    $sheet->getStyle($cellName.'1:'.$cellName.'2')->applyFromArray($styleArray);
                    $cellName = $sheet->getCellByColumnAndRow($f1+3, 1)->getColumn();
                    $sheet->getStyle($cellName.'1:'.$cellName.'2')->applyFromArray($styleArray);
                    $cellName = $sheet->getCellByColumnAndRow($f1+4, 1)->getColumn();
                    $sheet->getStyle($cellName.'1:'.$cellName.'2')->applyFromArray($styleArray);
                    $cellName = $sheet->getCellByColumnAndRow($f1+5, 1)->getColumn();
                    $sheet->getStyle($cellName.'1:'.$cellName.'2')->applyFromArray($styleArray);
                    
                    $currentRow = 3;
                    $sheet->setCellValue('A'.$currentRow, html_entity_decode('clinicname', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B'.$currentRow, html_entity_decode('clinicid', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C'.$currentRow, html_entity_decode('monthcount', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D'.$currentRow, html_entity_decode('yearcount', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E'.$currentRow, html_entity_decode('dcvisitdate', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F'.$currentRow, html_entity_decode('attunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G'.$currentRow, html_entity_decode('att15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H'.$currentRow, html_entity_decode('att20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I'.$currentRow, html_entity_decode('attageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J'.$currentRow, html_entity_decode('atttotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K'.$currentRow, html_entity_decode('prevposunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L'.$currentRow, html_entity_decode('prevpos15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M'.$currentRow, html_entity_decode('prevpos20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N'.$currentRow, html_entity_decode('prevposageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O'.$currentRow, html_entity_decode('prevpostotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P'.$currentRow, html_entity_decode('prevnegunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q'.$currentRow, html_entity_decode('prevneg15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R'.$currentRow, html_entity_decode('prevneg20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S'.$currentRow, html_entity_decode('prevnegageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T'.$currentRow, html_entity_decode('prevnegtotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U'.$currentRow, html_entity_decode('unkhivunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V'.$currentRow, html_entity_decode('unkhiv15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W'.$currentRow, html_entity_decode('unkhiv20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('X'.$currentRow, html_entity_decode('unkhivageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Y'.$currentRow, html_entity_decode('unkhivtotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Z'.$currentRow, html_entity_decode('rdttestunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AA'.$currentRow, html_entity_decode('rdttest15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AB'.$currentRow, html_entity_decode('rdttest20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AC'.$currentRow, html_entity_decode('rdttestageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AD'.$currentRow, html_entity_decode('rdttesttotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AE'.$currentRow, html_entity_decode('rdtposunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AF'.$currentRow, html_entity_decode('rdtpos15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AG'.$currentRow, html_entity_decode('rdtpos20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AH'.$currentRow, html_entity_decode('rdtposageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AI'.$currentRow, html_entity_decode('rdtpostotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AJ'.$currentRow, html_entity_decode('rdtnegunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AK'.$currentRow, html_entity_decode('rdtneg15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AL'.$currentRow, html_entity_decode('rdtneg20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AM'.$currentRow, html_entity_decode('rdtnegageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AN'.$currentRow, html_entity_decode('rdtnegtotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AO'.$currentRow, html_entity_decode('rdtindunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AP'.$currentRow, html_entity_decode('rdtind15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AQ'.$currentRow, html_entity_decode('rdtind20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AR'.$currentRow, html_entity_decode('rdtindageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AS'.$currentRow, html_entity_decode('rdtindtotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AT'.$currentRow, html_entity_decode('enrunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AU'.$currentRow, html_entity_decode('enr15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AV'.$currentRow, html_entity_decode('enr20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AW'.$currentRow, html_entity_decode('enrageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AX'.$currentRow, html_entity_decode('enrtotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AY'.$currentRow, html_entity_decode('notenrunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('AZ'.$currentRow, html_entity_decode('notenr15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BA'.$currentRow, html_entity_decode('notenr20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BB'.$currentRow, html_entity_decode('notenrageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BC'.$currentRow, html_entity_decode('notenrtotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BD'.$currentRow, html_entity_decode('rectestunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BE'.$currentRow, html_entity_decode('rectest15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BF'.$currentRow, html_entity_decode('rectest20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BG'.$currentRow, html_entity_decode('rectestageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BH'.$currentRow, html_entity_decode('rectesttotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BI'.$currentRow, html_entity_decode('anc1vistotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BJ'.$currentRow, html_entity_decode('recresunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BK'.$currentRow, html_entity_decode('recres15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BL'.$currentRow, html_entity_decode('recres20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BM'.$currentRow, html_entity_decode('recresageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BN'.$currentRow, html_entity_decode('recrestotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BO'.$currentRow, html_entity_decode('gotresunder15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BP'.$currentRow, html_entity_decode('gotres15to19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BQ'.$currentRow, html_entity_decode('gotres20to24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BR'.$currentRow, html_entity_decode('gotresageunk', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BS'.$currentRow, html_entity_decode('gotrestotal', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BT'.$currentRow, html_entity_decode('ancmoncomment', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BU'.$currentRow, html_entity_decode('ancmonadddate', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BV'.$currentRow, html_entity_decode('ancmonaddby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BW'.$currentRow, html_entity_decode('ancmonupdate', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BX'.$currentRow, html_entity_decode('ancmonupdby', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('BY'.$currentRow, html_entity_decode('ancdistrict', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
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
                    $sheet->getStyle('BU'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BV'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BW'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BX'.$currentRow)->applyFromArray($styleArray);
                    $sheet->getStyle('BY'.$currentRow)->applyFromArray($styleArray);
                    
                    $currentRow = 4;
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
                    $filename = 'ANC-DATA-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "na";
                }
            }catch (Exception $exc) {
                error_log("ANC-DATA-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getAllLabRecencyResult($parameters){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchAllLabRecencyResult($parameters);
    }
    
    public function getLabReportResult(){
        $queryContainer = new Container('query');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $rQueryStr = $sql->getSqlStringForSqlObject($queryContainer->labRecencyResultQuery);
      return $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getLatestDataCollectionInfo(){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $lrQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                                 ->columns(array('data_collection_id'))
                                 ->order('da_c.data_collection_id DESC');
        $lrQueryStr = $sql->getSqlStringForSqlObject($lrQuery);
      return $dbAdapter->query($lrQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function getPatientRecord($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchPatientRecord($params);
    }
    
    public function checkDublicateClinicDataReport($params){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $rQuery = $sql->select()->from(array('cl_da_c' => 'clinic_data_collection'))
                                ->columns(array('cl_data_collection_id'));
        if(isset($params['clDataCollectionID']) && trim($params['clDataCollectionID'])!= ''){
            $rQuery = $rQuery->where('cl_da_c.cl_data_collection_id != "'.base64_decode($params['clDataCollectionID']).'"');
        }if(isset($params['reportingMonthYear']) && trim($params['reportingMonthYear'])!= ''){
            $rQuery = $rQuery->where(array('cl_da_c.reporting_month_year'=>strtolower($params['reportingMonthYear'])));
        }if(isset($params['anc']) && trim($params['anc'])!= ''){
            $rQuery = $rQuery->where(array('cl_da_c.anc'=>base64_decode($params['anc'])));
        }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
            $rQuery = $rQuery->where(array('cl_da_c.country'=>base64_decode($params['countryId'])));
        }
        $rQueryStr = $sql->getSqlStringForSqlObject($rQuery);
        $rResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if(isset($rResult->cl_data_collection_id)){
            return "/clinic/data-collection/edit/".base64_encode($rResult->cl_data_collection_id)."/".$params['countryId'];
        }else{
            return "";
        }
    }
    
    public function generateRot47String($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->rot47($params);
    }
    
    public function getCountryLabDataReportingDetails($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchCountryLabDataReportingDetails($params);
    }
    
    public function getCountryClinicDataReportingDetails($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchCountryClinicDataReportingDetails($params);
    }
    
    public function getDataReportingLocations($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchDataReportingLocations($params); 
    }
    
    public function getStudyOverviewData($parameters){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchStudyOverviewData($parameters);
    }
    
    public function exportStudyOverviewInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->overviewQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->overviewQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $manageColumnsDb = $this->sm->get('ManageColumnsTable');
                    $manage_columns = $manageColumnsDb->fetchUserManageColumns();
                    $sor_Columns = array();
                    if(isset($manage_columns) && isset($manage_columns->study_overview) && trim($manage_columns->study_overview)!= ''){
                        $manage_Columns = json_decode($manage_columns->study_overview,true);
                        for($i=0;$i<count($manage_Columns);$i++){
                            if($manage_Columns[$i]['data_Visible'] == '1'){
                                $sor_Columns[] = $manage_Columns[$i]['data_Column'];
                            }
                        }
                    }
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $output = array();
                    foreach ($sResult as $key=>$aRow) {
                        $ancSiteName = '';
                        $ancSiteDistrict = '';
                        $patientBarcodeID = '';
                        $sampleType = '';
                        $specimenCollectedDate = '';
                        $specimenPickedupDateatANC = '';
                        $dob = '';
                        $receiptDateatLab = '';
                        $resultDispatchedDatetoClinic = '';
                        $dateofTestCompletion = '';
                        $dateResultReturnedatClinic = '';
                        $timeResultReturnedatClinic = '';
                        $dateReturnedtoParticipant = '';
                        $timeReturnedtoParticipant = '';
                        $lagResult = '';
                        //$hIVRNAResult = '';
                        $rapidRecencyAssay = '';
                        $rapidRecencyAssayDuration = '';
                        $status = '';
                        if(isset($aRow['anc_site_name']) && $aRow['anc_site_name']!= null && trim($aRow['anc_site_name'])!= ''){
                            $ancSiteName = ucwords($aRow['anc_site_name']);
                        }else if(isset($aRow['r_anc_site_name']) && $aRow['r_anc_site_name']!= null && trim($aRow['r_anc_site_name'])!= ''){
                            $ancSiteName = ucwords($aRow['r_anc_site_name']);
                        }
                        if(isset($aRow['r_anc_site_district']) && $aRow['r_anc_site_district']!= null && trim($aRow['r_anc_site_district'])!= ''){
                           $ancSiteDistrict = ucwords($aRow['r_anc_site_district']);
                        }else if(isset($aRow['anc_site_district']) && $aRow['anc_site_district']!= null && trim($aRow['anc_site_district'])!= ''){
                           $ancSiteDistrict = ucwords($aRow['anc_site_district']);
                        }
                        if(isset($aRow['patient_barcode_id']) && $aRow['patient_barcode_id']!= null && trim($aRow['patient_barcode_id'])!= ''){
                            $patientBarcodeID = $aRow['patient_barcode_id'];
                        }else if(isset($aRow['r_patient_barcode_id']) && $aRow['r_patient_barcode_id']!= null && trim($aRow['r_patient_barcode_id'])!= ''){
                            $patientBarcodeID = $aRow['r_patient_barcode_id'];
                        }
                        //specimen collected date
                        if(isset($aRow['specimen_collected_date']) && $aRow['specimen_collected_date']!= null && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
                            $specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
                        }
                        //sample type
                         if(isset($aRow['specimen_type']) && $aRow['specimen_type']!= null && trim($aRow['specimen_type'])!= '' && (int)$aRow['specimen_type'] == 1){
                            $sampleType = 'Venous';
                         }else if(isset($aRow['specimen_type']) && $aRow['specimen_type']!= null && trim($aRow['specimen_type'])!= '' && (int)$aRow['specimen_type'] == 2){
                            $sampleType = 'Plasma';
                         }else if(isset($aRow['specimen_type']) && $aRow['specimen_type']!= null && trim($aRow['specimen_type'])!= '' && (int)$aRow['specimen_type'] == 3){
                            $sampleType = 'DBS';
                         }
                        //specimen picked up date at ANC
                        if(isset($aRow['specimen_picked_up_date_at_anc']) && $aRow['specimen_picked_up_date_at_anc']!= null && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
                            $specimenPickedupDateatANC = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
                        }
                        //dob
                        if(isset($aRow['patient_dob']) && $aRow['patient_dob']!= null && trim($aRow['patient_dob'])!= '' && $aRow['patient_dob']!= '0000-00-00'){
                            $dob = $common->humanDateFormat($aRow['patient_dob']);
                        }
                        //receipt date at lab
                        if(isset($aRow['receipt_date_at_central_lab']) && $aRow['receipt_date_at_central_lab']!= null && trim($aRow['receipt_date_at_central_lab'])!= '' && $aRow['receipt_date_at_central_lab']!= '0000-00-00'){
                            $receiptDateatLab = $common->humanDateFormat($aRow['receipt_date_at_central_lab']);
                        }
                        //result dispatched date to clinic
                        if(isset($aRow['result_dispatched_date_to_clinic']) && $aRow['result_dispatched_date_to_clinic']!= null && trim($aRow['result_dispatched_date_to_clinic'])!= '' && $aRow['result_dispatched_date_to_clinic']!= '0000-00-00'){
                            $resultDispatchedDatetoClinic = $common->humanDateFormat($aRow['result_dispatched_date_to_clinic']);
                        }
                        //date of test completion
                        if(isset($aRow['date_of_test_completion']) && $aRow['date_of_test_completion']!= null && trim($aRow['date_of_test_completion'])!= '' && $aRow['date_of_test_completion']!= '0000-00-00'){
                            $dateofTestCompletion = $common->humanDateFormat($aRow['date_of_test_completion']);
                        }
                        //date result return at clinic
                        if(isset($aRow['date_result_returned_clinic']) && trim($aRow['date_result_returned_clinic'])!= '' && $aRow['date_result_returned_clinic']!= '0000-00-00 00:00:00'){
                            $dateArray = explode(" ",$aRow['date_result_returned_clinic']);
                            $dateResultReturnedatClinic = $common->humanDateFormat($dateArray[0]);
                            $timeResultReturnedatClinic = $dateArray[1];
                        }
                        //date returned to participant
                        if(isset($aRow['date_returned_to_participant']) && trim($aRow['date_returned_to_participant'])!= '' && $aRow['date_returned_to_participant']!= '0000-00-00 00:00:00'){
                            $dateArray = explode(" ",$aRow['date_returned_to_participant']);
                            $dateReturnedtoParticipant = $common->humanDateFormat($dateArray[0]);
                            $timeReturnedtoParticipant = $dateArray[1];
                        }
                        //status
                        if(isset($aRow['test_status_name']) && $aRow['test_status_name']!= null && trim($aRow['test_status_name'])!= ''){
                           $status = ucfirst($aRow['test_status_name']);
                        }
                        //LAg assay
                        $lagResult = (isset($aRow['lag_avidity_result']) && $aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
                        //HIV rna values
                    //    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
                    //	$hIVRNAResult = 'High Viral Load';
                    //    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
                    //	$hIVRNAResult = 'Low Viral Load';
                    //    }
                        $finalLagRecencyInfection = '';
                        if(isset($aRow['recent_infection']) && $aRow['recent_infection'] != null){
                            if($aRow['recent_infection'] == 'yes'){
                                $finalLagRecencyInfection = 'Recent';
                            }else if($aRow['recent_infection'] == 'no'){
                                $finalLagRecencyInfection = 'Long Term';
                            }else{
                                $finalLagRecencyInfection = 'Incomplete';
                            }
                        }
                        //rapid assay
                        if(isset($aRow['asante_rapid_recency_assy']) && $aRow['asante_rapid_recency_assy']!= null && trim($aRow['asante_rapid_recency_assy'])!= ''){
                            $asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
                            if(isset($asanteRapidRecencyAssy['rrdt'])){
                                $rapidRecencyAssay = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?ucwords($asanteRapidRecencyAssy['rrdt']['assay']):'';
                            }
                            if(isset($asanteRapidRecencyAssy['rrr'])){
                                $rapidRecencyAssayDuration = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
                            }
                        }
                        //ANC rapid recency result
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
                        if(count($sor_Columns) == 0 || in_array('location_name',$sor_Columns)){
                          $row[] = (isset($aRow['location_name']) && $aRow['location_name']!= null && trim($aRow['location_name'])!= '')?ucwords($aRow['location_name']):'';
                        }
                        if(count($sor_Columns) == 0 || in_array('patient_barcode_id',$sor_Columns)){
                           $row[] = $patientBarcodeID;
                        }
                        if(count($sor_Columns) == 0 || in_array('specimen_collected_date',$sor_Columns)){
                           $row[] = $specimenCollectedDate;
                        }
                        if(count($sor_Columns) == 0 || in_array('anc_site_name',$sor_Columns)){
                          $row[] = $ancSiteName;
                        }
                        if(count($sor_Columns) == 0 || in_array('specimen_picked_up_date_at_anc',$sor_Columns)){
                           $row[] = $specimenPickedupDateatANC;
                        }
                        if(count($sor_Columns) == 0 || in_array('specimen_type',$sor_Columns)){
                           $row[] = $sampleType;
                        }
                        if(count($sor_Columns) == 0 || in_array('anc_patient_id',$sor_Columns)){
                           $row[] = (isset($aRow['anc_patient_id']))?$aRow['anc_patient_id']:'';
                        }
                        if(count($sor_Columns) == 0 || in_array('art_patient_id',$sor_Columns)){
                           $row[] = (isset($aRow['art_patient_id']))?$aRow['art_patient_id']:'';
                        }
                        if(count($sor_Columns) == 0 || in_array('patient_dob',$sor_Columns)){
                           $row[] = $dob;
                        }
                        if(count($sor_Columns) == 0 || in_array('age',$sor_Columns)){
                           $row[] = (isset($aRow['age']))?$aRow['age']:'';
                        }
                        if(count($sor_Columns) == 0 || in_array('gestational_age',$sor_Columns)){
                           $row[] = (isset($aRow['gestational_age']))?$aRow['gestational_age']:'';
                        }
                        if(count($sor_Columns) == 0 || in_array('facility_name',$sor_Columns)){
                           $row[] = (isset($aRow['facility_name']))?ucwords($aRow['facility_name']):'';
                        }
                        if(count($sor_Columns) == 0 || in_array('rejection_reason',$sor_Columns)){
                           $row[] = (isset($aRow['rejectionReasonName']) && $aRow['rejection_reason']!= 1)?ucwords($aRow['rejectionReasonName']):'';
                        }
                        if(count($sor_Columns) == 0 || in_array('receipt_date_at_central_lab',$sor_Columns)){
                           $row[] = $receiptDateatLab;
                        }
                        if(count($sor_Columns) == 0 || in_array('lab_tech_name',$sor_Columns)){
                           $row[] = (isset($aRow['lab_tech_name']))?ucwords($aRow['lab_tech_name']):'';
                        }
                        if(count($sor_Columns) == 0 || in_array('date_of_test_completion',$sor_Columns)){
                            $row[] = $dateofTestCompletion;
                        }
                        if(count($sor_Columns) == 0 || in_array('result_dispatched_date_to_clinic',$sor_Columns)){
                           $row[] = $resultDispatchedDatetoClinic;
                        }
                        if(count($sor_Columns) == 0 || in_array('final_lag_avidity_odn',$sor_Columns)){
                           $row[] = (isset($aRow['final_lag_avidity_odn']))?$aRow['final_lag_avidity_odn']:'';
                        }
                        if(count($sor_Columns) == 0 || in_array('lag_avidity_result',$sor_Columns)){
                           $row[] = $lagResult;
                        }
                        if(count($sor_Columns) == 0 || in_array('hiv_rna',$sor_Columns)){
                           $row[] = (isset($aRow['hiv_rna']) && $aRow['hiv_rna']!= null && trim($aRow['hiv_rna'])!= '')?$aRow['hiv_rna']:'';
                        }
                        if(count($sor_Columns) == 0 || in_array('recent_infection',$sor_Columns)){
                           $row[] = $finalLagRecencyInfection;
                        }
                        if(count($sor_Columns) == 0 || in_array('asante_rapid_recency_assy_rrdt',$sor_Columns)){
                            $row[] = $rapidRecencyAssay;
                        }
                        if(count($sor_Columns) == 0 || in_array('asante_rapid_recency_assy_rrr',$sor_Columns)){
                           $row[] = $rapidRecencyAssayDuration;
                        }
                        if(count($sor_Columns) == 0 || in_array('HIV_diagnostic_line',$sor_Columns)){
                           $row[] = $ancHIVVerificationClassification;
                        }
                        if(count($sor_Columns) == 0 || in_array('recency_line',$sor_Columns)){
                           $row[] = $ancRecencyVerificationClassification;
                        }
                        if(count($sor_Columns) == 0 || in_array('test_status_name',$sor_Columns)){
                           $row[] = $status;
                        }
                        if(count($sor_Columns) == 0 || in_array('assessment_id',$sor_Columns)){
                           $row[] = (isset($aRow['r_assessment_id']) && $aRow['r_assessment_id']!= null && trim($aRow['r_assessment_id'])!= '')?'Yes':'No';
                        }
                        if(count($sor_Columns) == 0 || in_array('date_result_returned_clinic',$sor_Columns)){
                           $row[] = $dateResultReturnedatClinic;
                           $row[] = $timeResultReturnedatClinic;
                        }
                        if(count($sor_Columns) == 0 || in_array('date_returned_to_participant',$sor_Columns)){
                           $row[] = $dateReturnedtoParticipant;
                           $row[] = $timeReturnedtoParticipant;
                        }
                        $row[] = $ancSiteDistrict;
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
                    $yellowTxtArray = array(
                        'fill' => array(
                            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'FFFF00')
                        )
                    );
                    $redTxtArray = array(
                        'font' => array(
                            'color' => array('rgb' => 'F44336')
                        )
                    );
                    $blueTxtArray = array(
                        'font' => array(
                            'color' => array('rgb' => '3792a8')
                        )
                    );
                    if(count($sor_Columns) == 0){
                        $sheet->setCellValue('A1', html_entity_decode('Lab Province/State ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('B1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('C1', html_entity_decode('Specimen Collected Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('D1', html_entity_decode('ANC Site ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('E1', html_entity_decode('Specimen Pick Up Date at ANC ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('F1', html_entity_decode('Specimen Type ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('G1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('H1', html_entity_decode('ART Number ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('I1', html_entity_decode('DOB ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('J1', html_entity_decode('Age ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('K1', html_entity_decode('Gestation Age (Weeks) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('L1', html_entity_decode('Lab/Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('M1', html_entity_decode('Rejection Reason ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('N1', html_entity_decode('Receipt Date at Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('O1', html_entity_decode('Lab Tech. Name/ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('P1', html_entity_decode('Date of Test Completion ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('Q1', html_entity_decode('Result Dispatched Date to Clinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('R1', html_entity_decode('LAg Avidity ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('S1', html_entity_decode('Lab LAg Recency (Based on LAg ODn) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('T1', html_entity_decode('HIV RNA (cp/ml) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('U1', html_entity_decode('Lab LAg Recency (Based on algorithm) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('V1', html_entity_decode('Lab Positive Verification Line (Visual) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('W1', html_entity_decode('Lab Long Term Line (Visual) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('X1', html_entity_decode('ANC Positive Verification Line ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('Y1', html_entity_decode('ANC Long Term Line ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('Z1', html_entity_decode('Lab Data Status ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AA1', html_entity_decode('Behaviour Data Recorded ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AB1', html_entity_decode('Date Result Returned at Clinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AC1', html_entity_decode('Time Result Returned at Clinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AD1', html_entity_decode('Date Returned to Participant ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AE1', html_entity_decode('Time Returned to Participant ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue('AF1', html_entity_decode('ANC District ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        
                        $sheet->getStyle('A1')->applyFromArray($styleArray);
                        $sheet->getStyle('B1')->applyFromArray($styleArray);
                        $sheet->getStyle('C1')->applyFromArray($styleArray);
                        $sheet->getStyle('D1')->applyFromArray($styleArray);
                        $sheet->getStyle('E1')->applyFromArray($styleArray);
                        $sheet->getStyle('F1')->applyFromArray($styleArray);
                        $sheet->getStyle('G1')->applyFromArray($styleArray);
                        $sheet->getStyle('H1')->applyFromArray($styleArray);
                        $sheet->getStyle('I1')->applyFromArray($styleArray);
                        $sheet->getStyle('J1')->applyFromArray($styleArray);
                        $sheet->getStyle('K1')->applyFromArray($styleArray);
                        $sheet->getStyle('L1')->applyFromArray($styleArray);
                        $sheet->getStyle('M1')->applyFromArray($styleArray);
                        $sheet->getStyle('N1')->applyFromArray($styleArray);
                        $sheet->getStyle('O1')->applyFromArray($styleArray);
                        $sheet->getStyle('P1')->applyFromArray($styleArray);
                        $sheet->getStyle('Q1')->applyFromArray($styleArray);
                        $sheet->getStyle('R1')->applyFromArray($styleArray);
                        $sheet->getStyle('S1')->applyFromArray($styleArray);
                        $sheet->getStyle('T1')->applyFromArray($styleArray);
                        $sheet->getStyle('U1')->applyFromArray($styleArray);
                        $sheet->getStyle('V1')->applyFromArray($styleArray);
                        $sheet->getStyle('W1')->applyFromArray($styleArray);
                        $sheet->getStyle('X1')->applyFromArray($styleArray);
                        $sheet->getStyle('Y1')->applyFromArray($styleArray);
                        $sheet->getStyle('Z1')->applyFromArray($styleArray);
                        $sheet->getStyle('AA1')->applyFromArray($styleArray);
                        $sheet->getStyle('AB1')->applyFromArray($styleArray);
                        $sheet->getStyle('AC1')->applyFromArray($styleArray);
                        $sheet->getStyle('AD1')->applyFromArray($styleArray);
                        $sheet->getStyle('AE1')->applyFromArray($styleArray);
                        $sheet->getStyle('AF1')->applyFromArray($styleArray);
                    }else{
                        $j=0;
                        for($col=0;$col < count($manage_Columns);$col++){
                            if(isset($manage_Columns[$col]) && isset($manage_Columns[$col]['data_Visible']) && $manage_Columns[$col]['data_Visible'] == '1'){
                                if($manage_Columns[$col]['data_Label'] == 'Date Result Returned at Clinic'){
                                    $sheet->getCellByColumnAndRow($j, 1)->setValueExplicit(html_entity_decode('Date Result Returned at Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                                    $cellName = $sheet->getCellByColumnAndRow($j, 1)->getColumn();
                                    $sheet->getStyle($cellName . '1')->applyFromArray($styleArray);
                                  $j++;
                                    
                                    $sheet->getCellByColumnAndRow($j, 1)->setValueExplicit(html_entity_decode('Time Result Returned at Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                                    $cellName = $sheet->getCellByColumnAndRow($j, 1)->getColumn();
                                    $sheet->getStyle($cellName . '1')->applyFromArray($styleArray);
                                  $j++;
                                }else if($manage_Columns[$col]['data_Label'] == 'Date Returned to Participant'){
                                    $sheet->getCellByColumnAndRow($j, 1)->setValueExplicit(html_entity_decode('Date Returned to Participant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                                    $cellName = $sheet->getCellByColumnAndRow($j, 1)->getColumn();
                                    $sheet->getStyle($cellName . '1')->applyFromArray($styleArray);
                                  $j++;
                                    
                                    $sheet->getCellByColumnAndRow($j, 1)->setValueExplicit(html_entity_decode('Time Returned to Participant', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                                    $cellName = $sheet->getCellByColumnAndRow($j, 1)->getColumn();
                                    $sheet->getStyle($cellName . '1')->applyFromArray($styleArray);
                                   $j++;
                                }else{
                                    $sheet->getCellByColumnAndRow($j, 1)->setValueExplicit(html_entity_decode($manage_Columns[$col]['data_Label'], ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                                    $cellName = $sheet->getCellByColumnAndRow($j, 1)->getColumn();
                                    $sheet->getStyle($cellName . '1')->applyFromArray($styleArray);
                                  $j++;
                                }
                            }
                        }
                        $sheet->getCellByColumnAndRow($j, 1)->setValueExplicit(html_entity_decode('ANC District', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $cellName = $sheet->getCellByColumnAndRow($j, 1)->getColumn();
                        $sheet->getStyle($cellName . '1')->applyFromArray($styleArray);
                    }
                    
                    $rej_Col = array_search('rejection_reason', $sor_Columns);
                    $status_Col = array_search('test_status_name', $sor_Columns);
                    $lag_Col = array_search('recent_infection', $sor_Columns);
                    $labHIVV_Col = array_search('asante_rapid_recency_assy_rrdt', $sor_Columns);
                    $labHIVR_Col = array_search('asante_rapid_recency_assy_rrr', $sor_Columns);
                    $ancHIVV_Col = array_search('HIV_diagnostic_line', $sor_Columns);
                    $ancHIVR_Col = array_search('recency_line', $sor_Columns);
                    $currentRow = 2;
                    foreach ($output as $rowData) {
                        $rejection = '';
                        $status = '';
                        $lag = '';
                        $labHIVV = '';
                        $labHIVR = '';
                        $ancHIVV = '';
                        $ancHIVR = '';
                        $colNo = 0;
                        $lastCol = (count($sor_Columns) == 0)?31:count($sor_Columns)-1;
                        foreach ($rowData as $key=>$value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                        
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            
                            if((count($sor_Columns) == 0 && $colNo == 12) || $key == $rej_Col){ $rejection = $value; }
                            if((count($sor_Columns) == 0 && $colNo == 25) || $key == $status_Col){ $status = $value; }
                            if((count($sor_Columns) == 0 && $colNo == 20) || $key == $lag_Col){ $lag = $value; }
                            if((count($sor_Columns) == 0 && $colNo == 21) || $key == $labHIVV_Col){ $labHIVV = $value; }
                            if((count($sor_Columns) == 0 && $colNo == 22) || $key == $labHIVR_Col){ $labHIVR = $value; }
                            if((count($sor_Columns) == 0 && $colNo == 23) || $key == $ancHIVV_Col){ $ancHIVV = str_replace("-","",$value); }
                            if((count($sor_Columns) == 0 && $colNo == 24) || $key == $ancHIVR_Col){ $ancHIVR = str_replace("-","",$value); }
                            $recencyMismatch = false;
                            if(trim($lag)!= '' && trim($labHIVR)!= '' && trim($ancHIVR)!= ''){
                                if(($lag == 'Recent' && $labHIVR == 'Absent') && ($labHIVR == $ancHIVR)){
                                    $recencyMismatch = false;
                                }else if(($lag == 'Long Term' && $labHIVR == 'Present') && ($labHIVR == $ancHIVR)){
                                   $recencyMismatch = false;
                                }else{
                                    $recencyMismatch = true;
                                }
                            }
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            if($colNo == $lastCol){
                                if(trim($rejection)!= ''){
                                    $sheet->getStyle('A'.$currentRow.':'.$cellName.''.$currentRow)->applyFromArray($blueTxtArray);
                                }else{
                                    if($status == 'Incomplete'){
                                      $sheet->getStyle('A'.$currentRow.':'.$cellName.''.$currentRow)->applyFromArray($yellowTxtArray); 
                                    }
                                    if($labHIVV =='Absent' || ($lag == 'Long Term' && $labHIVR == 'Absent') || ($lag == 'Recent' && $labHIVR == 'Present' || $recencyMismatch === true)){
                                      $sheet->getStyle('A'.$currentRow.':'.$cellName.''.$currentRow)->applyFromArray($redTxtArray);
                                    }
                                }
                            }
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                      $currentRow++;
                    }
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = 'STUDY-OVERVIEW-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "na";
                }
            }catch (Exception $exc) {
                error_log("STUDY-OVERVIEW-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function lockClinicDataCollection($params){
       $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
      return $clinicDataCollectionDb->lockClinicDataCollectionDetails($params);
    }
    
    public function unlockClinicDataCollection($params){
       $clinicDataCollectionDb = $this->sm->get('ClinicDataCollectionTable');
      return $clinicDataCollectionDb->unlockClinicDataCollectionDetails($params);
    }
    
    public function exportDashboardInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->dashboardQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->dashboardQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                $i=0;
                foreach($sResult as $dataCollection){
                     $riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                                                ->columns(array(
                                                                'assessments' => new \Zend\Db\Sql\Expression("COUNT(*)")
                                                             ))
                                                ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('noofANCRecencyTestRecent' => new \Zend\Db\Sql\Expression("SUM(IF(anc_r_r.recency_line = 'recent', 1,0))")),'left')
                                                ->where('r_a.country = '.$dataCollection['country_id'].' AND MONTH(r_a.interview_date) ="'.$dataCollection['month'].'" AND YEAR(r_a.interview_date) ="'.$dataCollection['year'].'"');
                     $riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($riskAssessmentQuery);
                     $sResult[$i][$dataCollection['monthName'].' - '.$dataCollection['year']] = $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                 $i++;
                }
                if(isset($sResult) && count($sResult)>0){
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $output = array();
                    $samplesReceivedArray = array();
                    $samplesIncompleteArray = array();
                    $samplesTestedArray = array();
                    $samplesFinalizedArray = array();
                    $noofLAgRecentwtVlArray = array();
                    $noofLAgRecentArray = array();
                    $noofLabRecencyAssayRecentArray = array();
                    $assessmentsArray = array();
                    $noofANCRecencyTestRecentArray = array();
                    foreach ($sResult as $aRow) {
                        $assessments = 0;
                        $noofANCRecencyTestRecent = 0;
                        if(isset($aRow[$aRow['monthName'].' - '.$aRow['year']])){
                          $assessments = (isset($aRow[$aRow['monthName'].' - '.$aRow['year']]->assessments))?$aRow[$aRow['monthName'].' - '.$aRow['year']]->assessments:0;
                          $noofANCRecencyTestRecent = (isset($aRow[$aRow['monthName'].' - '.$aRow['year']]->noofANCRecencyTestRecent))?$aRow[$aRow['monthName'].' - '.$aRow['year']]->noofANCRecencyTestRecent:0;
                        }
                        $samplesReceivedArray[] = $aRow['totalSample'];
                        $samplesIncompleteArray[] = $aRow['samplesIncomplete'];
                        $samplesTestedArray[] = $aRow['samplesTested'];
                        $samplesFinalizedArray[] = $aRow['samplesFinalized'];
                        $noofLAgRecentwtVlArray[] = $aRow['noofLAgRecentwtVl'];
                        $noofLAgRecentArray[] = $aRow['noofLAgRecent'];
                        $noofLabRecencyAssayRecentArray[] = $aRow['noofLabRecencyAssayRecent'];
                        $assessmentsArray[] = $assessments;
                        $noofANCRecencyTestRecentArray[] = $noofANCRecencyTestRecent;
                        $row = array();
                        $row[] = $aRow['monthName'].' - '.$aRow['year'];
                        $row[] = ucwords($aRow['country_name']);
                        $row[] = $aRow['totalSample'];
                        $row[] = $aRow['samplesIncomplete'];
                        $row[] = $aRow['samplesTested'];
                        $row[] = $aRow['samplesFinalized'];
                        $row[] = $aRow['noofLAgRecentwtVl'];
                        $row[] = $aRow['noofLAgRecent'];
                        $row[] = $aRow['noofLabRecencyAssayRecent'];
                        $row[] = $assessments;
                        $row[] = $noofANCRecencyTestRecent;
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
                    $contentAlignmentArray = array(
                        'font' => array(
                            'size' => 12,
                        
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
                    $totalArray = array(
                        'font' => array(
                            'size' => 15,
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
                    
                    $sheet->setCellValue('A1', html_entity_decode('Month - Year ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Name of the Country ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Samples Received ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Samples Incomplete ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Samples Tested ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Samples Locked for Editing ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G1', html_entity_decode('LAg Recent with no Viral Load entry ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H1', html_entity_decode('Lab LAg Recent (based on algorithm) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I1', html_entity_decode('Lab Rapid Recency Assay Recent (Visual) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J1', html_entity_decode('Risk Questionnaires ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K1', html_entity_decode('ANC Rapid Recency Assay Recent (Visual)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                   
                    $sheet->getStyle('A1')->applyFromArray($styleArray);
                    $sheet->getStyle('B1')->applyFromArray($styleArray);
                    $sheet->getStyle('C1')->applyFromArray($styleArray);
                    $sheet->getStyle('D1')->applyFromArray($styleArray);
                    $sheet->getStyle('E1')->applyFromArray($styleArray);
                    $sheet->getStyle('F1')->applyFromArray($styleArray);
                    $sheet->getStyle('G1')->applyFromArray($styleArray);
                    $sheet->getStyle('H1')->applyFromArray($styleArray);
                    $sheet->getStyle('I1')->applyFromArray($styleArray);
                    $sheet->getStyle('J1')->applyFromArray($styleArray);
                    $sheet->getStyle('K1')->applyFromArray($styleArray);
                    
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
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray(($colNo <= 1)?$borderStyle:$contentAlignmentArray);
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                      $currentRow++;
                    }
                    //total row
                    $sheet->mergeCells('A'.$currentRow.':B'.$currentRow);
                    
                    $sheet->getStyle('A'.$currentRow.':B'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('C'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('D'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('E'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('F'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('G'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('H'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('I'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('J'.$currentRow)->applyFromArray($totalArray);
                    $sheet->getStyle('K'.$currentRow)->applyFromArray($totalArray);
                    
                    $sheet->setCellValue('A'.$currentRow, html_entity_decode('Total' , ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C'.$currentRow, html_entity_decode(array_sum($samplesReceivedArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D'.$currentRow, html_entity_decode(array_sum($samplesIncompleteArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E'.$currentRow, html_entity_decode(array_sum($samplesTestedArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F'.$currentRow, html_entity_decode(array_sum($samplesFinalizedArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G'.$currentRow, html_entity_decode(array_sum($noofLAgRecentwtVlArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H'.$currentRow, html_entity_decode(array_sum($noofLAgRecentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I'.$currentRow, html_entity_decode(array_sum($noofLabRecencyAssayRecentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J'.$currentRow, html_entity_decode(array_sum($assessmentsArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K'.$currentRow, html_entity_decode(array_sum($noofANCRecencyTestRecentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = 'DASHBOARD-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "na";
                }
            }catch (Exception $exc) {
                error_log("DASHBOARD-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function exportCountryDashboardInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->countryLabDataReportingQuery) && isset($queryContainer->countryClinicDataReportingQuery)){
            try{
                $excel = new PHPExcel();
                $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                $cacheSettings = array('memoryCacheSize' => '80MB');
                \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
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
                $contentAlignmentArray = array(
                    'font' => array(
                        'size' => 12,
                    
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
                $totalArray = array(
                    'font' => array(
                        'size' => 15,
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
                
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                //Lab data reporting
                $dataCollectionQueryStr = $sql->getSqlStringForSqlObject($queryContainer->countryLabDataReportingQuery);
                $dataCollectionResult = $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                $output = array();
                $samplesReceivedArray = array();
                $samplesIncompleteArray = array();
                $samplesTestedArray = array();
                $samplesFinalizedArray = array();
                $noofLAgRecentwtVlArray = array();
                $noofLAgRecentArray = array();
                $noofLabRecencyAssayRecentArray = array();
                foreach($dataCollectionResult as $aRow) {
                    $samplesReceivedArray[] = $aRow['totalSample'];
                    $samplesIncompleteArray[] = $aRow['samplesIncomplete'];
                    $samplesTestedArray[] = $aRow['samplesTested'];
                    $samplesFinalizedArray[] = $aRow['samplesFinalized'];
                    $noofLAgRecentwtVlArray[] = $aRow['noofLAgRecentwtVl'];
                    $noofLAgRecentArray[] = $aRow['noofLAgRecent'];
                    $noofLabRecencyAssayRecentArray[] = $aRow['noofLabRecencyAssayRecent'];
                    $row = array();
                    $row[] = ucwords($aRow['facility_name']);
                    $row[] = $aRow['monthName'].' - '.$aRow['year'];
                    $row[] = $aRow['noofANCSites'];
                    $row[] = $aRow['totalSample'];
                    $row[] = $aRow['samplesIncomplete'];
                    $row[] = $aRow['samplesTested'];
                    $row[] = $aRow['samplesFinalized'];
                    $row[] = $aRow['noofLAgRecentwtVl'];
                    $row[] = $aRow['noofLAgRecent'];
                    $row[] = $aRow['noofLabRecencyAssayRecent'];
                    $output[] = $row;
                }
                
                $sheet = new PHPExcel_Worksheet($excel, '');
                $sheet->getSheetView()->setZoomScale(80);
                $excel->addSheet($sheet, 0);
                $sheet->setTitle('Lab Data Reporting');
                $sheet->setCellValue('A1', html_entity_decode('Lab Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('B1', html_entity_decode('Month - Year ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('C1', html_entity_decode('No. of ANC Sites', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D1', html_entity_decode('Samples Received ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E1', html_entity_decode('Samples Incomplete ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('F1', html_entity_decode('Samples Tested ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('G1', html_entity_decode('Samples Locked for Editing ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('H1', html_entity_decode('LAg Recent with no Viral Load entry ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('I1', html_entity_decode('Lab LAg Recent (based on algorithm) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('J1', html_entity_decode('Lab Rapid Recency Assay Recent (Visual) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
               
                $sheet->getStyle('A1')->applyFromArray($styleArray);
                $sheet->getStyle('B1')->applyFromArray($styleArray);
                $sheet->getStyle('C1')->applyFromArray($styleArray);
                $sheet->getStyle('D1')->applyFromArray($styleArray);
                $sheet->getStyle('E1')->applyFromArray($styleArray);
                $sheet->getStyle('F1')->applyFromArray($styleArray);
                $sheet->getStyle('G1')->applyFromArray($styleArray);
                $sheet->getStyle('H1')->applyFromArray($styleArray);
                $sheet->getStyle('I1')->applyFromArray($styleArray);
                $sheet->getStyle('J1')->applyFromArray($styleArray);
                
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
                        $sheet->getStyle($cellName . $currentRow)->applyFromArray(($colNo <= 1)?$borderStyle:$contentAlignmentArray);
                        $sheet->getDefaultRowDimension()->setRowHeight(20);
                        $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                        $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                        $colNo++;
                    }
                  $currentRow++;
                }
                //total row
                $sheet->mergeCells('A'.$currentRow.':C'.$currentRow);
                
                $sheet->getStyle('A'.$currentRow.':C'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('D'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('E'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('F'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('G'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('H'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('I'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('J'.$currentRow)->applyFromArray($totalArray);
                
                $sheet->setCellValue('A'.$currentRow, html_entity_decode('Total' , ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D'.$currentRow, html_entity_decode(array_sum($samplesReceivedArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E'.$currentRow, html_entity_decode(array_sum($samplesIncompleteArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('F'.$currentRow, html_entity_decode(array_sum($samplesTestedArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('G'.$currentRow, html_entity_decode(array_sum($samplesFinalizedArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('H'.$currentRow, html_entity_decode(array_sum($noofLAgRecentwtVlArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('I'.$currentRow, html_entity_decode(array_sum($noofLAgRecentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('J'.$currentRow, html_entity_decode(array_sum($noofLabRecencyAssayRecentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                //ANC data reporting
                $riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($queryContainer->countryClinicDataReportingQuery);
                $riskAssessmentResult = $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                $output = array();
                $assessmentArray = array();
                $noofANCRecencyAssayRecentArray = array();
                foreach($riskAssessmentResult as $aRow) {
                    $assessmentArray[] = $aRow['assessments'];
                    $noofANCRecencyAssayRecentArray[] = $aRow['noofANCRecencyTestRecent'];
                    $row = array();
                    $row[] = (isset($aRow['location_name']))?ucwords($aRow['location_name']):'';
                    $row[] = $aRow['monthName'].' - '.$aRow['year'];
                    $row[] = $aRow['noofANCSites'];
                    $row[] = $aRow['assessments'];
                    $row[] = $aRow['noofANCRecencyTestRecent'];
                    $output[] = $row;
                }
                $sheet = new PHPExcel_Worksheet($excel, '');
                $sheet->getSheetView()->setZoomScale(80);
                $excel->addSheet($sheet,1);
                $sheet->setTitle('ANC Data Reporting');
                
                $sheet->setCellValue('A1', html_entity_decode('Province Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('B1', html_entity_decode('Month - Year ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('C1', html_entity_decode('No. of ANC Sites', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D1', html_entity_decode('Risk Questionnaires ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E1', html_entity_decode('ANC Rapid Recency Assay Recent (Visual) ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                
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
                        
                        if (is_numeric($value)) {
                            $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        }else{
                            $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                        $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                        $sheet->getStyle($cellName . $currentRow)->applyFromArray(($colNo <= 1)?$borderStyle:$contentAlignmentArray);
                        $sheet->getDefaultRowDimension()->setRowHeight(20);
                        $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                        $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                        $colNo++;
                    }
                  $currentRow++;
                }
                //total row
                $sheet->mergeCells('A'.$currentRow.':C'.$currentRow);
                
                $sheet->getStyle('A'.$currentRow.':C'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('D'.$currentRow)->applyFromArray($totalArray);
                $sheet->getStyle('E'.$currentRow)->applyFromArray($totalArray);
                
                $sheet->setCellValue('A'.$currentRow, html_entity_decode('Total' , ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D'.$currentRow, html_entity_decode(array_sum($assessmentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E'.$currentRow, html_entity_decode(array_sum($noofANCRecencyAssayRecentArray), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $excel->setActiveSheetIndex(0);
                $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                $filename = strtoupper($params['countryname']).'-DASHBOARD-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                return $filename;
            }catch (Exception $exc) {
                error_log(strtoupper($params['countryname'])."-DASHBOARD-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getSummaryDetails(){
       $dataCollectionDb = $this->sm->get('DataCollectionTable');
      return $dataCollectionDb->fecthSummaryDetails(); 
    }
    
    public function getWeeklyDataReportingDetails($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
      return $dataCollectionDb->fetchWeeklyDataReportingDetails($params);
    }
    
    public function importUSSDData(){
        $ussdFilesPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . "ussd";
        $ussdSyncedFilesPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . "ussd-synced";
        $ussdFiles = scandir($ussdFilesPath, SCANDIR_SORT_DESCENDING);
        if(sizeof($ussdFiles) > 0){
            $common = new CommonService();
            $ussdSurveryDb = $this->sm->get('USSDSurveyTable');
            $ussdImportStatusDb = $this->sm->get('USSDImportStatusTable');
            $ussdNotEnrolledDb = $this->sm->get('USSDNotEnrolledTable');
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            try {
                for($file=0;$file < count($ussdFiles);$file++){
                    if($ussdFiles[$file]!= '..' && $ussdFiles[$file]!= '.' && file_exists($ussdFilesPath . DIRECTORY_SEPARATOR . $ussdFiles[$file])){
                        $objPHPExcel = \PHPExcel_IOFactory::load($ussdFilesPath . DIRECTORY_SEPARATOR . $ussdFiles[$file]);
                        foreach($objPHPExcel->getWorksheetIterator() as $worksheet) {
                            $highestRow = $worksheet->getHighestRow(); // get last row
                            $highestColumn = $worksheet->getHighestColumn(); // get highest column
                            $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
                            $nrColumns = ord($highestColumn) - 64;
                            $totalImportedCount = $highestRow - 1;
                            $notEnrolledCount = 0;
                            $notMatchingCount = 0;
                            $notMatchingIDsArray = array();
                            $insertedCount = 0;
                            $insertedIDsArray = array();
                            $updatedCount = 0;
                            $updatedIDsArray = array();
                            $importedOn = $lastUpdatedOn = $common->getDateTime();
                            for($row = 2; $row <= $highestRow; $row++){
                                $id = NULL;
                                $facility = NULL;
                                $lastEvent = NULL;
                                $patientBarcodeID = NULL;
                                $enrolledOn = NULL;
                                $dateResultReturnedClinic = NULL;
                                $dateReturnedtoParticipant = NULL;
                                $notEnrolled = false;
                                $notEnrolledOther = false;
                                $reasonForNotEnrolling = NULL;
                                $reasonForNotEnrollingOther = NULL;
                                $reasonForClientRefused = NULL;
                                $reasonForClientRefusedOther = NULL;
                                $dateVal = NULL;
                                $timeVal = NULL;
                                for($col = 0; $col < $highestColumnIndex; $col++){
                                    //get header and current col. values
                                    $headerCell = $worksheet->getCellByColumnAndRow($col, 1);
                                    $headerVal = strtolower(trim($headerCell->getValue()));
                                    $currentCell = $worksheet->getCellByColumnAndRow($col, $row);
                                    $currentVal = trim($currentCell->getValue());
                                    //set db data values
                                    if($headerVal == 'id' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $id = $currentVal;
                                    }
                                    if($headerVal == 'facility' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $facility = $currentVal;
                                    }
                                    if($headerVal == 'event_type' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                       $lastEvent = $currentVal;
                                    }
                                    if($headerVal == 'reason_not_enrolled' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $reasonForNotEnrolling = $currentVal;
                                        $notEnrolled = true;
                                    }
                                    if($headerVal == 'reason_not_enrolled_other' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $reasonForNotEnrollingOther = $currentVal;
                                        $notEnrolledOther = true;
                                    }
                                    if($headerVal == 'reason_client_refused' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $reasonForClientRefused = $currentVal;
                                    }
                                    if($headerVal == 'reason_client_refused_other' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $reasonForClientRefusedOther = $currentVal;
                                    }
                                    if($headerVal == 'date' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= '' && $currentVal!= '00-00-00'){
                                        $dateArray = explode('-',str_replace('/','-',$currentVal));
                                        $dateVal = (sizeof($dateArray) == 3)?date('Y-m-d',strtotime($dateArray[2].'-'.$dateArray[1].'-'.$dateArray[0])):NULL;
                                    }
                                    if($headerVal == 'time' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                       $timeVal = $currentVal;
                                    }
                                    if($headerVal == 'id_enrolled' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $patientBarcodeID = $currentVal;
                                        $enrolledOn = ($dateVal!= NULL)?$dateVal.' '.$timeVal:$enrolledOn;
                                    }else if($headerVal == 'id_result_returned_clinic' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $patientBarcodeID = $currentVal;
                                        $dateResultReturnedClinic = ($dateVal!= NULL)?$dateVal.' '.$timeVal:$dateResultReturnedClinic;
                                    }else if($headerVal == 'id_result_returned_ppt' && $currentVal!= NULL && $currentVal!= 'NULL' && $currentVal!= 'Null' && $currentVal!= 'null' && $currentVal!= ''){
                                        $patientBarcodeID = $currentVal;
                                        $dateReturnedtoParticipant = ($dateVal!= NULL)?$dateVal.' '.$timeVal:$dateReturnedtoParticipant;
                                    }
                                }
                                //DB operation
                               if($id!= NULL){
                                    $rowQuery = $sql->select()->from(array('ussd_s' => 'ussd_survey'))
                                                    ->columns(array('id'))
                                                    ->where(array('ussd_s.id'=>$id));
                                    $rowQueryStr = $sql->getSqlStringForSqlObject($rowQuery);
                                    $rowResult = $dbAdapter->query($rowQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                                    if(!isset($rowResult) || count($rowResult) == 0){
                                        if($patientBarcodeID!= NULL){
                                            $validateQuery = $sql->select()->from(array('ussd_s' => 'ussd_survey'))
                                                                 ->columns(array('patient_barcode_id'))
                                                                 ->join(array('da_c'=>'data_collection'),'da_c.patient_barcode_id=ussd_s.patient_barcode_id',array('data_collection_id'),'left')
                                                                 ->where(array('ussd_s.patient_barcode_id'=>$patientBarcodeID));
                                            $validateQueryStr = $sql->getSqlStringForSqlObject($validateQuery);
                                            $validateResult = $dbAdapter->query($validateQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                                            if(isset($validateResult->patient_barcode_id)){
                                                $data = array(
                                                            'last_update_on'=>$lastUpdatedOn
                                                        );
                                                if($facility!= NULL){
                                                   $data['facility_code'] = $facility;
                                                }
                                                if($lastEvent!= NULL){
                                                   $data['last_event'] = $lastEvent;
                                                }
                                                if($enrolledOn!= NULL){
                                                   $data['enrolled_on'] = $enrolledOn; 
                                                }
                                                if($dateResultReturnedClinic!= NULL){
                                                   $data['date_result_returned_clinic'] = $dateResultReturnedClinic; 
                                                }
                                                if($dateReturnedtoParticipant!= NULL){
                                                   $data['date_returned_to_participant'] = $dateReturnedtoParticipant; 
                                                }
                                                if($reasonForNotEnrolling!= NULL){
                                                   $data['reason_for_not_enrolling'] = $reasonForNotEnrolling; 
                                                }
                                                if($reasonForNotEnrollingOther!= NULL){
                                                   $data['reason_for_not_enrolling_other'] = $reasonForNotEnrollingOther; 
                                                }
                                                if($reasonForClientRefused!= NULL){
                                                   $data['reason_for_client_refusal'] = $reasonForClientRefused; 
                                                }
                                                if($reasonForClientRefusedOther!= NULL){
                                                   $data['reason_for_client_refusal_other'] = $reasonForClientRefusedOther; 
                                                }
                                                $ussdSurveryDb->update($data,array('patient_barcode_id'=>$validateResult->patient_barcode_id));
                                                $updatedIDsArray[] = $patientBarcodeID;
                                                $updatedCount+= 1;
                                            }else{
                                                $data = array(
                                                              'id'=>$id,
                                                              'patient_barcode_id'=>$patientBarcodeID,
                                                              'facility_code'=>$facility,
                                                              'last_event'=>$lastEvent,
                                                              'last_update_on'=>$lastUpdatedOn,
                                                              'enrolled_on'=>$enrolledOn,
                                                              'date_result_returned_clinic'=>$dateResultReturnedClinic,
                                                              'date_returned_to_participant'=>$dateReturnedtoParticipant,
                                                              'reason_for_not_enrolling'=>$reasonForNotEnrolling,
                                                              'reason_for_not_enrolling_other'=>$reasonForNotEnrollingOther,
                                                              'reason_for_client_refusal'=>$reasonForClientRefused,
                                                              'reason_for_client_refusal_other'=>$reasonForClientRefusedOther
                                                            );
                                                $ussdSurveryDb->insert($data);
                                                $insertedIDsArray[] = $patientBarcodeID;
                                                $insertedCount+= 1;
                                                //For import status count
                                                $notMatchingIDsArray[] = $patientBarcodeID;
                                                $notMatchingCount+= 1;
                                            }
                                        }else{
                                            $rowQuery = $sql->select()->from(array('ussd_n_e' => 'ussd_not_enrolled'))
                                                            ->columns(array('id'))
                                                            ->where(array('ussd_n_e.id'=>$id));
                                            $rowQueryStr = $sql->getSqlStringForSqlObject($rowQuery);
                                            $rowResult = $dbAdapter->query($rowQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                                            if(!isset($rowResult) || count($rowResult) == 0){
                                                $notEnrolledCount = ($notEnrolled || $notEnrolledOther)?$notEnrolledCount+=1:$notEnrolledCount;
                                                $date = ($dateVal!= NULL)?$dateVal.' '.$timeVal:NULL;
                                                $data = array(
                                                            'id'=>$id,
                                                            'date'=>$date,
                                                            'facility'=>$facility,
                                                            'reason_not_enrolled'=>$reasonForNotEnrolling,
                                                            'reason_not_enrolled_other'=>$reasonForNotEnrollingOther,
                                                            'reason_client_refused'=>$reasonForClientRefused,
                                                            'reason_client_refused_other'=>$reasonForClientRefusedOther
                                                          );
                                                $ussdNotEnrolledDb->insert($data);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        //print_r($updatedIDsArray);die;
                        //Add USSD import status row
                        $statusData = array(
                                        'file_name'=>$ussdFiles[$file],
                                        'total_imported_count'=>$totalImportedCount,
                                        'not_enrolled_count'=>$notEnrolledCount,
                                        'not_matching_count'=>$notMatchingCount,
                                        'not_matching_ids'=>(sizeof($notMatchingIDsArray) > 0)?implode(',',$notMatchingIDsArray):NULL,
                                        'inserted_count'=>$insertedCount,
                                        'inserted_ids'=>(sizeof($insertedIDsArray) > 0)?implode(',',$insertedIDsArray):NULL,
                                        'updated_count'=>$updatedCount,
                                        'updated_ids'=>(sizeof($updatedIDsArray) > 0)?implode(',',$updatedIDsArray):NULL,
                                        'imported_on'=>$importedOn
                                    );
                        $ussdImportStatusDb->insert($statusData);
                        //File operation
                        if(!file_exists($ussdSyncedFilesPath) && !is_dir($ussdSyncedFilesPath)) {
                            mkdir($ussdSyncedFilesPath);
                        }
                        if(file_exists($ussdFilesPath . DIRECTORY_SEPARATOR . $ussdFiles[$file]) && copy($ussdFilesPath . DIRECTORY_SEPARATOR . $ussdFiles[$file], $ussdSyncedFilesPath. DIRECTORY_SEPARATOR.$ussdFiles[$file])) {
                            unlink($ussdFilesPath . DIRECTORY_SEPARATOR . $ussdFiles[$file]);
                        }
                    }
                }
            }catch (Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
            }
        }
    }
    
    public function getNotEnrolledData($parameters){
        $ussdNotEnrolledDb = $this->sm->get('USSDNotEnrolledTable');
        return $ussdNotEnrolledDb->fetchNotEnrolledData($parameters);
    }
    
    public function exportNotEnrolledInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->notEnrolledQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->notEnrolledQuery);
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
                        $row = array();
                        $row[] = $aRow['facility'].' - '.ucwords($aRow['anc_site_name']);
                        if($params['reasonType'] == '' || $params['reasonType'] == 1){
                           $row[] = $aRow['reasonNotEnrolled'];
                        }
                        if($params['reasonType'] == '' || $params['reasonType'] == 2){
                           $row[] = $aRow['reasonNotEnrolledOther'];
                        }
                        $row[] = $aRow['reasonRefused'];
                        $row[] = $aRow['reasonRefusedOther'];
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
                    //title section
                    $sheet->setCellValue('A1', html_entity_decode('Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    if($params['reasonType'] == '' || $params['reasonType'] == 1){
                       $sheet->setCellValue('B1', html_entity_decode('Participant Refused ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    if($params['reasonType'] == '' || $params['reasonType'] == 2){
                       $sheet->setCellValue(($params['reasonType'] == 2)?'B1':'C1', html_entity_decode('Other Reason ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $sheet->setCellValue(($params['reasonType'] == '')?'D1':'C1', html_entity_decode('Reason Client Refused ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue(($params['reasonType'] == '')?'E1':'D1', html_entity_decode('Other Reason ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                   
                    //style section
                    $sheet->getStyle('A1')->applyFromArray($styleArray);
                    if($params['reasonType'] == '' || $params['reasonType'] == 1){
                       $sheet->getStyle('B1')->applyFromArray($styleArray);
                    }
                    if($params['reasonType'] == '' || $params['reasonType'] == 2){
                       $sheet->getStyle(($params['reasonType'] == 2)?'B1':'C1')->applyFromArray($styleArray);
                    }
                    $sheet->getStyle(($params['reasonType'] == '')?'D1':'C1')->applyFromArray($styleArray);
                    $sheet->getStyle(($params['reasonType'] == '')?'E1':'D1')->applyFromArray($styleArray);
                    
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
                    $filename = 'NOT-ENROLLED-REPORT(USSD)--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "na";
                }
            }catch (Exception $exc) {
                error_log("NOT-ENROLLED-REPORT(USSD)--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getNotEnrolledPieChartData($params){
        $ussdNotEnrolledDb = $this->sm->get('USSDNotEnrolledTable');
        return $ussdNotEnrolledDb->fetchNotEnrolledPieChartData($params);
    }
    
    public function getOdkSupervisoryAuditDetails($parameters){
        $locationDetailsDb = $this->sm->get('LocationDetailsTable');
        return $locationDetailsDb->fetchOdkSupervisoryAuditDetails($parameters);
    }
    
    public function exportOdkSupervisoryAuditInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        if(isset($queryContainer->odkSupervisoryAuditQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->odkSupervisoryAuditQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $sheet->getSheetView()->setZoomScale(80);
                    $start_date = '';
                    $end_date = '';
                    if(isset($params['dateRange']) && trim($params['dateRange'])!= ''){
                        $date = explode("to", $params['dateRange']);
                        if(isset($date[0]) && trim($date[0]) != "") {
                           $start_date = $common->dateRangeFormat(trim($date[0]));
                        }if(isset($date[1]) && trim($date[1]) != "") {
                           $end_date = $common->dateRangeFormat(trim($date[1]));
                        }
                    }
                    $tbl = "supervisor_checklist_".$params['province'];
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $noofVisittoClinic = 0;
                        if($aRow['code_known_group:code'] != NULL && trim($aRow['code_known_group:code'])!= ''){
                            $countQuery = $sql->select()->from(array('s_c_'.$params['province']=>$tbl))
                                              ->columns(array("totalVisit" => new Expression('COUNT(*)')))
                                              ->where('`code_known_group:code` = "'.$aRow['code_known_group:code'].'"');
                            if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
                                $countQuery = $countQuery->where(array("DATE(date) >='" . $start_date ."'", "DATE(date) <='" . $end_date."'"));
                            }else if (trim($start_date) != "") {
                                $countQuery = $countQuery->where(array("DATE(date) = '" . $start_date. "'"));
                            }
                            $countQueryStr = $sql->getSqlStringForSqlObject($countQuery);
                            $countResult = $dbAdapter->query($countQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                            $noofVisittoClinic = (isset($countResult->totalVisit))?$countResult->totalVisit:0;
                        }
                        $supportVisitDate = '';
                        if($aRow['date']!= NULL && $aRow['date']!= '' && $aRow['date']!='0000-00-00 00:00:00.0' && $aRow['date']!='0000-00-00 00:00:00' && $aRow['date']!='1970-01-01 00:00:00.0' && $aRow['date']!='1970-01-01 00:00:00'){
                            $dateArray = explode(" ",$aRow['date']);
                            $supportVisitDate = $common->humanDateFormat($dateArray[0])." ".$dateArray[1];
                        }
                        $noofEligibleWomennotInvitedtoParticipateinReportingPeriod = (int)$aRow['eligibility_1'] - (int)$aRow['participants_2'] - (int)$aRow['eligibility_2'];
                        $refusal_1 = 0;
                        $refusal_2 = 0;
                        $refusal_3 = 0;
                        $refusal_4 = 0;
                        $refusal_5 = 0;
                        $refusal_6 = 0;
                        if((int)$aRow['refuse_1'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_1'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_1'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_1'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_1'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_1'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_2_1'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_2_1'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_2_1'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_2_1'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_2_1'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_2_1'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_2_2'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_2_2'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_2_2'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_2_2'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_2_2'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_2_2'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_3_1'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_3_1'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_3_1'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_3_1'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_3_1'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_3_1'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_3_2'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_3_2'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_3_2'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_3_2'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_3_2'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_3_2'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_3_3'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_3_3'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_3_3'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_3_3'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_3_3'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_3_3'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_4_1'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_4_1'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_4_1'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_4_1'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_4_1'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_4_1'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_4_2'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_4_2'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_4_2'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_4_2'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_4_2'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_4_2'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_4_3'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_4_3'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_4_3'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_4_3'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_4_3'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_4_3'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_4_3'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_4_3'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_4_3'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_4_3'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_4_3'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_4_3'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_4_4'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_4_4'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_4_4'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_4_4'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_4_4'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_4_4'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_5_1'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_5_1'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_5_1'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_5_1'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_5_1'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_5_1'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_5_2'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_5_2'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_5_2'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_5_2'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_5_2'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_5_2'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_5_3'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_5_3'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_5_3'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_5_3'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_5_3'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_5_3'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_5_4'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_5_4'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_5_4'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_5_4'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_5_4'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_5_4'] === 6){
                            $refusal_6+=1;
                        }
                        
                        if((int)$aRow['refuse_5_5'] === 1){
                            $refusal_1+=1;
                        }else if((int)$aRow['refuse_5_5'] === 2){
                            $refusal_2+=1;
                        }else if((int)$aRow['refuse_5_5'] === 3){
                            $refusal_3+=1;
                        }else if((int)$aRow['refuse_5_5'] === 4){
                            $refusal_4+=1;
                        }else if((int)$aRow['refuse_5_5'] === 5){
                            $refusal_5+=1;
                        }else if((int)$aRow['refuse_5_5'] === 6){
                            $refusal_6+=1;
                        }
                        $row = array();
                        $row[] = $aRow['code_known_group:code'];
                        $row[] = (isset($aRow['anc_site_name']))?ucwords($aRow['anc_site_name']):ucwords($aRow['facility_name']);
                        $row[] = $noofVisittoClinic;
                        $row[] = $aRow['rep_period_1'];
                        $row[] = $supportVisitDate;
                        $row[] = $aRow['eligibility_2'];
                        $row[] = $refusal_1;
                        $row[] = $refusal_2;
                        $row[] = $refusal_3;
                        $row[] = $refusal_4;
                        $row[] = $refusal_5;
                        $row[] = $refusal_6;
                        $row[] = $noofEligibleWomennotInvitedtoParticipateinReportingPeriod;
                        $row[] = $aRow['study_activity:not_eligible_to_calculate'];
                        $row[] = $aRow['study_activity:dc_review_1'];
                        $row[] = $aRow['study_activity:dc_review_2'];
                        $row[] = $aRow['study_activity:dc_review_3'];
                        $row[] = $aRow['study_activity:dc_review_4'];
                        $row[] = $aRow['study_activity:dc_review_5'];
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
                    $sheet->mergeCells('M1:M2');
                    $sheet->mergeCells('N1:N2');
                    $sheet->mergeCells('O1:O2');
                    $sheet->mergeCells('P1:P2');
                    $sheet->mergeCells('Q1:Q2');
                    $sheet->mergeCells('R1:R2');
                    $sheet->mergeCells('S1:S2');
                    
                    $sheet->setCellValue('A1', html_entity_decode('Clinic ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Clinic name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Number of visits to clinic ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Reporting Period ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Support Visit Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Number of women who declined to participate in this reporting period ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G1', html_entity_decode('Reasons for refusal in this reporting period ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M1', html_entity_decode('Number of eligible women who were not invited to participate in this reporting period ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N1', html_entity_decode('Number of ineligible women who were enrolled in this reporting period ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O1', html_entity_decode('DC Assessment - Screening ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P1', html_entity_decode('DC Assessment - Informed Consent ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q1', html_entity_decode('DC Assessment - Blood Collection ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R1', html_entity_decode('DC Assessment - Packaging and Storage ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S1', html_entity_decode('DC Assessment - Return of Results ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->setCellValue('G2', html_entity_decode('Do not have time to participate in study ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H2', html_entity_decode('Not interested in this study ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I2', html_entity_decode('Fear of needles or blood draw ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J2', html_entity_decode('Religious objection to blood draw ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K2', html_entity_decode('Need partner permission to participate ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L2', html_entity_decode('Other ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                   
                    $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B2')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C2')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D2')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E2')->applyFromArray($styleArray);
                    $sheet->getStyle('F1:F2')->applyFromArray($styleArray);
                    $sheet->getStyle('G1:L1')->applyFromArray($styleArray);
                    $sheet->getStyle('M1:M2')->applyFromArray($styleArray);
                    $sheet->getStyle('N1:N2')->applyFromArray($styleArray);
                    $sheet->getStyle('O1:O2')->applyFromArray($styleArray);
                    $sheet->getStyle('P1:P2')->applyFromArray($styleArray);
                    $sheet->getStyle('Q1:Q2')->applyFromArray($styleArray);
                    $sheet->getStyle('R1:R2')->applyFromArray($styleArray);
                    $sheet->getStyle('S1:S2')->applyFromArray($styleArray);
                    
                    $sheet->getStyle('G2')->applyFromArray($styleArray)
                                          ->getAlignment()->setWrapText(true);
                    $sheet->getStyle('H2')->applyFromArray($styleArray)
                                          ->getAlignment()->setWrapText(true);
                    $sheet->getStyle('I2')->applyFromArray($styleArray)
                                          ->getAlignment()->setWrapText(true);
                    $sheet->getStyle('J2')->applyFromArray($styleArray)
                                          ->getAlignment()->setWrapText(true);
                    $sheet->getStyle('K2')->applyFromArray($styleArray)
                                          ->getAlignment()->setWrapText(true);
                    $sheet->getStyle('L2')->applyFromArray($styleArray)
                                          ->getAlignment()->setWrapText(true);
                    
                    $sheet->getStyle('F1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('M1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('N1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('O1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('P1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('Q1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('R1')->getAlignment()->setWrapText(true);
                    $sheet->getStyle('S1')->getAlignment()->setWrapText(true);
                   
                    
                    $currentRow = 3;
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
                    $filename = 'ODK-SUPERVISORY-AUDIT-REPROT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "na";
                }
            }catch (Exception $exc) {
                error_log("ODK-SUPERVISORY-AUDIT-REPROT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getReasonforRefusedPieChartData($params){
        $ussdNotEnrolledDb = $this->sm->get('USSDNotEnrolledTable');
       return $ussdNotEnrolledDb->fetchReasonforRefusedPieChartData($params);
    }
}