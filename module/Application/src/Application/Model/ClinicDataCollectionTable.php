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
        $lastInsertedId = 0;
        if(isset($params['anc']) && trim($params['anc'])!= ''){
            $common = new CommonService();
            //Set characteristics data
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
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */

       $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','country_name','characteristics_data','cl_da_c.comments');
       $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','reporting_month_year','country_name');

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
       $mappedANC = array();
       $uMapQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
                                  ->where(array('cl_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped ANC
       foreach($uMapResult as $anc){
	   $mappedANC[] = $anc['clinic_id'];
       }
       $sQuery = $sql->select()->from(array('cl_da_c'=>'clinic_data_collection'))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_id=cl_da_c.anc',array('anc_site_name','anc_site_code'))
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'));
        if($loginContainer->roleCode == 'ANCDEO'){
            $sQuery = $sQuery->where('cl_da_c.anc IN ("' . implode('", "', $mappedANC) . '")');
        }
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
          $sQuery = $sQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
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
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'));
        if($loginContainer->roleCode == 'ANCDEO'){
            $tQuery = $tQuery->where('cl_da_c.anc IN ("' . implode('", "', $mappedANC) . '")');
        }
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
          $tQuery = $tQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
       }if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
          $tQuery = $tQuery->where(array('cl_da_c.anc'=>base64_decode($parameters['anc'])));
       }if(isset($parameters['reportingMonthYear']) && trim($parameters['reportingMonthYear'])!= ''){
          $tQuery = $tQuery->where(array('cl_da_c.reporting_month_year'=>strtolower($parameters['reportingMonthYear'])));
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
           foreach($ancFormFieldList as $key=>$value){
                //For non-existing fields
                $colVal = '';
                $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : 0,';
                $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : 0,';
                $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : 0,';
                $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : 0';
                if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                    $fields = json_decode($aRow['characteristics_data'],true);
                    foreach($fields as $fieldName=>$fieldValue){
                        if($key == $fieldName){
                            //Re-intialize to show existing fields
                            $colVal = '';
                            foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                               if($characteristicsName =='age_lt_15'){
                                  $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : '.$characteristicsValue.',';
                               }elseif($characteristicsName =='age_15_to_19'){
                                  $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : '.$characteristicsValue.',';
                               }elseif($characteristicsName =='age_20_to_24'){
                                  $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : '.$characteristicsValue.',';
                               }elseif($characteristicsName =='total'){
                                  $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : '.$characteristicsValue;
                               }
                            }
                        }
                    }
                }
              $row[] = '<div>'.$colVal.'&nbsp;&nbsp;</div>';
           }
           $row[] = ucfirst($aRow['comments']);
           if($loginContainer->hasViewOnlyAccess =='no') {
              $row[] = '<a href="/clinic/data-collection/edit/' . base64_encode($aRow['cl_data_collection_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
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
        $clinicDataCollectionId = 0;
        if(isset($params['anc']) && trim($params['anc'])!= ''){
            $clinicDataCollectionId = base64_decode($params['clinicDataCollectionId']);
            $common = new CommonService();
            //Set updated characteristics data
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

       $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','country_name','characteristics_data','cl_da_c.comments');
       $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','reporting_month_year','country_name');

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
       $mappedANC = array();
       $uMapQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
                                  ->where(array('cl_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped ANC
       foreach($uMapResult as $anc){
	   $mappedANC[] = $anc['clinic_id'];
       }
       $sQuery = $sql->select()->from(array('cl_da_c'=>'clinic_data_collection'))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_id=cl_da_c.anc',array('anc_site_name','anc_site_code'))
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'));
        if($loginContainer->roleCode == 'ANCDEO'){
            $sQuery = $sQuery->where('cl_da_c.anc IN ("' . implode('", "', $mappedANC) . '")');
        }
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
          $sQuery = $sQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
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
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'));
        if($loginContainer->roleCode == 'ANCDEO'){
            $tQuery = $tQuery->where('cl_da_c.anc IN ("' . implode('", "', $mappedANC) . '")');
        }
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
          $tQuery = $tQuery->where(array('cl_da_c.country'=>$parameters['countryId']));
       }if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
          $tQuery = $tQuery->where(array('cl_da_c.anc'=>base64_decode($parameters['anc'])));
       }if(isset($parameters['reportingMonthYear']) && trim($parameters['reportingMonthYear'])!= ''){
          $tQuery = $tQuery->where(array('cl_da_c.reporting_month_year'=>strtolower($parameters['reportingMonthYear'])));
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
           foreach($ancFormFieldList as $key=>$value){
                //For non-existing fields
                $colVal = '';
                $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : 0,';
                $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : 0,';
                $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : 0,';
                $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : 0';
                if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                    $fields = json_decode($aRow['characteristics_data'],true);
                    foreach($fields as $fieldName=>$fieldValue){
                        if($key == $fieldName){
                            //Re-intialize to show existing fields
                            $colVal = '';
                            foreach($fieldValue[0] as $characteristicsName=>$characteristicsValue){
                                $characteristicsValue = ($characteristicsValue!= '')?$characteristicsValue:0;
                               if($characteristicsName =='age_lt_15'){
                                  $colVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : '.$characteristicsValue.',';
                               }elseif($characteristicsName =='age_15_to_19'){
                                  $colVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : '.$characteristicsValue.',';
                               }elseif($characteristicsName =='age_20_to_24'){
                                  $colVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : '.$characteristicsValue.',';
                               }elseif($characteristicsName =='total'){
                                  $colVal.= ' <span style="color:#7cb5ec;"><strong>Total</strong></span> : '.$characteristicsValue;
                               }
                            }
                        }
                    }
                }
              $row[] = '<div>'.$colVal.'&nbsp;&nbsp;</div>';
           }
           $row[] = ucfirst($aRow['comments']);
           $output['aaData'][] = $row;
       }
      return $output;
    }
}