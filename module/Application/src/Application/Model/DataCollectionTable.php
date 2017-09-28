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
        if(isset($params['surveillanceId']) && trim($params['surveillanceId'])!= ''){
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
            }if(!isset($params['readerValueRRDT'])){
                $params['readerValueRRDT'] = '';
            }if(!isset($params['asanteRapidRecencyAssayRlt'])){
                $params['asanteRapidRecencyAssayRlt'] = '';
            }if(!isset($params['readerValueRRR'])){
                $params['readerValueRRR'] = '';
            }if(trim($params['specimenType'])!= '' && $params['specimenType']!= 3 && (($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt'] == '') || ($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt']!= '') || ($params['asanteRapidRecencyAssayPn'] == 'p' && $params['asanteRapidRecencyAssayRlt']== ''))){
                $asanteValidate = false;
            }
	    $asanteRapidRecencyAssay = array('rrdt'=>array(
						'assay'=>$params['asanteRapidRecencyAssayPn'],
						'reader'=>$params['readerValueRRDT']
					    ),
					    'rrr'=>array(
						'assay'=>$params['asanteRapidRecencyAssayRlt'],
						'reader'=>$params['readerValueRRR']
					    )
					);
	    //status
	    $status = 1;//complete
	    if($rejectionReason == NULL){
		if($lagAssayValidate == false || $asanteValidate == false || ($params['lagAvidityResult'] == 'r' && trim($params['hivRna']) == '') || ($params['asanteRapidRecencyAssayRlt'] == 'r' && trim($params['hivRna']) == '')){
		    $status = 4;//incomplete
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
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$common = new CommonService();
	if($parameters['countryId']== ''){
	    $aColumns = array('da_c.patient_barcode_id','t.test_status_name',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name','c.country_name');
	    $orderColumns = array('da_c.patient_barcode_id','t.test_status_name','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments','da_c.added_on','u.user_name','c.country_name');
	}else{
	    $aColumns = array('da_c.patient_barcode_id','t.test_status_name',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name');
	    $orderColumns = array('da_c.patient_barcode_id','t.test_status_name','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments','da_c.added_on','u.user_name');
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
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('da_c.country'=>trim($parameters['countryId'])));
	}if(isset($parameters['date']) && trim($parameters['date'])!= ''){
	   $splitReportingMonthYear = explode("/",$parameters['date']);
	   $sQuery = $sQuery->where('MONTH(da_c.added_on) ="'.date('m', strtotime($splitReportingMonthYear[0])).'" AND YEAR(da_c.added_on) ="'.$splitReportingMonthYear[1].'"');
	}if(isset($parameters['type']) && trim($parameters['type'])== 'nltc'){
	  $sQuery = $sQuery->where(array('da_c.status'=>2)); 
	}if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
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
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>trim($parameters['countryId'])));
	}if(isset($parameters['date']) && trim($parameters['date'])!= ''){
	   $splitReportingMonthYear = explode("/",$parameters['date']);
	   $tQuery = $tQuery->where('MONTH(da_c.added_on) ="'.date('m', strtotime($splitReportingMonthYear[0])).'" AND YEAR(da_c.added_on) ="'.$splitReportingMonthYear[1].'"');
	}if(isset($parameters['type']) && trim($parameters['type'])== 'nltc'){
	   $tQuery = $tQuery->where(array('da_c.status'=>2)); 
	}if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
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
	    $lAgAvidityResult = '';
	    if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='lt'){
		$lAgAvidityResult = 'Long Term';
	    }else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
		$lAgAvidityResult = 'Recent';
	    }
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
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?$asanteRapidRecencyAssy['rrdt']['assay']:'';
		    if($asanteRapidRecencyAssayPn == 'p'){
			$asanteRapidRecencyAssayPn = 'Positive';
		    }else if($asanteRapidRecencyAssayPn == 'n'){
			$asanteRapidRecencyAssayPn = 'Negative';
		    }
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?$asanteRapidRecencyAssy['rrr']['assay']:'';
		    if($asanteRapidRecencyAssayRlt == 'r'){
			$asanteRapidRecencyAssayRlt = 'Recent';
		    }else if($asanteRapidRecencyAssayRlt == 'lt'){
			$asanteRapidRecencyAssayRlt = 'Long Term';
		    }
		}
	    }
	    $userUnlockedHistory = '';
	    if(isset($aRow['unlocked_on']) && $aRow['unlocked_on']!= null && trim($aRow['unlocked_on'])!= '' && $aRow['unlocked_on']!= '0000-00-00 00:00:00'){
		$unlockedDate = explode(" ",$aRow['unlocked_on']);
		$userQuery = $sql->select()->from(array('u' => 'user'))
		                           ->columns(array('user_id','full_name'))
				           ->where(array('u.user_id'=>$aRow['unlocked_by']));
	        $userQueryStr = $sql->getSqlStringForSqlObject($userQuery);
	        $userResult = $dbAdapter->query($userQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
		$unlockedBy = 'System';
		if(isset($userResult->user_id) && $userResult->user_id == $loginContainer->userId){
		    $unlockedBy = 'You';
		}else if(isset($userResult->user_id)){
		    $unlockedBy = ucwords($userResult->full_name);
		}
	      $userUnlockedHistory = '<i class="zmdi zmdi-info-outline unlocKbtn" title="This row was unlocked on '.$common->humanDateFormat($unlockedDate[0])." ".$unlockedDate[1].' by '.$unlockedBy.'" style="font-size:1.3rem;"></i>';
	    }
	    $dataView = '';
	    $dataEdit = '';
	    $dataLock = '';
	    $dataUnlock = '';
	    $pdfLink = '';
	    //data view
	    $dataView = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($aRow['country']) . '" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-10" title="View"><i class="zmdi zmdi-eye"></i> View</a>&nbsp;&nbsp;';
	    //for ls/ldeo
	    if(($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO') && ($aRow['test_status_name']== 'incomplete' || $aRow['test_status_name']== 'unlocked')){
		$dataEdit = '<a href="/data-collection/edit/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp;';
	    }
	    //for csc/cc
	    if($loginContainer->roleCode== 'CSC' || $loginContainer->roleCode== 'CC'){
		if($aRow['test_status_name']== 'locked'){
		   $dataUnlock = '<a href="javascript:void(0);" onclick="unlockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn red-text custom-btn custom-btn-red margin-bottom-10" title="Unlock"><i class="zmdi zmdi-lock-open"></i> Unlock</a>&nbsp;&nbsp;';
		}else{
		   $dataEdit = '<a href="/data-collection/edit/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp;';  
		}if($aRow['test_status_name']== 'completed'){
		   $dataLock = '<a href="javascript:void(0);" onclick="lockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn green-text custom-btn custom-btn-green margin-bottom-10" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a>&nbsp;&nbsp;';
	        }
	    }
	    //for individual result pdf
	    if($aRow['test_status_name']== 'locked'){
	       $pdfLink = '<a href="javascript:void(0);" onclick="printDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-10" title="PDF"><i class="zmdi zmdi-collection-pdf"></i> PDF</a>&nbsp;&nbsp;';
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
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = ucwords($aRow['rejection_code']);
	    $row[] = $receiptDateAtCentralLab;
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
	    $row[] = $common->humanDateFormat($addedDate[0])." ".$addedDate[1];
	    $row[] = ucwords($aRow['user_name']);
	    if($parameters['countryId']== ''){
	       $row[] = ucwords($aRow['country_name']);
	    }if($loginContainer->hasViewOnlyAccess =='no'){
		$dataLockUnlock = (trim($dataLock)!= '')?$dataLock:$dataUnlock;
	       $row[] = $dataEdit.$dataView.$dataLockUnlock.$pdfLink.$userUnlockedHistory;
	    }
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
        if(isset($params['surveillanceId']) && trim($params['surveillanceId'])!= ''){
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
            }if(!isset($params['readerValueRRDT'])){
                $params['readerValueRRDT'] = '';
            }if(!isset($params['asanteRapidRecencyAssayRlt'])){
                $params['asanteRapidRecencyAssayRlt'] = '';
            }if(!isset($params['readerValueRRDT'])){
                $params['readerValueRRDT'] = '';
            }if(trim($params['specimenType'])!= '' && $params['specimenType']!= 3 && (($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt'] == '') || ($params['asanteRapidRecencyAssayPn'] == '' && $params['asanteRapidRecencyAssayRlt']!= '') || ($params['asanteRapidRecencyAssayPn'] == 'p' && $params['asanteRapidRecencyAssayRlt']== ''))){
                $asanteValidate = false;
            }
	    $asanteRapidRecencyAssay = array('rrdt'=>array(
						'assay'=>$params['asanteRapidRecencyAssayPn'],
						'reader'=>$params['readerValueRRDT']
					    ),
					    'rrr'=>array(
						'assay'=>$params['asanteRapidRecencyAssayRlt'],
						'reader'=>$params['readerValueRRR']
					    )
					);
	    //status
	    $status = 1;//complete
	    if($rejectionReason == NULL){
		if($lagAssayValidate == false || $asanteValidate == false || ($params['lagAvidityResult'] == 'r' && trim($params['hivRna']) == '') || ($params['asanteRapidRecencyAssayRlt'] == 'r' && trim($params['hivRna']) == '')){
		    $status = 4;//incomplete
		}else if($params['formStatus'] == 2){
		   $status = $params['formStatus'];//locked
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
	    if(base64_decode($params['status']) !=$params['prevStatus']){
		if(base64_decode($params['status'])== 2){
		    $data['locked_on'] = $common->getDateTime();
		    $data['locked_by'] = $loginContainer->userId;
		}else if(base64_decode($params['status'])== 3){
		    $data['unlocked_on'] = $common->getDateTime();
		    $data['unlocked_by'] = $loginContainer->userId;
		}
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
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$common = new CommonService();
	if($parameters['countryId']== ''){
	    $aColumns = array('da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments','c.country_name');
	    $orderColumns = array('da_c.patient_barcode_id','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments','c.country_name');
	}else{
	    $aColumns = array('da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments');
	    $orderColumns = array('da_c.patient_barcode_id','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy','da_c.comments');
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
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left')
	             ->where('da_c.status IN (2)');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}
	//Custom Filter Start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("da_c.specimen_collected_date >='" . $start_date ."'", "da_c.specimen_collected_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.specimen_collected_date = '" . $start_date. "'"));
        }if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
            $sQuery = $sQuery->where(array('da_c.anc_site'=>base64_decode($parameters['anc'])));
        }if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
            $sQuery = $sQuery->where(array('da_c.lab'=>base64_decode($parameters['lab'])));
        }if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
	}
	//Custom Filter End
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
				->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left')
	                        ->where('da_c.status IN (2)');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
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
	    $lAgAvidityResult = '';
	    if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='lt'){
		$lAgAvidityResult = 'Long Term';
	    }else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
		$lAgAvidityResult = 'Recent';
	    }
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
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?$asanteRapidRecencyAssy['rrdt']['assay']:'';
		    if($asanteRapidRecencyAssayPn == 'p'){
			$asanteRapidRecencyAssayPn = 'Positive';
		    }else if($asanteRapidRecencyAssayPn == 'n'){
			$asanteRapidRecencyAssayPn = 'Negative';
		    }
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?$asanteRapidRecencyAssy['rrr']['assay']:'';
		    if($asanteRapidRecencyAssayRlt == 'r'){
			$asanteRapidRecencyAssayRlt = 'Recent';
		    }else if($asanteRapidRecencyAssayRlt == 'lt'){
			$asanteRapidRecencyAssayRlt = 'Long Term';
		    }
		}
	    }
	    
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = $specimenCollectedDate;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = ucwords($aRow['rejection_code']);
	    $row[] = $receiptDateAtCentralLab;
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
	$common = new CommonService();
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
	    $dataCollectionQuery = $dataCollectionQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $dataCollectionQuery = $dataCollectionQuery->where(array("da_c.specimen_collected_date >='" . $start_date ."'", "da_c.specimen_collected_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $dataCollectionQuery = $dataCollectionQuery->where(array("da_c.specimen_collected_date = '" . $start_date. "'"));
        }
	if(isset($params['chosenCountryId']) && trim($params['chosenCountryId'])!= ''){
            $dataCollectionQuery = $dataCollectionQuery->where(array('da_c.country'=>base64_decode($params['chosenCountryId'])));
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
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'year' => new \Zend\Db\Sql\Expression("YEAR(da_c.added_on)"),
						   'month' => new \Zend\Db\Sql\Expression("MONTHNAME(da_c.added_on)"),
						   'totalDataPoints' => new \Zend\Db\Sql\Expression("COUNT(*)"),
						   'dataPointFinalized' => new \Zend\Db\Sql\Expression("SUM(IF(status = 2, 1,0))"),
						))
				   ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('assessments' => new \Zend\Db\Sql\Expression("COUNT(assessment_id)")),'left')
				   ->join(array('c'=>'country'),'c.country_id=da_c.country',array('country_id','country_name'))
				   ->where(array('c.country_status'=>'active'))
				   ->group(new \Zend\Db\Sql\Expression("YEAR(da_c.added_on)"))
				   ->group(new \Zend\Db\Sql\Expression("MONTHNAME(da_c.added_on)"))
				   ->group('da_c.country')
				   ->order('da_c.added_on desc');
	if($loginContainer->roleCode == 'CC' && count($loginContainer->country) >0){
            $dataCollectionQuery = $dataCollectionQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}else if(($loginContainer->roleCode == 'LS' || $loginContainer->roleCode == 'LDEO') && count($loginContainer->laboratory) >0){
	    $dataCollectionQuery = $dataCollectionQuery->where('da_c.lab IN ("' . implode('", "', $loginContainer->laboratory) . '")');
	}else if($loginContainer->roleCode == 'ANCSC' && count($loginContainer->clinic) >0){
	   $dataCollectionQuery = $dataCollectionQuery->where('da_c.anc_site IN ("' . implode('", "', $loginContainer->clinic) . '")'); 
	}
	$dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
      return $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
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
	if($params['type'] == 'lab' && $params['src'] == 'barcodeid'){
	    $patientQuery = $sql->select()->from(array('da_c' => 'data_collection'))->columns(array('data_collection_id','status'))
				->where(array('da_c.patient_barcode_id'=>trim($params['patientBarcodeId'])));
	    if(isset($params['dataCollectionID']) && trim($params['dataCollectionID'])!= ''){
                $patientQuery = $patientQuery->where('da_c.data_collection_id != "'.base64_decode($params['dataCollectionID']).'"');
            }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		$patientQuery = $patientQuery->where(array('da_c.country'=>base64_decode(($params['countryId']))));
	    }else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		$patientQuery = $patientQuery->where(array('da_c.country'=>base64_decode(($params['optCountryId']))));
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
	}else if($params['type'] == 'lab' && $params['src'] == 'ancid'){
	    $patientQuery = $sql->select()->from(array('da_c' => 'data_collection'))->columns(array('data_collection_id'))
				->where(array('da_c.anc_patient_id'=>trim($params['ancPatientId'])))
				->where('(da_c.status="3" OR da_c.status="4")');
	    if(isset($params['dataCollectionID']) && trim($params['dataCollectionID'])!= ''){
                $patientQuery = $patientQuery->where('da_c.data_collection_id != "'.base64_decode($params['dataCollectionID']).'"');
            }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		$patientQuery = $patientQuery->where(array('da_c.country'=>base64_decode(($params['countryId']))));
	    }else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		$patientQuery = $patientQuery->where(array('da_c.country'=>base64_decode(($params['optCountryId']))));
	    }
	    $patientQueryStr = $sql->getSqlStringForSqlObject($patientQuery);
	    $patientResult = $dbAdapter->query($patientQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	    if($patientResult){
		if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		   $redirectUrl = "/data-collection/edit/".base64_encode($patientResult->data_collection_id)."/".$params['countryId'];
		}else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		   $redirectUrl = "/data-collection/edit/".base64_encode($patientResult->data_collection_id)."/";
		}
	    }
	}else if($params['type'] == 'clinic' && $params['src'] == 'barcodeid'){
	    $patientQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))->columns(array('assessment_id'))
				->where(array('r_a.patient_barcode_id'=>trim($params['patientBarcodeId'])));
	    if(isset($params['assessmentId']) && trim($params['assessmentId'])!= ''){
                $patientQuery = $patientQuery->where('r_a.assessment_id != "'.base64_decode($params['assessmentId']).'"');
            }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		$patientQuery = $patientQuery->where(array('r_a.country'=>base64_decode(($params['countryId']))));
	    }else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		$patientQuery = $patientQuery->where(array('r_a.country'=>base64_decode(($params['optCountryId']))));
	    }
	    $patientQueryStr = $sql->getSqlStringForSqlObject($patientQuery);
	    $patientResult = $dbAdapter->query($patientQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	    if($patientResult){
		if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		   $redirectUrl = "/clinic/risk-assessment/edit/".base64_encode($patientResult->assessment_id)."/".$params['countryId'];
		}else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		   $redirectUrl = "/clinic/risk-assessment/edit/".base64_encode($patientResult->assessment_id)."/";
		}
	    }
	}else if($params['type'] == 'clinic' && $params['src'] == 'ancid'){
	    $patientQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))->columns(array('assessment_id'))
				->where(array('r_a.anc_patient_id'=>trim($params['ancPatientId'])));
	    if(isset($params['assessmentId']) && trim($params['assessmentId'])!= ''){
                $patientQuery = $patientQuery->where('r_a.assessment_id != "'.base64_decode($params['assessmentId']).'"');
            }if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		$patientQuery = $patientQuery->where(array('r_a.country'=>base64_decode(($params['countryId']))));
	    }else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		$patientQuery = $patientQuery->where(array('r_a.country'=>base64_decode(($params['optCountryId']))));
	    }
	    $patientQueryStr = $sql->getSqlStringForSqlObject($patientQuery);
	    $patientResult = $dbAdapter->query($patientQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	    if($patientResult){
		if(isset($params['countryId']) && trim($params['countryId'])!= ''){
		   $redirectUrl = "/clinic/risk-assessment/edit/".base64_encode($patientResult->assessment_id)."/".$params['countryId'];
		}else if(isset($params['optCountryId']) && trim($params['optCountryId'])!= ''){
		   $redirectUrl = "/clinic/risk-assessment/edit/".base64_encode($patientResult->assessment_id)."/";
		}
	    }
	}
       return $redirectUrl;
    }
    
    public function fecthAllLabLogbook($parameters){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$common = new CommonService();
	
	$aColumns = array("DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')",'da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy');
	$orderColumns = array('receipt_date_at_central_lab','da_c.patient_barcode_id','da_c.specimen_collected_date','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy');

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
       //Get all mapped lab
       foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
       }
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}if($loginContainer->roleCode== 'LS'){
	    $sQuery = $sQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
	}
	//Custom Filter Start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("da_c.receipt_date_at_central_lab >='" . $start_date ."'", "da_c.receipt_date_at_central_lab <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.receipt_date_at_central_lab = '" . $start_date. "'"));
        }if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
            $sQuery = $sQuery->where(array('da_c.anc_site'=>base64_decode($parameters['anc'])));
        }if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
            $sQuery = $sQuery->where(array('da_c.lab'=>base64_decode($parameters['lab'])));
        }if(isset($parameters['status']) && trim($parameters['status'])== 'completed'){
           $sQuery = $sQuery->where('da_c.status = "2"');
        }else if(isset($parameters['status']) && trim($parameters['status'])== 'pending'){
	   $sQuery = $sQuery->where('da_c.status IN (1,3,4)'); 
	}
	//Custom Filter End
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->logbookQuery = $sQuery;
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
				  ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
				  ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
				  ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}if($loginContainer->roleCode== 'LS'){
	    $tQuery = $tQuery->where('da_c.lab IN ("' . implode('", "', $mappedLab) . '")');
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
	    $lAgAvidityResult = '';
	    if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='lt'){
		$lAgAvidityResult = 'Long Term';
	    }else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
		$lAgAvidityResult = 'Recent';
	    }
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
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?$asanteRapidRecencyAssy['rrdt']['assay']:'';
		    if($asanteRapidRecencyAssayPn == 'p'){
			$asanteRapidRecencyAssayPn = 'Positive';
		    }else if($asanteRapidRecencyAssayPn == 'n'){
			$asanteRapidRecencyAssayPn = 'Negative';
		    }
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?$asanteRapidRecencyAssy['rrr']['assay']:'';
		    if($asanteRapidRecencyAssayRlt == 'r'){
			$asanteRapidRecencyAssayRlt = 'Recent';
		    }else if($asanteRapidRecencyAssayRlt == 'lt'){
			$asanteRapidRecencyAssayRlt = 'Long Term';
		    }
		}
	    }
	    
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = $specimenCollectedDate;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = ucwords($aRow['rejection_code']);
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = $asanteRapidRecencyAssayPn;
	    $row[] = $asanteRapidRecencyAssayRlt;
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
    
    public function fetchAllAncLabReportDatas($parameters){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$common = new CommonService();
	
	$aColumns = array("DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'da_c.status','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.comments');
	$orderColumns = array('da_c.specimen_collected_date','da_c.status','anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','f.facility_code','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.comments');

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
       $mappedANC = array();
       $uMapQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
                                  ->where(array('cl_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped ANC
       foreach($uMapResult as $anc){
	   $mappedANC[] = $anc['clinic_id'];
       }
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'),'left')
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'),'left')
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	             //->where('da_c.status IN (2)');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));
	}if($loginContainer->roleCode == 'ANCSC'){
            $sQuery = $sQuery->where('da_c.anc_site IN ("' . implode('", "', $mappedANC) . '")');
        }
	//Custom Filter Start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("da_c.specimen_collected_date >='" . $start_date ."'", "da_c.specimen_collected_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.specimen_collected_date = '" . $start_date. "'"));
        }if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
            $sQuery = $sQuery->where(array('da_c.anc_site'=>base64_decode($parameters['anc'])));
        }if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
            $sQuery = $sQuery->where(array('da_c.lab'=>base64_decode($parameters['lab'])));
        }
	//Custom Filter End
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->labReportQuery = $sQuery;
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
				->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	                        //->where('da_c.status IN (2)');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}if($loginContainer->roleCode == 'ANCSC'){
            $tQuery = $tQuery->where('da_c.anc_site IN ("' . implode('", "', $mappedANC) . '")');
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
	    $lAgAvidityResult = '';
	    if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='lt'){
		$lAgAvidityResult = 'Long Term';
	    }else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
		$lAgAvidityResult = 'Recent';
	    }
	//    $hIVRNAResult = '';
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    //status
	    if($aRow['final_lag_avidity_odn'] <= 2 && (trim($aRow['hiv_rna']) == '' || $aRow['hiv_rna'] == null)){
		$status = 'Results Awaited';
	    }else{
		$status = ucwords($aRow['test_status_name']);
	    }
	    
	    $row[] = $specimenCollectedDate;
	    $row[] = $status;
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = ucwords($aRow['rejection_code']);
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = ucfirst($aRow['comments']);
	   $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchCountryDashboardDetails($params){
	$loginContainer = new Container('user');
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'year' => new \Zend\Db\Sql\Expression("YEAR(da_c.added_on)"),
						   'month' => new \Zend\Db\Sql\Expression("MONTHNAME(da_c.added_on)"),
						   'totalDataPoints' => new \Zend\Db\Sql\Expression("COUNT(*)"),
						   'dataPointFinalized' => new \Zend\Db\Sql\Expression("SUM(IF(da_c.status = 2, 1,0))")
						))
				   ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('assessments' => new \Zend\Db\Sql\Expression("COUNT(assessment_id)")),'left')
				   ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array('country'))
				   ->join(array('p'=>'province'),'p.province_id=f.province',array('province_name'))
				   ->where(array('da_c.country'=>$params['country']))
				   ->group(new \Zend\Db\Sql\Expression("YEAR(da_c.added_on)"))
				   ->group(new \Zend\Db\Sql\Expression("MONTHNAME(da_c.added_on)"))
				   ->group('f.province')
				   ->order('da_c.added_on desc');
	if(($loginContainer->roleCode == 'LS' || $loginContainer->roleCode == 'LDEO') && count($loginContainer->laboratory) >0){
	    $dataCollectionQuery = $dataCollectionQuery->where('da_c.lab IN ("' . implode('", "', $loginContainer->laboratory) . '")');
	}else if($loginContainer->roleCode == 'ANCSC' && count($loginContainer->clinic) >0){
	   $dataCollectionQuery = $dataCollectionQuery->where('da_c.anc_site IN ("' . implode('", "', $loginContainer->clinic) . '")'); 
	}if(trim($params['province'])!= ''){
	    $dataCollectionQuery = $dataCollectionQuery->where(array('f.province'=>base64_decode($params['province'])));
	}if(trim($params['reportingMonthYear'])!= ''){
	    $splitReportingMonthYear = explode("/",$params['reportingMonthYear']);
	    $dataCollectionQuery = $dataCollectionQuery->where('MONTH(da_c.added_on) ="'.date('m', strtotime($splitReportingMonthYear[0])).'" AND YEAR(da_c.added_on) ="'.$splitReportingMonthYear[1].'"');
	}
	$dataCollectionQueryStr = $sql->getSqlStringForSqlObject($dataCollectionQuery);
      return $dbAdapter->query($dataCollectionQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchDataReportingLocations($params){
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$location = array();
	//facility query
	$facilityLocationQuery = $sql->select()->from(array('f' => 'facility'))
				     ->columns(array('facility_name','latitude','longitude'))
				     ->where(array('f.country'=>$params['country']));
	$facilityLocationQueryStr = $sql->getSqlStringForSqlObject($facilityLocationQuery);
        $location['facilities'] = $dbAdapter->query($facilityLocationQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	//anc query
	$ancLocationQuery = $sql->select()->from(array('anc' => 'anc_site'))
				->columns(array('anc_site_name','latitude','longitude'))
				->where(array('anc.country'=>$params['country']));
	$ancLocationQueryStr = $sql->getSqlStringForSqlObject($ancLocationQuery);
        $location['anc'] = $dbAdapter->query($ancLocationQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
      return $location;
    }
    
    public function fetchStudyOverviewData($parameters){
	$queryContainer = new Container('query');
	$common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$aColumns = array('province_name','da_c.patient_barcode_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'da_c.status','assessment_id','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy');
	$orderColumns = array('province_name','da_c.patient_barcode_id','da_c.specimen_collected_date','da_c.status','assessment_id','da_c.lag_avidity_result','da_c.hiv_rna','da_c.recent_infection','da_c.asante_rapid_recency_assy','da_c.asante_rapid_recency_assy');
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
        $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'data_collection_id',
						   'patient_barcode_id',
						   'specimen_collected_date',
						   'rejection_reason',
						   'country',
						   'lag_avidity_result',
						   'hiv_rna',
						   'hiv_rna_gt_1000',
						   'recent_infection',
						   'asante_rapid_recency_assy',
						   'labDataPresentComplete' => new \Zend\Db\Sql\Expression("IF(da_c.status = 1, 1,0)")
						))
				   ->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array())
				   ->join(array('p'=>'province'),'p.province_id=f.province',array('province_name'))
				   ->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('assessment_id'),'left')
				   ->where(array('da_c.country'=>$parameters['country']));
	if(trim($s_c_start_date) != "" && trim($s_c_start_date)!= trim($s_c_end_date)) {
           $sQuery = $sQuery->where(array("da_c.specimen_collected_date >='" . $s_c_start_date ."'", "da_c.specimen_collected_date <='" . $s_c_end_date."'"));
        }else if (trim($s_c_start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.specimen_collected_date = '" . $s_c_start_date. "'"));
        }if(trim($s_t_start_date) != "" && trim($s_t_start_date)!= trim($s_t_end_date)) {
           $sQuery = $sQuery->where(array("da_c.date_of_test_completion >='" . $s_t_start_date ."'", "da_c.date_of_test_completion <='" . $s_t_end_date."'"));
        }else if (trim($s_t_start_date) != "") {
            $sQuery = $sQuery->where(array("da_c.date_of_test_completion = '" . $s_t_start_date. "'"));
        }if(trim($parameters['province'])!= ''){
	    $sQuery = $sQuery->where(array('f.province'=>base64_decode($parameters['province'])));
	}if(trim($parameters['finalLagAvidityOdn'])!= '' && $parameters['finalLagAvidityOdn'] == 'lt2'){
	    $sQuery = $sQuery->where('da_c.final_lag_avidity_odn < 2');
	}else if(trim($parameters['finalLagAvidityOdn'])!= '' && $parameters['finalLagAvidityOdn'] == 'gt2'){
	    $sQuery = $sQuery->where('da_c.final_lag_avidity_odn > 2');
	}if(trim($parameters['hivRna'])!= '' && $parameters['hivRna'] == 'lte1000'){
	    $sQuery = $sQuery->where('da_c.hiv_rna <= 1000');
	}else if(trim($parameters['hivRna'])!= '' && $parameters['hivRna'] == 'gt1000'){
	    $sQuery = $sQuery->where('da_c.hiv_rna > 1000');
	}if(trim($parameters['asanteRapidRecencyAssayRlt'])!= ''){
	    $sQuery = $sQuery->where('da_c.asante_rapid_recency_assy like "%'.$parameters['asanteRapidRecencyAssayRlt'].'%"');
	}
	
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
	$tQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				->columns(array(
						'data_collection_id',
						'patient_barcode_id',
						'specimen_collected_date',
						'rejection_reason',
						'country',
						'lag_avidity_result',
						'hiv_rna',
						'hiv_rna_gt_1000',
						'recent_infection',
						'asante_rapid_recency_assy',
						'labDataPresentComplete' => new \Zend\Db\Sql\Expression("IF(da_c.status = 1, 1,0)")
					     ))
				->join(array('f'=>'facility'),'f.facility_id=da_c.lab',array())
				->join(array('p'=>'province'),'p.province_id=f.province',array('province_name'))
				->join(array('r_a'=>'clinic_risk_assessment'),'r_a.patient_barcode_id=da_c.patient_barcode_id',array('assessment_id'),'left')
				->where(array('da_c.country'=>$parameters['country']));
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
	    $lagResult = '';
	    //$hIVRNAResult = '';
	    $rapidRecencyAssay = '';
	    $rapidRecencyAssayDuration = '';
	    $status = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($aRow['country']) .'" target="_blank" title="View data"> Incomplete</a>';
	    if($aRow['rejection_reason']!= null && trim($aRow['rejection_reason'])!= '' && $aRow['rejection_reason']> 0){
		$aRow['labDataPresentComplete'] = -1;
	    }
	    //specimen collected date
	    if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		$specimenCollectedDate = $common->humanDateFormat($aRow['specimen_collected_date']);
	    }
	    //status
	    if($aRow['labDataPresentComplete'] == 1){
	       $status = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($aRow['country']) . '"target="_blank" title="View data"> Complete</a>';
	    }else if($aRow['labDataPresentComplete'] == -1){
	      $status = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($aRow['country']) . '"target="_blank" title="View data"> Rejected</a>';
	    }
	    //LAg assay
	    if($aRow['lag_avidity_result']=='lt'){
		$lagResult = 'Long Term';
	    }else if($aRow['lag_avidity_result']=='r'){
		$lagResult = 'Recent';
	    }
	    //HIV rna values
	//    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
	//	$hIVRNAResult = 'High Viral Load';
	//    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
	//	$hIVRNAResult = 'Low Viral Load';
	//    }
	    //rapid assay
	    if(trim($aRow['asante_rapid_recency_assy'])!= ''){
		$asanteRapidRecencyAssy = json_decode($aRow['asante_rapid_recency_assy'],true);
		if(isset($asanteRapidRecencyAssy['rrdt'])){
		    $asanteRapidRecencyAssayPn = (isset($asanteRapidRecencyAssy['rrdt']['assay']))?$asanteRapidRecencyAssy['rrdt']['assay']:'';
		    if($asanteRapidRecencyAssayPn == 'p'){
			$rapidRecencyAssay = 'Positive';
		    }else if($asanteRapidRecencyAssayPn == 'n'){
			$rapidRecencyAssay = 'Negative';
		    }
		}if(isset($asanteRapidRecencyAssy['rrr'])){
		    $asanteRapidRecencyAssayRlt = (isset($asanteRapidRecencyAssy['rrr']['assay']))?$asanteRapidRecencyAssy['rrr']['assay']:'';
		    if($asanteRapidRecencyAssayRlt == 'r'){
			$rapidRecencyAssayDuration = 'Recent';
		    }else if($asanteRapidRecencyAssayRlt == 'lt'){
			$rapidRecencyAssayDuration = 'Long Term';
		    }
		}
	    }
	    $row = array();
	    $row[] = ucwords($aRow['province_name']);
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = $specimenCollectedDate;
	    $row[] = $status;
	    $row[] = (isset($aRow['assessment_id']))?'<a href="/clinic/risk-assessment/view/' . base64_encode($aRow['assessment_id']). '/' . base64_encode($aRow['country']) . '" style="text-decoration:underline;" target="_blank" title="View data"> Yes</a>':'No';
	    $row[] = $lagResult;
	    $row[] = $aRow['hiv_rna'];
	    //$row[] = $hIVRNAResult;
	    $row[] = ucfirst($aRow['recent_infection']);
	    $row[] = $rapidRecencyAssay;
	    $row[] = $rapidRecencyAssayDuration;
	    $output['aaData'][] = $row;
	}
      return $output;
    }
}