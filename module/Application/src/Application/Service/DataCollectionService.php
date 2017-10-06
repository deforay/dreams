<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use PHPExcel;


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
               $alertContainer->msg = 'Lab Data Reporting added successfully.';
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
               $alertContainer->msg = 'Lab Data Reporting updated successfully.';
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
        $lockHour = '+72 hours';//default lab data lock-hour
        //set lab data's lock-hour
        if(isset($global['locking_data_after_login']) && (int)$global['locking_data_after_login'] > 0){
            $lockHour = '+'.(int)$global['locking_data_after_login'].' hours';
        }
        //To lock completed lab datas
        $dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                                   ->columns(array('data_collection_id','added_on'))
                                   ->where(array('da_c.added_by'=>$loginContainer->userId,'da_c.status'=> 1));
        $dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
        $dataCollectionResult = $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(count($dataCollectionResult)>0){
            $now = date("Y-m-d H:i:s");
            foreach($dataCollectionResult as $dataCollection){
               $newDate = date("Y-m-d H:i:s", strtotime($dataCollection['added_on'] . $lockHour));
               if($newDate <=$now){
                   $params = array();
                   $params['dataCollectionId'] = base64_encode($dataCollection['data_collection_id']);
                   $dataCollectionDb->lockDataCollectionDetails($params);
               }
            }
        }
        //lab data end
        //clinic data start
        $lockHour = '+48 hours';//default clinic data lock-hour
        //set clinic data's lock-hour
        if(isset($global['locking_clinic_data_after_login']) && (int)$global['locking_clinic_data_after_login'] > 0){
            $lockHour = '+'.(int)$global['locking_clinic_data_after_login'].' hours';
        }
        //To lock completed clinic datas
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
                   $params['clDataCollectionId'] = base64_encode($clinicDataCollection['cl_data_collection_id']);
                   $clinicDataCollectionDb->lockClinicDataCollectionDetails($params);
               }
            }
        }
        //clinic data end
        //clinic risk assessment data start
        $lockHour = '+48 hours';//default clinic risk assessment data lock-hour
        //set clinic risk assessment data's lock-hour
        if(isset($global['locking_risk_assessment_data_after_login']) && (int)$global['locking_risk_assessment_data_after_login'] > 0){
            $lockHour = '+'.(int)$global['locking_risk_assessment_data_after_login'].' hours';
        }
        //To lock completed clinic risk assessment datas
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
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $dQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                      ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
                      ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
                      ->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
                      ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
		      ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
		      ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
        if(count($params['dataCollection'])>0){
            $dataCollectionArray = array();
            for($i=0;$i<count($params['dataCollection']);$i++){
                $dataCollectionArray[] = base64_decode($params['dataCollection'][$i]);
            }
            $dQuery = $dQuery->where('da_c.data_collection_id IN ("' . implode('", "', $dataCollectionArray) . '")');
        }
        $dQueryStr = $sql->getSqlStringForSqlObject($dQuery);
      return $dbAdapter->query($dQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function exportDataCollectionInExcel($params){
        $queryContainer = new Container('query');
        $common = new CommonService();
        $ancSiteDb = $this->sm->get('AncSiteTable');
        $facilityDb = $this->sm->get('FacilityTable');
        $name = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'LOGBOOK--':'LAB-DATA-DOWNLOAD--';
        $sQuery = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$queryContainer->logbookQuery:$queryContainer->dataCollectionQuery;
        if(isset($sQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(isset($sResult) && count($sResult)>0){
                    $selectedANC = '';
                    $displayANC = '';
                    $allANCs = array();
                    $displayAllANCs = '';
                    $selectedLab = '';
                    $displayLab = '';
                    $allLabs = array();
                    $displayAllLabs = '';
                    $receiptDateatLab = '';
                    $resultReported = 'Completed Tests, Pending Tests';
                    $headerRow = 1;
                    if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                        $headerRow = 4;
                        $ancSites = $ancSiteDb->fetchActiveAncSites('extract-excel',$params['countryId']);
                        $facilities = $facilityDb->fetchActivefacilities('extract-excel',$params['countryId']);
                        //filter content
                        //set ancs
                        if(trim($params['anc'])!= ''){
                            $selectedANC = base64_decode($params['anc']);
                        }
                        if(isset($ancSites) && count($ancSites) > 0){
                            foreach($ancSites as $anc){
                                $allANCs[] = ' '.ucwords($anc['anc_site_name']);
                                if(trim($selectedANC)!= '' && $selectedANC == $anc['anc_site_id']){
                                    $displayANC = ucwords($anc['anc_site_name']); break;
                                }
                            }
                           $displayAllANCs = implode(',',$allANCs);
                        }
                        $ancs = (trim($displayANC)!= '')?$displayANC:$displayAllANCs;
                        //set labs
                        if(trim($params['lab'])!= ''){
                            $selectedLab = base64_decode($params['lab']);
                        }
                        if(isset($facilities) && count($facilities) > 0){
                            foreach($facilities as $facility){
                                $allLabs[] = ' '.ucwords($facility['facility_name']);
                                if(trim($selectedLab)!= '' && $selectedLab == $facility['facility_id']){
                                    $displayLab = ucwords($facility['facility_name']); break;
                                }
                            }
                           $displayAllLabs = implode(',',$allLabs);
                        }
                        $labs = (trim($displayLab)!= '')?$displayLab:$displayAllLabs;
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
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $row = array();
                        $specimenCollectedDate = '';
                        if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
                            $specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
                        }
                        $specimenPickedUpDateAtAnc = '';
                        if(isset($aRow['specimen_picked_up_date_at_anc']) && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
                            $specimenPickedUpDateAtAnc = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
                        }
                        $rejectionCode = '';
                        if(isset($aRow['rejection_code']) && trim($aRow['rejection_code'])!= ''){
                            $rejectionCode = $aRow['rejection_code'];
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
                        $recencyInfection = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
                        //$hIVRNAResult = '';
                        //if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
                        //    $hIVRNAResult = 'High Viral Load';
                        //}else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
                        //    $hIVRNAResult = 'Low Viral Load';
                        //}
                        $rapidRecencyAssay = '';
                        $diagnosisReaderValue = '';
                        $rapidRecencyAssayDuration = '';
                        $recencyReaderValue = '';
                        if(trim($aRow['asante_rapid_recency_assy'])!= ''){
                            $asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
                            if(isset($asanteRapidRecencyAssy['rrdt'])){
                                $rapidRecencyAssay = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?$asanteRapidRecencyAssy['rrdt']['assay']:'';
                                $diagnosisReaderValue = (isset($asanteRapidRecencyAssy['rrdt']['reader']))?$asanteRapidRecencyAssy['rrdt']['reader']:'';
                            }if(isset($asanteRapidRecencyAssy['rrr'])){
                                $rapidRecencyAssayDuration = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
                                $recencyReaderValue = (isset($asanteRapidRecencyAssy['rrr']['reader']))?$asanteRapidRecencyAssy['rrr']['reader']:'';
                            }
                        }
                        
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$receiptDateAtCentralLab:$aRow['patient_barcode_id'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['patient_barcode_id']:$specimenCollectedDate;
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$specimenCollectedDate:ucwords($aRow['anc_site_name']);
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?ucwords($aRow['anc_site_name']):$aRow['anc_site_code'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['anc_site_code']:$aRow['enc_anc_patient_id'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['enc_anc_patient_id']:'';
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'':$aRow['age'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['age']:$specimenPickedUpDateAtAnc;
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$specimenPickedUpDateAtAnc:ucwords($aRow['facility_name']);
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?ucwords($aRow['facility_name']):$aRow['facility_code'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['facility_code']:$aRow['lab_specimen_id'];
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$aRow['lab_specimen_id']:$rejectionCode;
                        $row[] = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?$rejectionCode:$receiptDateAtCentralLab;
                        $row[] = $testCompletionDate;
                        $row[] = $resultDispatchedDateToClinic;
                        $row[] = $aRow['final_lag_avidity_odn'];
                        $row[] = $aRow['hiv_rna'];
                        $row[] = $recencyInfection;
                        $row[] = $rapidRecencyAssay;
                        $row[] = $diagnosisReaderValue;
                        $row[] = $rapidRecencyAssayDuration;
                        $row[] = $recencyReaderValue;
                        $row[] = ucfirst($aRow['comments']);
                        if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                            $row[] = ucfirst($aRow['country_name']);
                        }if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $row[] = '';
                        }
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
                    
                    if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                      $sheet->mergeCells('A1:X1');
                      
                      $sheet->setCellValue('A1', html_entity_decode('Logbook for Recency Test ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('A2', html_entity_decode('ANC Site ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('B2', html_entity_decode($ancs, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('C2', html_entity_decode('Lab Site/Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('D2', html_entity_decode($labs, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('A3', html_entity_decode('Receipt Date at Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('B3', html_entity_decode($receiptDateatLab, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('C3', html_entity_decode('Result Reported ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $sheet->setCellValue('D3', html_entity_decode($resultReported, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'B'.$headerRow:'A'.$headerRow, html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'C'.$headerRow:'B'.$headerRow, html_entity_decode('Specimen Collected Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'D'.$headerRow:'C'.$headerRow, html_entity_decode('ANC Site ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'E'.$headerRow:'D'.$headerRow, html_entity_decode('ANC Site Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'F'.$headerRow:'E'.$headerRow, html_entity_decode('Encrypted ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'G'.$headerRow:'F'.$headerRow, html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'H'.$headerRow:'G'.$headerRow, html_entity_decode('Age ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'I'.$headerRow:'H'.$headerRow, html_entity_decode('Specimen Picked Up Date at ANC ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'J'.$headerRow:'I'.$headerRow, html_entity_decode('Lab/Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'K'.$headerRow:'J'.$headerRow, html_entity_decode('Lab/Facility Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'L'.$headerRow:'K'.$headerRow, html_entity_decode('Lab Specimen ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'M'.$headerRow:'L'.$headerRow, html_entity_decode('Rejection Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue((isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?'A'.$headerRow:'M'.$headerRow, html_entity_decode('Receipt Date at Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N'.$headerRow, html_entity_decode('Date of Test Completion', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O'.$headerRow, html_entity_decode('Result Dispatched Date to Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P'.$headerRow, html_entity_decode('LAg Avidity ODn ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q'.$headerRow, html_entity_decode('HIV RNA (cp/ml)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    //$sheet->setCellValue('R'.$headerRow, html_entity_decode('HIV RNA > 1000', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R'.$headerRow, html_entity_decode('Recent Infection (LAg Assay)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S'.$headerRow, html_entity_decode('Rapid Recency Diagnosis Test', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T'.$headerRow, html_entity_decode('Diagnosis Reader Value', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('U'.$headerRow, html_entity_decode('Rapid Recency Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V'.$headerRow, html_entity_decode('Recency Reader Value', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('W'.$headerRow, html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                        $sheet->setCellValue('X'.$headerRow, html_entity_decode('Country', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->setCellValue('Y'.$headerRow, html_entity_decode('Manager\'s Approval', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                    }else{
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                          $sheet->setCellValue('X'.$headerRow, html_entity_decode('Manager\'s Approval', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                    }
                    
                    if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                      $sheet->getRowDimension(1)->setRowHeight(-1);
                      $sheet->getRowDimension(2)->setRowHeight(-1);
                      $sheet->getStyle('B2')->getAlignment()->setWrapText(true);
                      $sheet->getStyle('D2')->getAlignment()->setWrapText(true);
                      
                      $sheet->getStyle('A1')->applyFromArray($titleTxtArray);
                      $sheet->getStyle('A2')->applyFromArray($labelArray);
                      $sheet->getStyle('B2')->applyFromArray($wrapTxtArray);
                      $sheet->getStyle('C2')->applyFromArray($labelArray);
                      $sheet->getStyle('D2')->applyFromArray($wrapTxtArray);
                      $sheet->getStyle('A3')->applyFromArray($labelArray);
                      $sheet->getStyle('C3')->applyFromArray($labelArray);
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
                    if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                        $sheet->getStyle('X'.$headerRow)->applyFromArray($styleArray);
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                         $sheet->getStyle('Y'.$headerRow)->applyFromArray($styleArray);
                        }
                    }else{
                        if(isset($params['frmSrc']) && trim($params['frmSrc']) == 'log'){
                         $sheet->getStyle('X'.$headerRow)->applyFromArray($styleArray);
                        }
                    }
                    
                    $currentRow = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?5:2;
                    foreach ($output as $rowData) {
                        $lag = '';
                        $assay1 = '';
                        $assay2 = '';
                        $colNo = 0;
                        $lstColumn = (count($rowData)-1);
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                                $keyCol = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?24:23;
                                if($colNo > $keyCol){
                                    break;
                                }
                            }else{
                                $keyCol = (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?23:22;
                                if($colNo > $keyCol){
                                    break;
                                }
                            }
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            if($colNo == 15){ $lag = $value; }
                            if($colNo == 18){ $assay1 = $value; }
                            if($colNo == 20){ $assay2 = $value; }
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            if($colNo > (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?22:21){
                                if($lstColumn == (isset($params['frmSrc']) && trim($params['frmSrc']) == 'log')?23:22){
                                   if($assay1 =='HIV Negative' || ($lag > 2 && (($assay1 == 'HIV Positive' && $assay2 == 'Recent') || $assay2 == 'Recent'))){
                                    $sheet->getStyle('A'.$currentRow.':X'.$currentRow)->applyFromArray($redTxtArray);
                                   }
                                }else{
                                   if($assay1 =='HIV Negative' || ($lag > 2 && (($assay1 == 'HIV Positive' && $assay2 == 'Recent') || $assay2 == 'Recent'))){
                                    $sheet->getStyle('A'.$currentRow.':Y'.$currentRow)->applyFromArray($redTxtArray);
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
                    return "";
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
             $alertContainer->msg = 'OOPS..';
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
             $alertContainer->msg = 'OOPS..';
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
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $row = array();
                        $reportingMonth = '';
                        $reportingYear = '';
                        if(isset($aRow['reporting_month_year']) && trim($aRow['reporting_month_year'])!= ''){
                            $xplodReportingMonthYear = explode('/',$aRow['reporting_month_year']);
                            $reportingMonth = $xplodReportingMonthYear[0];
                            $reportingYear = $xplodReportingMonthYear[1];
                        }
                        $row[] = ucwords($aRow['anc_site_name']);
                        $row[] = $aRow['anc_site_code'];
                        $row[] = ucfirst($reportingMonth);
                        $row[] = $reportingYear;
                        $row[] = ucwords($aRow['country_name']);
                        foreach($ancFormFields as $key=>$value){
                            //for non-existing fields
                            $col1Val = '0';
                            $col2Val = '0';
                            $col3Val = '0';
                            $col4Val = '0';
                            if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                                $fields = json_decode($aRow['characteristics_data'],true);
                                foreach($fields as $fieldName=>$fieldValue){
                                    if($key == $fieldName){
                                        //re-intialize to show existing fields
                                        foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                            $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                                           if($characteristicsName =='age_lt_15'){
                                              $col1Val = $characteristicsValue;
                                           }elseif($characteristicsName =='age_15_to_19'){
                                              $col2Val = $characteristicsValue;
                                           }elseif($characteristicsName =='age_20_to_24'){
                                              $col3Val = $characteristicsValue;
                                           }elseif($characteristicsName =='total'){
                                              $col4Val = $characteristicsValue;
                                           }
                                        }
                                    }
                                }
                            }
                          $row[] = $col1Val;
                          $row[] = $col2Val;
                          $row[] = $col3Val;
                          $row[] = $col4Val;
                        }
                        $row[] = ucfirst($aRow['comments']);
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
                     
                    $e1 = 5;
                    foreach($ancFormFields as $fieldRow){
                        $e2 = $e1+3;
                        $cellName1Value = $sheet->getCellByColumnAndRow($e1, 1)->getColumn();
                        $cellName2Value = $sheet->getCellByColumnAndRow($e2, 1)->getColumn();
                        $sheet->mergeCells($cellName1Value.'1:'.$cellName2Value.'1');
                      $e1 = $e2;
                      $e1++;
                    }
                    $cellNameValue = $sheet->getCellByColumnAndRow($e1, 1)->getColumn();
                    $sheet->mergeCells($cellNameValue.'1:'.$cellNameValue.'2');
                    
                    $sheet->setCellValue('A1', html_entity_decode('Clinic Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Clinic ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Month ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Year ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Country ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $a1 = 5;
                    foreach($ancFormFields as $key=>$value){
                        $columnTitle = ucwords(str_replace("_"," ",$key));
                        $columnTitle = str_replace("No","No.",$columnTitle);
                        $cellNameValue = $sheet->getCellByColumnAndRow($a1, 1)->getColumn();
                        $subCellName1Value = $sheet->getCellByColumnAndRow($a1, 2)->getColumn();
                        $subCellName2Value = $sheet->getCellByColumnAndRow($a1+1, 2)->getColumn();
                        $subCellName3Value = $sheet->getCellByColumnAndRow($a1+2, 2)->getColumn();
                        $subCellName4Value = $sheet->getCellByColumnAndRow($a1+3, 2)->getColumn();
                        $sheet->setCellValue($cellNameValue.'1', html_entity_decode($columnTitle, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue($subCellName1Value.'2', html_entity_decode('Age < 15', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue($subCellName2Value.'2', html_entity_decode('Age 15-19', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue($subCellName3Value.'2', html_entity_decode('Age 20-24', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->setCellValue($subCellName4Value.'2', html_entity_decode('Total', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $a1+=3;  
                      $a1++;
                    }
                    $cellNameValue = $sheet->getCellByColumnAndRow($a1, 1)->getColumn();
                    $sheet->setCellValue($cellNameValue.'1', html_entity_decode('Comments ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
                    $sheet->getStyle('B1:B2')->applyFromArray($styleArray);
                    $sheet->getStyle('C1:C2')->applyFromArray($styleArray);
                    $sheet->getStyle('D1:D2')->applyFromArray($styleArray);
                    $sheet->getStyle('E1:E2')->applyFromArray($styleArray);
                    $f1 = 5;
                    foreach($ancFormFields as $fieldRow){
                        $f2 = $f1+3;
                        $cellName1Value = $sheet->getCellByColumnAndRow($f1, 1)->getColumn();
                        $cellName2Value = $sheet->getCellByColumnAndRow($f2, 1)->getColumn();
                        $subCellName1Value = $sheet->getCellByColumnAndRow($f1, 2)->getColumn();
                        $subCellName2Value = $sheet->getCellByColumnAndRow($f1+1, 2)->getColumn();
                        $subCellName3Value = $sheet->getCellByColumnAndRow($f1+2, 2)->getColumn();
                        $subCellName4Value = $sheet->getCellByColumnAndRow($f1+3, 2)->getColumn();
                        $sheet->getStyle($cellName1Value.'1:'.$cellName2Value.'1')->applyFromArray($styleArray);
                        $sheet->getStyle($subCellName1Value.'2')->applyFromArray($styleArray);
                        $sheet->getStyle($subCellName2Value.'2')->applyFromArray($styleArray);
                        $sheet->getStyle($subCellName3Value.'2')->applyFromArray($styleArray);
                        $sheet->getStyle($subCellName4Value.'2')->applyFromArray($styleArray);
                      $f1 = $f2;
                      $f1++;
                    }
                    $cellName1Value = $sheet->getCellByColumnAndRow($f1, 1)->getColumn();
                    $sheet->getStyle($cellName1Value.'1:'.$cellName1Value.'2')->applyFromArray($styleArray);
                    
                    $currentRow = 3;
                    $highestColumn = ($f1+1)-1;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }if($colNo > $highestColumn){
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
                    $filename = 'ANC-DATA-COLLECTION--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "";
                }
            }catch (Exception $exc) {
                error_log("ANC-DATA-COLLECTION--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }  
        }else{
            return "";
        }
    }
    
    public function getAllAncLabReportDatas($parameters){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchAllAncLabReportDatas($parameters);
    }
    
    public function getLabReportResult(){
        $queryContainer = new Container('query');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $rQueryStr = $sql->getSqlStringForSqlObject($queryContainer->labReportQuery);
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
        }
        $rQueryStr = $sql->getSqlStringForSqlObject($rQuery);
        $rResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if(isset($rResult->cl_data_collection_id)){
            return 1;
        }else{
            return 0;
        }
    }
    
    public function generateRot47String($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->rot47($params);
    }
    
    public function getCountryDashboardDetails($params){
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        return $dataCollectionDb->fetchCountryDashboardDetails($params);
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
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $output = array();
                    foreach ($sResult as $aRow) {
                        $specimenCollectedDate = '';
                        $recentInfection = '';
                        //$hIVRNAResult = '';
                        $rapidRecencyAssay = '';
                        $rapidRecencyAssayDuration = '';
                        $status = 'Incomplete';
                        if($aRow['rejection_reason']!= null && trim($aRow['rejection_reason'])!= '' && $aRow['rejection_reason']> 0){
                            $aRow['labDataPresentComplete'] = -1;
                        }
                        //specimen collected date
                        if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
                            $specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
                        }
                        //status
                        if($aRow['labDataPresentComplete'] == 1){
                           $status = 'Complete';
                        }else if($aRow['labDataPresentComplete'] == -1){
                          $status = 'Rejected';
                        }
                        //recent infection
                        $recentInfection = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
                        //HIV rna values
                        //if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
                        //    $hIVRNAResult = 'High Viral Load';
                        //}else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
                        //    $hIVRNAResult = 'Low Viral Load';
                        //}
                        //rapid assay
                        if(trim($aRow['asante_rapid_recency_assy'])!= ''){
                            $asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
                            if(isset($asanteRapidRecencyAssy['rrdt'])){
                                $rapidRecencyAssay = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?$asanteRapidRecencyAssy['rrdt']['assay']:'';
                            }if(isset($asanteRapidRecencyAssy['rrr'])){
                                $rapidRecencyAssayDuration = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
                            }
                        }
                        $row = array();
                        $row[] = ucwords($aRow['province_name']);
                        $row[] = $aRow['anc_site_code'];
                        $row[] = $aRow['patient_barcode_id'];
                        $row[] = $specimenCollectedDate;
                        $row[] = $status;
                        $row[] = (isset($aRow['assessment_id']))?'Yes':'No';
                        $row[] = $aRow['hiv_rna'];
                        //$row[] = $hIVRNAResult;
                        $row[] = $recentInfection;
                        $row[] = $rapidRecencyAssay;
                        $row[] = $rapidRecencyAssayDuration;
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
                    $sheet->setCellValue('A1', html_entity_decode('Province Name ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('ANC Facility ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('Patient Barcode ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('Specimen Collected Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('Lab Data ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Behaviour Data ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G1', html_entity_decode('HIV RNA (cp/ml)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    //$sheet->setCellValue('H1', html_entity_decode('HIV RNA > 1000 ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H1', html_entity_decode('Recent Infection ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I1', html_entity_decode('Lab Rapid Recency Diagnosis Test ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J1', html_entity_decode('Lab Rapid Recency Result ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                   
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
                        $lag = '';
                        $assay1 = '';
                        $assay2 = '';
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            if($colNo > 9){
                                break;
                            }
                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            }else{
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            if($colNo == 6){ $lag = $value; }
                            if($colNo == 8){ $assay1 = $value; }
                            if($colNo == 9){ $assay2 = $value; }
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            if($colNo > 8){
                                if($assay1 =='HIV Negative' || ($lag == 'Long Term' && (($assay1 == 'HIV Positive' && $assay2 == 'Recent') || $assay2 == 'Recent'))){
                                  $sheet->getStyle('A'.$currentRow.':J'.$currentRow)->applyFromArray($redTxtArray);
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
                    return "";
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
}