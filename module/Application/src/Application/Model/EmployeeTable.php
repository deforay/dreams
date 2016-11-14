<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;


class EmployeeTable extends AbstractTableGateway {

    protected $table = 'employee';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function getEmployeeLogin($params){
         $alertContainer = new Container('alert');
         $config = new \Zend\Config\Reader\Ini();
         $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
         if(isset($params['email']) && trim($params['email'])!= ''){
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $email = trim($params['email']);
            $password = sha1($params['password'].$configResult["password"]["salt"]);
            $loginQuery = $sql->select()->from(array('e' => 'employee'))
                              ->where(array('e.email' => $email, 'e.password' => $password));
            $loginQueryStr = $sql->getSqlStringForSqlObject($loginQuery);
            $loginResult = $dbAdapter->query($loginQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if($loginResult){
                $loginContainer = new Container('employee');
                $loginContainer->employeeId = $loginResult->employee_id;
                $loginContainer->employeeName = ucwords($loginResult->employee_name);
                return 'home';
            }else{
                $alertContainer->msg = 'The email id or password that you entered is incorrect..!';
                return 'login';
            }
         }else{
            $alertContainer->msg = 'Please enter the all the fields..!';
            return 'home';
         }
    }
}