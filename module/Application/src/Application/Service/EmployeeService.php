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
    
    public function addEmployee($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $employeeDb = $this->sm->get('EmployeeTable');
            $result = $employeeDb->addEmployeeDetails($params);
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
    
    public function getAllEmployees($parameters){
        $employeeDb = $this->sm->get('EmployeeTable');
        return $employeeDb->fetchAllEmployees($parameters);
    }
    
    public function getEmployee($employeeId){
        $employeeDb = $this->sm->get('EmployeeTable');
        return $employeeDb->fetchEmployee($employeeId);
    }
    
    public function updateEmployee($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $employeeDb = $this->sm->get('EmployeeTable');
            $result = $employeeDb->updateEmployeeDetails($params);
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