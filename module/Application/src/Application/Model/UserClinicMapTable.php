<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class UserClinicMapTable extends AbstractTableGateway {

    protected $table = 'user_clinic_map';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addUserClinicMapDetails($params,$userId){
        //Check exist country nd update
        $dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$sQuery = $sql->select()->from(array('cl_map' => 'user_clinic_map'))
                                ->where(array('cl_map.user_id'=>$userId));
	$sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
	$sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if($sResult){
            $this->delete(array('user_id'=>$userId));
        }
        
        if(isset($params['role']) && base64_decode($params['role'])== 5){
            if(count($params['ancSite'])>0){
                $c = count($params['ancSite']);
                for($i=0;$i<$c;$i++){
                    if(trim($params['ancSite'][$i])){
                        $data = array('user_id'=>$userId,'clinic_id'=>base64_decode($params['ancSite'][$i]));
                        $this->insert($data);
                    }
                }
            }
        }
      return true;
    }

}