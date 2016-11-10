<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;


class EmployeeTable extends AbstractTableGateway {

    protected $table = 'employee';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function loginProcessDetails($params){
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if(isset($params['empName']) && trim($params['empName'])!="" && trim($params['password'])!=""){
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $password = sha1($params['password'].$configResult["password"]["salt"]);
            
            $sQuery = $sql->select()->from(array('e' => 'employee'))
                            ->where(array('emp_name' => $params['empName'], 'password' => $password));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            $alertContainer = new Container('alert');
            $logincontainer = new Container('credo');
            if($rResult) {
                $logincontainer->employeeId = $rResult->emp_id;
                $logincontainer->name = ucwords($rResult->emp_name);
                return 'home';
            }else {
                $alertContainer->alertMsg = 'The email id or password you entered is incorrect';
                return 'login';
            }
        }else {
            $alertContainer->alertMsg = 'The email id or password you entered is incorrect';
            return 'login';
        }
    }
    
}
?>
