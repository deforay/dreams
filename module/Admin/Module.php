<?php

namespace Admin;

use Application\Service\CommonService;
use Application\Service\EmployeeService;

use Application\Model\EmployeeTable;

class Module {

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                'CommonService' => function($sm) {
                    return new CommonService($sm);
                },'EmployeeService' => function($sm) {
                    return new EmployeeService($sm);
                },
                
                'EmployeeTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new EmployeeTable($dbAdapter);
                    return $table;
                },
            ),
        );
    }

}
