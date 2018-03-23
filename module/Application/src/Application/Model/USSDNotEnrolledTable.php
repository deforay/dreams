<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class USSDNotEnrolledTable extends AbstractTableGateway {

    protected $table = 'ussd_not_enrolled';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchUSSDNotEnrolledData($parameters){
        $queryContainer = new Container('query');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $aColumns = array('anc_site_name','facility','reason_not_enrolled','reason_not_enrolled_other');
        $orderColumns = array('anc_site_name','reason_not_enrolled','reason_not_enrolled_other');

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
        if(isset($parameters['dateRange']) && trim($parameters['dateRange'])!= ''){
	    $date = explode("to", $parameters['dateRange']);
	    if(isset($date[0]) && trim($date[0]) != "") {
	       $start_date = $common->dateRangeFormat(trim($date[0]));
	    }if(isset($date[1]) && trim($date[1]) != "") {
	       $end_date = $common->dateRangeFormat(trim($date[1]));
	    }
	}
       $dbAdapter = $this->adapter;
       $sql = new Sql($dbAdapter);
       $sQuery = $sql->select()->from(array('ussd_n_e'=>'ussd_not_enrolled'))
                     ->columns(array('facility','reasonNotEnrolled' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 1), 1,0))"),'reasonNotEnrolledOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 2), 1,0))")))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_code=ussd_n_e.facility',array('anc_site_name'))
                     ->group('facility');
        //custom filter start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
            $sQuery = $sQuery->where(array("date >='" . $start_date ."'", "date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("date = '" . $start_date. "'"));
        }
        if(isset($parameters['facility']) && trim($parameters['facility'])!= ''){
            $facilityArray = array_filter(explode(',',$parameters['facility']));
            $parameters['facility'] = implode(',',$facilityArray);
           $sQuery = $sQuery->where('anc.anc_site_id IN('.$parameters['facility'].')');
        }
        if(isset($parameters['reasonType']) && trim($parameters['reasonType'])!= ''){
           $sQuery = $sQuery->where(array('ussd_n_e.reason_not_enrolled'=>$parameters['reasonType']));
        }
       //custom filter end
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->ussdNotEnrolledQuery = $sQuery;
       
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
        $tQuery = $sql->select()->from(array('ussd_n_e'=>'ussd_not_enrolled'))
                      ->columns(array('facility','reasonNotEnrolled' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 1), 1,0))"),'reasonNotEnrolledOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 2), 1,0))")))
                      ->join(array('anc'=>'anc_site'),'anc.anc_site_code=ussd_n_e.facility',array('anc_site_name'))
                      ->group('facility');
       $tQueryStr = $sql->getSqlStringForSqlObject($tQuery);
       $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       $iTotal = count($tResult);
       $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
       );
       foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $aRow['facility'].' - '.ucwords($aRow['anc_site_name']);
            $row[] = $aRow['reasonNotEnrolled'];
            $row[] = $aRow['reasonNotEnrolledOther'];
            $output['aaData'][] = $row;
       }
      return $output;
    }
}