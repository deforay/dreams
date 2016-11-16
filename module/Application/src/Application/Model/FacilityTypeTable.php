<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class FacilityTypeTable extends AbstractTableGateway {

    protected $table = 'facility_type';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchActiveFacilityTypes(){
        return $this->select(array('status'=>'active'));
    }
}