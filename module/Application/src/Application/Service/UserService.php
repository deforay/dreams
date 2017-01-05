<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class UserService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function getLogin($params){
        $userDb = $this->sm->get('UserTable');
        return $userDb->getUserLogin($params);
    }
    
    public function addUser($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->addUserDetails($params);
            if ($result > 0) {
                $userCountryMapDb = $this->sm->get('UserCountryMapTable');
                $userClinicMapDb = $this->sm->get('UserClinicMapTable');
                $userLaboratoryMapDb = $this->sm->get('UserLaboratoryMapTable');
                $userCountryMapDb->addUserCountryMapDetails($params,$result);
		if(base64_decode($params['role'])== 5){
		   $userClinicMapDb->addUserClinicMapDetails($params,$result);
		}else if(base64_decode($params['role'])== 3 || base64_decode($params['role'])== 4){
		   $userLaboratoryMapDb->addUserLaboratoryMapDetails($params,$result);
		}
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->msg = 'User added successfully.';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
    
    public function getAllUsers($parameters){
        $userDb = $this->sm->get('UserTable');
        return $userDb->fetchAllUsers($parameters);
    }
    
    public function getUser($userId){
        $userDb = $this->sm->get('UserTable');
        return $userDb->fetchUser($userId);
    }
    
    public function updateUser($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->updateUserDetails($params);
            if ($result > 0) {
                $userCountryMapDb = $this->sm->get('UserCountryMapTable');
		$userClinicMapDb = $this->sm->get('UserClinicMapTable');
		$userLaboratoryMapDb = $this->sm->get('UserLaboratoryMapTable');
                $userCountryMapDb->addUserCountryMapDetails($params,$result);
		$userClinicMapDb->addUserClinicMapDetails($params,$result);
		$userLaboratoryMapDb->addUserLaboratoryMapDetails($params,$result);
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->msg = 'User updated successfully.';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
    
    public function changeAccountPassword($params){
        $userDb = $this->sm->get('UserTable');
        return $userDb->updateAccountPassword($params);
    }
    
    public function checkAccountPassword($params){
        $loginContainer = new Container('user');
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $config = new \Zend\Config\Reader\Ini();
	$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
	$password = sha1($params['password'] . $configResult["password"]["salt"]);
        $userQuery = $sql->select()->from(array('u' => 'user'))
                         ->columns(array('user_id'))
                         ->where(array('u.user_id'=>$loginContainer->userId,'u.password'=>$password));
        $userQueryStr = $sql->getSqlStringForSqlObject($userQuery);
      return $dbAdapter->query($userQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
}