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
	     
	    if(!isset($params['age']) || trim($params['age'])== ''){
                $params['age'] = NULL;
            }if(!isset($params['lagAvidityResult'])){
                $params['lagAvidityResult'] = NULL;
            } if(!isset($params['hivRnaGT1000'])){
                $params['hivRnaGT1000'] = NULL;
            } if(!isset($params['recentInfection'])){
                $params['recentInfection'] = NULL;
            } if(!isset($params['asanteRapidRecencyAssayPn'])){
                $params['asanteRapidRecencyAssayPn'] = NULL;
            }if(!isset($params['asanteRapidRecencyAssayRlt'])){
                $params['asanteRapidRecencyAssayRlt'] = NULL;
            }
	    $asanteRapidRecencyAssay = $params['asanteRapidRecencyAssayPn'].'/'.$params['asanteRapidRecencyAssayRlt'];
	    if(isset($params['chosenCountry']) && trim($params['chosenCountry'])!=''){
		$country = base64_decode($params['chosenCountry']);
	    }else if(isset($params['country']) && trim($params['country'])!=''){
		$country = base64_decode($params['country']);
	    }else{
		return $lastInsertedId;
	    }
            $data = array(
                        'surveillance_id'=>$params['surveillanceId'],
                        'specimen_collected_date'=>$specimenCollectedDate,
                        'anc_site'=>base64_decode($params['ancSite']),
                        'anc_patient_id'=>$params['ancPatientId'],
                        'age'=>$params['age'],
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
                        'asante_rapid_recency_assy'=>$asanteRapidRecencyAssay,
                        'country'=>$country,
			'status'=>1,
                        'added_on'=>$common->getDateTime(),
                        'added_by'=>$loginContainer->userId
                    );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
		if($lastInsertedId >0){
		    //Add new row into data collection event log table
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
	if($loginContainer->roleCode =='CSC' && $parameters['countryId']== ''){
	    $aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name','c.country_name');
	    $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name','da_c.added_on','u.user_name','c.country_name');
	}else{
	    $aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name');
	    $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name','da_c.added_on','u.user_name');
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
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
                     ->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
                     ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
		     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	    if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	       $sQuery = $sQuery->where(array('da_c.country'=>trim($parameters['countryId'])));
	    }else{
		if($loginContainer->roleCode!= 'CSC'){
		    $sQuery = $sQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
		}
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
				  ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
				  ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
				  ->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
				  ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
				  ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
				  ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>trim($parameters['countryId'])));
	    }else{
		if($loginContainer->roleCode!= 'CSC'){
		    $tQuery = $tQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
		}
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
		    $lAgAvidityResult = 'LONG TERM';
		}else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
		    $lAgAvidityResult = 'RECENT';
		}
		$hIVRNAResult = '';
		if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
		    $hIVRNAResult = 'HIGH VIRAL LOAD';
		}else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
		    $hIVRNAResult = 'LOW VIRAL LOAD';
		}
		$asanteRapidRecencyAssay = '';
		if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='p/lt'){
		    $asanteRapidRecencyAssay = 'POSITIVE/LONG TERM';
		}else if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='n/lt'){
		    $asanteRapidRecencyAssay = 'NEGATIVE/LONG TERM';
		}else if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='n/r'){
		    $asanteRapidRecencyAssay = 'NEGATIVE/RECENT';
		}else if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='p/r'){
		    $asanteRapidRecencyAssay = 'POSITIVE/RECENT';
		}
		$date = explode(" ",$aRow['added_on']);
    
		$row[] = $aRow['surveillance_id'];
		$row[] = $specimenCollectedDate;
		$row[] = ucwords($aRow['anc_site_name'])." - ".$aRow['anc_site_code'];
		$row[] = $aRow['anc_patient_id'];
		$row[] = $aRow['age'];
		$row[] = $specimenPickUpDateatAnc;
		$row[] = ucwords($aRow['facility_name'])." - ".$aRow['facility_code'];
		$row[] = $aRow['lab_specimen_id'];
		$row[] = ucwords($aRow['rejection_code']);
		$row[] = $receiptDateAtCentralLab;
		$row[] = $testCompletionDate;
		$row[] = $resultDispatchedDateToClinic;
		$row[] = $aRow['final_lag_avidity_odn'];
		$row[] = $lAgAvidityResult;
		$row[] = $aRow['hiv_rna'];
		$row[] = $hIVRNAResult;
		$row[] = ucwords($aRow['recent_infection']);
		$row[] = $asanteRapidRecencyAssay;
		$row[] = ucwords($aRow['test_status_name']);
		$row[] = $common->humanDateFormat($date[0])." ".$date[1];
		$row[] = ucwords($aRow['user_name']);
		if($loginContainer->roleCode =='CSC' && $parameters['countryId']== ''){
		   $row[] = ucwords($aRow['country_name']);
		}
		$dataView = '';
		$lockView = '';
		if($loginContainer->roleCode== 'LDEO'){
		    $dataView = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($aRow['country']) . '" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-10" title="View"><i class="zmdi zmdi-eye"></i> View</a><br>';
		    if($aRow['test_status_name']== 'completed'){
		       $lockView = '<a href="javascript:void(0);" onclick="lockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-10" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a>';
		    }
		}else if($loginContainer->roleCode== 'LS'){
		    if($aRow['test_status_name']== 'unlocked'){
		       $dataView = '<a href="/data-collection/edit/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a><br>';
		    }else{
		       $dataView = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-10" title="View"><i class="zmdi zmdi-eye"></i> View</a><br>';
		    }
		}else{
		    $dataView = '<a href="/data-collection/edit/' . base64_encode($aRow['data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a><br>';
		}
		 
		if($loginContainer->roleCode== 'CSC' || $loginContainer->roleCode== 'CC'){
		    if($aRow['test_status_name']== 'completed' || $aRow['test_status_name']== 'unlocked'){
		       $lockView = '<a href="javascript:void(0);" onclick="lockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-10" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a>';
		    }else if($aRow['test_status_name']== 'locked'){
		       $lockView = '<a href="javascript:void(0);" onclick="unlockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn green-text custom-btn custom-btn-green margin-bottom-10" title="Unlock"><i class="zmdi zmdi-lock-open"></i> Unlock</a>';
		    }
		}
		$row[] = $dataView.$lockView;
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
	    if(!isset($params['age']) || trim($params['age'])== ''){
                $params['age'] = NULL;
            }if(!isset($params['lagAvidityResult'])){
                $params['lagAvidityResult'] = NULL;
            } if(!isset($params['hivRnaGT1000'])){
                $params['hivRnaGT1000'] = NULL;
            } if(!isset($params['recentInfection'])){
                $params['recentInfection'] = NULL;
            } if(!isset($params['asanteRapidRecencyAssayPn'])){
                $params['asanteRapidRecencyAssayPn'] = NULL;
            }if(!isset($params['asanteRapidRecencyAssayRlt'])){
                $params['asanteRapidRecencyAssayRlt'] = NULL;
            }
	    $asanteRapidRecencyAssay = $params['asanteRapidRecencyAssayPn'].'/'.$params['asanteRapidRecencyAssayRlt'];
            $data = array(
                        'surveillance_id'=>$params['surveillanceId'],
                        'specimen_collected_date'=>$specimenCollectedDate,
                        'anc_site'=>base64_decode($params['ancSite']),
                        'anc_patient_id'=>$params['ancPatientId'],
                        'age'=>$params['age'],
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
                        'asante_rapid_recency_assy'=>$asanteRapidRecencyAssay,
                        'status'=>base64_decode($params['status'])
                    );
            $this->update($data,array('data_collection_id'=>$dataCollectionId));
		//Add new row into data collection event log table
		$dbAdapter = $this->adapter;
		$dataCollectionEventLogDb = new DataCollectionEventLogTable($dbAdapter);
		$data['data_collection_id'] = $dataCollectionId;
		$data['country'] = $params['chosenCountry'];
		$data['updated_on'] = $common->getDateTime();
		$data['updated_by'] = $loginContainer->userId;
		$dataCollectionEventLogDb->insert($data);
        }
      return $dataCollectionId;
    }
	
    public function lockDataCollectionDetails($params){
	return $this->update(array('status'=>2),array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function unlockDataCollectionDetails($params){
	return $this->update(array('status'=>3),array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
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
	if($loginContainer->roleCode =='CSC' && $parameters['countryId']== ''){
	    $aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name','c.country_name');
	    $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name','da_c.added_on','u.user_name','c.country_name');
	}else{
	    $aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','da_c.anc_patient_id','da_c.age',"DATE_FORMAT(da_c.specimen_picked_up_date_at_anc,'%d-%b-%Y')",'f.facility_name','da_c.lab_specimen_id','r_r.rejection_code',"DATE_FORMAT(da_c.receipt_date_at_central_lab,'%d-%b-%Y')","DATE_FORMAT(da_c.date_of_test_completion,'%d-%b-%Y')","DATE_FORMAT(da_c.result_dispatched_date_to_clinic,'%d-%b-%Y')",'da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name',"DATE_FORMAT(da_c.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name');
	    $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.age','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','r_r.rejection_code','da_c.receipt_date_at_central_lab','da_c.date_of_test_completion','da_c.result_dispatched_date_to_clinic','da_c.final_lag_avidity_odn','da_c.lag_avidity_result','da_c.hiv_rna','da_c.hiv_rna_gt_1000','da_c.recent_infection','da_c.asante_rapid_recency_assy','t.test_status_name','da_c.added_on','u.user_name');
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
       $sQuery = $sql->select()->from(array('da_c' => 'data_collection'))
                     ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
                     ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
		     ->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
                     ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
		     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left')
	             ->where('da_c.status IN (2,3)');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
	}else{
	    if($loginContainer->roleCode!= 'CSC'){
	       $sQuery = $sQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	    }
	}
	$queryContainer->exportQuery = $sQuery;
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
				  ->join(array('anc' => 'anc_site'), "anc.anc_site_id=da_c.anc_site",array('anc_site_name','anc_site_code'))
				  ->join(array('f' => 'facility'), "f.facility_id=da_c.lab",array('facility_name','facility_code'))
				  ->join(array('u' => 'user'), "u.user_id=da_c.added_by",array('user_name'))
				  ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
				  ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
				  ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left')
	                          ->where('da_c.status IN (2,3)');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>$parameters['countryId']));  
	}else if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
	}else{
	    if($loginContainer->roleCode!= 'CSC'){
	       $tQuery = $tQuery->where('da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	    }
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
		$specimenCollectionDate = $common->humanDateFormat($aRow['specimen_collected_date']);
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
		$lAgAvidityResult = 'LONG TERM';
	    }else if(trim($aRow['lag_avidity_result'])!= '' && $aRow['lag_avidity_result'] =='r'){
		$lAgAvidityResult = 'RECENT';
	    }
	    $hIVRNAResult = '';
	    if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='yes'){
		$hIVRNAResult = 'HIGH VIRAL LOAD';
	    }else if(trim($aRow['hiv_rna_gt_1000'])!= '' && $aRow['hiv_rna_gt_1000'] =='no'){
		$hIVRNAResult = 'LOW VIRAL LOAD';
	    }
	    $asanteRapidRecencyAssay = '';
	    if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='p/lt'){
		$asanteRapidRecencyAssay = 'POSITIVE/LONG TERM';
	    }else if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='n/lt'){
		$asanteRapidRecencyAssay = 'NEGATIVE/LONG TERM';
	    }else if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='n/r'){
		$asanteRapidRecencyAssay = 'NEGATIVE/RECENT';
	    }else if(trim($aRow['asante_rapid_recency_assy'])!= '' && $aRow['asante_rapid_recency_assy'] =='p/r'){
		$asanteRapidRecencyAssay = 'POSITIVE/RECENT';
	    }
	    $date = explode(" ",$aRow['added_on']);
	    
	    $row[] = $aRow['surveillance_id'];
	    $row[] = $specimenCollectedDate;
	    $row[] = ucwords($aRow['anc_site_name'])." - ".$aRow['anc_site_code'];
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $aRow['age'];
	    $row[] = $specimenPickUpDateatAnc;
	    $row[] = ucwords($aRow['facility_name'])." - ".$aRow['facility_code'];
	    $row[] = $aRow['lab_specimen_id'];
	    $row[] = ucwords($aRow['rejection_code']);
	    $row[] = $receiptDateAtCentralLab;
	    $row[] = $testCompletionDate;
	    $row[] = $resultDispatchedDateToClinic;
	    $row[] = $aRow['final_lag_avidity_odn'];
	    $row[] = $lAgAvidityResult;
	    $row[] = $aRow['hiv_rna'];
	    $row[] = $hIVRNAResult;
	    $row[] = ucwords($aRow['recent_infection']);
	    $row[] = $asanteRapidRecencyAssay;
	    $row[] = ucwords($aRow['test_status_name']);
	    $row[] = $common->humanDateFormat($date[0])." ".$date[1];
	    $row[] = ucwords($aRow['user_name']);
	    if($loginContainer->roleCode =='CSC' && $parameters['countryId']== ''){
	       $row[] = ucwords($aRow['country_name']);
	    }
	   $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchSearchableDataCollection($params){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$common = new CommonService();
	$start_date = '';
	$end_date = '';
	if(isset($params['specimenCollectedDate']) && trim($params['specimenCollectedDate'])!= ''){
	   $s_c_date = explode("to", $_POST['specimenCollectedDate']);
	   if(isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($s_c_date[0]));
	   }if(isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($s_c_date[1]));
	   }
	}
        $dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
	                           ->columns(array('data_collection_id','surveillance_id'))
				   ->where(array('da_c.status'=>2));
	if(trim($start_date) != "" && trim($end_date) != "") {
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
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$dataCollectionQuery = $sql->select()->from(array('da_c' => 'data_collection'))
				   ->columns(array(
						   'year' => new \Zend\Db\Sql\Expression("YEAR(added_on)"),
						   'month' => new \Zend\Db\Sql\Expression("MONTHNAME(added_on)"),
						   'totalDataPoints' => new \Zend\Db\Sql\Expression("COUNT(*)"),
						   'dataPointFinalized' => new \Zend\Db\Sql\Expression("SUM(IF(status = 2, 1,0))")
						))
				   ->join(array('c'=>'country'),'c.country_id=da_c.country',array('country_name'))
				   ->where(array('c.country_status'=>'active'))
				   ->group(new \Zend\Db\Sql\Expression("YEAR(added_on)"))
				   ->group(new \Zend\Db\Sql\Expression("MONTHNAME(added_on)"))
				   ->group('da_c.country')
				   ->order('da_c.added_on desc');
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
}