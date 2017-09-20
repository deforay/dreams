<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class UserTable extends AbstractTableGateway {

    protected $table = 'user';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function getUserLogin($params){
        $alertContainer = new Container('alert');
	$common = new CommonService();
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if(isset($params['userName']) && trim($params['userName'])!= ''){
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
	    $loginTrackerDb = new LoginTrackerTable($dbAdapter);
            $userName = trim($params['userName']);
            $password = sha1(trim($params['password']).$configResult["password"]["salt"]);
	    $isCountrySelected = false;
	    if(isset($params['country']) && trim($params['country'])!= ''){
	       $isCountrySelected = true;
	       $selectedCountry = base64_decode($params['country']);
	    }
            $loginQuery = $sql->select()->from(array('u' => 'user'))
                              ->join(array('r'=>'role'),'r.role_id=u.role',array('role_code'))
                              ->where(array('u.user_name' => $userName, 'u.password' => $password));
            $loginQueryStr = $sql->getSqlStringForSqlObject($loginQuery);
            $loginResult = $dbAdapter->query($loginQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if($loginResult){
		if($loginResult->status== 'inactive'){
		    $alertContainer->msg = 'Your account seems to inactive.Please contact admin to reactivate your account..';
		    return 'login';
		}
		$loginContainer = new Container('user');
		$userCountry = array();
		$userClinic = array();
		$userLaboratory = array();
		if($loginResult->role_code =='CSC'){
		    $isCountrySelected = false;
		}else{
		    //Set user countries
		    $countryMapQuery = $sql->select()->from(array('c_map' => 'user_country_map'))
					   ->where(array('c_map.user_id' => $loginResult->user_id));
		    $countryMapQueryStr = $sql->getSqlStringForSqlObject($countryMapQuery);
		    $countryMapResult = $dbAdapter->query($countryMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		    if(isset($countryMapResult) && count($countryMapResult)>0){
			foreach($countryMapResult as $country){
			    $userCountry[] = $country['country_id'];
			}
		    }
		    //set user clinics
		    $clinicMapQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
					  ->where(array('cl_map.user_id' => $loginResult->user_id));
		    $clinicMapQueryStr = $sql->getSqlStringForSqlObject($clinicMapQuery);
		    $clinicMapResult = $dbAdapter->query($clinicMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		    if(isset($clinicMapResult) && count($clinicMapResult)>0){
			foreach($clinicMapResult as $clinic){
			    $userClinic[] = $clinic['clinic_id'];
			}
		    }
		    //set user laboratories
		    $laboratoryMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
					      ->where(array('l_map.user_id' => $loginResult->user_id));
		    $laboratoryMapQueryStr = $sql->getSqlStringForSqlObject($laboratoryMapQuery);
		    $laboratoryMapResult = $dbAdapter->query($laboratoryMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		    if(isset($laboratoryMapResult) && count($laboratoryMapResult)>0){
			foreach($laboratoryMapResult as $laboratory){
			    $userLaboratory[] = $laboratory['laboratory_id'];
			}
		    }
		}
		if($isCountrySelected){
		    if(in_array($selectedCountry,$userCountry)){
			//Update last login
		        $this->update(array('last_login'=>$common->getDateTime()),array('user_id'=>$loginResult->user_id));
			$loginTrackerDb->addNewLogin($loginResult->user_id);
			$loginContainer->userId = $loginResult->user_id;
			$loginContainer->userName = $loginResult->user_name;
			$loginContainer->roleCode = $loginResult->role_code;
			$loginContainer->hasViewOnlyAccess = $loginResult->has_view_only_access;
			$loginContainer->hasDRAccess = $loginResult->has_data_reporting_access;
			$loginContainer->hasPRAccess = $loginResult->has_print_report_access;
			$loginContainer->country = $userCountry;
			$loginContainer->clinic = $userClinic;
			$loginContainer->laboratory = $userLaboratory;
		       return 'home';
		    }else{
		       $alertContainer->msg = 'Please check the country that you have choosen..!';
		       return 'login';
		    }
		}else{
		    //Update last login
		    $this->update(array('last_login'=>$common->getDateTime()),array('user_id'=>$loginResult->user_id));
		    $loginTrackerDb->addNewLogin($loginResult->user_id);
		    $loginContainer->userId = $loginResult->user_id;
		    $loginContainer->userName = $loginResult->user_name;
		    $loginContainer->roleCode = $loginResult->role_code;
		    $loginContainer->hasViewOnlyAccess = $loginResult->has_view_only_access;
		    $loginContainer->hasDRAccess = $loginResult->has_data_reporting_access;
		    $loginContainer->hasPRAccess = $loginResult->has_print_report_access;
		    $loginContainer->country = $userCountry;
		    $loginContainer->clinic = $userClinic;
		    $loginContainer->laboratory = $userLaboratory;
		    return 'home';
		}
            }else{
                $alertContainer->msg = 'The user name or password that you entered is incorrect..!';
                return 'login';
            }
        }else{
            $alertContainer->msg = 'Please enter all the require fields..!';
            return 'home';
        }
    }
    
    public function addUserDetails($params){
	$loginContainer = new Container('user');
	$lastInsertedId = 0;
	if(isset($params['userName']) && trim($params['userName'])!= ''){
	    $common = new CommonService();
	    $config = new \Zend\Config\Reader\Ini();
	    $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
	    $password = sha1($params['password'] . $configResult["password"]["salt"]);
	    $data = array(
		'full_name' => $params['fullName'],
		//'user_code' => $params['userCode'],
		'user_name' => $params['userName'],
		'password' => $password,
		'role' => base64_decode($params['role']),
		'email' => $params['email'],
		'mobile' => $params['mobile'],
		'alt_contact' => $params['altContact'],
		'has_view_only_access' => $params['hasViewOnlyAccess'],
		'comments' => $params['comments'],
		'status' => 'active',
		'created_by' => $loginContainer->userId,
		'created_on' => $common->getDateTime()
	    );
	    $data['has_data_reporting_access']  =NULL;
	    $data['has_print_report_access']  =NULL;
	    if(isset($params['role']) && base64_decode($params['role'])== 5){
	       $data['has_data_reporting_access'] = $params['hasDataReportingAccess'];
	       $data['has_print_report_access'] = $params['hasPrintReportAccess']; 
	    }
	    $this->insert($data);
	    $lastInsertedId = $this->lastInsertValue;
	}
       return $lastInsertedId;
    }
    
    public function fetchAllUsers($parameters){
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$loginContainer = new Container('user');
	$common = new CommonService();
	if(trim($parameters['countryId']) ==''){
	    $aColumns = array('u.full_name','u.user_name','u.email','u.mobile','u.has_view_only_access','u.status',"DATE_FORMAT(u.last_login,'%d-%b-%Y %H:%i:%s')");
	    $orderColumns = array('u.full_name','u.user_name','u.email','u.mobile','u.has_view_only_access','u.status','u.last_login');
	}else{
	   $aColumns = array('u.full_name','r.role_name','u.user_name','u.email','u.mobile','u.has_view_only_access','u.status',"DATE_FORMAT(u.last_login,'%d-%b-%Y %H:%i:%s')");
	   $orderColumns = array('u.full_name','r.role_name','u.user_name','u.email','u.mobile','u.has_view_only_access','u.status','u.last_login');
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
       $createdByUser = array();
       $uQuery = $sql->select()->from(array('u' => 'user'))
                               ->where(array('u.created_by'=>$loginContainer->userId));
       $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
       $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //For own profile edit
       $createdByUser[] = $loginContainer->userId;
       //Get all created by user
       foreach($uResult as $user){
	 $createdByUser[] = $user['user_id'];
       }
       $sQuery = $sql->select()->from(array('u' => 'user'))
		               ->join(array('r' => 'role'), "r.role_id=u.role",array('role_name'))
		               ->join(array('c_map' => 'user_country_map'), "c_map.user_id=u.user_id",array(),'left')
		               ->join(array('c' => 'country'), "c.country_id=c_map.country_id",array(),'left');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('c.country_id'=>trim($parameters['countryId'])));
	}else{
	    if($loginContainer->roleCode == 'CSC'){
	       $sQuery = $sQuery->where('r.role_code IN ("CSC")');
	    }else{
		$sQuery = $sQuery->where(array('c.country_id'=> 0));
	    }
	}
       if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'ANCSC' || $loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where('u.user_id IN ("' . implode('", "', $createdByUser) . '")');
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
	$tQuery = $sql->select()->from(array('u' => 'user'))
			        ->join(array('r' => 'role'), "r.role_id=u.role",array('role_name'))
				->join(array('c_map' => 'user_country_map'), "c_map.user_id=u.user_id",array(),'left')
		                ->join(array('c' => 'country'), "c.country_id=c_map.country_id",array(),'left');
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $tQuery = $tQuery->where(array('c.country_id'=>trim($parameters['countryId'])));
	}else{
	    if($loginContainer->roleCode == 'CSC'){
	       $tQuery = $tQuery->where('r.role_code IN ("CSC")');
	    }else{
		$tQuery = $tQuery->where(array('c.country_id'=> 0));
	    }
	}
	
	if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'ANCSC' || $loginContainer->roleCode== 'LDEO'){
	   $tQuery = $tQuery->where('u.user_id IN ("' . implode('", "', $createdByUser) . '")');
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
	    $lastLogin = '';
	    if(isset($aRow['last_login']) && trim($aRow['last_login'])!= ''){
	       $date = explode(" ",$aRow['last_login']);
	       $lastLogin = $common->humanDateFormat($date[0])." ".$date[1];
	    }
	    $access = 'Full Access';
	    if(isset($aRow['has_view_only_access']) && trim($aRow['has_view_only_access'])== 'yes'){
		$access = 'View Only';
	    }
	    $row[] = ucwords($aRow['full_name']);
	    if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	       $row[] = ucwords($aRow['role_name']);
	    }
	    $row[] = $aRow['user_name'];
	    $row[] = $aRow['email'];
	    $row[] = $aRow['mobile'];
	    $row[] = $access;
	    $row[] = ucwords($aRow['status']);
	    $row[] = $lastLogin;
	    if($loginContainer->hasViewOnlyAccess =='no') {
	       $row[] = '<a href="/user/edit/'. base64_encode($aRow['user_id']).'/'. base64_encode($parameters['countryId']).'" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
	    }
	    $output['aaData'][] = $row;
	}
      return $output;
    }
    
    public function fetchUser($userId){
	$dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$rQuery = $sql->select()->from(array('u' => 'user'))
			        ->where(array('u.user_id'=>$userId));
	$rQueryStr = $sql->getSqlStringForSqlObject($rQuery);
	$rResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	//Get user's countries if exist
	$cQuery = $sql->select()->from(array('c_map' => 'user_country_map'))
				->where(array('c_map.user_id'=>$userId));
	$cQueryStr = $sql->getSqlStringForSqlObject($cQuery);
	$rResult['userCountries'] = $dbAdapter->query($cQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	//Get user's clinics if exist
	$clQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
				->where(array('cl_map.user_id'=>$userId));
	$clQueryStr = $sql->getSqlStringForSqlObject($clQuery);
	$rResult['userClinics'] = $dbAdapter->query($clQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	//Get user's laboratories if exist
	$lQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
				->where(array('l_map.user_id'=>$userId));
	$lQueryStr = $sql->getSqlStringForSqlObject($lQuery);
	$rResult['userLaboratories'] = $dbAdapter->query($lQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
      return $rResult;
    }
	
    public function updateUserDetails($params){
	$userId = 0;
	if(isset($params['userName']) && trim($params['userName'])!= ''){
	    $userId = base64_decode($params['userId']);
	    $common = new CommonService();
	    $data = array(
		'full_name' => $params['fullName'],
		//'user_code' => $params['userCode'],
		'user_name' => $params['userName'],
		'role' => base64_decode($params['role']),
		'email' => $params['email'],
		'mobile' => $params['mobile'],
		'alt_contact' => $params['altContact'],
		'has_view_only_access' => $params['hasViewOnlyAccess'],
		'comments' => $params['comments'],
		'status' => $params['status']
	    );
	    if(isset($params['password']) && trim($params['password']) != ''){
		$config = new \Zend\Config\Reader\Ini();
		$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
		$data['password'] = sha1($params['password'] . $configResult["password"]["salt"]);
	    }
	    
	    if(isset($params['role']) && base64_decode($params['role'])== 5){
	       $data['has_data_reporting_access'] = $params['hasDataReportingAccess'];
	       $data['has_print_report_access'] = $params['hasPrintReportAccess']; 
	    }else{
		$data['has_data_reporting_access']  =NULL;
		$data['has_print_report_access']  =NULL;
	    }
	    $this->update($data,array('user_id'=>$userId));
	}
      return $userId;
    }
    
    public function updateAccountPassword($params){
	$loginContainer = new Container('user');
	$alertContainer = new Container('alert');
	$config = new \Zend\Config\Reader\Ini();
	$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
	$changedPassword = sha1($params['newPassword'] . $configResult["password"]["salt"]);
	$updated = $this->update(array('password'=>$changedPassword),array('user_id'=>$loginContainer->userId));
	if($updated >0){
	    $alertContainer->msg = 'Your have successfully updated your password.';
	    return true;
	}else{
	    $alertContainer->msg = 'OOPS..Unable to change your password.';
	    return false;
	}
    }
}