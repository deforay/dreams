<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class AncFormTable extends AbstractTableGateway {

    protected $table = 'anc_form';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchActiveAncFormFields(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fieldQuery = $sql->select()->from(array('anc_f'=>'anc_form'))
                                    ->where(array('anc_f.status'=>'active'));
        $fieldQueryStr = $sql->getSqlStringForSqlObject($fieldQuery);
        $fieldsValues = $dbAdapter->query($fieldQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $size = sizeof($fieldsValues);
        $arr = array();
        // now we create an associative array so that we can easily create view variables
        for ($i = 0; $i < $size; $i++) {
            $arr[$fieldsValues[$i]['field_name']] = $fieldsValues[$i]['age_disaggregation'];
        }
        // using assign to automatically create view variables
        // the column names will now become view variables
        return $arr;
    }
}