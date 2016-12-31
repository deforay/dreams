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
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */

       $aColumns = array('anc_site_name','anc_site_code','reporting_month_year','country_name','characteristics_data');
       $orderColumns = array('anc_site_name','anc_site_code','reporting_month_year','country_name');

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
                     ->join(array('c'=>'country'),'c.country_id=cl_da_c.country',array('country_name'));
       if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
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
       $iTotal = $this->select()->count();
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
           $row[] = ucwords($aRow['anc_site_name']);
           $row[] = $aRow['anc_site_code'];
           $row[] = ucfirst($aRow['reporting_month_year']);
           $row[] = ucwords($aRow['country_name']);
           foreach($ancFormFieldList as $key=>$value){
                $characteristicsVal = '';
                if(isset($aRow['characteristics_data']) && trim($aRow['characteristics_data'])!= ''){
                    $fields = json_decode($aRow['characteristics_data'],true);
                    foreach($fields as $fieldName=>$fieldValue){
                        if($key == $fieldName){
                            $fieldValue[0]['age_lt_15'] = (trim($fieldValue[0]['age_lt_15'])!= '')?$fieldValue[0]['age_lt_15']:0;
                            $fieldValue[0]['age_15_to_19'] = (trim($fieldValue[0]['age_15_to_19'])!= '')?$fieldValue[0]['age_15_to_19']:0;
                            $fieldValue[0]['age_20_to_24'] = (trim($fieldValue[0]['age_20_to_24'])!= '')?$fieldValue[0]['age_20_to_24']:0;
                            $fieldValue[0]['total'] = (trim($fieldValue[0]['total'])!= '')?$fieldValue[0]['total']:0;
                            $characteristicsVal.= '<span style="color:red;"><strong>Age < 15</strong></span> : '.$fieldValue[0]['age_lt_15'].',';
                            $characteristicsVal.= ' <span style="color:orange;"><strong>Age 15-19</strong></span> : '.$fieldValue[0]['age_15_to_19'].',';
                            $characteristicsVal.= ' <span style="color:#8DD63E;"><strong>Age 20-24</strong></span> : '.$fieldValue[0]['age_20_to_24'].',';
                            $characteristicsVal.= ' <span style="color:#528A16;"><strong>Total</strong></span> : '.$fieldValue[0]['total'];
                        }
                    }
                }
              $row[] = '<div style="width:310px !important;">'.$characteristicsVal.'</div>';
           }
           $output['aaData'][] = $row;
       }
      return $output;
    }
}