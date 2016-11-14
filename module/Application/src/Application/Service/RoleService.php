<?php
namespace Application\Service;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;


class RoleService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function addRole($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
           $roleDb = $this->sm->get('RoleTable');
           $result = $roleDb->addRoleDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'Role added successfully.';
           }else{
             $alertContainer->msg = 'OOPS..';
           }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }
    public function getActiveRoleList()
    {
        $roleDb = $this->sm->get('RoleTable');
        $result = $roleDb->fetchActiveRoleList();
        return $result;
    }
    
}