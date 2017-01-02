<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class UserLaboratoryMapTable extends AbstractTableGateway {

    protected $table = 'user_laboratory_map';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addUserLaboratoryMapDetails($params,$userId){
        //Check exist laboratory nd update
        $dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$sQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                ->where(array('l_map.user_id'=>$userId));
	$sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
	$sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if($sResult){
            $this->delete(array('user_id'=>$userId));
        }
        
        if(isset($params['role']) && base64_decode($params['role'])== 3){
            if(count($params['lab'])>0){
                $c = count($params['lab']);
                for($i=0;$i<$c;$i++){
                    if(trim($params['lab'][$i])){
                        $data = array('user_id'=>$userId,'laboratory_id'=>base64_decode($params['lab'][$i]));
                        $this->insert($data);
                    }
                }
            }
        }
      return true;
    }
}