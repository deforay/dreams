<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class ReturnOfRecencyResultsTable extends AbstractTableGateway {

    protected $table = 'return_of_recency_results';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function saveReturnOfRecencyResults($params){
        $loginContainer = new Container('user');
        $common = new CommonService();
        $lastInsertedId = 0;

       // $status = 'incomplete';

       
        if(isset($params['patientBarcodeId']) && trim($params['patientBarcodeId'])!= ''){
            
            $existing = $this->fetchSingleReturnOfRecencyResult($params['patientBarcodeId']);
       
            if((isset($params['reasonForNotReturningResult']) && $params['reasonForNotReturningResult'] !='') || (isset($params['dateReturnedToPatient']) && $params['dateReturnedToPatient'] != '')){
                $status = 'complete';
            }else {
                $status = 'incomplete';
            }
           
            $data = array(
                        'anc'=>base64_decode($params['ancSite']),
                        'patient_barcode_id'=>($params['patientBarcodeId']),
                        'anc_patient_id'=>($params['ancPatientId']),
                        'date_returned_to_anc'=>(isset($params['dateReturnedToANC']) && trim($params['dateReturnedToANC'])!= '')?$common->dateFormat($params['dateReturnedToANC']):NULL,
                        'date_returned_to_participant'=>(isset($params['dateReturnedToPatient']) && trim($params['dateReturnedToPatient'])!= '')?$common->dateFormat($params['dateReturnedToPatient']):NULL,
                        'reason_for_not_returning'=>(isset($params['reasonForNotReturningResult']) && trim($params['reasonForNotReturningResult'])!= '')?($params['reasonForNotReturningResult']):NULL,
                        'reason_for_not_returning_other'=>(isset($params['reasonForNotReturningResultOther']) && trim($params['reasonForNotReturningResultOther'])!= '')?($params['reasonForNotReturningResultOther']):NULL,
                        'country'=>base64_decode($params['chosenCountry']),
                        'added_on'=>$common->getDateTime(),
                        'added_by'=>$loginContainer->userId,
                        'status'=>$status
                    );

                    //var_dump($data);

            if($existing == false){
                $this->insert($data);
                $lastInsertedId = $this->lastInsertValue;
            }else{
                $data['updated_on']=$common->getDateTime();
                $data['updated_by']=$loginContainer->userId;

                $lastInsertedId = $this->update($data,array('patient_barcode_id'=>$params['patientBarcodeId']));
            }   
        }
        //var_dump($lastInsertedId);die;
      return $lastInsertedId;
    }
    
    public function fetchAllReturnOfRecencyResults($parameters){
        $loginContainer = new Container('user');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
       if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
        $aColumns = array('patient_barcode_id','anc_site_name','anc_site_code','anc_patient_id',"DATE_FORMAT(date_returned_to_anc,'%d-%b-%Y')","DATE_FORMAT(date_returned_to_participant,'%d-%b-%Y'),'status'");
        $orderColumns = array('patient_barcode_id','anc_site_name','anc_site_code','anc_patient_id','date_returned_to_anc','date_returned_to_participant','status');
       }else{
        $aColumns = array('patient_barcode_id','anc_site_name','anc_site_code','anc_patient_id',"DATE_FORMAT(date_returned_to_anc,'%d-%b-%Y')","DATE_FORMAT(date_returned_to_participant,'%d-%b-%Y')",'country_name','status');
        $orderColumns = array('patient_barcode_id','anc_site_name','anc_site_code','anc_patient_id','date_returned_to_anc','date_returned_to_participant','country_name','status');
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
       $sQuery = $sql->select()->from(array('r'=>'return_of_recency_results'))
                     ->join(array('anc'=>'anc_site'),'anc.anc_site_id=r.anc',array('anc_site_name','anc_site_code'))
                     ->join(array('c'=>'country'),'c.country_id=r.country',array('country_name'));
        
        
        if($loginContainer->roleCode == 'ANCSC'){
           $sQuery = $sQuery->where(array('r.added_by'=>$loginContainer->userId));
        }
        
        
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
           $sQuery = $sQuery->where(array('r.country'=>$parameters['countryId']));
        }else if($loginContainer->roleCode== 'CC'){
	        $sQuery = $sQuery->where('r.country IN ("' . implode('", "', $loginContainer->country) . '")');
        }
        

        if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
           $sQuery = $sQuery->where(array('r.anc'=>base64_decode($parameters['anc'])));
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
       $tQuery = $sql->select()->from(array('r'=>'return_of_recency_results'))
       ->join(array('anc'=>'anc_site'),'anc.anc_site_id=r.anc',array('anc_site_name','anc_site_code'))
       ->join(array('c'=>'country'),'c.country_id=r.country',array('country_name'));

        if($loginContainer->roleCode == 'ANCSC'){
           $tQuery = $tQuery->where(array('r.added_by'=>$loginContainer->userId));
        }
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
            $tQuery = $tQuery->where(array('r.country'=>$parameters['countryId']));
        }else if($loginContainer->roleCode== 'CC'){
	        $tQuery = $tQuery->where('r.country IN ("' . implode('", "', $loginContainer->country) . '")');
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

        $reasons =    array("1" => "Woman has not returned for follow-up",
                            "2" =>"Woman returned for follow-up, but result had not yet been returned to ANC site at that time",
                            "3" =>"Woman returned for follow-up after recency result was returned to ANC site, but staff did not return recency result.",
                            "4" =>"Other");



       foreach ($rResult as $aRow) {
           if($aRow['reason_for_not_returning'] != 4){
                $reason = $reasons[$aRow['reason_for_not_returning']];
           }else{
               $reason = $aRow['reason_for_not_returning_other'];
           }
            $dateReturnedToAnc = '';
            if($aRow['date_returned_to_anc']!= null && trim($aRow['date_returned_to_anc'])!= '' && $aRow['date_returned_to_anc']!= '0000-00-00'){
                $dateReturnedToAnc = $common->humanDateFormat($aRow['date_returned_to_anc']);
            }           
            $dateReturnedToPatient = '';
            if($aRow['date_returned_to_participant']!= null && trim($aRow['date_returned_to_participant'])!= '' && $aRow['date_returned_to_participant']!= '0000-00-00'){
                $dateReturnedToPatient = $common->humanDateFormat($aRow['date_returned_to_participant']);
            }           
            $row = array();
            $row[] = ($aRow['patient_barcode_id']);
            $row[] = ($aRow['anc_site_name']);
            $row[] = $aRow['anc_site_code'];
            $row[] = $aRow['anc_patient_id'];
            $row[] = $dateReturnedToAnc;
            $row[] = $dateReturnedToPatient;
            $row[] = $reason;
            if($parameters['countryId'] == ''){
              $row[] = ucwords($aRow['country_name']);
            }
            $row[] = $aRow['status'];
            //data edit
            $dataEdit = '<a href="/clinic/return-recency/edit/' . base64_encode($aRow['patient_barcode_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-1" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp;';

            
            if($loginContainer->hasViewOnlyAccess!= 'yes'){
              $row[] = $dataEdit;
            }

            
            $output['aaData'][] = $row;
       }
      return $output;
    }
    
    public function fetchSingleReturnOfRecencyResult($patientBarcodeId){
        return $this->select(array('patient_barcode_id'=>$patientBarcodeId))->current();
    }
    
}