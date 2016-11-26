<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class TestStatusTable extends AbstractTableGateway {

    protected $table = 'test_status';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchAllTestStatus(){
        return $this->select()->toArray();
    }
}