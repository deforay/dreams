<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class ClinicDataCollectionTable extends AbstractTableGateway {

    protected $table = 'clinic_data_collection';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addClinicDataCollectionDetails($params){
        $loginContainer = new Container('user');
        $common = new CommonService();
        $lastInsertedId = 0;
        if(isset($params['anc']) && trim($params['anc'])!= ''){
            //set characteristics data
            $reportingArray = array();
            if(count($params['field']) >0){
                for($f=0;$f<count($params['field']);$f++){
                    $listArray = array();
                    foreach($params[$params['field'][$f]] as $key=>$value){
                       $listArray[$key] = $value;
                    }
                    $reportingArray[$params['field'][$f]][] = $listArray;
                }
            }
            $characteristicsVal = ($reportingArray >0)?json_encode($reportingArray):NULL;
            $data = array(
                        'anc'=>base64_decode($params['anc']),
                        'reporting_month_year'=>strtolower($params['reportingMonthYear']),
                        'characteristics_data'=>$characteristicsVal,
                        'comments'=>$params['comments'],
                        'country'=>base64_decode($params['chosenCountry']),
                        'status'=>1,
                        'added_on'=>$common->getDateTime(),
                        'added_by'=>$loginContainer->userId
                    );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
      return $lastInsertedId;
    }
    
    public function fetchAllClinicDataCollections($parameters){
        $loginContainer = new Container('user');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
        $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','characteristics_data','test_status_name');
        $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','reporting_month_year','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','test_status_name');
       }else{
        $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','country_name','characteristics_data','test_status_name');
        $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','reporting_month_year','country_name','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','test_status_name');
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
       $sQuery = $sql->select()->from(array('cl_da_c'=>'clinic_data_collection'))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_id=cl_da_c.anc',array('anc_site_name','anc_site_code'))
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'))
                     ->join(array('t' => 'test_status'), "t.test_status_id=cl_da_c.status",array('test_status_name'));
        if($loginContainer->roleCode == 'ANCSC'){
           $sQuery = $sQuery->where(array('cl_da_c.added_by'=>$loginContainer->userId));
        } if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
            $sQuery = $sQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
        }else if($loginContainer->roleCode== 'CC'){
	   $sQuery = $sQuery->where('cl_da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	} if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
           $sQuery = $sQuery->where(array('cl_da_c.anc'=>base64_decode($parameters['anc'])));
        }if(isset($parameters['reportingMonthYear']) && trim($parameters['reportingMonthYear'])!= ''){
           $sQuery = $sQuery->where(array('cl_da_c.reporting_month_year'=>strtolower($parameters['reportingMonthYear'])));
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
       $tQuery = $sql->select()->from(array('cl_da_c'=>'clinic_data_collection'))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_id=cl_da_c.anc',array('anc_site_name','anc_site_code'))
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'))
                     ->join(array('t' => 'test_status'), "t.test_status_id=cl_da_c.status",array('test_status_name'));
        if($loginContainer->roleCode == 'ANCSC'){
           $tQuery = $tQuery->where(array('cl_da_c.added_by'=>$loginContainer->userId));
        } if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
            $tQuery = $tQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
        }else if($loginContainer->roleCode== 'CC'){
	   $tQuery = $tQuery->where('cl_da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
       $tQueryStr = $sql->getSqlStringForSqlObject($tQuery);
       $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       $iTotal = count($tResult);
       $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
       );
       $ancFormDb = new AncFormTable($dbAdapter);
       $ancFormFieldList = $ancFormDb->fetchActiveAncFormFields();
       foreach ($rResult as $aRow) {
            $reportingMonth = '';
            $reportingYear = '';
            if($aRow['reporting_month_year']!= null && trim($aRow['reporting_month_year'])!= ''){
                $xplodReportingMonthYear = explode('/',$aRow['reporting_month_year']);
                $reportingMonth = $xplodReportingMonthYear[0];
                $reportingYear = $xplodReportingMonthYear[1];
            }
            $row = array();
            $row[] = ucwords($aRow['anc_site_name']);
            $row[] = $aRow['anc_site_code'];
            $row[] = ucfirst($reportingMonth);
            $row[] = $reportingYear;
            if($parameters['countryId']== ''){
              $row[] = ucwords($aRow['country_name']);
            }
            foreach($ancFormFieldList as $key=>$value){
                //for non-existing fields
                $colVal = '';
                if($value == 'yes'){
                    $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : 0,';
                    $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : 0,';
                    $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : 0,';
                }
                $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : 0';
                if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                    $fields = json_decode($aRow['characteristics_data'],true);
                    foreach($fields as $fieldName=>$fieldValue){
                        if($key == $fieldName){
                            //re-intialize to show existing fields
                            $colVal = '';
                            foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                               if($characteristicsName =='age_lt_15'){
                                  if($value == 'yes'){ $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : '.$characteristicsValue.','; }
                               }elseif($characteristicsName =='age_15_to_19'){
                                  if($value == 'yes'){ $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : '.$characteristicsValue.','; }
                               }elseif($characteristicsName =='age_20_to_24'){
                                  if($value == 'yes'){ $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : '.$characteristicsValue.','; }
                               }elseif($characteristicsName =='total'){
                                  $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : '.$characteristicsValue;
                               }
                            }
                        }
                    }
                }
               $row[] = '<div>&nbsp;&nbsp;'.$colVal.'&nbsp;&nbsp;</div>';
            }
            $userUnlockedHistory = '';
	    if($aRow['unlocked_on']!= null && trim($aRow['unlocked_on'])!= '' && $aRow['unlocked_on']!= '0000-00-00 00:00:00'){
		$unlockedDate = explode(" ",$aRow['unlocked_on']);
		$userQuery = $sql->select()->from(array('u' => 'user'))
		                           ->columns(array('user_id','full_name'))
				           ->where(array('u.user_id'=>$aRow['unlocked_by']));
	        $userQueryStr = $sql->getSqlStringForSqlObject($userQuery);
	        $userResult = $dbAdapter->query($userQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
		$unlockedBy = 'System';
		if(isset($userResult->user_id)){
		    $unlockedBy = ($userResult->user_id == $loginContainer->userId)?'You':ucwords($userResult->full_name);
		}
	       $userUnlockedHistory = '<i class="zmdi zmdi-info-outline unlocKbtn" title="This row was unlocked on '.$common->humanDateFormat($unlockedDate[0])." ".$unlockedDate[1].' by '.$unlockedBy.'" style="font-size:1.3rem;"></i>';
	    }
            $dataEdit = '';
	    $dataLock = '';
	    $dataUnlock = '';
            //for edit
            $dataEdit = '<a href="/clinic/data-collection/edit/' . base64_encode($aRow['cl_data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp;';
            if($aRow['test_status_name']== 'completed'){
                $dataLock = '<a href="javascript:void(0);" onclick="lockClinicDataCollection(\''.base64_encode($aRow['cl_data_collection_id']).'\');" class="waves-effect waves-light btn-small btn green-text custom-btn custom-btn-green margin-bottom-10" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a>&nbsp;&nbsp;';
            }
            //for csc/cc
            if(($loginContainer->roleCode== 'CSC' || $loginContainer->roleCode== 'CC') && $aRow['test_status_name']== 'locked'){
                $dataUnlock = '<a href="javascript:void(0);" onclick="unlockClinicDataCollection(\''.base64_encode($aRow['cl_data_collection_id']).'\');" class="waves-effect waves-light btn-small btn red-text custom-btn custom-btn-red margin-bottom-10" title="Unlock"><i class="zmdi zmdi-lock-open"></i> Unlock</a>&nbsp;&nbsp;';
            }
            $dataLockUnlock = (trim($dataLock)!= '')?$dataLock:$dataUnlock;
            $row[] = ucfirst($aRow['test_status_name']);
            if($loginContainer->hasViewOnlyAccess!= 'yes'){
              $row[] = $dataEdit.$dataLockUnlock.$userUnlockedHistory;
            }
            $output['aaData'][] = $row;
       }
      return $output;
    }
    
    public function fetchClinicDataCollection($clinicDataCollectionId){
        return $this->select(array('cl_data_collection_id'=>$clinicDataCollectionId))->current();
    }
    
    public function updateClinicDataCollectionDetails($params){
        $loginContainer = new Container('user');
        $common = new CommonService();
        $clinicDataCollectionId = 0;
        if(isset($params['anc']) && trim($params['anc'])!= ''){
            $clinicDataCollectionId = base64_decode($params['clinicDataCollectionId']);
            //set updated characteristics data
            $reportingArray = array();
            if(count($params['field']) >0){
                for($f=0;$f<count($params['field']);$f++){
                    $listArray = array();
                    foreach($params[$params['field'][$f]] as $key=>$value){
                       $listArray[$key] = $value;
                    }
                    $reportingArray[$params['field'][$f]][] = $listArray;
                }
            }
            $characteristicsVal = ($reportingArray >0)?json_encode($reportingArray):NULL;
            $status = (base64_decode($params['status']) == 2)?base64_decode($params['status']):1;
            $data = array(
                        'anc'=>base64_decode($params['anc']),
                        'reporting_month_year'=>strtolower($params['reportingMonthYear']),
                        'characteristics_data'=>$characteristicsVal,
                        'comments'=>$params['comments'],
                        'country'=>base64_decode($params['chosenCountry']),
                        'status'=>$status,
                        'updated_on'=>$common->getDateTime(),
                        'updated_by'=>$loginContainer->userId
                    );
            $this->update($data,array('cl_data_collection_id'=>$clinicDataCollectionId));
        }
      return $clinicDataCollectionId;
    }
    
    public function fetchAllClinicalDataExtractions($parameters){
        $loginContainer = new Container('user');
        $queryContainer = new Container('query');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
          $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','characteristics_data','cl_da_c.comments');
          $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','reporting_month_year','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','cl_da_c.comments');
       }else{
          $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','country_name','characteristics_data','cl_da_c.comments');
          $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','reporting_month_year','country_name','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','characteristics_data','cl_da_c.comments');
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
       $sQuery = $sql->select()->from(array('cl_da_c'=>'clinic_data_collection'))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_id=cl_da_c.anc',array('anc_site_name','anc_site_code'))
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'))
                     ->join(array('t' => 'test_status'), "t.test_status_id=cl_da_c.status",array('test_status_name'))
                     ->where('cl_da_c.status IN (2)');
        if($loginContainer->roleCode == 'ANCSC'){
           $sQuery = $sQuery->where(array('cl_da_c.added_by'=>$loginContainer->userId));
        } if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
            $sQuery = $sQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
        }else if($loginContainer->roleCode== 'CC'){
	   $sQuery = $sQuery->where('cl_da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
          $sQuery = $sQuery->where(array('cl_da_c.anc'=>base64_decode($parameters['anc'])));
        }if(isset($parameters['reportingMonthYear']) && trim($parameters['reportingMonthYear'])!= ''){
          $sQuery = $sQuery->where(array('cl_da_c.reporting_month_year'=>strtolower($parameters['reportingMonthYear'])));
        }
       
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->clinicDataCollectionQuery = $sQuery;
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
        $tQuery = $sql->select()->from(array('cl_da_c'=>'clinic_data_collection'))
                      ->join(array('anc'=>'anc_site'),'anc.anc_site_id=cl_da_c.anc',array('anc_site_name','anc_site_code'))
                      ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'))
                      ->join(array('t' => 'test_status'), "t.test_status_id=cl_da_c.status",array('test_status_name'))
                      ->where('cl_da_c.status IN (2)');
        if($loginContainer->roleCode == 'ANCSC'){
           $tQuery = $tQuery->where(array('cl_da_c.added_by'=>$loginContainer->userId));
        } if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
            $tQuery = $tQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
        }else if($loginContainer->roleCode== 'CC'){
	   $tQuery = $tQuery->where('cl_da_c.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
       $tQueryStr = $sql->getSqlStringForSqlObject($tQuery);
       $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       $iTotal = count($tResult);
       $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
       );
       $ancFormDb = new AncFormTable($dbAdapter);
       $ancFormFieldList = $ancFormDb->fetchActiveAncFormFields();
       foreach ($rResult as $aRow) {
           $reportingMonth = '';
           $reportingYear = '';
           if(isset($aRow['reporting_month_year']) && trim($aRow['reporting_month_year'])!= ''){
               $xplodReportingMonthYear = explode('/',$aRow['reporting_month_year']);
               $reportingMonth = $xplodReportingMonthYear[0];
               $reportingYear = $xplodReportingMonthYear[1];
           }
           $row = array();
           $row[] = ucwords($aRow['anc_site_name']);
           $row[] = $aRow['anc_site_code'];
           $row[] = ucfirst($reportingMonth);
           $row[] = $reportingYear;
           if($parameters['countryId']== ''){
              $row[] = ucwords($aRow['country_name']);
           }
           foreach($ancFormFieldList as $key=>$value){
                //for non-existing fields
                $colVal = '';
                if($value == 'yes'){
                    $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : 0,';
                    $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : 0,';
                    $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : 0,';
                }
                $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : 0';
                if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                    $fields = json_decode($aRow['characteristics_data'],true);
                    foreach($fields as $fieldName=>$fieldValue){
                        if($key == $fieldName){
                            //re-intialize to show existing fields
                            $colVal = '';
                            foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                               if($characteristicsName =='age_lt_15'){
                                  if($value == 'yes'){ $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : '.$characteristicsValue.','; }
                               }elseif($characteristicsName =='age_15_to_19'){
                                  if($value == 'yes'){ $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : '.$characteristicsValue.','; }
                               }elseif($characteristicsName =='age_20_to_24'){
                                  if($value == 'yes'){ $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : '.$characteristicsValue.','; }
                               }elseif($characteristicsName =='total'){
                                  $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : '.$characteristicsValue;
                               }
                            }
                        }
                    }
                }
              $row[] = '<div>&nbsp;&nbsp;'.$colVal.'&nbsp;&nbsp;</div>';
           }
           $row[] = ucfirst($aRow['comments']);
           $output['aaData'][] = $row;
       }
      return $output;
    }
    
    public function lockClinicDataCollectionDetails($params){
        $loginContainer = new Container('user');
	$common = new CommonService();
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$data = array(
	    'status'=>2,
	    'locked_on'=>$common->getDateTime(),
	    'locked_by'=>(isset($loginContainer->userId))?$loginContainer->userId:NULL
	);
      return $this->update($data,array('cl_data_collection_id'=>base64_decode($params['clinicDataCollectionId'])));
    }
    
    public function unlockClinicDataCollectionDetails($params){
        $loginContainer = new Container('user');
	$common = new CommonService();
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$data = array(
	    'status'=>3,
	    'unlocked_on'=>$common->getDateTime(),
	    'unlocked_by'=>(isset($loginContainer->userId))?$loginContainer->userId:NULL
	);
      return $this->update($data,array('cl_data_collection_id'=>base64_decode($params['clinicDataCollectionId'])));
    }
}