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
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if(isset($params['userName']) && trim($params['userName'])!= ''){
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $userName = trim($params['userName']);
            $password = sha1($params['password'].$configResult["password"]["salt"]);
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
		$countryMapQuery = $sql->select()->from(array('c_map' => 'user_country_map'))
                                       ->where(array('c_map.user_id' => $loginResult->user_id));
                $countryMapQueryStr = $sql->getSqlStringForSqlObject($countryMapQuery);
                $countryMapResult = $dbAdapter->query($countryMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		if(isset($countryMapResult) && count($countryMapResult)>0){
		    foreach($countryMapResult as $country){
			$userCountry[] = $country['country_id'];
		    }
		}
		if($isCountrySelected){
		    if(in_array($selectedCountry,$userCountry)){
			$loginContainer->userId = $loginResult->user_id;
			$loginContainer->userName = $loginResult->user_name;
			$loginContainer->roleCode = $loginResult->role_code;
			$loginContainer->country = $userCountry;
		       return 'home';
		    }else{
		       $alertContainer->msg = 'Please check the country that you have choosen..!';
		       return 'login';
		    }
		}else{
		    $loginContainer->userId = $loginResult->user_id;
		    $loginContainer->userName = $loginResult->user_name;
		    $loginContainer->roleCode = $loginResult->role_code;
		    $loginContainer->country = $userCountry;
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
		$lastInsertedId = 0;
		if(isset($params['userName']) && trim($params['userName'])!= ''){
			$common = new CommonService();
			$config = new \Zend\Config\Reader\Ini();
			$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
			$password = sha1($params['password'] . $configResult["password"]["salt"]);
			$data = array(
			'full_name' => $params['fullName'],
			'user_code' => $params['userCode'],
			'user_name' => $params['userName'],
			'password' => $password,
			'role' => base64_decode($params['role']),
			'email' => $params['email'],
			'mobile' => $params['mobile'],
			'alt_contact' => $params['altContact'],
			'status' => 'active',
			'created_on' => $common->getDateTime()
			);
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
	if($loginContainer->roleCode =='CSC' && $parameters['countryId'] ==''){
	    $aColumns = array('u.full_name','u.user_code','r.role_name','u.user_name','u.email','u.mobile','c.country_name','u.status',"DATE_FORMAT(u.created_on,'%d-%b-%Y %H:%i:%s')");
	    $orderColumns = array('u.full_name','r.role_name','u.user_name','u.email','u.mobile','c.country_name','u.status','u.created_on');
	}else{
	   $aColumns = array('u.full_name','u.user_code','r.role_name','u.user_name','u.email','u.mobile','u.status',"DATE_FORMAT(u.created_on,'%d-%b-%Y %H:%i:%s')");
	   $orderColumns = array('u.full_name','r.role_name','u.user_name','u.email','u.mobile','u.status','u.created_on');
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
       $sQuery = $sql->select()->from(array('u' => 'user'))
		             ->join(array('r' => 'role'), "r.role_id=u.role",array('role_name'))
			     ->join(array('ucm' => 'user_country_map'), "ucm.user_id=u.user_id")
		             ->join(array('c' => 'country'), "c.country_id=ucm.country_id",array('country_name' => new \Zend\Db\Sql\Expression("GROUP_CONCAT(DISTINCT c.country_name ORDER BY c.country_name SEPARATOR ', ')")))
			     ->group('u.user_id');
	if($loginContainer->roleCode== 'CC'){		
	     $sQuery = $sQuery->where('r.role_code IN ("CC","LS","LDEO")');
	}else if($loginContainer->roleCode== 'LS'){
	     $sQuery = $sQuery->where('r.role_code IN ("LS","LDEO")');
	}else if($loginContainer->roleCode== 'LDEO'){	
	     $sQuery = $sQuery->where('r.role_code IN ("LDEO")'); 
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('c.country_id'=>trim($parameters['countryId'])));
	}else if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	   $sQuery = $sQuery->where(array('c.country_id'=>base64_decode($parameters['country'])));  
	}else{
	    if($loginContainer->roleCode!= 'CSC'){
	       $sQuery = $sQuery->where('c.country_id IN ("' . implode('", "', $loginContainer->country) . '")');
	    }
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
			      ->join(array('ucm' => 'user_country_map'), "ucm.user_id=u.user_id")
			      ->join(array('c' => 'country'), "c.country_id=ucm.country_id",array('country_name' => new \Zend\Db\Sql\Expression("GROUP_CONCAT(DISTINCT c.country_name ORDER BY c.country_name SEPARATOR ', ')")))
			      ->group('u.user_id');
	if($loginContainer->roleCode== 'CC'){	
	    $sQuery = $tQuery->where('r.role_code IN ("CC","LS","LDEO")');
	}else if($loginContainer->roleCode== 'LS'){	
	    $tQuery = $tQuery->where('r.role_code IN ("LS","LDEO")');
	}else if($loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where('r.role_code IN ("LDEO")'); 
	}
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $tQuery = $tQuery->where(array('c.country_id'=>trim($parameters['countryId'])));
	}else if(isset($parameters['country']) && trim($parameters['country'])!= ''){
	   $tQuery = $tQuery->where(array('c.country_id'=>base64_decode($parameters['country'])));  
	}else{
	    if($loginContainer->roleCode!= 'CSC'){
	       $tQuery = $tQuery->where('c.country_id IN ("' . implode('", "', $loginContainer->country) . '")');
	    }
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
	    $date = explode(" ",$aRow['created_on']);
	    $row[] = ucwords($aRow['full_name'])." - ".$aRow['user_code'];
	    $row[] = ucwords($aRow['role_name']);
	    $row[] = $aRow['user_name'];
	    $row[] = $aRow['email'];
	    $row[] = $aRow['mobile'];
	    if($loginContainer->roleCode =='CSC' && $parameters['countryId'] ==''){
	      $row[] = ucwords($aRow['country_name']);
	    }
	    $row[] = ucwords($aRow['status']);
	    $row[] = $common->humanDateFormat($date[0])." ".$date[1];
	    $row[] = '<a href="/user/edit/'. base64_encode($aRow['user_id']).'/'. base64_encode($parameters['countryId']).'" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
	    $output['aaData'][] = $row;
	}
      return $output;
    }
    
    public function fetchUser($userId){
	$dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$rQuery = $sql->select()->from(array('u' => 'user'))
		             ->join(array('ucm' => 'user_country_map'), "ucm.user_id=u.user_id")
		             ->join(array('c' => 'country'), "c.country_id=ucm.country_id",array('country_id','country_name'))
			     ->where(array('u.user_id'=>$userId));
	$rQueryStr = $sql->getSqlStringForSqlObject($rQuery);
	$rResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	
	$cQuery = $sql->select()->from(array('ucm' => 'user_country_map'))
				->where(array('ucm.user_id'=>$userId));
	$cQueryStr = $sql->getSqlStringForSqlObject($cQuery);
	$cResult = $dbAdapter->query($cQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	return array($rResult,'country'=>$cResult);
    }
	
    public function updateUserDetails($params){
	$userId = 0;
	if(isset($params['userName']) && trim($params['userName'])!= ''){
		$userId = base64_decode($params['userId']);
		$common = new CommonService();
		$data = array(
		'full_name' => $params['fullName'],
		'user_code' => $params['userCode'],
		'user_name' => $params['userName'],
		'role' => base64_decode($params['role']),
		'email' => $params['email'],
		'mobile' => $params['mobile'],
		'alt_contact' => $params['altContact'],
		'status' => $params['status']
			);
		if (isset($params['password']) && trim($params['password']) != ''){
			$config = new \Zend\Config\Reader\Ini();
			$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
			$data['password'] = sha1($params['password'] . $configResult["password"]["salt"]);
		}
		$this->update($data,array('user_id'=>$userId));
	}
	return $userId;
    }
}