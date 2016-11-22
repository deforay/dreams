<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class DataCollectionTable extends AbstractTableGateway {

    protected $table = 'data_collection';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addDataCollectionDetails($params){
        $loginContainer = new Container('employee');
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
            } if(!isset($params['asanteRapidRecencyAssay'])){
                $params['asanteRapidRecencyAssay'] = NULL;
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
                        'receipt_date_at_central_lab'=>$receiptDateAtCentralLab,
                        'rejection_reason'=>$rejectionReason,
                        'final_lag_avidity_odn'=>$params['finalLagAvidityOdn'],
                        'lag_avidity_result'=>$params['lagAvidityResult'],
                        'hiv_rna'=>$params['hivRna'],
                        'hiv_rna_gt_1000'=>$params['hivRnaGT1000'],
                        'recent_infection'=>$params['recentInfection'],
                        'result_dispatched_date_to_clinic'=>$resultDispatchedDateToClinic,
                        'asante_rapid_recency_assy'=>$params['asanteRapidRecencyAssay'],
                        'country'=>$loginContainer->country,
			'status'=>1,
                        'added_on'=>$common->getDateTime(),
                        'added_by'=>$loginContainer->employeeId
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
	$queryContainer = new Container('query');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	    $common = new CommonService();
		if($loginContainer->roleCode =='CSC'){
            $aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','f.facility_name','f.facility_code','da_c.lab_specimen_id','c.country_name','t.test_status_name');
            $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','c.country_name','t.test_status_name');
		}else{
			$aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','f.facility_name','f.facility_code','da_c.lab_specimen_id','t.test_status_name');
            $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','t.test_status_name');
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
                     ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
		     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode!= 'CSC'){
	    $sQuery = $sQuery->where(array('da_c.country'=>$loginContainer->country));
	}
	if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
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
				  ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
				  ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'));
	if($loginContainer->roleCode!= 'CSC'){
	    $tQuery = $tQuery->where(array('da_c.country'=>$loginContainer->country));
	}
	if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
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
		$specimenCollectionDate = '';
		if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		    $specimenCollectionDate = $common->humanDateFormat($aRow['specimen_collected_date']);
		}
		$row[] = $aRow['surveillance_id'];
		$row[] = $specimenCollectionDate;
		$row[] = ucwords($aRow['anc_site_name'])." - ".$aRow['anc_site_code'];
		$row[] = $aRow['anc_patient_id'];
		$row[] = ucwords($aRow['facility_name'])." - ".$aRow['facility_code'];
		$row[] = $aRow['lab_specimen_id'];
		if($loginContainer->roleCode =='CSC'){
		   $row[] = ucwords($aRow['country_name']);
		}
		$row[] = ucwords($aRow['test_status_name']);
		 $dataView = '';
		 if($loginContainer->roleCode== 'LDEO' && trim($aRow['test_status_name'])== 'locked'){
		    $dataView = '<a href="/data-collection/view/' . base64_encode($aRow['data_collection_id']) . '" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-10" title="View"><i class="zmdi zmdi-eye"></i> View</a><br>';
		 }else{
		    $dataView = '<a href="/data-collection/edit/' . base64_encode($aRow['data_collection_id']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
		 }
		 $lockState = '';
		 if($loginContainer->roleCode!= 'LDEO'){
			if($aRow['test_status_name']== 'locked'){
			   $lockState = '<a href="javascript:void(0);" onclick="lockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-10" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a><br>';
			}else{
			   $lockState = '<a href="javascript:void(0);" onclick="unlockDataCollection(\''.base64_encode($aRow['data_collection_id']).'\');" class="waves-effect waves-light btn-small btn green-text custom-btn custom-btn-green margin-bottom-10" title="Unlock"><i class="zmdi zmdi-lock-open"></i> Unlock</a><br>';
			}
		 }
		$row[] = $dataView.$lockState;
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
		$loginContainer = new Container('employee');
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
            } if(!isset($params['asanteRapidRecencyAssay'])){
                $params['asanteRapidRecencyAssay'] = NULL;
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
                        'receipt_date_at_central_lab'=>$receiptDateAtCentralLab,
                        'rejection_reason'=>$rejectionReason,
                        'final_lag_avidity_odn'=>$params['finalLagAvidityOdn'],
                        'lag_avidity_result'=>$params['lagAvidityResult'],
                        'hiv_rna'=>$params['hivRna'],
                        'hiv_rna_gt_1000'=>$params['hivRnaGT1000'],
                        'recent_infection'=>$params['recentInfection'],
                        'result_dispatched_date_to_clinic'=>$resultDispatchedDateToClinic,
                        'asante_rapid_recency_assy'=>$params['asanteRapidRecencyAssay'],
                        'status'=>base64_decode($params['status'])
                    );
            $this->update($data,array('data_collection_id'=>$dataCollectionId));
			//Add new row into data collection event log table
			$dbAdapter = $this->adapter;
			$dataCollectionEventLogDb = new DataCollectionEventLogTable($dbAdapter);
			$data['data_collection_id'] = $dataCollectionId;
			$data['country']=$loginContainer->country;
			$data['updated_on'] = $common->getDateTime();
			$data['updated_by'] = $loginContainer->employeeId;
			$dataCollectionEventLogDb->insert($data);
        }
      return $dataCollectionId;
    }
	
    public function lockDataCollectionDetails($params){
	    return $this->update(array('lock_state'=>'lock'),array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function unlockDataCollectionDetails($params){
	    return $this->update(array('lock_state'=>'unlock'),array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function requestForUnlockDataCollectionDetails($params){
	    return $this->update(array('request_state'=>'requested'),array('data_collection_id'=>base64_decode($params['dataCollectionId'])));
    }
    
    public function fetchAllDataExtractions($parameters){
	$loginContainer = new Container('user');
	$queryContainer = new Container('query');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	    $common = new CommonService();
		if($loginContainer->roleCode =='CSC'){
            $aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','f.facility_name','f.facility_code','da_c.lab_specimen_id','c.country_name','t.test_status_name');
            $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','c.country_name','t.test_status_name');
		}else{
			$aColumns = array('da_c.surveillance_id',"DATE_FORMAT(da_c.specimen_collected_date,'%d-%b-%Y')",'anc.anc_site_name','anc.anc_site_code','da_c.anc_patient_id','f.facility_name','f.facility_code','da_c.lab_specimen_id','t.test_status_name');
            $orderColumns = array('da_c.surveillance_id','da_c.specimen_collected_date','anc.anc_site_name','da_c.anc_patient_id','da_c.specimen_picked_up_date_at_anc','f.facility_name','da_c.lab_specimen_id','t.test_status_name');
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
                     ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
		     ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'))
		     ->join(array('r_r' => 'specimen_rejection_reason'), "r_r.rejection_reason_id=da_c.rejection_reason",array('rejection_code'),'left');
	if($loginContainer->roleCode!= 'CSC'){
	    $sQuery = $sQuery->where(array('da_c.country'=>$loginContainer->country));
	}
	if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $sQuery = $sQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
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
				  ->join(array('c' => 'country'), "c.country_id=da_c.country",array('country_name'))
				  ->join(array('t' => 'test_status'), "t.test_status_id=da_c.status",array('test_status_name'));
	if($loginContainer->roleCode!= 'CSC'){
	    $tQuery = $tQuery->where(array('da_c.country'=>$loginContainer->country));
	}
	if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $tQuery = $tQuery->where(array('da_c.country'=>base64_decode($parameters['country'])));  
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
		$specimenCollectionDate = '';
		if(isset($aRow['specimen_collected_date']) && trim($aRow['specimen_collected_date'])!= '' && $aRow['specimen_collected_date']!= '0000-00-00'){
		    $specimenCollectionDate = $common->humanDateFormat($aRow['specimen_collected_date']);
		}
		$row[] = $aRow['surveillance_id'];
		$row[] = $specimenCollectionDate;
		$row[] = ucwords($aRow['anc_site_name'])." - ".$aRow['anc_site_code'];
		$row[] = $aRow['anc_patient_id'];
		$row[] = ucwords($aRow['facility_name'])." - ".$aRow['facility_code'];
		$row[] = $aRow['lab_specimen_id'];
		if($loginContainer->roleCode =='CSC'){
		   $row[] = ucwords($aRow['country_name']);
		}
		$row[] = ucwords($aRow['test_status_name']);
		$output['aaData'][] = $row;
	}
       return $output;
    }
}