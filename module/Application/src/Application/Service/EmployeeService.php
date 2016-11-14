<?php
namespace Application\Service;

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
}