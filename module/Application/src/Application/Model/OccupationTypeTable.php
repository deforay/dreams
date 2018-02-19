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
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('o_t' => 'occupation_type'))
                               ->where(array('o_t.occupation_status'=>'active'))
                               ->order('o_t.occupation_code asc');
        $queryStr = $sql->getSqlStringForSqlObject($query);
       return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}