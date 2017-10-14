<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class UserCountryMapTable extends AbstractTableGateway {

    protected $table = 'user_country_map';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addUserCountryMapDetails($params,$userId){
        //TO check existing map-countries nd delete
        $dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$sQuery = $sql->select()->from(array('c_map' => 'user_country_map'))
                                ->where(array('c_map.user_id'=>$userId));
	$sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
	$sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if($sResult){
            $this->delete(array('user_id'=>$userId));
        }
	if(base64_decode($params['role'])== 2){
	   $params['country'] = $params['countryMulti'];
	}else if(base64_decode($params['role'])== 3 || base64_decode($params['role'])== 4 || base64_decode($params['role'])== 5){
	   $params['country'] = $params['country'];
	}
        $params['country'] = array_values(array_unique(array_filter($params['country'])));
        if(count($params['country'])>0){
            $c = count($params['country']);
            for($i=0;$i<$c;$i++){
                if(trim($params['country'][$i])){
		    $data = array('user_id'=>$userId,'country_id'=>base64_decode($params['country'][$i]));
		    $this->insert($data);
                }
            }
        }
      return true;
    }
}