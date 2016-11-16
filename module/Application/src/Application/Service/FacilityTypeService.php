<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;


class FacilityTypeService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function getActiveFacilityTypes(){
        $facilityTypeDb = $this->sm->get('FacilityTypeTable');
        return $facilityTypeDb->fetchActiveFacilityTypes();
    }
}