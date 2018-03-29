<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;


class LocationDetailsTable extends AbstractTableGateway {

    protected $table = 'location_details';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchOdkSupervisoryAuditDetails($parameters){
        $queryContainer = new Container('query');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $aColumns = array('code_known_group:code','facility_name','rep_period_1',"DATE_FORMAT(`date`,'%d-%b-%Y %H:%i:%s')",'eligibility_2','study_activity:not_eligible_to_calculate','study_activity:dc_review_1','study_activity:dc_review_2','study_activity:dc_review_3','study_activity:dc_review_4','study_activity:dc_review_5');
        $orderColumns = array('code_known_group:code','facility_name','','rep_period_1','date','eligibility_2','','','','','','','','study_activity:not_eligible_to_calculate','study_activity:dc_review_1','study_activity:dc_review_2','study_activity:dc_review_3','study_activity:dc_review_4','study_activity:dc_review_5');

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
                   $aColumns[$i] = ($i == 3)?$aColumns[$i]:"`".$aColumns[$i]."`";
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
               $aColumns[$i] = ($i == 3)?$aColumns[$i]:"`".$aColumns[$i]."`";
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
        $tbl = "";
        $iTotal = 0;
        $iFilteredTotal = 0;
        $rResult = array();
        if(isset($parameters['province']) && trim($parameters['province'])!= ''){
            $tbl = "supervisor_checklist_".$parameters['province'];
            $sQuery = $sql->select()->from(array('s_c_'.$parameters['province']=>$tbl))
                         ->columns(array('code_known_group:code',
                                        'facility_name',
                                        'rep_period_1',
                                        'date',
                                        'eligibility_1',
                                        'eligibility_2',
                                        'participants_2',
                                        'study_activity:not_eligible_to_calculate',
                                        'study_activity:dc_review_1',
                                        'study_activity:dc_review_2',
                                        'study_activity:dc_review_3',
                                        'study_activity:dc_review_4',
                                        'study_activity:dc_review_5'
                                    ));
            //custom filter start
            if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
                $sQuery = $sQuery->where(array("date >='" . $start_date ."'", "date <='" . $end_date."'"));
            }else if (trim($start_date) != "") {
                $sQuery = $sQuery->where(array("date = '" . $start_date. "'"));
            }
           //custom filter end
           if (isset($sWhere) && $sWhere != "") {
               $sQuery->where($sWhere);
           }
    
           if (isset($sOrder) && $sOrder != "") {
               $sQuery->order($sOrder);
           }
           
           $queryContainer->odkSupervisoryAuditQuery = $sQuery;
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
           $tQuery = $sql->select()->from(array('s_c_'.$parameters['province']=>$tbl))
                         ->columns(array('code_known_group:code',
                                        'facility_name',
                                        'rep_period_1',
                                        'date',
                                        'eligibility_1',
                                        'eligibility_2',
                                        'participants_2',
                                        'study_activity:not_eligible_to_calculate',
                                        'study_activity:dc_review_1',
                                        'study_activity:dc_review_2',
                                        'study_activity:dc_review_3',
                                        'study_activity:dc_review_4',
                                        'study_activity:dc_review_5'
                                    ));
           $tQueryStr = $sql->getSqlStringForSqlObject($tQuery);
           $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
           $iTotal = count($tResult);
        }
       $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
       );
       foreach ($rResult as $aRow) {
            $supportVisitDate = '';
            $noofVisittoClinic = 0;
            $countQuery = $sql->select()->from(array('s_c_'.$parameters['province']=>$tbl))
                              ->columns(array("totalVisit" => new Expression('COUNT(*)')))
                              ->where('(`code_known_group:code` = "'.$aRow['code_known_group:code'].'" OR `facility_name` = "'.$aRow['facility_name'].'")');
            if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
                $countQuery = $countQuery->where(array("date >='" . $start_date ."'", "date <='" . $end_date."'"));
            }else if (trim($start_date) != "") {
                $countQuery = $countQuery->where(array("date = '" . $start_date. "'"));
            }
            $countQueryStr = $sql->getSqlStringForSqlObject($countQuery);
            $countResult = $dbAdapter->query($countQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            $noofVisittoClinic = (isset($countResult->totalVisit))?$countResult->totalVisit:0;
            if($aRow['date']!= NULL && $aRow['date']!= '' && $aRow['date']!='0000-00-00 00:00:00.0' && $aRow['date']!='0000-00-00 00:00:00' && $aRow['date']!='1970-01-01 00:00:00.0' && $aRow['date']!='1970-01-01 00:00:00'){
                $dateArray = explode(" ",$aRow['date']);
                $supportVisitDate = $common->humanDateFormat($dateArray[0])." ".$dateArray[1];
            }
            $noofEligibleWomennotInvitedtoParticipateinReportingPeriod = (int)$aRow['eligibility_1'] - (int)$aRow['participants_2'] - (int)$aRow['eligibility_2'];
            $row = array();
            $row[] = $aRow['code_known_group:code'];
            $row[] = ucwords($aRow['facility_name']);
            $row[] = $noofVisittoClinic;
            $row[] = $aRow['rep_period_1'];
            $row[] = $supportVisitDate;
            $row[] = $aRow['eligibility_2'];
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = $noofEligibleWomennotInvitedtoParticipateinReportingPeriod;
            $row[] = $aRow['study_activity:not_eligible_to_calculate'];
            $row[] = $aRow['study_activity:dc_review_1'];
            $row[] = $aRow['study_activity:dc_review_2'];
            $row[] = $aRow['study_activity:dc_review_3'];
            $row[] = $aRow['study_activity:dc_review_4'];
            $row[] = $aRow['study_activity:dc_review_5'];
            $output['aaData'][] = $row;
       }
      return $output;
    }
}