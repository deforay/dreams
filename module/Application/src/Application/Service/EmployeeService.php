<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class EmployeeService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function getLogin($params){
        $employeeDb = $this->sm->get('EmployeeTable');
        return $employeeDb->getEmployeeLogin($params);
    }
    
    public function addEmployeeData($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $db = $this->sm->get('EmployeeTable');
            $result = $db->addEmployee($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->msg = 'Employee added successfully.';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
    
    public function getAllEmployeeList($params){
        $empDb = $this->sm->get('EmployeeTable');
        return $empDb->fetchAllEmployeeList($params);
    }
    
    public function getEmployeeDetail($empId){
        $empDb = $this->sm->get('EmployeeTable');
        return $empDb->fetchEmployeeDetail($empId);
    }
    
    public function updateEmployeeDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $db = $this->sm->get('EmployeeTable');
            $result = $db->updateEmployee($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->msg = 'Employee updated successfully.';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}