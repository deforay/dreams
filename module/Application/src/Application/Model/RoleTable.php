<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;


class RoleTable extends AbstractTableGateway {

    protected $table = 'role';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addRoleDetails($params){
        $roleId = 0;
        if(isset($params['roleName']) && trim($params['roleName'])!= ''){
            $data=array('role_name'=>$params['roleName'],
                        'role_code'=>$params['roleCode'],
                        'has_global_access'=>$params['roleCode'],
                        'role_description'=>$params['roleDescription'],
                        'role_status'=>'active',
                        );
            $this->insert($data);
            return $this->lastInsertValue;
        }
    }
    public function fetchActiveRoleList()
    {
        $query = $this->select(array('role_status'=>'active'));
        return $query;
    }
}