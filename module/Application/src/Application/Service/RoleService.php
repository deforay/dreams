<?php
namespace Application\Service;

use Zend\Session\Container;
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
             $alertContainer->msg = 'Error-Oops, something went wrong!!';
           }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }
    
    public function getAllRoles($parameters){
        $roleDb = $this->sm->get('RoleTable');
       return $roleDb->fetchAllRoles($parameters);
    }
    
    public function getRole($roleId){
        $roleDb = $this->sm->get('RoleTable');
        return $roleDb->fetchRole($roleId);
    }
    
    public function getActiveRoles($country){
        $roleDb = $this->sm->get('RoleTable');
        return $roleDb->fetchActiveRoles($country);
    }
    
    public function updateRole($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
           $roleDb = $this->sm->get('RoleTable');
           $result = $roleDb->updateRoleDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'Role updated successfully.';
           }else{
             $alertContainer->msg = 'Error-Oops, something went wrong!!';
           }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }
}