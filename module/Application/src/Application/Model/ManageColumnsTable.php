<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class ManageColumnsTable extends AbstractTableGateway {

    protected $table = 'manage_columns';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchUserManageColumns(){
        $loginContainer = new Container('user');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $mCQuery = $sql->select()->from(array('m_c' => 'manage_columns'))
                       ->where(array('user_id'=>$loginContainer->userId));
        $mCQueryStr = $sql->getSqlStringForSqlObject($mCQuery);
      return $dbAdapter->query($mCQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
}