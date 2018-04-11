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
    
    public function fetchNotEnrolledData($parameters){
        $queryContainer = new Container('query');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $aColumns = array('anc_site_name','facility','reason_not_enrolled','reason_not_enrolled_other','reason_client_refused','reason_client_refused_other');
        $orderColumns = array('anc_site_name','reason_not_enrolled','reason_not_enrolled_other','reason_client_refused','reason_client_refused_other');

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
                     ->columns(array(
                                'facility',
                                'reasonNotEnrolled' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 1), 1,0))"),
                                'reasonNotEnrolledOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 2), 1,0))"),
                                'reasonRefused' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 1 OR reason_client_refused = 2 OR reason_client_refused = 3 OR reason_client_refused = 4 OR reason_client_refused = 5), 1,0))"),
                                'reasonRefusedOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 6), 1,0))")
                            )
                        )
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_code=ussd_n_e.facility',array('anc_site_name'))
                     ->group('facility');
        //custom filter start
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
            $sQuery = $sQuery->where(array("date >='" . $start_date ."'", "date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("date = '" . $start_date. "'"));
        }
        if(isset($parameters['facility']) && trim($parameters['facility'])!= ''){
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
       $queryContainer->notEnrolledQuery = $sQuery;
       
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
                      ->columns(array(
                                'facility',
                                'reasonNotEnrolled' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 1), 1,0))"),
                                'reasonNotEnrolledOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 2), 1,0))"),
                                'reasonRefused' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 1 OR reason_client_refused = 2 OR reason_client_refused = 3 OR reason_client_refused = 4 OR reason_client_refused = 5), 1,0))"),
                                'reasonRefusedOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 6), 1,0))")
                            )
                        )
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
            $row[] = $aRow['reasonRefused'];
            $row[] = $aRow['reasonRefusedOther'];
            $output['aaData'][] = $row;
       }
      return $output;
    }
    
    public function fetchNotEnrolledPieChartData($params){
        $common = new CommonService();
        $result = array();
        $start_date = '';
        $end_date = '';
        if(isset($params['dateRange']) && trim($params['dateRange'])!= ''){
	    $date = explode("to", $params['dateRange']);
	    if(isset($date[0]) && trim($date[0]) != "") {
	       $start_date = $common->dateRangeFormat(trim($date[0]));
	    }if(isset($date[1]) && trim($date[1]) != "") {
	       $end_date = $common->dateRangeFormat(trim($date[1]));
	    }
	}
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('ussd_n_e'=>'ussd_not_enrolled'))
                      ->columns(array('reasonNotEnrolled' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 1), 1,0))"),'reasonNotEnrolledOther' => new \Zend\Db\Sql\Expression("SUM(IF((reason_not_enrolled = 2), 1,0))")))
                      ->join(array('anc'=>'anc_site'),'anc.anc_site_code=ussd_n_e.facility',array());
        if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
            $sQuery = $sQuery->where(array("date >='" . $start_date ."'", "date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("date = '" . $start_date. "'"));
        }
        if(isset($params['facility']) && trim($params['facility'])!= ''){
           $sQuery = $sQuery->where('anc.anc_site_id IN('.$params['facility'].')');
        }
        if(isset($params['reasonType']) && trim($params['reasonType'])!= ''){
           $sQuery = $sQuery->where(array('ussd_n_e.reason_not_enrolled'=>$params['reasonType']));
        }
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $result[0]['reason_Not_Enrolled'] = (isset($sResult->reasonNotEnrolled))?$sResult->reasonNotEnrolled:0;
        $result[0]['reason_Not_Enrolled_Other'] = (isset($sResult->reasonNotEnrolledOther))?$sResult->reasonNotEnrolledOther:0;
      return $result;
    }
    
    public function fetchReasonforRefusedPieChartData($params){
        $common = new CommonService();
        $result = array();
        $start_date = '';
        $end_date = '';
        if(isset($params['dateRange']) && trim($params['dateRange'])!= ''){
	    $date = explode("to", $params['dateRange']);
	    if(isset($date[0]) && trim($date[0]) != "") {
	       $start_date = $common->dateRangeFormat(trim($date[0]));
	    }if(isset($date[1]) && trim($date[1]) != "") {
	       $end_date = $common->dateRangeFormat(trim($date[1]));
	    }
	}
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('ussd_n_e'=>'ussd_not_enrolled'))
                      ->columns(array(
                                    'reason1' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 1), 1,0))"),
                                    'reason2' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 2), 1,0))"),
                                    'reason3' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 3), 1,0))"),
                                    'reason4' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 4), 1,0))"),
                                    'reason5' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 5), 1,0))"),
                                    'reason6' => new \Zend\Db\Sql\Expression("SUM(IF((reason_client_refused = 6), 1,0))")
                                ))
                      ->join(array('anc'=>'anc_site'),'anc.anc_site_code=ussd_n_e.facility',array());
        if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
            $sQuery = $sQuery->where(array("date >='" . $start_date ."'", "date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("date = '" . $start_date. "'"));
        }
        if(isset($params['facility']) && trim($params['facility'])!= ''){
           $sQuery = $sQuery->where('anc.anc_site_id IN('.$params['facility'].')');
        }
        if(isset($params['reasonType']) && trim($params['reasonType'])!= ''){
           $sQuery = $sQuery->where(array('ussd_n_e.reason_not_enrolled'=>$params['reasonType']));
        }
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $result[0]['Reason 1'] = (isset($sResult->reason1))?$sResult->reason1:0;
        $result[0]['Reason 2'] = (isset($sResult->reason2))?$sResult->reason2:0;
        $result[0]['Reason 3'] = (isset($sResult->reason3))?$sResult->reason3:0;
        $result[0]['Reason 4'] = (isset($sResult->reason4))?$sResult->reason4:0;
        $result[0]['Reason 5'] = (isset($sResult->reason5))?$sResult->reason5:0;
        $result[0]['Reason 6'] = (isset($sResult->reason6))?$sResult->reason6:0;
      return $result;
    }
}