<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class OccupationTypeTable extends AbstractTableGateway {

    protected $table = 'occupation_type';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchOccupationTypes(){
        return $this->select(array('occupation_status'=>'active'))->toArray();
    }
}