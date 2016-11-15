<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;


class EmployeeTable extends AbstractTableGateway {

    protected $table = 'employee';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function getEmployeeLogin($params){
         $alertContainer = new Container('alert');
         $config = new \Zend\Config\Reader\Ini();
         $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
         if(isset($params['email']) && trim($params['email'])!= ''){
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $email = trim($params['email']);
            $password = sha1($params['password'].$configResult["password"]["salt"]);
            $loginQuery = $sql->select()->from(array('e' => 'employee'))
                              ->where(array('e.email' => $email, 'e.password' => $password));
            $loginQueryStr = $sql->getSqlStringForSqlObject($loginQuery);
            $loginResult = $dbAdapter->query($loginQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if($loginResult){
                $loginContainer = new Container('employee');
                $loginContainer->employeeId = $loginResult->employee_id;
                $loginContainer->employeeName = ucwords($loginResult->employee_name);
                return 'home';
            }else{
                $alertContainer->msg = 'The email id or password that you entered is incorrect..!';
                return 'login';
            }
         }else{
            $alertContainer->msg = 'Please enter the all the fields..!';
            return 'home';
         }
    }
    public function addEmployee($params){
	$lastInsertedId = 0;
	if(isset($params['employeeName']) && trim($params['employeeName'])!= ''){
	    $common = new CommonService();
	    $config = new \Zend\Config\Reader\Ini();
	    $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
	    $password = sha1($params['password'] . $configResult["password"]["salt"]);
	    $data = array(
		'employee_name' => $params['employeeName'],
		'employee_code' => $params['employeeCode'],
		'mobile' => $params['mobile'],
		'role' => base64_decode($params['role']),
		'email' => $params['email'],
		'password' => $password,
		'alt_contact' => $params['altContact'],
		'status' => 'active',
		'created_on' => $common->getDateTime()
	    );
	    $this->insert($data);
	    $lastInsertedId = $this->lastInsertValue;
	}
	return $lastInsertedId;
    }
    
    public function fetchAllEmployeeList($parameters){
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	$common = new CommonService();
        $aColumns = array('e.employee_name','r.role_name','e.email','e.mobile','e.status',"DATE_FORMAT(e.created_on,'%d-%b-%Y %H:%i:%s')");
        $orderColumns = array('e.employee_name','r.role_name','e.email','e.mobile','e.status','e.created_on');

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
       $sQuery = $sql->select()->from(array('e' => 'employee'))
		     ->join(array('r' => 'role'), "r.role_id=e.role",array('role_name'));
	
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
		$tQuery = $sql->select()->from(array('e' => 'employee'))
                              ->join(array('r' => 'role'), "r.role_id=e.role",array('role_name'));
	
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
			$row[] = ucwords($aRow['employee_name'])." - ".$aRow['employee_code'];
			$row[] = ucwords($aRow['role_name']);
			$row[] = $aRow['email'];
			$row[] = $aRow['mobile'];
			$row[] = ucwords($aRow['status']);
			$row[] = $common->humanDateFormat($date[0])." ".$date[1];
			$row[] = '<a href="/employee/edit/' . base64_encode($aRow['employee_id']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> EDIT</a>';
			$output['aaData'][] = $row;
		}
		return $output;
    }
    
    public function fetchEmployeeDetail($empid){
	return $this->select(array('employee_id'=>$empid))->current();
    }
	
    public function updateEmployee($params){
	$lastInsertedId = 0;
	if(isset($params['employeeName']) && trim($params['employeeName'])!= ''){
	    $lastInsertedId = base64_decode($params['employeeId']);
	    $common = new CommonService();
            $data = array(
		'employee_name' => $params['employeeName'],
		'employee_code' => $params['employeeCode'],
		'mobile' => $params['mobile'],
		'role' => base64_decode($params['role']),
		'email' => $params['email'],
		'alt_contact' => $params['altContact'],
		'status' => $params['status']
            );
	    if (isset($params['password']) && trim($params['password']) != ''){
		$config = new \Zend\Config\Reader\Ini();
		$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
		$data['password'] = sha1($params['password'] . $configResult["password"]["salt"]);
	    }
	    $updateResult = $this->update($data,array('employee_id'=>$lastInsertedId));
	}
	return $lastInsertedId;
    }
}