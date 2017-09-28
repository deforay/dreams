<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class FacilityTable extends AbstractTableGateway {

    protected $table = 'facility';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addFacilityDetails($params){
        $lastInsertedId = 0;
	if(isset($params['facilityName']) && trim($params['facilityName'])!= ''){
	    //set province
	    $dbAdapter = $this->adapter;
	    $provinceDb = new ProvinceTable($dbAdapter);
	    $province = null;
	    if(isset($params['provinceNew']) && trim($params['provinceNew'])!= ''){
		$provinceData = array(
		                  'province_name'=>$params['provinceNew'],
		                  'country'=>base64_decode($params['country'])
				);
		$provinceDb->insert($provinceData);
		$province = $provinceDb->lastInsertValue;
	    }else if(isset($params['province']) && trim($params['province'])!= ''){
		$province = base64_decode($params['province']);
	    }
	    $data = array(
		'facility_name' => $params['facilityName'],
		'facility_code' => $params['facilityCode'],
		'facility_type' => base64_decode($params['facilityType']),
		'email' => $params['email'],
		'contact_person' => $params['contactPerson'],
		'phone_number' => $params['mobile'],
		'country' => base64_decode($params['country']),
		'province' => $province,
		'address' => $params['address'],
		'latitude' => $params['latitude'],
		'longitude' => $params['longitude'],
		'comments' => $params['comments'],
		'status' => 'active'
	    );
	    $this->insert($data);
	    $lastInsertedId = $this->lastInsertValue;
	}
      return $lastInsertedId;
    }
    
    public function fetchAllFacilites($parameters){
	$loginContainer = new Container('user');
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	if($loginContainer->roleCode =='CSC' && $parameters['countryId']== ''){
           $aColumns = array('f.facility_name','f.facility_code','f_typ.facility_type_name','f.email','f.phone_number','c.country_name','f.status');
           $orderColumns = array('f.facility_name','f.facility_code','f_typ.facility_type_name','f.email','f.phone_number','c.country_name','f.status');
	}else{
	    $aColumns = array('f.facility_name','f.facility_code','f_typ.facility_type_name','f.email','f.phone_number','f.status');
            $orderColumns = array('f.facility_name','f.facility_code','f_typ.facility_type_name','f.email','f.phone_number','f.status');
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
       $mappedLab = array();
       $uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                  ->where(array('l_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped lab
       foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
       }
       
        $sQuery = $sql->select()->from(array('f' => 'facility'))
			       ->join(array('f_typ' => 'facility_type'), "f_typ.facility_type_id=f.facility_type",array('facility_type_name'))
			       ->join(array('c' => 'country'), "c.country_id=f.country",array('country_name'));
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
           $sQuery = $sQuery->where(array('f.country'=>trim($parameters['countryId'])));
        }else if(isset($parameters['country']) && trim($parameters['country'])!= ''){  
           $sQuery = $sQuery->where(array('f.country'=>base64_decode($parameters['country'])));
        }
	if($loginContainer->roleCode== 'LS'){
	    $sQuery = $sQuery->where('f.facility_id IN ("' . implode('", "', $mappedLab) . '")');
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
	$tQuery = $sql->select()->from(array('f' => 'facility'))
				->join(array('f_typ' => 'facility_type'), "f_typ.facility_type_id=f.facility_type",array('facility_type_name'))
				->join(array('c' => 'country'), "c.country_id=f.country",array('country_name'));
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $tQuery = $tQuery->where(array('f.country'=>trim($parameters['countryId'])));
	 }else if(isset($parameters['country']) && trim($parameters['country'])!= ''){  
	    $tQuery = $tQuery->where(array('f.country'=>base64_decode($parameters['country'])));
	}
	 
	if($loginContainer->roleCode== 'LS'){
	    $tQuery = $tQuery->where('f.facility_id IN ("' . implode('", "', $mappedLab) . '")');
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
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = ucwords($aRow['facility_type_name']);
	    $row[] = $aRow['email'];
	    $row[] = $aRow['phone_number'];
	    if($loginContainer->roleCode =='CSC' && $parameters['countryId']== ''){
	      $row[] = ucwords($aRow['country_name']);
	    }
	    $row[] = ucwords($aRow['status']);
	    if($loginContainer->hasViewOnlyAccess =='no') {
	       $row[] = '<a href="/facility/edit/' . base64_encode($aRow['facility_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
	    }
	    $output['aaData'][] = $row;
	}
      return $output;
    }
    
    public function fetchFacility($facilityId){
        return $this->select(array('facility_id'=>$facilityId))->current();
    }
    
    public function updateFacilityDetails($params){
        $facilityId = 0;
	if(isset($params['facilityName']) && trim($params['facilityName'])!= ''){
	    $facilityId = base64_decode($params['facilityId']);
	    //set province
	    $dbAdapter = $this->adapter;
	    $provinceDb = new ProvinceTable($dbAdapter);
	    $province = null;
	    if(isset($params['provinceNew']) && trim($params['provinceNew'])!= ''){
		$provinceData = array(
		                  'province_name'=>$params['provinceNew'],
		                  'country'=>base64_decode($params['country'])
				);
		$provinceDb->insert($provinceData);
		$province = $provinceDb->lastInsertValue;
	    }else if(isset($params['province']) && trim($params['province'])!= ''){
		$province = base64_decode($params['province']);
	    }
	    $data = array(
		'facility_name' => $params['facilityName'],
		'facility_code' => $params['facilityCode'],
		'facility_type' => base64_decode($params['facilityType']),
		'email' => $params['email'],
		'contact_person' => $params['contactPerson'],
		'phone_number' => $params['mobile'],
		'country' => base64_decode($params['country']),
		'province' => $province,
		'address' => $params['address'],
		'latitude' => $params['latitude'],
		'longitude' => $params['longitude'],
		'comments' => $params['comments'],
		'status' => $params['facilityStatus']
	    );
	    $this->update($data,array('facility_id'=>$facilityId));
	}
      return $facilityId;
    }
	
    public function fetchActivefacilities($from,$countryId){
	$loginContainer = new Container('user');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$mappedLab = array();
	$uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
				   ->where(array('l_map.user_id'=>$loginContainer->userId));
	$uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
	$uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	//Get all mapped lab
	foreach($uMapResult as $lab){
	    $mappedLab[] = $lab['laboratory_id'];
	}
        $facilitiesQuery = $sql->select()->from(array('f' => 'facility'))
                               ->where(array('f.status'=>'active'));
	if(trim($countryId)!='' && $countryId >0){
            $facilitiesQuery = $facilitiesQuery->where(array('f.country'=>$countryId));
        } if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $facilitiesQuery = $facilitiesQuery->where('f.facility_id IN ("' . implode('", "', $mappedLab) . '")');
	}
        $facilitiesQueryStr = $sql->getSqlStringForSqlObject($facilitiesQuery);
        return $dbAdapter->query($facilitiesQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}