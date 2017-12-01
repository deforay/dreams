<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class AncRapidRecencyTable extends AbstractTableGateway {

    protected $table = 'anc_rapid_recency';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchANCAsanteResults($parameters){
        $loginContainer = new Container('user');
        $queryContainer = new Container('query');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$aColumns = array('patient_barcode_id','anc_site_name','location_name','HIV_diagnostic_line','recency_line');

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
                   $sOrder .= $aColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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
                        if($aColumns[$i] == 'HIV_diagnostic_line' && strpos($absent,$search) !== false){
                           $search = 'negative';	
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                        }else if($aColumns[$i] == 'HIV_diagnostic_line' && strpos($present,$search) !== false){
                           $search = 'positive';
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                        }else if($aColumns[$i] == 'recency_line' && strpos($absent,$search) !== false){
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
                        if($aColumns[$i] == 'HIV_diagnostic_line' && strpos($absent,$search) !== false){
                           $search = 'negative';	
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                        }else if($aColumns[$i] == 'HIV_diagnostic_line' && strpos($present,$search) !== false){
                           $search = 'positive';
			   $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                        }else if($aColumns[$i] == 'recency_line' && strpos($absent,$search) !== false){
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
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('anc_r_r' => 'anc_rapid_recency'))
                                ->join(array('r_a' => 'clinic_risk_assessment'), "r_a.assessment_id=anc_r_r.assessment_id",array('patient_barcode_id'))
                                ->join(array('anc' => 'anc_site'), "anc.anc_site_id=r_a.anc",array('anc_site_name'))
                                ->join(array('l_d' => 'location_details'), "l_d.location_id=anc.district",array('location_name'),'left');
        if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $sQuery = $sQuery->where(array('r_a.country'=>$parameters['country']));
	} if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){  
           $sQuery = $sQuery->where(array('r_a.anc'=>base64_decode($parameters['anc'])));
        } if(isset($parameters['district']) && trim($parameters['district'])!= ''){  
           $sQuery = $sQuery->where(array('l_d.location_id'=>base64_decode($parameters['district'])));
        } if(isset($parameters['recencyLine']) && trim($parameters['recencyLine'])!= ''){
           $sQuery = $sQuery->where(array('anc_r_r.recency_line'=>$parameters['recencyLine']));
        } if(isset($parameters['hasPatientHadRapidRecencyTest']) && trim($parameters['hasPatientHadRapidRecencyTest']) == 'yes'){
           $sQuery = $sQuery->where(array('anc_r_r.has_patient_had_rapid_recency_test'=>'done'));
        }else if(isset($parameters['hasPatientHadRapidRecencyTest']) && trim($parameters['hasPatientHadRapidRecencyTest']) == 'no'){
           $sQuery = $sQuery->where(array('anc_r_r.has_patient_had_rapid_recency_test'=>'not done')); 
        }
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->ancAsanteResultQuery = $sQuery;
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
	$tQuery = $sql->select()->from(array('anc_r_r' => 'anc_rapid_recency'))
                                ->join(array('r_a' => 'clinic_risk_assessment'), "r_a.assessment_id=anc_r_r.assessment_id",array('patient_barcode_id'))
                                ->join(array('anc' => 'anc_site'), "anc.anc_site_id=r_a.anc",array('anc_site_name'))
                                ->join(array('l_d' => 'location_details'), "l_d.location_id=anc.district",array('location_name'),'left');
	if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	    $tQuery = $tQuery->where(array('r_a.country'=>$parameters['country']));
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
            $ancHIVVerificationClassification = 'Not Done';
            $ancRecencyVerificationClassification = 'Not Done';
            if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line']) == 'positive'){
                $ancHIVVerificationClassification = 'Present';
            }else if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line']) == 'negative'){
                $ancHIVVerificationClassification = 'Absent';
                $ancRecencyVerificationClassification = '';
            }else if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line']) == 'invalid') {
                $ancHIVVerificationClassification = 'Invalid';
            }
            if(isset($aRow['HIV_diagnostic_line']) && trim($aRow['HIV_diagnostic_line'])!= 'negative'){
                if(isset($aRow['recency_line']) && trim($aRow['recency_line']) == 'recent'){
                    $ancRecencyVerificationClassification = 'Absent';
                }else if(isset($aRow['recency_line']) && trim($aRow['recency_line']) == 'long term'){
                    $ancRecencyVerificationClassification = 'Present';
                }else if(isset($aRow['recency_line']) && trim($aRow['recency_line']) == 'invalid') {
                    $ancRecencyVerificationClassification = 'Invalid';
                }
            }
	    $row = array();
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = (isset($aRow['location_name']))?ucwords($aRow['location_name']):'';
            $row[] = $ancHIVVerificationClassification;
            $row[] = $ancRecencyVerificationClassification;
	    $output['aaData'][] = $row;
	}
      return $output;
    }
}