<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class DataCollectionTable extends AbstractTableGateway {

    protected $table = 'data_collection';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addDataCollectionDetails($params){
        $loginContainer = new Container('user');
        $lastInsertedId = 0;
        if(isset($params['patientBarcodeId']) && trim($params['patientBarcodeId'])!= ''){
            $common = new CommonService();
	    if(isset($params['chosenCountry']) && trim($params['chosenCountry'])!=''){
		$country = base64_decode($params['chosenCountry']);
	    }else if(isset($params['country']) && trim($params['country'])!=''){
		$country = base64_decode($params['country']);
	    }else{
		return false;
	    }
            $specimenCollectedDate = NULL;
            if(isset($params['specimenCollectedDate']) && trim($params['specimenCollectedDate'])!= ''){
                $specimenCollectedDate = $common->dateFormat($params['specimenCollectedDate']);
            }
            $specimenPickedUpDateAtAnc = NULL;
            if(isset($params['specimenPickedUpDateAtAnc']) && trim($params['specimenPickedUpDateAtAnc'])!= ''){
                $specimenPickedUpDateAtAnc = $common->dateFormat($params['specimenPickedUpDateAtAnc']);
            }
            $receiptDateAtCentralLab = NULL;
            if(isset($params['dateOfReceiptAtCentralLab']) && trim($params['dateOfReceiptAtCentralLab'])!= ''){
                $receiptDateAtCentralLab = $common->dateFormat($params['dateOfReceiptAtCentralLab']);
            }
	    $testCompletionDate = NULL;
            if(isset($params['dateOfTestCompletion']) && trim($params['dateOfTestCompletion'])!= ''){
                $testCompletionDate = $common->dateFormat($params['dateOfTestCompletion']);
            }
            $resultDispatchedDateToClinic = NULL;
            if(isset($params['dateOfResultDispatchedToClinic']) && trim($params['dateOfResultDispatchedToClinic'])!= ''){
                $resultDispatchedDateToClinic = $common->dateFormat($params['dateOfResultDispatchedToClinic']);
            }
            $rejectionReason = NULL;
            if(isset($params['rejectionReason']) && trim($params['rejectionReason'])!= ''){
                $rejectionReason = base64_decode($params['rejectionReason']);
            }
	    $patientDOB = NULL;
            if(isset($params['dob']) && trim($params['dob'])!= ''){
                $patientDOB = $common->dateFormat($params['dob']);
            }
	    $lagAssayValidate = true;
	    $asanteValidate = true;
	    if(!isset($params['age'])){
                $params['age'] = NULL;
            }if(!isset($params['lagAvidityResult'])){
		$lagAssayValidate = false;
                $params['lagAvidityResult'] = NULL;
            }if(!isset($params['hivRnaGT1000'])){
                $params['hivRnaGT1000'] = NULL;
            }if(!isset($params['recentInfection'])){
                $params['recentInfection'] = NULL;
            }if(!isset($params['asanteRapidRecencyAssayPn'])){
                $params['asanteRapidRecencyAssayPn'] = '';
            }if(!isset($params['readerValueRRDTLog'])){
                $params['readerValueRRDTLog'] = '';
            }if(!isset($params['asanteRapidRecencyAssayRlt'])){
                $params['asanteRapidRecencyAssayRlt'] = '';
            }if(!isset($params['readerValueRRRLog'])){
                $params['readerValueRRRLog'] = '';
            }if(trim($params['specimenType'])!= '' && $params['specimenType']!= 3 && (($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt'] == '') || ($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt']!= '') || ($params['asanteRapidRecencyAssayPn'] == 'present' && $params['asanteRapidRecencyAssayRlt']== ''))){
                $asanteValidate = false;
            }if(!isset($params['labTechName'])){
		$params['labTechName'] = NULL;
	    }if($params['asanteRapidRecencyAssayPn'] == 'absent'){
		$params['asanteRapidRecencyAssayRlt'] = '';
		$params['readerValueRRRLog'] = '';
	    }
	    $asanteRapidRecencyAssay = array('rrdt'=>array(
						'assay'=>$params['asanteRapidRecencyAssayPn'],
						'reader'=>$params['readerValueRRDTLog']
					    ),
					    'rrr'=>array(
						'assay'=>$params['asanteRapidRecencyAssayRlt'],
						'reader'=>$params['readerValueRRRLog']
					    )
					);
	    //status
	    $status = 1;//complete
	    $formCompletionDate = $common->getDateTime();
	    if($rejectionReason == NULL || $rejectionReason == 1){
		if($lagAssayValidate == false || $asanteValidate == false || ($params['lagAvidityResult'] == 'recent' && trim($params['hivRna']) == '') || ($params['asanteRapidRecencyAssayRlt'] == 'absent' && trim($params['hivRna']) == '')){
		    $status = 4;//incomplete
		    $formCompletionDate = NULL;
		}
	    }
            $data = array(
                        'surveillance_id'=>$params['surveillanceId'],
                        'patient_barcode_id'=>$params['patientBarcodeId'],
                        'specimen_collected_date'=>$specimenCollectedDate,
                        'anc_site'=>base64_decode($params['ancSite']),
                        'anc_patient_id'=>$params['ancPatientId'],
                        'enc_anc_patient_id'=>$this->rot47($params['ancPatientId']),
                        'art_patient_id'=>$params['artPatientId'],
                        'age'=>$params['age'],
                        'gestational_age'=>$params['gestationalAge'],
                        'patient_dob'=>$patientDOB,
			'specimen_type'=>$params['specimenType'],
                        'specimen_picked_up_date_at_anc'=>$specimenPickedUpDateAtAnc,
                        'lab'=>base64_decode($params['lab']),
                        'lab_specimen_id'=>$params['labSpecimenId'],
			'rejection_reason'=>$rejectionReason,
                        'receipt_date_at_central_lab'=>$receiptDateAtCentralLab,
			'lab_tech_name'=>$params['labTechName'],
                        'date_of_test_completion'=>$testCompletionDate,
			'result_dispatched_date_to_clinic'=>$resultDispatchedDateToClinic,
                        'final_lag_avidity_odn'=>$params['finalLagAvidityOdn'],
                        'lag_avidity_result'=>$params['lagAvidityResult'],
                        'hiv_rna'=>$params['hivRna'],
                        'hiv_rna_gt_1000'=>$params['hivRnaGT1000'],
                        'recent_infection'=>$params['recentInfection'],
                        'asante_rapid_recency_assy'=>json_encode($asanteRapidRecencyAssay),
			'comments'=>$params['comments'],
                        'country'=>$country,
			'date_of_form_completion'=>$formCompletionDate,
			'status'=>$status,
                        'added_on'=>$common->getDateTime(),
                        'added_by'=>$loginContainer->userId
                    );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
	    if($lastInsertedId >0){
		//Add a new row into data collection event log table
		$dbAdapter = $this->adapter;
		$dataCollectionEventLogDb = new DataCollectionEventLogTable($dbAdapter);
		$data['data_collection_id'] = $lastInsertedId;
		$dataCollectionEventLogDb->insert($data);
	    }
        }
      return $lastInsertedId;
    }
    
    public function fetchAllDataCollections($parameters){
	$loginContainer = new Container('user');
	$common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $aColumns = array('da_c.patient_barcode_id','t.test_status_name',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')",'lab_tech_name',"DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y')",'u.user_name');
	    $orderColumns = array('da_c.patient_barcode_id','t.test_status_name','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','lab_tech_name','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.added_on','u.user_name');
	}else{
	    $aColumns = array('da_c.patient_barcode_id','t.test_status_name',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')",'lab_tech_name',"DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y')",'u.user_name','c.country_name');
	    $orderColumns = array('da_c.patient_barcode_id','t.test_status_name','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','lab_tech_name','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.added_on','u.user_name','c.country_name');
	}

       /*
        * Paging
        */
       $sLimit = "";
       if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
           $sOffset = $parameters['iDisplayStart'];
           $sLimit = $parameters['iDisplayLength'];
       }

       /*
        * Ordering
        */

       $sOrder = "";
       if (isset($parameters['iSortCol_0'])) {
           for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
               if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                   $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
               }
           }
           $sOrder = substr_replace($sOrder, "", -1);
       }

       /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

       $sWhere = "";
       if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
           $searchArray = explode(" ", $parameters['sSearch']);
           $sWhereSub = "";
           foreach ($searchArray as $search) {
               if ($sWhereSub == "") {
                   $sWhereSub .= "(";
               } else {
                   $sWhereSub .= " AND (";
               }
               $colSize = count($aColumns);

               for ($i = 0; $i < $colSize; $i++) {
                   if ($i < $colSize - 1) {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                   } else {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                   }
               }
               $sWhereSub .= ")";
           }
           $sWhere .= $sWhereSub;
       }

       /* Individual column filtering */
       for ($i = 0; $i < count($aColumns); $i++) {
           if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
               if ($sWhere == "") {
                   $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               } else {
                   $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               }
           }
       }

       /*
        * SQL queries
        * Get data to display
        */
       $dbAdapter = $this->adapter;
       $sql = new Sql($dbAdapter);
       $mappedLab = array();
       $uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                  ->where(array('l_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped lab
       foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
       }
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
                     ->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
                     ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
		     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if(isset($parameters['dashLab']) && trim($parameters['dashLab'])!= ''){
	   $sQuery = $sQuery->where(array('da_c.lab'=>trim($parameters['dashLab'])));
	}else if($loginContainer->roleCode== 'LS'){
	    $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('da_c.country'=>trim($parameters['countryId'])));
	}else if($loginContainer->roleCode== 'CC'){
	    $sQuery = $sQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	if(isset($parameters['date']) && trim($parameters['date'])!= ''){
	   $data_Column = ($parameters['dateSrc'] == 'collected')?'da_c.specimen_collected_date':'da_c.added_on';
	   $reportingMonthYearArray = explode("/",$parameters['date']);
	   $sQuery = $sQuery->where('MONTH('.$data_Column.') ="'.date('m', strtotime($reportingMonthYearArray[0])).'" AND YEAR('.$data_Column.') ="'.$reportingMonthYearArray[1].'"');
	}
	if(isset($parameters['type']) && trim($parameters['type'])== 'incomplete'){
	    $sQuery = $sQuery->where('da_c.status = 4');
	}else if(isset($parameters['type']) && trim($parameters['type'])== 'tested'){
	    $sQuery = $sQuery->where('da_c.status IN(1,2,3)');
	}else if(isset($parameters['type']) && trim($parameters['type'])== 'finalized'){
	    $sQuery = $sQuery->where(array('da_c.status'=>2));
	}else if(isset($parameters['type']) && trim($parameters['type'])== 'LAg-rececnt'){
	    $sQuery = $sQuery->where(array('da_c.lag_avidity_result'=>'recent'));
	}else if(isset($parameters['type']) && trim($parameters['type'])== 'lab-rr-recent'){
	    $sQuery = $sQuery->where('da_c.asante_rapid_recency_assy like \'%rrr":{"assay":"absent"%\'');
	}
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }

       if (isset($sLimit) && isset($sOffset)) {
           $sQuery->limit($sLimit);
           $sQuery->offset($sOffset);
       }

       $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
       //echo $sQueryStr;die;
       $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

       /* Data set length after filtering */
       $sQuery->reset('limit');
       $sQuery->reset('offset');
       $fQuery = $sql->getSqlStringForSqlObject($sQuery);
       $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
       $iFilteredTotal = count($aResultFilterTotal);

       /* Total data set length */
	$tQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
				->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
				->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
				->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
				->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
				->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode== 'LS'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>trim($parameters['countryId'])));
	}else if($loginContainer->roleCode== 'CC'){
	    $tQuery = $tQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	$tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
	$tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
	$iTotal = count($tResult);
	$output = array(
		   "sEcho" => intval($parameters['sEcho']),
		   "iTotalRecords" => $iTotal,
		   "iTotalDisplayRecords" => $iFilteredTotal,
		   "aaData" => array()
	);
	foreach ($rResult as $aRow) {
	    $specimenCollectedDate = '';
	    $specimenPickUpDateatAnc = '';
	    $receiptDateAtCentralLab = '';
	    $testCompletionDate = '';
	    $resultDispatchedDateToClinic = '';
	    if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		$specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
	    }if(isset($aRow['specimen_picked_up_date_at_anc']) && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
		$specimenPickUpDateatAnc = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
	    }if(isset($aRow['receipt_date_at_central_lab']) && trim($aRow['receipt_date_at_central_lab'])!= '' && $aRow['receipt_date_at_central_lab']!= '0000-00-00'){
		$receiptDateAtCentralLab = $common->humanDateFormat($aRow['receipt_date_at_central_lab']);
	    }if(isset($aRow['date_of_test_completion']) && trim($aRow['date_of_test_completion'])!= '' && $aRow['date_of_test_completion']!= '0000-00-00'){
		$testCompletionDate = $common->humanDateFormat($aRow['date_of_test_completion']);
	    }if(isset($aRow['result_dispatched_date_to_clinic']) && trim($aRow['result_dispatched_date_to_clinic'])!= '' && $aRow['result_dispatched_date_to_clinic']!= '0000-00-00'){
		$resultDispatchedDateToClinic = $common->humanDateFormat($aRow['result_dispatched_date_to_clinic']);
	    }
	    $lAgAvidityResult = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
	//    $hIVRNAResult = '';
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    
	    $asanteRapidRecencyAssayPn = '';
	    $asanteRapidRecencyAssayRlt = '';
	    if(trim($aRow['asante_rapid_recency_assy'])!= ''){
		$asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
		if(isset($asanteRapidRecencyAssy['rrdt'])){
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?ucwords($asanteRapidRecencyAssy['rrdt']['assay']):'';
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
		}
	    }
	    $userUnlockedHistory = '';
	    if($aRow['unlocked_on']!= null && trim($aRow['unlocked_on'])!= '' && $aRow['unlocked_on']!= '0000-00-00 00:00:00'){
		$unlockedDate = explode(" ",$aRow['unlocked_on']);
		
		$userQuery = $sql->select()->from(array('u' => 'user'))->columns(array('user_id','full_name'))->where(array('u.user_id'=>$aRow['unlocked_by']));
	        $userQueryStr = $sql->getSqlStringForSqlObject($userQuery);
	        $userResult = $dbAdapter->query($userQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
		$unlockedBy = 'System';
		if(isset($userResult->user_id)){
		    $unlockedBy = ($userResult->user_id == $loginContainer->userId)?'You':ucwords($userResult->full_name);
		}
	        $userUnlockedHistory = '<i class="zmdi zmdi-info-outline" title="This row was unlocked on '.$common->humanDateFormat($unlockedDate[0])." ".$unlockedDate[1].' by '.$unlockedBy.'"></i>';
	    }
	    $dataView = '';
	    $dataEdit = '';
	    $dataLock = '';
	    $dataUnlock = '';
	    $pdfLink = '';
	    //data view
	    $dataView = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-1" title="View"><i class="zmdi zmdi-eye"></i> View</a>&nbsp;&nbsp;';
	    //data edit
	    if($loginContainer->hasViewOnlyAccess != 'yes' && $aRow['test_status_name']!= 'locked'){
		$dataEdit = '<a href="/data-collection/edit/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-1" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp;';
	    }
	    //data lock
	    if($loginContainer->hasViewOnlyAccess != 'yes' && $aRow['test_status_name'] == 'completed'){
		$dataLock = '<a href="javascript:void(0);" onclick="lockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn green-text custom-btn custom-btn-green margin-bottom-1" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a>&nbsp;&nbsp;';
	    }
	    //data unlock(csc/cc)
	    if(($loginContainer->roleCode == 'CSC' || $loginContainer->roleCode == 'CC') && $loginContainer->hasViewOnlyAccess != 'yes' && $aRow['test_status_name'] == 'locked'){
		$dataUnlock = '<a href="javascript:void(0);" onclick="unlockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn red-text custom-btn custom-btn-red margin-bottom-1" title="Unlock"><i class="zmdi zmdi-lock-open"></i> Unlock</a>&nbsp;&nbsp;';
	    }
	    $dataLockUnlock = (trim($dataLock)!= '')?$dataLock:$dataUnlock;
	    //individual result pdf
	    if($aRow['test_status_name']== 'locked'){
	       $pdfLink = '<a href="javascript:void(0);" onclick="printDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-1" title="PDF"><i class="zmdi zmdi-collection-pdf"></i> PDF</a>&nbsp;&nbsp;';
	    }
	    $addedDate = explode(" ",$aRow['added_on']);
	    $row = array();
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = ucwords($aRow['test_status_name']);
	    $row[] = $specimenCollectedDate;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $aRow['gestational_age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = (isset($aRow['rejection_code']) && (int)$aRow['rejection_code'] > 1)?$aRow['rejection_code']:'';
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = ucwords($aRow['lab_tech_name']);
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = $asanteRapidRecencyAssayPn;
	    $row[] = $asanteRapidRecencyAssayRlt;
	    $row[] = $common->humanDateFormat($addedDate[0]);
	    $row[] = ucwords($aRow['user_name']);
	    if($parameters['countryId']== ''){
	       $row[] = ucwords($aRow['country_name']);
	    }
	    $row[] = $dataEdit.$dataView.$dataLockUnlock.$pdfLink.$userUnlockedHistory;
	    $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchDataCollection($dataCollectionId){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                                   ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
                                   ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
                                   ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
                                   ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left')
                                   ->where(array('da_c.data_collection_id'=>$dataCollectionId));
	$dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
      return $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function updateDataCollectionDetails($params){
	$loginContainer = new Container('user');
        $dataCollectionId = 0;
        if(isset($params['patientBarcodeId']) && trim($params['patientBarcodeId'])!= ''){
            $common = new CommonService();
            $dataCollectionId = base64_decode($params['dataCollectionId']);
            $specimenCollectedDate = NULL;
            if(isset($params['specimenCollectedDate']) && trim($params['specimenCollectedDate'])!= ''){
                $specimenCollectedDate = $common->dateFormat($params['specimenCollectedDate']);
            }
            $specimenPickedUpDateAtAnc = NULL;
            if(isset($params['specimenPickedUpDateAtAnc']) && trim($params['specimenPickedUpDateAtAnc'])!= ''){
                $specimenPickedUpDateAtAnc = $common->dateFormat($params['specimenPickedUpDateAtAnc']);
            }
            $receiptDateAtCentralLab = NULL;
            if(isset($params['dateOfReceiptAtCentralLab']) && trim($params['dateOfReceiptAtCentralLab'])!= ''){
                $receiptDateAtCentralLab = $common->dateFormat($params['dateOfReceiptAtCentralLab']);
            }
	    $testCompletionDate = NULL;
            if(isset($params['dateOfTestCompletion']) && trim($params['dateOfTestCompletion'])!= ''){
                $testCompletionDate = $common->dateFormat($params['dateOfTestCompletion']);
            }
            $resultDispatchedDateToClinic = NULL;
            if(isset($params['dateOfResultDispatchedToClinic']) && trim($params['dateOfResultDispatchedToClinic'])!= ''){
                $resultDispatchedDateToClinic = $common->dateFormat($params['dateOfResultDispatchedToClinic']);
            }
            $rejectionReason = NULL;
            if(isset($params['rejectionReason']) && trim($params['rejectionReason'])!= ''){
                $rejectionReason = base64_decode($params['rejectionReason']);
            }
            $patientDOB = NULL;
            if(isset($params['dob']) && trim($params['dob'])!= ''){
                $patientDOB = $common->dateFormat($params['dob']);
            }
	    $lagAssayValidate = true;
	    $asanteValidate = true;
	    if(!isset($params['age'])){
                $params['age'] = NULL;
            }if(!isset($params['lagAvidityResult'])){
		$lagAssayValidate = false;
                $params['lagAvidityResult'] = NULL;
            }if(!isset($params['hivRnaGT1000'])){
                $params['hivRnaGT1000'] = NULL;
            }if(!isset($params['recentInfection'])){
                $params['recentInfection'] = NULL;
            }if(!isset($params['asanteRapidRecencyAssayPn'])){
                $params['asanteRapidRecencyAssayPn'] = '';
            }if(!isset($params['readerValueRRDTLog'])){
                $params['readerValueRRDTLog'] = '';
            }if(!isset($params['asanteRapidRecencyAssayRlt'])){
                $params['asanteRapidRecencyAssayRlt'] = '';
            }if(!isset($params['readerValueRRRLog'])){
                $params['readerValueRRRLog'] = '';
            }if(trim($params['specimenType'])!= '' && $params['specimenType']!= 3 && (($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt'] == '') || ($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt']!= '') || ($params['asanteRapidRecencyAssayPn'] == 'present' && $params['asanteRapidRecencyAssayRlt']== ''))){
                $asanteValidate = false;
            }if(!isset($params['labTechName'])){
		$params['labTechName'] = NULL;
	    }
	    $readerValueRRDTLog = (trim($params['readerValueRRDTLog'])!= '')?$params['readerValueRRDTLog']:$params['readerValueRRDTLogOld'];
	    $readerValueRRRLog = (trim($params['readerValueRRRLog'])!= '')?$params['readerValueRRRLog']:$params['readerValueRRRLogOld'];
	    
	    if($params['asanteRapidRecencyAssayPn'] == 'absent'){
		$params['asanteRapidRecencyAssayRlt'] = '';
		$readerValueRRRLog = '';
	    }
	    $asanteRapidRecencyAssay = array('rrdt'=>array(
						'assay'=>$params['asanteRapidRecencyAssayPn'],
						'reader'=>$readerValueRRDTLog
					    ),
					    'rrr'=>array(
						'assay'=>$params['asanteRapidRecencyAssayRlt'],
						'reader'=>$readerValueRRRLog
					    )
					);
	    //status
	    $status = 1;//complete
	    $formCompletion = true;
	    $formCompletionDate = $common->getDateTime();
	    if($rejectionReason == NULL || $rejectionReason == 1){
		if($lagAssayValidate == false || $asanteValidate == false || ($params['lagAvidityResult'] == 'recent' && trim($params['hivRna']) == '') || ($params['asanteRapidRecencyAssayRlt'] == 'absent' && trim($params['hivRna']) == '')){
		    $status = 4;//incomplete
		    $formCompletionDate = NULL;
		}else if(($params['formStatus'] == 1 || $params['formStatus'] == 3) && $params['formCompletionDate']!= null && trim($params['formCompletionDate'])!= '' && $params['formCompletionDate']!= '0000-00-00 00:00:00'){
		    $formCompletion = false;//submitted with the status 'completed/unlocked' with completed date
		}
	    }
            $data = array(
                        'surveillance_id'=>$params['surveillanceId'],
			'patient_barcode_id'=>$params['patientBarcodeId'],
                        'specimen_collected_date'=>$specimenCollectedDate,
                        'anc_site'=>base64_decode($params['ancSite']),
                        'anc_patient_id'=>$params['ancPatientId'],
			'enc_anc_patient_id'=>$this->rot47($params['ancPatientId']),
			'art_patient_id'=>$params['artPatientId'],
                        'age'=>$params['age'],
			'gestational_age'=>$params['gestationalAge'],
                        'patient_dob'=>$patientDOB,
                        'specimen_type'=>$params['specimenType'],
                        'specimen_picked_up_date_at_anc'=>$specimenPickedUpDateAtAnc,
                        'lab'=>base64_decode($params['lab']),
                        'lab_specimen_id'=>$params['labSpecimenId'],
			'rejection_reason'=>$rejectionReason,
                        'receipt_date_at_central_lab'=>$receiptDateAtCentralLab,
			'lab_tech_name'=>$params['labTechName'],
                        'date_of_test_completion'=>$testCompletionDate,
                        'result_dispatched_date_to_clinic'=>$resultDispatchedDateToClinic,
                        'final_lag_avidity_odn'=>$params['finalLagAvidityOdn'],
                        'lag_avidity_result'=>$params['lagAvidityResult'],
                        'hiv_rna'=>$params['hivRna'],
                        'hiv_rna_gt_1000'=>$params['hivRnaGT1000'],
                        'recent_infection'=>$params['recentInfection'],
                        'asante_rapid_recency_assy'=>json_encode($asanteRapidRecencyAssay),
			'comments'=>$params['comments'],
                        'status'=>$status,
                        'updated_on'=>$common->getDateTime(),
                        'updated_by'=>$loginContainer->userId
                    );
	    if($formCompletion){
		$data['date_of_form_completion'] = $formCompletionDate;
	    }
	    $this->update($data,array('data_collection_id'=>$dataCollectionId));
	    //Add a new row into data collection event log table
	    $dbAdapter = $this->adapter;
	    $dataCollectionEventLogDb = new DataCollectionEventLogTable($dbAdapter);
	    $data['data_collection_id'] = $dataCollectionId;
	    $data['country'] = base64_decode($params['chosenCountry']);
	    $dataCollectionEventLogDb->insert($data);
        }
      return $dataCollectionId;
    }
	
    public function lockDataCollectionDetails($params){
	$loginContainer = new Container('user');
	$common = new CommonService();
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$data = array(
	    'status'=>2,
	    'locked_on'=>$common->getDateTime(),
	    'locked_by'=>(isset($loginContainer->userId))?$loginContainer->userId:NULL
	);
        $dataCollectionEventLogQuery = $sql->select()->from(array('da_c_e' => 'data_collection_event_log'))
                                           ->where(array('da_c_e.data_collection_id'=>base64_decode($params['dataCollectionId'])))
				           ->order('da_c_e.data_collection_event_log_id desc');
	$dataCollectionEventLogQueryStr = $sql->getSqlStringForSqlObject($dataCollectionEventLogQuery);
	$result = $dbAdapter->query($dataCollectionEventLogQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	if(isset($result->data_collection_event_log_id)){
	    $dataCollectionEventLogDb = new DataCollectionEventLogTable($dbAdapter);
	    $dataCollectionEventLogDb->update($data,array('data_collection_event_log_id'=>$result->data_collection_event_log_id));
	}
      return $this->update($data,array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function unlockDataCollectionDetails($params){
	$loginContainer = new Container('user');
	$common = new CommonService();
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$data = array(
	    'status'=>3,
	    'unlocked_on'=>$common->getDateTime(),
	    'unlocked_by'=>$loginContainer->userId
	);
        $dataCollectionEventLogQuery = $sql->select()->from(array('da_c_e' => 'data_collection_event_log'))
                                           ->where(array('da_c_e.data_collection_id'=>base64_decode($params['dataCollectionId'])))
				           ->order('da_c_e.data_collection_event_log_id desc');
	$dataCollectionEventLogQueryStr = $sql->getSqlStringForSqlObject($dataCollectionEventLogQuery);
	$result = $dbAdapter->query($dataCollectionEventLogQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	if(isset($result->data_collection_event_log_id)){
	    $dataCollectionEventLogDb = new DataCollectionEventLogTable($dbAdapter);
	    $dataCollectionEventLogDb->update($data,array('data_collection_event_log_id'=>$result->data_collection_event_log_id));
	}
      return $this->update($data,array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function requestForUnlockDataCollectionDetails($params){
	return $this->update(array('status'=>'requested'),array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function fetchAllDataExtractions($parameters){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
	$common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $aColumns = array('da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')",'lab_tech_name',"DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments');
	    $orderColumns = array('da_c.patient_barcode_id','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','lab_tech_name','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments');
	}else{
	    $aColumns = array('da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')",'lab_tech_name',"DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments','c.country_name');
	    $orderColumns = array('da_c.patient_barcode_id','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','lab_tech_name','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments','c.country_name');
	}

       /*
        * Paging
        */
       $sLimit = "";
       if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
           $sOffset = $parameters['iDisplayStart'];
           $sLimit = $parameters['iDisplayLength'];
       }

       /*
        * Ordering
        */

       $sOrder = "";
       if (isset($parameters['iSortCol_0'])) {
           for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
               if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                   $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
               }
           }
           $sOrder = substr_replace($sOrder, "", -1);
       }

       /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

       $sWhere = "";
       if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
           $searchArray = explode(" ", $parameters['sSearch']);
           $sWhereSub = "";
           foreach ($searchArray as $search) {
               if ($sWhereSub == "") {
                   $sWhereSub .= "(";
               } else {
                   $sWhereSub .= " AND (";
               }
               $colSize = count($aColumns);

               for ($i = 0; $i < $colSize; $i++) {
                   if ($i < $colSize - 1) {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                   } else {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                   }
               }
               $sWhereSub .= ")";
           }
           $sWhere .= $sWhereSub;
       }

       /* Individual column filtering */
       for ($i = 0; $i < count($aColumns); $i++) {
           if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
               if ($sWhere == "") {
                   $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               } else {
                   $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               }
           }
       }

       /*
        * SQL queries
        * Get data to display
        */
       $start_date = '';
       $end_date = '';
       if(isset($parameters['specimenCollectedDate']) && trim($parameters['specimenCollectedDate'])!= ''){
	   $s_c_date = explode("to", $parameters['specimenCollectedDate']);
	   if(isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($s_c_date[0]));
	   }if(isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($s_c_date[1]));
	   }
	}
       $dbAdapter = $this->adapter;
       $sql = new Sql($dbAdapter);
       $mappedLab = array();
       $uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                  ->where(array('l_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped lab
       foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
       }
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	             //->where('da_c.status IN (2)');
	if($loginContainer->roleCode== 'LS'){
	    if(trim($parameters['lab']) ==''){
	       $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	    }
	}else if($loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if($loginContainer->roleCode== 'CC'){
	    $sQuery = $sQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	//custom filter start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("da_c.specimen_collected_date >='" . $start_date ."'", "da_c.specimen_collected_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.specimen_collected_date = '" . $start_date. "'"));
        }
	if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
            $sQuery = $sQuery->where(array('da_c.anc_site'=>base64_decode($parameters['anc'])));
        }
	if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
            $sQuery = $sQuery->where(array('da_c.lab'=>base64_decode($parameters['lab'])));
        }
	if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
	}
	//custom filter end
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->dataCollectionQuery = $sQuery;
       if (isset($sLimit) && isset($sOffset)) {
           $sQuery->limit($sLimit);
           $sQuery->offset($sOffset);
       }
       
       $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
       //echo $sQueryStr;die;
       $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

       /* Data set length after filtering */
       $sQuery->reset('limit');
       $sQuery->reset('offset');
       $fQuery = $sql->getSqlStringForSqlObject($sQuery);
       $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
       $iFilteredTotal = count($aResultFilterTotal);

       /* Total data set length */
	$tQuery = $sql->select()->from(array('da_c' => 'data_collection'))
	                        ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
				->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
				->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
				->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	                        //->where('da_c.status IN (2)');
	if($loginContainer->roleCode== 'LS'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if($loginContainer->roleCode== 'CC'){
	    $tQuery = $tQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	$tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
	$tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
	$iTotal = count($tResult);
	$output = array(
		   "sEcho" => intval($parameters['sEcho']),
		   "iTotalRecords" => $iTotal,
		   "iTotalDisplayRecords" => $iFilteredTotal,
		   "aaData" => array()
	);
	foreach ($rResult as $aRow) {
	    $row = array();
	    $specimenCollectedDate = '';
	    $specimenPickUpDateatAnc = '';
	    $receiptDateAtCentralLab = '';
	    $testCompletionDate = '';
	    $resultDispatchedDateToClinic = '';
	    if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		$specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
	    }if(isset($aRow['specimen_picked_up_date_at_anc']) && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
		$specimenPickUpDateatAnc = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
	    }if(isset($aRow['receipt_date_at_central_lab']) && trim($aRow['receipt_date_at_central_lab'])!= '' && $aRow['receipt_date_at_central_lab']!= '0000-00-00'){
		$receiptDateAtCentralLab = $common->humanDateFormat($aRow['receipt_date_at_central_lab']);
	    }if(isset($aRow['date_of_test_completion']) && trim($aRow['date_of_test_completion'])!= '' && $aRow['date_of_test_completion']!= '0000-00-00'){
		$testCompletionDate = $common->humanDateFormat($aRow['date_of_test_completion']);
	    }if(isset($aRow['result_dispatched_date_to_clinic']) && trim($aRow['result_dispatched_date_to_clinic'])!= '' && $aRow['result_dispatched_date_to_clinic']!= '0000-00-00'){
		$resultDispatchedDateToClinic = $common->humanDateFormat($aRow['result_dispatched_date_to_clinic']);
	    }
	    $lAgAvidityResult = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
	//    $hIVRNAResult = '';
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    $asanteRapidRecencyAssayPn = '';
	    $asanteRapidRecencyAssayRlt = '';
	    if(trim($aRow['asante_rapid_recency_assy'])!= ''){
		$asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
		if(isset($asanteRapidRecencyAssy['rrdt'])){
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?ucwords($asanteRapidRecencyAssy['rrdt']['assay']):'';
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
		}
	    }
	    
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = $specimenCollectedDate;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $aRow['gestational_age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = (isset($aRow['rejection_code']) && (int)$aRow['rejection_code'] > 1)?$aRow['rejection_code']:'';
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = $aRow['lab_tech_name'];
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = $asanteRapidRecencyAssayPn;
	    $row[] = $asanteRapidRecencyAssayRlt;
	    $row[] = ucfirst($aRow['comments']);
	    if($parameters['countryId']== ''){
	       $row[] = ucwords($aRow['country_name']);
	    }
	   $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchSearchableDataCollection($params){
	$loginContainer = new Container('user');
	$common = new CommonService();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$mappedLab = array();
	$uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
				   ->where(array('l_map.user_id'=>$loginContainer->userId));
	$uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
	$uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	//get all mapped lab
	foreach($uMapResult as $lab){
	    $mappedLab[] = $lab['laboratory_id'];
	}
	
	$start_date = '';
	$end_date = '';
	if(isset($params['specimenCollectedDate']) && trim($params['specimenCollectedDate'])!= ''){
	   $s_c_date = explode("to", $params['specimenCollectedDate']);
	   if(isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($s_c_date[0]));
	   }if(isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($s_c_date[1]));
	   }
	}
        $dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
	                           ->columns(array('data_collection_id','surveillance_id','lag_avidity_result','hiv_rna_gt_1000'))
				   ->where(array('da_c.status'=>2));
	if($loginContainer->roleCode== 'LS'){
	    if(trim($params['facility']) ==''){
	       $dataCollectionQuery = $dataCollectionQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	    }
	}else if($loginContainer->roleCode== 'LDEO'){
	    $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}if(trim($start_date) != "" && trim($end_date) != "" && trim($start_date)!= trim($end_date)) {
           $dataCollectionQuery = $dataCollectionQuery->where(array("da_c.specimen_collected_date >='" . $start_date ."'", "da_c.specimen_collected_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $dataCollectionQuery = $dataCollectionQuery->where(array("da_c.specimen_collected_date = '" . $start_date. "'"));
        }if(isset($params['chosenCountry']) && trim($params['chosenCountry'])!= ''){
            $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.country'=>base64_decode($params['chosenCountry'])));
        }if(isset($params['anc']) && trim($params['anc'])!= ''){
            $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.anc_site'=>base64_decode($params['anc'])));
        }if(isset($params['facility']) && trim($params['facility'])!= ''){
            $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.lab'=>base64_decode($params['facility'])));
        }if(isset($params['mailSentStatus']) && trim($params['mailSentStatus'])!= ''){
            $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.result_mail_sent'=>$params['mailSentStatus']));
        }
        $dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
       return $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchDashboardDetails($params){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'year' => new \Zend\Db\Sql\Expression("YEAR(da_c.added_on)"),
						   'month' => new \Zend\Db\Sql\Expression("MONTH(da_c.added_on)"),
						   'monthName' => new \Zend\Db\Sql\Expression("MONTHNAME(da_c.added_on)"),
						   'totalSample' => new \Zend\Db\Sql\Expression("COUNT(*)"),
						   'samplesIncomplete' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 4, 1,0))"),
						   'samplesTested' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 1 OR da_c.status = 2 OR da_c.status = 3, 1,0))"),
						   'samplesFinalized' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 2, 1,0))"),
						   'noofLAgRecent' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.lag_avidity_result = 'recent', 1,0))"),
						   'noofLabRecencyAssayRecent' => new \Zend\Db\Sql\Expression('SUM(IF(da_c.asante_rapid_recency_assy like \'%rrr":{"assay":"absent"%\', 1,0))')
						))
				   ->join(array('c'=>'country'),'c.country_id=da_c.country',array('country_id','country_name'))
				   ->where(array('c.country_status'=>'active'))
				   ->group(new \Zend\Db\Sql\Expression("YEAR(da_c.added_on)"))
				   ->group(new \Zend\Db\Sql\Expression("MONTHNAME(da_c.added_on)"))
				   ->group('da_c.country');
	if(trim($params['country'])!= ''){
	    $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.country'=>base64_decode($params['country'])));
	}else if($loginContainer->roleCode == 'CC'){
	    $dataCollectionQuery = $dataCollectionQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	} if(trim($params['reportingMonthYear'])!= ''){
	    $reportingMonthYearArray = explode("/",$params['reportingMonthYear']);
	    $dataCollectionQuery = $dataCollectionQuery->where('MONTH(da_c.added_on) ="'.date('m', strtotime($reportingMonthYearArray[0])).'" AND YEAR(da_c.added_on) ="'.$reportingMonthYearArray[1].'"');
	}
	$queryContainer->dashboardQuery = $dataCollectionQuery;
	$dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
        $dataCollectionResult = $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	if(isset($dataCollectionResult) && count($dataCollectionResult) >0){
	    $i=0;
	    foreach($dataCollectionResult as $dataCollection){
		 $riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
					    ->columns(array(
							    'assessments' => new \Zend\Db\Sql\Expression("COUNT(*)")
							 ))
					    ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('noofANCRecencyTestRecent' => new \Zend\Db\Sql\Expression("SUM(IF(anc_r_r.recency_line = 'recent', 1,0))")),'left')
					    ->where('r_a.country = '.$dataCollection['country_id'].' AND MONTH(r_a.interview_date) ="'.$dataCollection['month'].'" AND YEAR(r_a.interview_date) ="'.$dataCollection['year'].'"');
		 $riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($riskAssessmentQuery);
                 $dataCollectionResult[$i][$dataCollection['monthName'].' - '.$dataCollection['year']] = $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	     $i++;
	    }
	}
      return $dataCollectionResult;
    }
    
    public function fetchCountriesLabAncDetails($params){
	$countriesLabAnc = array();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$ancQuery = $sql->select()->from(array('anc' => 'anc_site'))
	                ->columns(array('anc_site_id','anc_site_name','anc_site_code'))
                        ->where(array('anc.country'=>base64_decode($params['country']),'anc.status'=>'active'));
        $ancQueryStr = $sql->getSqlStringForSqlObject($ancQuery);
        $countriesLabAnc['ancsites'] = $dbAdapter->query($ancQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	$facilitiesQuery = $sql->select()->from(array('f' => 'facility'))
	                       ->columns(array('facility_id','facility_name','facility_code'))
                               ->where(array('f.country'=>base64_decode($params['country']),'f.status'=>'active'));
        $facilitiesQueryStr = $sql->getSqlStringForSqlObject($facilitiesQuery);
        $countriesLabAnc['labs'] = $dbAdapter->query($facilitiesQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
      return $countriesLabAnc;
    }
    
    public function fetchPatientRecord($params){
	$redirectUrl = '';
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	if($params['type'] == 'lab'){
	    $patientQuery = $sql->select()->from(array('da_c' => 'data_collection'))->columns(array('data_collection_id','status'))
				->where(array('da_c.patient_barcode_id'=>trim($params['patientBarcodeId'])));
	    if(isset($params['dataCollectionID']) && trim($params['dataCollectionID'])!= ''){
                $patientQuery = $patientQuery->where('da_c.data_collection_id != "'.base64_decode($params['dataCollectionID']).'"');
            }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		$patientQuery = $patientQuery->where(array('da_c.country'=>base64_decode($params['countryId'])));
	    }else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		$patientQuery = $patientQuery->where(array('da_c.country'=>base64_decode($params['optCountryId'])));
	    }
	    $patientQueryStr = $sql->getSqlStringForSqlObject($patientQuery);
	    $patientResult = $dbAdapter->query($patientQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	    if($patientResult){
		if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		    if($patientResult->status == 2){
		      $redirectUrl = "/data-collection/view/".base64_encode($patientResult->data_collection_id)."/".$params['countryId'];
		    }else{
		      $redirectUrl = "/data-collection/edit/".base64_encode($patientResult->data_collection_id)."/".$params['countryId'];
		    }
		}else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		    if($patientResult->status == 2){ 
		      $redirectUrl = "/data-collection/view/".base64_encode($patientResult->data_collection_id)."/";
		    }else{
		      $redirectUrl = "/data-collection/edit/".base64_encode($patientResult->data_collection_id)."/";
		    }
		}
	    }
	}else if($params['type'] == 'clinic'){
	    $patientQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))->columns(array('assessment_id','status'))
				->where(array('r_a.patient_barcode_id'=>trim($params['patientBarcodeId'])));
	    if(isset($params['assessmentId']) && trim($params['assessmentId'])!= ''){
                $patientQuery = $patientQuery->where('r_a.assessment_id != "'.base64_decode($params['assessmentId']).'"');
            }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		$patientQuery = $patientQuery->where(array('r_a.country'=>base64_decode($params['countryId'])));
	    }else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		$patientQuery = $patientQuery->where(array('r_a.country'=>base64_decode($params['optCountryId'])));
	    }
	    $patientQueryStr = $sql->getSqlStringForSqlObject($patientQuery);
	    $patientResult = $dbAdapter->query($patientQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	    if($patientResult){
		if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		    if($patientResult->status == 2){
		      $redirectUrl = "/clinic/risk-assessment/view/".base64_encode($patientResult->assessment_id)."/".$params['countryId'];
		    }else{
		      $redirectUrl = "/clinic/risk-assessment/edit/".base64_encode($patientResult->assessment_id)."/".$params['countryId'];
		    }
		}else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		    if($patientResult->status == 2){
		       $redirectUrl = "/clinic/risk-assessment/view/".base64_encode($patientResult->assessment_id)."/";
		    }else{
		       $redirectUrl = "/clinic/risk-assessment/edit/".base64_encode($patientResult->assessment_id)."/";
		    }
		}
	    }
	}
       return $redirectUrl;
    }
    
    public function fecthAllLabLogbook($parameters){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
	$common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	
	$aColumns = array("DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')",'da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','lab_tech_name',"DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments');
	$orderColumns = array('receipt_date_at_central_lab','da_c.patient_barcode_id','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','lab_tech_name','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments');

       /*
        * Paging
        */
       $sLimit = "";
       if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
           $sOffset = $parameters['iDisplayStart'];
           $sLimit = $parameters['iDisplayLength'];
       }

       /*
        * Ordering
        */

       $sOrder = "";
       if (isset($parameters['iSortCol_0'])) {
           for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
               if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                   $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
               }
           }
           $sOrder = substr_replace($sOrder, "", -1);
       }

       /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

       $sWhere = "";
       if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
           $searchArray = explode(" ", $parameters['sSearch']);
           $sWhereSub = "";
           foreach ($searchArray as $search) {
               if ($sWhereSub == "") {
                   $sWhereSub .= "(";
               } else {
                   $sWhereSub .= " AND (";
               }
               $colSize = count($aColumns);

               for ($i = 0; $i < $colSize; $i++) {
                   if ($i < $colSize - 1) {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                   } else {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                   }
               }
               $sWhereSub .= ")";
           }
           $sWhere .= $sWhereSub;
       }

       /* Individual column filtering */
       for ($i = 0; $i < count($aColumns); $i++) {
           if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
               if ($sWhere == "") {
                   $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               } else {
                   $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               }
           }
       }

       /*
        * SQL queries
        * Get data to display
        */
       $start_date = '';
       $end_date = '';
       if(isset($parameters['receiptDateAtCentralLab']) && trim($parameters['receiptDateAtCentralLab'])!= ''){
	   $r_date = explode("to", $parameters['receiptDateAtCentralLab']);
	   if(isset($r_date[0]) && trim($r_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($r_date[0]));
	   }if(isset($r_date[1]) && trim($r_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($r_date[1]));
	   }
	}
       $dbAdapter = $this->adapter;
       $sql = new Sql($dbAdapter);
       $mappedLab = array();
       $uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                  ->where(array('l_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //get all mapped lab
       foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
       }
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode== 'LS'){
	    if(trim($parameters['lab']) ==''){
	       $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	    }
	}else if($loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if($loginContainer->roleCode== 'CC'){
	    $sQuery = $sQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	//custom filter start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("da_c.receipt_date_at_central_lab >='" . $start_date ."'", "da_c.receipt_date_at_central_lab <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.receipt_date_at_central_lab = '" . $start_date. "'"));
        }
	if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
            $sQuery = $sQuery->where(array('da_c.anc_site'=>base64_decode($parameters['anc'])));
        }
	if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
            $sQuery = $sQuery->where(array('da_c.lab'=>base64_decode($parameters['lab'])));
        }
	if(isset($parameters['status']) && trim($parameters['status'])== 'completed'){
           $sQuery = $sQuery->where('da_c.status = "2"');
        }else if(isset($parameters['status']) && trim($parameters['status'])== 'pending'){
	   $sQuery = $sQuery->where('da_c.status IN (1,3,4)'); 
	}
	//custom filter end
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->logbookQuery = $sQuery;
       if (isset($sLimit) && isset($sOffset)) {
           $sQuery->limit($sLimit);
           $sQuery->offset($sOffset);
       }
       
       $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
       //echo $sQueryStr;die;
       $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

       /* Data set length after filtering */
       $sQuery->reset('limit');
       $sQuery->reset('offset');
       $fQuery = $sql->getSqlStringForSqlObject($sQuery);
       $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
       $iFilteredTotal = count($aResultFilterTotal);

       /* Total data set length */
	$tQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				  ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
				  ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
				  ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode== 'LS'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if($loginContainer->roleCode== 'CC'){
	    $tQuery = $tQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	$tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
	$tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
	$iTotal = count($tResult);
	$output = array(
		   "sEcho" => intval($parameters['sEcho']),
		   "iTotalRecords" => $iTotal,
		   "iTotalDisplayRecords" => $iFilteredTotal,
		   "aaData" => array()
	);
	foreach ($rResult as $aRow) {
	    $row = array();
	    $specimenCollectedDate = '';
	    $specimenPickUpDateatAnc = '';
	    $receiptDateAtCentralLab = '';
	    $testCompletionDate = '';
	    $resultDispatchedDateToClinic = '';
	    if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		$specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
	    }if(isset($aRow['specimen_picked_up_date_at_anc']) && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
		$specimenPickUpDateatAnc = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
	    }if(isset($aRow['receipt_date_at_central_lab']) && trim($aRow['receipt_date_at_central_lab'])!= '' && $aRow['receipt_date_at_central_lab']!= '0000-00-00'){
		$receiptDateAtCentralLab = $common->humanDateFormat($aRow['receipt_date_at_central_lab']);
	    }if(isset($aRow['date_of_test_completion']) && trim($aRow['date_of_test_completion'])!= '' && $aRow['date_of_test_completion']!= '0000-00-00'){
		$testCompletionDate = $common->humanDateFormat($aRow['date_of_test_completion']);
	    }if(isset($aRow['result_dispatched_date_to_clinic']) && trim($aRow['result_dispatched_date_to_clinic'])!= '' && $aRow['result_dispatched_date_to_clinic']!= '0000-00-00'){
		$resultDispatchedDateToClinic = $common->humanDateFormat($aRow['result_dispatched_date_to_clinic']);
	    }
	    $lAgAvidityResult = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
	//    $hIVRNAResult = '';
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    $asanteRapidRecencyAssayPn = '';
	    $asanteRapidRecencyAssayRlt = '';
	    if(trim($aRow['asante_rapid_recency_assy'])!= ''){
		$asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
		if(isset($asanteRapidRecencyAssy['rrdt'])){
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?ucwords($asanteRapidRecencyAssy['rrdt']['assay']):'';
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?ucwords($asanteRapidRecencyAssy['rrr']['assay']):'';
		}
	    }
	    
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = $specimenCollectedDate;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $aRow['gestational_age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = (isset($aRow['rejection_code']) && (int)$aRow['rejection_code'] > 1)?$aRow['rejection_code']:'';
	    $row[] = $aRow['lab_tech_name'];
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = $asanteRapidRecencyAssayPn;
	    $row[] = $asanteRapidRecencyAssayRlt;
	    $row[] = ucfirst($aRow['comments']);
	   $output['aaData'][] = $row;
	}
       return $output;
    }
	
	
    public function rot47($str){
	$str = (isset($str['pId']))?$str['pId']:$str;
	if (!function_exists('str_rot47')) {
	  function str_rot47($str) {
		return strtr($str, 
		  '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 
		  'PQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNO'
		);
	  }
	}
      return str_rot47($str);
    }	
    
    public function fetchAllLabRecencyResult($parameters){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
	$common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	if($parameters['printSrc'] == 'anc'){
	    $db_Column = 'anc_print_status';
	    $aColumns = array('data_collection_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'da_c.status','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.comments','anc_print_status');
	    $orderColumns = array('data_collection_id','da_c.specimen_collected_date','da_c.status','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.comments','anc_print_status');
	}else{
	    $db_Column = 'lab_print_status';
	    $aColumns = array('data_collection_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'da_c.status','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.comments','lab_print_status');
	    $orderColumns = array('data_collection_id','da_c.specimen_collected_date','da_c.status','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.gestational_age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.comments','lab_print_status');
	}

       /*
        * Paging
        */
       $sLimit = "";
       if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
           $sOffset = $parameters['iDisplayStart'];
           $sLimit = $parameters['iDisplayLength'];
       }

       /*
        * Ordering
        */

       $sOrder = "";
       if (isset($parameters['iSortCol_0'])) {
           for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
               if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                   $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
               }
           }
           $sOrder = substr_replace($sOrder, "", -1);
       }

       /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

       $sWhere = "";
       if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
	   $printed = 'Printed printed';
           $searchArray = explode(" ", $parameters['sSearch']);
           $sWhereSub = "";
           foreach ($searchArray as $search) {
               if ($sWhereSub == "") {
                   $sWhereSub .= "(";
               } else {
                   $sWhereSub .= " AND (";
               }
               $colSize = count($aColumns);

               for ($i = 0; $i < $colSize; $i++) {
                   if ($i < $colSize - 1) {
		       if($aColumns[$i] == $db_Column && strpos($printed,$search) !== false){
		          $sWhereSub .= $aColumns[$i] . " = 1 OR ";
		       }else{
                          $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
		       }
                   } else {
		       if($aColumns[$i] == $db_Column && strpos($printed,$search) !== false){
		          $sWhereSub .= $aColumns[$i] . " = 1 ";
		       }else{
                          $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
		       }
                   }
               }
               $sWhereSub .= ")";
           }
           $sWhere .= $sWhereSub;
       }

       /* Individual column filtering */
       for ($i = 0; $i < count($aColumns); $i++) {
           if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
               if ($sWhere == "") {
                   $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               } else {
                   $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               }
           }
       }

       /*
        * SQL queries
        * Get data to display
        */
       $start_date = '';
       $end_date = '';
       if(isset($parameters['specimenCollectedDate']) && trim($parameters['specimenCollectedDate'])!= ''){
	   $s_c_date = explode("to", $parameters['specimenCollectedDate']);
	   if(isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($s_c_date[0]));
	   }if(isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($s_c_date[1]));
	   }
	}
       $dbAdapter = $this->adapter;
       $sql = new Sql($dbAdapter);
       $mappedANC = array();
       $uMapQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
                                  ->where(array('cl_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped ANC
       foreach($uMapResult as $anc){
	   $mappedANC[] = $anc['clinic_id'];
       }
       $mappedLab = array();
       $uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                  ->where(array('l_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped lab
       foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
       }
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
		     ->join(array('l_d' => 'location_details'), "l_d.location_id=f.district",array('location_name'),'left')
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode == 'ANCSC' && trim($parameters['anc']) == ''){
            $sQuery = $sQuery->where('da_c.anc_site IN ("' . implode('", "', $mappedANC) . '")');
        }else if($loginContainer->roleCode== 'LS' && trim($parameters['lab']) == ''){
	    $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}else if($loginContainer->roleCode== 'LDEO' && trim($parameters['lab']) == ''){
	    $sQuery = $sQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));
	}else if($loginContainer->roleCode== 'CC'){
	    $sQuery = $sQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	//custom filter start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("da_c.specimen_collected_date >='" . $start_date ."'", "da_c.specimen_collected_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.specimen_collected_date = '" . $start_date. "'"));
        }
	if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
            $sQuery = $sQuery->where(array('da_c.anc_site'=>base64_decode($parameters['anc'])));
        }
	if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
            $sQuery = $sQuery->where(array('da_c.lab'=>base64_decode($parameters['lab'])));
        }
	//custom filter end
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->labRecencyResultQuery = $sQuery;
       if (isset($sLimit) && isset($sOffset)) {
           $sQuery->limit($sLimit);
           $sQuery->offset($sOffset);
       }
       
       $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
       //echo $sQueryStr;die;
       $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

       /* Data set length after filtering */
       $sQuery->reset('limit');
       $sQuery->reset('offset');
       $fQuery = $sql->getSqlStringForSqlObject($sQuery);
       $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
       $iFilteredTotal = count($aResultFilterTotal);

       /* Total data set length */
	$tQuery = $sql->select()->from(array('da_c' => 'data_collection'))
	                        ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
				->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
				->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
				->join(array('l_d' => 'location_details'), "l_d.location_id=f.district",array('location_name'),'left')
				->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode == 'ANCSC'){
            $tQuery = $tQuery->where('da_c.anc_site IN ("' . implode('", "', $mappedANC) . '")');
        }else if($loginContainer->roleCode== 'LS'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if($loginContainer->roleCode== 'CC'){
	    $tQuery = $tQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	$tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
	$tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
	$iTotal = count($tResult);
	$output = array(
		   "sEcho" => intval($parameters['sEcho']),
		   "iTotalRecords" => $iTotal,
		   "iTotalDisplayRecords" => $iFilteredTotal,
		   "aaData" => array()
	);
	foreach ($rResult as $aRow) {
	    $specimenCollectedDate = '';
	    $specimenPickUpDateatAnc = '';
	    $receiptDateAtCentralLab = '';
	    $testCompletionDate = '';
	    $resultDispatchedDateToClinic = '';
	    if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		$specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
	    }if(isset($aRow['specimen_picked_up_date_at_anc']) && trim($aRow['specimen_picked_up_date_at_anc'])!= '' && $aRow['specimen_picked_up_date_at_anc']!= '0000-00-00'){
		$specimenPickUpDateatAnc = $common->humanDateFormat($aRow['specimen_picked_up_date_at_anc']);
	    }if(isset($aRow['receipt_date_at_central_lab']) && trim($aRow['receipt_date_at_central_lab'])!= '' && $aRow['receipt_date_at_central_lab']!= '0000-00-00'){
		$receiptDateAtCentralLab = $common->humanDateFormat($aRow['receipt_date_at_central_lab']);
	    }if(isset($aRow['date_of_test_completion']) && trim($aRow['date_of_test_completion'])!= '' && $aRow['date_of_test_completion']!= '0000-00-00'){
		$testCompletionDate = $common->humanDateFormat($aRow['date_of_test_completion']);
	    }if(isset($aRow['result_dispatched_date_to_clinic']) && trim($aRow['result_dispatched_date_to_clinic'])!= '' && $aRow['result_dispatched_date_to_clinic']!= '0000-00-00'){
		$resultDispatchedDateToClinic = $common->humanDateFormat($aRow['result_dispatched_date_to_clinic']);
	    }
	    $lAgAvidityResult = ($aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
	//    $hIVRNAResult = '';
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    //status
	    $status = ucwords($aRow['test_status_name']);
	    if($aRow['final_lag_avidity_odn'] <= 2 && (trim($aRow['hiv_rna']) == '' || $aRow['hiv_rna'] == null)){
		$status = 'Results Awaited';
	    }
	    //individual result pdf
	    $pdfLink = '<a href="javascript:void(0);" onclick="printLabResult(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-1" title="PDF"><i class="zmdi zmdi-collection-pdf"></i> PDF</a>';
	    $row = array();
	    $row[] = '<input type="checkbox" name="data-select[]" id="'.$aRow['data_collection_id'].'" value="'.base64_encode($aRow['data_collection_id']).'" onchange="doMultipleDataSelect(this);"/><label for="'.$aRow['data_collection_id'].'" style="padding-left:40%;"></label>';
	    $row[] = $specimenCollectedDate;
	    $row[] = $status;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $aRow['gestational_age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = (isset($aRow['rejection_code']) && (int)$aRow['rejection_code'] > 1)?$aRow['rejection_code']:'';
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = ucfirst($aRow['comments']);
	    if($parameters['printSrc'] == 'anc'){
	       $row[] = ((int)$aRow['anc_print_status'] == 1)?'Printed':'';
	    }else{
	       $row[] = ((int)$aRow['lab_print_status'] == 1)?'Printed':'';
	    }
	    $row[] = $pdfLink;
	   $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchCountryLabDataReportingDetails($params){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
				                   'country',
						   'year' => new \Zend\Db\Sql\Expression("YEAR(".$params['labDataReportingDate'].")"),
						   'month' => new \Zend\Db\Sql\Expression("MONTH(".$params['labDataReportingDate'].")"),
						   'monthName' => new \Zend\Db\Sql\Expression("MONTHNAME(".$params['labDataReportingDate'].")"),
						   'totalSample' => new \Zend\Db\Sql\Expression("COUNT(*)"),
						   'noofANCSites' => new \Zend\Db\Sql\Expression("COUNT(DISTINCT(da_c.anc_site))"),
						   'samplesIncomplete' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 4, 1,0))"),
						   'samplesTested' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 1 OR da_c.status = 2 OR da_c.status = 3, 1,0))"),
						   'samplesFinalized' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 2, 1,0))"),
						   'noofLAgRecent' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.lag_avidity_result = 'recent', 1,0))"),
						   'noofLabRecencyAssayRecent' => new \Zend\Db\Sql\Expression('SUM(IF(da_c.asante_rapid_recency_assy like \'%rrr":{"assay":"absent"%\', 1,0))')
						))
				   ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array('facility_id','facility_name'))
				   ->where(array('da_c.country'=>$params['country']))
				   ->group(new \Zend\Db\Sql\Expression("YEAR(".$params['labDataReportingDate'].")"))
				   ->group(new \Zend\Db\Sql\Expression("MONTHNAME(".$params['labDataReportingDate'].")"))
				   ->group('da_c.lab')
				   ->order($params['labDataReportingDate'].' asc');
	if($loginContainer->roleCode== 'LS'){
	    $dataCollectionQuery = $dataCollectionQuery->where('da_c.lab IN ("' . implode('", "', $loginContainer->laboratory) . '")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.added_by'=>$loginContainer->userId));
	}
	if(trim($params['reportingMonthYear'])!= ''){
	    $reportingMonthYearArray = explode("/",$params['reportingMonthYear']);
	    $dataCollectionQuery = $dataCollectionQuery->where('MONTH('.$params['labDataReportingDate'].') ="'.date('m', strtotime($reportingMonthYearArray[0])).'" AND YEAR('.$params['labDataReportingDate'].') ="'.$reportingMonthYearArray[1].'"');
	}
	if(trim($params['province'])!= ''){
	    $dataCollectionQuery = $dataCollectionQuery->where(array('f.province'=>base64_decode($params['province'])));
	}
	if(trim($params['specimenType'])!= ''){
	    $dataCollectionQuery = $dataCollectionQuery->where('da_c.specimen_type IN('.$params['specimenType'].')');
	}
	$queryContainer->countryLabDataReportingQuery = $dataCollectionQuery;
	$dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
      return $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchCountryClinicDataReportingDetails($params){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
				   ->columns(array(
				                   'country',
						   'year' => new \Zend\Db\Sql\Expression("YEAR(".$params['clinicDataReportingDate'].")"),
						   'month' => new \Zend\Db\Sql\Expression("MONTH(".$params['clinicDataReportingDate'].")"),
						   'monthName' => new \Zend\Db\Sql\Expression("MONTHNAME(".$params['clinicDataReportingDate'].")"),
						   'assessments' => new \Zend\Db\Sql\Expression("COUNT(*)"),
						   'noofANCSites' => new \Zend\Db\Sql\Expression("COUNT(DISTINCT(r_a.anc))")
						 ))
				   ->join(array('anc'=>'anc_site'),'anc.anc_site_id=r_a.anc',array())
				   ->join(array('l_d'=>'location_details'),'l_d.location_id=anc.province',array('location_id','location_name'))
				   ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('noofANCRecencyTestRecent' => new \Zend\Db\Sql\Expression("SUM(IF(anc_r_r.recency_line = 'recent', 1,0))")),'left')
				   ->where(array('r_a.country'=>$params['country']))
				   ->group(new \Zend\Db\Sql\Expression("YEAR(".$params['clinicDataReportingDate'].")"))
				   ->group(new \Zend\Db\Sql\Expression("MONTHNAME(".$params['clinicDataReportingDate'].")"))
				   ->group('anc.province')
				   ->order($params['clinicDataReportingDate'].' asc');
	if($loginContainer->roleCode== 'ANCSC'){
	    $riskAssessmentQuery = $riskAssessmentQuery->where(array('r_a.added_by'=>$loginContainer->userId));
	}
	if(trim($params['reportingMonthYear'])!= ''){
	    $reportingMonthYearArray = explode("/",$params['reportingMonthYear']);
	    $riskAssessmentQuery = $riskAssessmentQuery->where('MONTH('.$params['clinicDataReportingDate'].') ="'.date('m', strtotime($reportingMonthYearArray[0])).'" AND YEAR('.$params['clinicDataReportingDate'].') ="'.$reportingMonthYearArray[1].'"');
	}
	if(trim($params['province'])!= ''){
	    $riskAssessmentQuery = $riskAssessmentQuery->where(array('anc.province'=>base64_decode($params['province'])));
	}
	$queryContainer->countryClinicDataReportingQuery = $riskAssessmentQuery;
	$riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($riskAssessmentQuery);
      return $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchDataReportingLocations($params){
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$location = array();
	$sitehavingRecentInfectionbyArray = array();
	//by default to show all recent points
	$showLabRecent = true;
	$showANCRecent = true;
	if(trim($params['sitehavingRecentInfectionby'])!= ''){
	    $showLabRecent = false;
	    $showANCRecent = false;
	    $sitehavingRecentInfectionbyArray = explode(",",$params['sitehavingRecentInfectionby']);
	    if(in_array('labLAgRecency',$sitehavingRecentInfectionbyArray) || in_array('labRecencyAssay',$sitehavingRecentInfectionbyArray)){
		$showLabRecent = true;
	    } if(in_array('ancRecencyAssay',$sitehavingRecentInfectionbyArray)){
		$showANCRecent = true;
	    }
	}
	//Show lab LAg/asante recent points
	if($showLabRecent){
	    $labRecentQuery = $sql->select()->from(array('anc' => 'anc_site'))
				  ->columns(array('anc_site_name','latitude','longitude'))
				  ->join(array('da_c'=>'data_collection'),'da_c.anc_site=anc.anc_site_id',array('noofLAgRecent' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.lag_avidity_result = 'recent', 1,0))"),'noofLabRecencyAssayRecent' => new \Zend\Db\Sql\Expression('SUM(IF(da_c.asante_rapid_recency_assy like \'%rrr":{"assay":"absent"%\', 1,0))')),'left')
				  ->where(array('anc.country'=>$params['country']))
				  ->group('anc.anc_site_id');
	    if(trim($params['reportingMonthYear'])!= ''){
		$reportingMonthYearArray = explode("/",$params['reportingMonthYear']);
		$labRecentQuery = $labRecentQuery->where('MONTH(da_c.added_on) ="'.date('m', strtotime($reportingMonthYearArray[0])).'" AND YEAR(da_c.added_on) ="'.$reportingMonthYearArray[1].'"');
	    } if(trim($params['province'])!= ''){
		$labRecentQuery = $labRecentQuery->where(array('anc.province'=>base64_decode($params['province'])));
	    } if(trim($params['specimenType'])!= ''){
		$labRecentQuery = $labRecentQuery->where('da_c.specimen_type IN('.$params['specimenType'].')');
	    } if(in_array('labLAgRecency',$sitehavingRecentInfectionbyArray) || in_array('labRecencyAssay',$sitehavingRecentInfectionbyArray)){
		$mapWhere = '';
		$mapOR = ' OR ';
		for($i=0;$i<count($sitehavingRecentInfectionbyArray);$i++){
		    if($sitehavingRecentInfectionbyArray[$i] == 'labLAgRecency'){
			if($i == 1){ $mapWhere.= $mapOR; }
			$mapWhere.= 'da_c.lag_avidity_result = "recent"';
		    }else if($sitehavingRecentInfectionbyArray[$i] == 'labRecencyAssay'){
		       if($i == 1){ $mapWhere.= $mapOR; }
		       $mapWhere.= 'da_c.asante_rapid_recency_assy like \'%rrr":{"assay":"absent"%\'';
		    }
		}
		if(trim($mapWhere)!= ''){
		  $labRecentQuery = $labRecentQuery->where('('.$mapWhere.')');
		}
	    }
	    $labRecentQueryStr = $sql->getSqlStringForSqlObject($labRecentQuery);
	    $location['labRecent'] = $dbAdapter->query($labRecentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	}
	//Show ANC recent points
	if($showANCRecent){
	    $ancRecentQuery = $sql->select()->from(array('anc' => 'anc_site'))
				  ->columns(array('anc_site_name','latitude','longitude'))
				  ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.anc=anc.anc_site_id',array(),'left')
				  ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('noofANCRecencyTestRecent' => new \Zend\Db\Sql\Expression("SUM(IF(anc_r_r.recency_line = 'recent', 1,0))")),'left')
				  ->join(array('da_c'=>'data_collection'),'da_c.patient_barcode_id=r_a.patient_barcode_id',array(),'left')
				  ->where(array('anc.country'=>$params['country']))
				  ->group('anc.anc_site_id');
	    if(trim($params['reportingMonthYear'])!= ''){
		$reportingMonthYearArray = explode("/",$params['reportingMonthYear']);
		$ancRecentQuery = $ancRecentQuery->where('MONTH(r_a.interview_date) ="'.date('m', strtotime($reportingMonthYearArray[0])).'" AND YEAR(r_a.interview_date) ="'.$reportingMonthYearArray[1].'"');
	    } if(trim($params['province'])!= ''){
		$ancRecentQuery = $ancRecentQuery->where(array('anc.province'=>base64_decode($params['province'])));
	    } if(trim($params['specimenType'])!= ''){
		$ancRecentQuery = $ancRecentQuery->where('da_c.specimen_type IN('.$params['specimenType'].')');
	    } /*if(trim($params['hasSitePerformedRapidRecencyTest'])!= ''){
		$ancRecentQuery = $ancRecentQuery->where(array('anc_r_r.has_patient_had_rapid_recency_test'=>$params['hasSitePerformedRapidRecencyTest']));
	    }*/ if(in_array('ancRecencyAssay',$sitehavingRecentInfectionbyArray)){
		$ancRecentQuery = $ancRecentQuery->where(array('anc_r_r.recency_line'=>'recent'));
	    }
	    $ancRecentQueryStr = $sql->getSqlStringForSqlObject($ancRecentQuery);
	    $location['ancRecent'] = $dbAdapter->query($ancRecentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	}
      return $location;
    }
    
    public function fetchStudyOverviewData($parameters){
	$queryContainer = new Container('query');
	$common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$aColumns = array('location_name','patient_barcode_id',"DATE_FORMAT(specimen_collected_date,'%d-%b-%Y')",'anc_site_name','anc_site_code',"DATE_FORMAT(specimen_picked_up_date_at_anc,'%d-%b-%Y')",'specimen_type','anc_patient_id','art_patient_id',"DATE_FORMAT(patient_dob,'%d-%b-%Y')",'age','gestational_age','facility_name','rejection_reason',"DATE_FORMAT(receipt_date_at_central_lab,'%d-%b-%Y')",'lab_tech_name',"DATE_FORMAT(date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(result_dispatched_date_to_clinic,'%d-%b-%Y')",'final_lag_avidity_odn','lag_avidity_result','hiv_rna','recent_infection','asante_rapid_recency_assy','asante_rapid_recency_assy','has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line','test_status_name');
	$orderColumns = array('location_name','patient_barcode_id','specimen_collected_date','anc_site_name','specimen_picked_up_date_at_anc','specimen_type','anc_patient_id','art_patient_id','patient_dob','age','gestational_age','facility_name','rejection_reason','receipt_date_at_central_lab','lab_tech_name','date_of_test_completion','result_dispatched_date_to_clinic','final_lag_avidity_odn','lag_avidity_result','hiv_rna','recent_infection','asante_rapid_recency_assy','asante_rapid_recency_assy','has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line','test_status_name','assessment_id');
       /*
        * Paging
        */
       $sLimit = "";
       if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
           $sOffset = $parameters['iDisplayStart'];
           $sLimit = $parameters['iDisplayLength'];
       }

       /*
        * Ordering
        */

       $sOrder = "";
       if (isset($parameters['iSortCol_0'])) {
           for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
               if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                   $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
               }
           }
           $sOrder = substr_replace($sOrder, "", -1);
       }

       /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

       $sWhere = "";
       if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
	   $absent = 'Absent absent Negative negative Recent recent';
	   $present = 'Present present Positive positive Long Term long term';
           $notDone = 'Not Done not done';
           $searchArray = explode(" ", $parameters['sSearch']);
           $sWhereSub = "";
           foreach ($searchArray as $search) {
               if ($sWhereSub == "") {
                   $sWhereSub .= "(";
               } else {
                   $sWhereSub .= " AND (";
               }
               $colSize = count($aColumns);
               
               for ($i = 0; $i < $colSize; $i++) {
                   if ($i < $colSize - 1) {
			if($aColumns[$i] == 'recency_line' && strpos($absent,$search) !== false){
			   $search = 'recent';	
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
			}else if($aColumns[$i] == 'recency_line' && strpos($present,$search) !== false){
			    $search = 'long term';	
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
			}else if(($aColumns[$i] == 'HIV_diagnostic_line' || $aColumns[$i] == 'recency_line') && strpos($notDone,$search) !== false){	
			   $sWhereSub .= $aColumns[$i] . " = '' OR ".$aColumns[$i] . " IS NULL OR ";
			}else{
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
			}
                   } else {
			if($aColumns[$i] == 'recency_line' && strpos($absent,$search) !== false){
			   $search = 'recent';
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
			}else if($aColumns[$i] == 'recency_line' && strpos($present,$search) !== false){
			   $search = 'long term';	
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
			}else if(($aColumns[$i] == 'HIV_diagnostic_line' || $aColumns[$i] == 'recency_line') && strpos($notDone,$search) !== false){	
			   $sWhereSub .= $aColumns[$i] . " = '' OR ".$aColumns[$i] . " IS NULL ";
			}else{
			    $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
			}
                   }
               }
               $sWhereSub .= ")";
           }
           $sWhere .= $sWhereSub;
       }

       /* Individual column filtering */
       for ($i = 0; $i < count($aColumns); $i++) {
           if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
               if ($sWhere == "") {
                   $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               } else {
                   $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               }
           }
       }

       /*
        * SQL queries
        * Get data to display
        */
        $s_c_start_date = '';
        $s_c_end_date = '';
        if(isset($parameters['sampleCollectedDate']) && trim($parameters['sampleCollectedDate'])!= ''){
	   $s_c_date = explode("to", $parameters['sampleCollectedDate']);
	   if(isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
	     $s_c_start_date = $common->dateRangeFormat(trim($s_c_date[0]));
	   }if(isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
	     $s_c_end_date = $common->dateRangeFormat(trim($s_c_date[1]));
	   }
	}
	$s_t_start_date = '';
        $s_t_end_date = '';
        if(isset($parameters['sampleTestedDate']) && trim($parameters['sampleTestedDate'])!= ''){
	   $s_t_date = explode("to", $parameters['sampleTestedDate']);
	   if(isset($s_t_date[0]) && trim($s_t_date[0]) != "") {
	     $s_t_start_date = $common->dateRangeFormat(trim($s_t_date[0]));
	   }if(isset($s_t_date[1]) && trim($s_t_date[1]) != "") {
	     $s_t_end_date = $common->dateRangeFormat(trim($s_t_date[1]));
	   }
	}
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $select1 = $sql->select()->from(array('da_c' => 'data_collection'))
				 ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('r_assessment_id'=>'assessment_id','r_patient_barcode_id'=>'patient_barcode_id','r_country'=>'country'),'left')
				 ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line'),'left')
				 ->join(array('r_anc'=>'anc_site'),'r_anc.anc_site_id=r_a.anc',array('r_anc_site_code'=>'anc_site_code','r_anc_site_name'=>'anc_site_name'),'left')
				 ->join(array('anc'=>'anc_site'),'anc.anc_site_id=da_c.anc_site',array('anc_site_code','anc_site_name'),'left')
				 ->join(array('t'=>'test_status'),'t.test_status_id=da_c.status',array('test_status_name'),'left')
				 ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array('facility_name','province'),'left')
				 ->join(array('l_d'=>'location_details'),'l_d.location_id=f.province',array('location_name'),'left')
				 ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejectionReasonName'=>'rejection_reason'),'left')
				 ->where('da_c.country = '.$parameters['country'].' OR r_a.country = '.$parameters['country']);
	$select2 = $sql->select()->from(array('da_c' => 'data_collection'))
				 ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('r_assessment_id'=>'assessment_id','r_patient_barcode_id'=>'patient_barcode_id','r_country'=>'country'),'right')
				 ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line'),'left')
				 ->join(array('r_anc'=>'anc_site'),'r_anc.anc_site_id=r_a.anc',array('r_anc_site_code'=>'anc_site_code','r_anc_site_name'=>'anc_site_name'),'left')
				 ->join(array('anc'=>'anc_site'),'anc.anc_site_id=da_c.anc_site',array('anc_site_code','anc_site_name'),'left')
				 ->join(array('t'=>'test_status'),'t.test_status_id=da_c.status',array('test_status_name'),'left')
				 ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array('facility_name','province'),'left')
				 ->join(array('l_d'=>'location_details'),'l_d.location_id=f.province',array('location_name'),'left')
				 ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejectionReasonName'=>'rejection_reason'),'left')
				 ->where('da_c.country = '.$parameters['country'].' OR r_a.country = '.$parameters['country']);
	$select1->combine($select2);
	$sQuery = $sql->select()->from(array('result' => $select1));
	//custom filter start
	if(trim($s_c_start_date) != "" && trim($s_c_start_date)!= trim($s_c_end_date)) {
           $sQuery = $sQuery->where(array("specimen_collected_date >='" . $s_c_start_date ."'", "specimen_collected_date <='" . $s_c_end_date."'"));
        }else if (trim($s_c_start_date) != "") {
            $sQuery = $sQuery->where(array("specimen_collected_date = '" . $s_c_start_date. "'"));
        } if(trim($s_t_start_date) != "" && trim($s_t_start_date)!= trim($s_t_end_date)) {
           $sQuery = $sQuery->where(array("date_of_test_completion >='" . $s_t_start_date ."'", "date_of_test_completion <='" . $s_t_end_date."'"));
        }else if (trim($s_t_start_date) != "") {
            $sQuery = $sQuery->where(array("date_of_test_completion = '" . $s_t_start_date. "'"));
        } if(trim($parameters['province'])!= ''){
	    $sQuery = $sQuery->where(array('province'=>base64_decode($parameters['province'])));
	} if(trim($parameters['specimenType'])!= ''){
	    $sQuery = $sQuery->where('specimen_type IN('.$parameters['specimenType'].')');
	} if(trim($parameters['finalLagAvidityOdn'])!= '' && $parameters['finalLagAvidityOdn'] == 'lt2'){
	    $sQuery = $sQuery->where('final_lag_avidity_odn <= 2');
	}else if(trim($parameters['finalLagAvidityOdn'])!= '' && $parameters['finalLagAvidityOdn'] == 'gt2'){
	    $sQuery = $sQuery->where('final_lag_avidity_odn > 2');
	} if(trim($parameters['hivRna'])!= '' && $parameters['hivRna'] == 'lte1000'){
	    $sQuery = $sQuery->where('hiv_rna <= 1000');
	}else if(trim($parameters['hivRna'])!= '' && $parameters['hivRna'] == 'gt1000'){
	    $sQuery = $sQuery->where('hiv_rna > 1000');
	} if(trim($parameters['asanteRapidRecencyAssayRlt'])!= ''){
	    $sQuery = $sQuery->where('asante_rapid_recency_assy like "%'.$parameters['asanteRapidRecencyAssayRlt'].'%"');
	}
	//custom filter end
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->overviewQuery = $sQuery;
       if (isset($sLimit) && isset($sOffset)) {
           $sQuery->limit($sLimit);
           $sQuery->offset($sOffset);
       }
       
       $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
       //echo $sQueryStr;die;
       $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

       /* Data set length after filtering */
       $sQuery->reset('limit');
       $sQuery->reset('offset');
       $fQuery = $sql->getSqlStringForSqlObject($sQuery);
       $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
       $iFilteredTotal = count($aResultFilterTotal);

       /* Total data set length */
	$select1 = $sql->select()->from(array('da_c' => 'data_collection'))
				 ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('r_assessment_id'=>'assessment_id','r_patient_barcode_id'=>'patient_barcode_id','r_country'=>'country'),'left')
				 ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line'),'left')
				 ->join(array('r_anc'=>'anc_site'),'r_anc.anc_site_id=r_a.anc',array('r_anc_site_code'=>'anc_site_code','r_anc_site_name'=>'anc_site_name'),'left')
				 ->join(array('anc'=>'anc_site'),'anc.anc_site_id=da_c.anc_site',array('anc_site_code','anc_site_name'),'left')
				 ->join(array('t'=>'test_status'),'t.test_status_id=da_c.status',array('test_status_name'),'left')
				 ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array('facility_name','province'),'left')
				 ->join(array('l_d'=>'location_details'),'l_d.location_id=f.province',array('location_name'),'left')
				 ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejectionReasonName'=>'rejection_reason'),'left')
				 ->where('da_c.country = '.$parameters['country'].' OR r_a.country = '.$parameters['country']);
	$select2 = $sql->select()->from(array('da_c' => 'data_collection'))
				 ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('r_assessment_id'=>'assessment_id','r_patient_barcode_id'=>'patient_barcode_id','r_country'=>'country'),'right')
				 ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line'),'left')
				 ->join(array('r_anc'=>'anc_site'),'r_anc.anc_site_id=r_a.anc',array('r_anc_site_code'=>'anc_site_code','r_anc_site_name'=>'anc_site_name'),'left')
				 ->join(array('anc'=>'anc_site'),'anc.anc_site_id=da_c.anc_site',array('anc_site_code','anc_site_name'),'left')
				 ->join(array('t'=>'test_status'),'t.test_status_id=da_c.status',array('test_status_name'),'left')
				 ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array('facility_name','province'),'left')
				 ->join(array('l_d'=>'location_details'),'l_d.location_id=f.province',array('location_name'),'left')
				 ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejectionReasonName'=>'rejection_reason'),'left')
				 ->where('da_c.country = '.$parameters['country'].' OR r_a.country = '.$parameters['country']);
	$select1->combine($select2);
	$tQuery = $sql->select()->from(array('result' => $select1));
	$tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
	$tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
	$iTotal = count($tResult);
	$output = array(
	    "sEcho" => intval($parameters['sEcho']),
	    "iTotalRecords" => $iTotal,
	    "iTotalDisplayRecords" => $iFilteredTotal,
	    "aaData" => array()
	);
	foreach ($rResult as $aRow) {
	    $ancSiteName = '';
	    $patientBarcodeID = '';
	    $sampleType = '';
	    $specimenCollectedDate = '';
	    $specimenPickedupDateatANC = '';
	    $dob = '';
	    $receiptDateatLab = '';
	    $resultDispatchedDatetoClinic = '';
	    $dateofTestCompletion = '';
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
	    //status
	    if(isset($aRow['test_status_name']) && $aRow['test_status_name']!= null && trim($aRow['test_status_name'])!= ''){
	       $status = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($aRow['country']) . '"target="_blank" title="View data"> '.ucfirst($aRow['test_status_name']).'</a>';
	    }
	    //LAg assay
	    $lagResult = (isset($aRow['lag_avidity_result']) && $aRow['lag_avidity_result']!= null && trim($aRow['lag_avidity_result'])!= '')?ucwords($aRow['lag_avidity_result']):'';
	    //HIV rna values
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    //rapid assay
	    if(isset($aRow['asante_rapid_recency_assy']) && $aRow['asante_rapid_recency_assy']!= null && trim($aRow['asante_rapid_recency_assy'])!= ''){
		$asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
		if(isset($asanteRapidRecencyAssy['rrdt'])){
		    $rapidRecencyAssay = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?ucwords($asanteRapidRecencyAssy['rrdt']['assay']):'';
		}if(isset($asanteRapidRecencyAssy['rrr'])){
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
	    $row[] = (isset($aRow['location_name']) && $aRow['location_name']!= null && trim($aRow['location_name'])!= '')?ucwords($aRow['location_name']):'';
	    $row[] = $patientBarcodeID;
	    $row[] = $specimenCollectedDate;
	    $row[] = $ancSiteName;
	    $row[] = $specimenPickedupDateatANC;
	    $row[] = $sampleType;
	    $row[] = (isset($aRow['anc_patient_id']))?$aRow['anc_patient_id']:'';
	    $row[] = (isset($aRow['art_patient_id']))?$aRow['art_patient_id']:'';
	    $row[] = $dob;
	    $row[] = (isset($aRow['age']))?$aRow['age']:'';
	    $row[] = (isset($aRow['gestational_age']))?$aRow['gestational_age']:'';
	    $row[] = (isset($aRow['facility_name']))?ucwords($aRow['facility_name']):'';
	    $row[] = (isset($aRow['rejection_code']) && $aRow['rejection_code'] > 1)?ucwords($aRow['rejectionReasonName']):'';
	    $row[] = $receiptDateatLab;
	    $row[] = (isset($aRow['lab_tech_name']))?ucwords($aRow['lab_tech_name']):'';
	    $row[] = $dateofTestCompletion;
	    $row[] = $resultDispatchedDatetoClinic;
	    $row[] = (isset($aRow['final_lag_avidity_odn']))?$aRow['final_lag_avidity_odn']:'';
	    $row[] = $lagResult;
	    $row[] = (isset($aRow['hiv_rna']) && $aRow['hiv_rna']!= null && trim($aRow['hiv_rna'])!= '')?$aRow['hiv_rna']:'';
	    //$row[] = $hIVRNAResult;
	    $row[] = (isset($aRow['recent_infection']) && $aRow['recent_infection']!= null && trim($aRow['recent_infection'])!= '')?ucfirst($aRow['recent_infection']):'';
	    $row[] = $rapidRecencyAssay;
	    $row[] = $rapidRecencyAssayDuration;
	    $row[] = $ancHIVVerificationClassification;
	    $row[] = $ancRecencyVerificationClassification;
	    $row[] = $status;
	    $row[] = (isset($aRow['r_assessment_id']) && $aRow['r_assessment_id']!= null && trim($aRow['r_assessment_id'])!= '')?'<a href="/clinic/risk-assessment/view/' . base64_encode($aRow['r_assessment_id']). '/' . base64_encode($aRow['r_country']) . '" style="text-decoration:underline;" target="_blank" title="View data"> Yes</a>':'No';
	    $output['aaData'][] = $row;
	}
      return $output;
    }
    
    public function fecthSummaryDetails(){
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$select1 = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'labTestCompleted' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 1 OR da_c.status = 2 OR da_c.status = 3, 1,0))"),
						   'labTestIncompletebyToday' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 4 AND DATE(specimen_collected_date) = CURDATE(), 1,0))"),
						))
				   ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('totalBD'=>null,'bdIncompletebyToday' => null),'left')
				   ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('totalANCRecencyTestRecent'=>null),'left');
	$select2 = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'labTestCompleted' =>null,
						   'labTestIncompletebyToday' =>null,
						))
				   ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('totalBD'=>new \Zend\Db\Sql\Expression("COUNT(*)"),'bdIncompletebyToday' => new \Zend\Db\Sql\Expression("SUM(IF(r_a.status = 4 AND DATE(interview_date) = CURDATE(), 1,0))")),'right')
				   ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array('totalANCRecencyTestRecent'=>new \Zend\Db\Sql\Expression("SUM(IF(anc_r_r.has_patient_had_rapid_recency_test = 'done', 1,0))")),'left');
	$select1->combine($select2);
	$collectionQuery = $sql->select()->from(array('result' => $select1));
	$collectionQueryStr = $sql->getSqlStringForSqlObject($collectionQuery);
      return $dbAdapter->query($collectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchWeeklyDataReportingDetails($params){
	$common = new CommonService();
	$dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$result = array();
	if((isset($params['fromDate']) && trim($params['fromDate'])!= '') && (isset($params['toDate']) && trim($params['toDate'])!= '')){
	    $fromDateArray = explode("/",$params['fromDate']);
	    $toDateArray = explode("/",$params['toDate']);
	    $start = strtotime($fromDateArray[1].'-'.date('m', strtotime($fromDateArray[0])));
	    $end = strtotime($toDateArray[1].'-'.date('m', strtotime($toDateArray[0])));
	}else{
	    $start = strtotime(date("Y", strtotime("-1 year")).'-'.date('m', strtotime('+1 month', strtotime('-1 year'))));
	    $end = strtotime(date('Y').'-'.date('m'));
	}
	$j=0;
	$d =0;
	$weekArray = array();
	while($start <= $end){
	    $month = date('m', $start);$year = date('Y', $start);$monthYearFormat = date("M-Y", $start);
            $query = $sql->select()->from(array('da_c'=>'data_collection'))
                          ->columns(
                                  array(
                                        'total'=>new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 1 OR da_c.status = 2 OR da_c.status = 3, 1,0))"),
					'startdayofweek'=>new \Zend\Db\Sql\Expression("DATE_ADD(specimen_collected_date, INTERVAL(1-DAYOFWEEK(specimen_collected_date)) DAY)"),
					'enddayofweek'=>new \Zend\Db\Sql\Expression("DATE_ADD(specimen_collected_date, INTERVAL(7-DAYOFWEEK(specimen_collected_date)) DAY)")
                                        )
                                  )
			  ->where("da_c.country = '".$params['countryId']."' AND Month(specimen_collected_date)='".$month."' AND Year(specimen_collected_date)='".$year."'")
			  ->group('startdayofweek');
	    $queryStr = $sql->getSqlStringForSqlObject($query);
	    $rows = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	    if(isset($rows) && count($rows) > 0){
		foreach($rows as $row){
		    if(isset($row['startdayofweek']) && $row['startdayofweek']!= null && trim($row['startdayofweek'])!= '' && !in_array($row['startdayofweek'],$weekArray)){
		      $result['week'][$d] = $common->humanDateFormat($row['startdayofweek']).' to '.$common->humanDateFormat($row['enddayofweek']);
		      $result['total'][$d] = $row['total'];
		      $weekArray[] = $row['startdayofweek'];
		      $d++;
		    }
		}
	    }
	 $start = strtotime("+1 month", $start);
         $j++;
	}
      return $result;
    }
    
    public function updateResultPrintStatus($dataCollectionID,$printSrc){
	$loginContainer = new Container('user');
	$common = new CommonService();
	if($printSrc == 'anc'){
	    $data = array(
		'anc_print_status'=>1,
		'last_printed_by_anc'=>$loginContainer->userId,
		'last_printed_on_anc'=>$common->getDateTime()
	    );
	}else{
	   $data = array(
		'lab_print_status'=>1,
		'last_printed_by_lab'=>$loginContainer->userId,
		'last_printed_on_lab'=>$common->getDateTime()
	    ); 
	}
      return $this->update($data,array('data_collection_id'=>$dataCollectionID));
    }
}