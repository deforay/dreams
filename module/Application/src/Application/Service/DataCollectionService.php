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
    
    public function automaticDataCollectionLockAfterLogin(){
        $loginContainer = new Container('user');
        $dataCollectionDb = $this->sm->get('DataCollectionTable');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        //Get locking hour to lock data
        $lockingHour = '+72 hours';//Default locking hour
        $globalConfigQuery = $sql->select()->from(array('conf' => 'global_config'))
                                 ->where(array('conf.name'=>'locking_data_after_login'));
        $globalConfigQueryStr = $sql->getSqlStringForSqlObject($globalConfigQuery);
        $globalConfigResult = $dbAdapter->query($globalConfigQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if(isset($globalConfigResult->value) && trim($globalConfigResult->value) >0){
            $lockingHour = '+'.$globalConfigResult->value.' hours';
        }
        //To lock completed data by logined user
        $dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                                   ->columns(array('data_collection_id','added_on'))
                                   ->where(array('da_c.added_by'=>$loginContainer->userId,'da_c.status'=>1));
        $dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
        $dataCollectionResult = $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(isset($dataCollectionResult) && count($dataCollectionResult)>0){
            $now = date("Y-m-d H:i:s");
            foreach($dataCollectionResult as $dataCollection){
               $newDate = date("Y-m-d H:i:s", strtotime($dataCollection['added_on'] . $lockingHour));
               if($newDate <=$now){
                   $params = array();
                   $params['dataCollectionId'] = base64_encode($dataCollection['data_collection_id']);
                   $dataCollectionDb->lockDataCollectionDetails($params);
               }
            }
          return true;
        }else{
            return false;
        }
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
        if(isset($queryContainer->exportQuery)){
            try{
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportQuery);
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
                        $lAgAvidityResult = '';
                        if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='lt'){
                            $lAgAvidityResult = 'Long Term';
                        }else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
                            $lAgAvidityResult = 'Recent';
                        }
                        $hIVRNAResult = '';
                        if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
                            $hIVRNAResult = 'High Viral Load';
                        }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
                            $hIVRNAResult = 'Low Viral Load';
                        }
                        $rapidRecencyAssay = '';
                        $rapidRecencyAssayDuration = '';
                        if(isset($aRow['asante_rapid_recency_assy']) && trim($aRow['asante_rapid_recency_assy'])!= ''){
                            $xplodRapidRecencyAssay = explode('/',$aRow['asante_rapid_recency_assy']);
                            if(trim($xplodRapidRecencyAssay[0])!= ''){
                                if($xplodRapidRecencyAssay[0] == 'p'){
                                    $rapidRecencyAssay = 'Positive';
                                }else if($xplodRapidRecencyAssay[0] == 'n'){
                                    $rapidRecencyAssay = 'Negative';
                                }
                            }if(trim($xplodRapidRecencyAssay[1])!= ''){
                                if($xplodRapidRecencyAssay[1] == 'r'){
                                    $rapidRecencyAssayDuration = 'Recent';
                                }else if($xplodRapidRecencyAssay[1] == 'lt'){
                                    $rapidRecencyAssayDuration = 'Long Term';
                                }
                            }
                        }
                        $row[] = $aRow['surveillance_id'];
                        $row[] = $specimenCollectedDate;
                        $row[] = ucwords($aRow['anc_site_name']);
                        $row[] = $aRow['anc_site_code'];
                        $row[] = $aRow['enc_anc_patient_id'];
                        $row[] = $aRow['age'];
                        $row[] = $specimenPickedUpDateAtAnc;
                        $row[] = ucwords($aRow['facility_name']);
                        $row[] = $aRow['facility_code'];
                        $row[] = $aRow['lab_specimen_id'];
                        $row[] = $rejectionCode;
                        $row[] = $receiptDateAtCentralLab;
                        $row[] = $testCompletionDate;
                        $row[] = $resultDispatchedDateToClinic;
                        $row[] = $aRow['final_lag_avidity_odn'];
                        $row[] = $lAgAvidityResult;
                        $row[] = $aRow['hiv_rna'];
                        $row[] = $hIVRNAResult;
                        $row[] = ucfirst($aRow['recent_infection']);
                        $row[] = $rapidRecencyAssay;
                        $row[] = $rapidRecencyAssayDuration;
                        $row[] = ucfirst($aRow['comments']);
                        if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                            $row[] = ucfirst($aRow['country_name']);
                        }
                        $output[] = $row;
                    }
                    $styleArray = array(
                        'font' => array(
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
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    
                    $sheet->mergeCells('T1:U1');
                    
                    $sheet->setCellValue('A1', html_entity_decode('Surveillance ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('B1', html_entity_decode('Specimen Collected Date ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('C1', html_entity_decode('ANC Site ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('D1', html_entity_decode('ANC Site Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('E1', html_entity_decode('ANC Patient ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('F1', html_entity_decode('Age ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('G1', html_entity_decode('Specimen Picked Up Date at ANC ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('H1', html_entity_decode('Lab/Facility ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('I1', html_entity_decode('Lab/Facility Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('J1', html_entity_decode('Recjection Code ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('K1', html_entity_decode('Lab Specimen ID ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('L1', html_entity_decode('Receipt Date at Central Lab ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('M1', html_entity_decode('Date of Test Completion', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('N1', html_entity_decode('Result Dispatched Date to Clinic', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('O1', html_entity_decode('Final LAg Avidity ODn ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('P1', html_entity_decode('LAg Avidity Result ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('Q1', html_entity_decode('HIV RNA ', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('R1', html_entity_decode('HIV RNA >=1000', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('S1', html_entity_decode('Recent Infection (LAg Assay)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('T1', html_entity_decode('Rapid Recency Assay (Eg. Assante)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->setCellValue('V1', html_entity_decode('Comments', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                       $sheet->setCellValue('W1', html_entity_decode('Country', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                   
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
                    $sheet->getStyle('R1')->applyFromArray($styleArray);
                    $sheet->getStyle('S1')->applyFromArray($styleArray);
                    $sheet->getStyle('T1:U1')->applyFromArray($styleArray);
                    $sheet->getStyle('V1')->applyFromArray($styleArray);
                    if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                       $sheet->getStyle('W1')->applyFromArray($styleArray);
                    }
                    $currentRow = 2;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }
                            if(!isset($params['countryId']) || trim($params['countryId'])== ''){
                                if($colNo > 22){
                                    break;
                                }
                            }else{
                                if($colNo > 21){
                                    break;
                                }
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
                    $filename = 'LAB-DATA-COLLECTION-EXCEL--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "";
                }
            }catch (Exception $exc) {
                error_log("LAB-DATA-COLLECTION-EXCEL--" . $exc->getMessage());
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
        if(isset($queryContainer->clinicXportQuery)){
            try{
                $ancFormDb = $this->sm->get('AncFormTable');
                $ancFormFields = $ancFormDb->fetchActiveAncFormFields();
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->clinicXportQuery);
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
                            //For non-existing fields
                            $col1Val = 'Age < 15 : 0';
                            $col2Val = ' Age 15-19 : 0';
                            $col3Val = ' Age 20-24 : 0';
                            $col4Val = ' Total : 0';
                            if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                                $fields = json_decode($aRow['characteristics_data'],true);
                                foreach($fields as $fieldName=>$fieldValue){
                                    if($key == $fieldName){
                                        //Re-intialize to show existing fields
                                        foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                            $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                                           if($characteristicsName =='age_lt_15'){
                                              $col1Val = 'Age < 15 : '.$characteristicsValue;
                                           }elseif($characteristicsName =='age_15_to_19'){
                                              $col2Val = ' Age 15-19 : '.$characteristicsValue;
                                           }elseif($characteristicsName =='age_20_to_24'){
                                              $col3Val = ' Age 20-24 : '.$characteristicsValue;
                                           }elseif($characteristicsName =='total'){
                                              $col4Val = ' Total : '.$characteristicsValue;
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
                        $output[] = $row;
                    }
                    $styleArray = array(
                        'font' => array(
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
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    
                    $e1 = 5;
                    foreach($ancFormFields as $fieldRow){
                        $e2 = $e1+3;
                        $cellName1Value = $sheet->getCellByColumnAndRow($e1, 1)->getColumn();
                        $cellName2Value = $sheet->getCellByColumnAndRow($e2, 1)->getColumn();
                        $sheet->mergeCells($cellName1Value.'1:'.$cellName2Value.'1');
                      $e1 = $e2;
                      $e1++;
                    }
                    
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
                        $sheet->setCellValue($cellNameValue.'1', html_entity_decode($columnTitle, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                      $a1+=3;  
                      $a1++;
                    }
                   
                    $sheet->getStyle('A1')->applyFromArray($styleArray);
                    $sheet->getStyle('B1')->applyFromArray($styleArray);
                    $sheet->getStyle('C1')->applyFromArray($styleArray);
                    $sheet->getStyle('D1')->applyFromArray($styleArray);
                    $sheet->getStyle('E1')->applyFromArray($styleArray);
                    $f1 = 5;
                    foreach($ancFormFields as $fieldRow){
                        $f2 = $f1+3;
                        $cellName1Value = $sheet->getCellByColumnAndRow($f1, 1)->getColumn();
                        $cellName2Value = $sheet->getCellByColumnAndRow($f2, 1)->getColumn();
                        $sheet->getStyle($cellName1Value.'1:'.$cellName2Value.'1')->applyFromArray($styleArray);
                      $f1 = $f2;
                      $f1++;
                    }
                    $currentRow = 2;
                    $highestColumn = $f1-1;
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
                    $filename = 'ANC-DATA-COLLECTION-EXCEL--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                }else{
                    return "";
                }
            }catch (Exception $exc) {
                error_log("ANC-DATA-COLLECTION-EXCEL--" . $exc->getMessage());
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
    
    public function getLastDataCollectionInfo(){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $lrQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                                 ->columns(array('data_collection_id'))
                                 ->order('da_c.data_collection_id DESC');
        $lrQueryStr = $sql->getSqlStringForSqlObject($lrQuery);
      return $dbAdapter->query($lrQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
}