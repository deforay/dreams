<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class LoginTrackerTable extends AbstractTableGateway {

    protected $table = 'login_tracker';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addNewLogin($userId){
      $id = 0;
        if(isset($userId) && $userId >0){
            $common = new CommonService();
            $data = array(
                'user_id'=>$userId,
                'logged_in_datetime'=>$common->getDateTime(),
                'ip_address'=>$_SERVER['REMOTE_ADDR']
            );
            $this->insert($data);
            $id = $this->lastInsertValue;
        }
      return $id;
    }
}