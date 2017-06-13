<?php
namespace Application;

use Zend\Session\Container;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

use Application\Service\CommonService;
use Application\Service\RoleService;
use Application\Service\UserService;
use Application\Service\FacilityService;
use Application\Service\CountryService;
use Application\Service\FacilityTypeService;
use Application\Service\AncSiteService;
use Application\Service\DataCollectionService;
use Application\Service\RiskAssessmentService;

use Application\Model\RoleTable;
use Application\Model\UserTable;
use Application\Model\FacilityTable;
use Application\Model\CountryTable;
use Application\Model\FacilityTypeTable;
use Application\Model\AncSiteTable;
use Application\Model\SpecimenRejectionReasonTable;
use Application\Model\DataCollectionTable;
use Application\Model\DataCollectionEventLogTable;
use Application\Model\TestStatusTable;
use Application\Model\UserCountryMapTable;
use Application\Model\GlobalConfigTable;
use Application\Model\UserClinicMapTable;
use Application\Model\AncFormTable;
use Application\Model\ClinicDataCollectionTable;
use Application\Model\UserLaboratoryMapTable;
use Application\Model\LoginTrackerTable;
use Application\Model\OccupationTypeTable;
use Application\Model\ClinicRiskAssessmentTable;
use Application\Model\ProvinceTable;

class Module{
    public function onBootstrap(MvcEvent $e){
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        // No need to call presetter if request is from CLI
        if (php_sapi_name() != 'cli') {
            $eventManager->attach('dispatch', array($this, 'preSetter'), 100);
            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'dispatchError'), -999);
        }
    }
    
    public function dispatchError(MvcEvent $event) {
        $error = $event->getError();

        if (empty($error) || $error != "ACL_ACCESS_DENIED") {
            return;
        }

        $baseModel = new ViewModel();
        $baseModel->setTemplate('layout/layout');
        return false;
    }
    
    public function preSetter(MvcEvent $e) {
        if(($e->getRouteMatch()->getParam('controller') != 'Application\Controller\Login')){
            $tempName=explode('Controller',$e->getRouteMatch()->getParam('controller'));
            if(substr($tempName[0], 0, -1) == 'Application'){
                $loginContainer = new Container('user');
                if (!isset($loginContainer->userId) || $loginContainer->userId == "") {
                    if( ! $e->getRequest()->isXmlHttpRequest()) {
                        $url = $e->getRouter()->assemble(array(), array('name' => 'login'));
                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine('Location', $url);
                        $response->setStatusCode(302);
                        $response->sendHeaders();
                
                        // To avoid additional processing
                        // we can attach a listener for Event Route with a high priority
                        $stopCallBack = function($event) use ($response) {
                            $event->stopPropagation();
                            return $response;
                        };
                        //Attach the "break" as a listener with a high priority
                        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                        return $response;
                    }
                }
                
                if ($e->getRequest()->isXmlHttpRequest()) {
                    return;
                }
            }
        }
    }
    
    public function getServiceConfig() {
        return array(
            'factories' => array(
                'CommonService' => function($sm) {
                    return new CommonService($sm);
                },'RoleService' => function($sm) {
                    return new RoleService($sm);
                },'UserService' => function($sm) {
                    return new UserService($sm);
                },'RoleService' => function($sm) {
                    return new RoleService($sm);
                },'FacilityService' => function($sm) {
                    return new FacilityService($sm);
                },'CountryService' => function($sm) {
                    return new CountryService($sm);
                },'FacilityTypeService' => function($sm) {
                    return new FacilityTypeService($sm);
                },'AncSiteService' => function($sm) {
                    return new AncSiteService($sm);
                },'DataCollectionService' => function($sm) {
                    return new DataCollectionService($sm);
                },'RiskAssessmentService' => function($sm) {
                    return new RiskAssessmentService($sm);
                },
                
                'RoleTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new RoleTable($dbAdapter);
                    return $table;
                },'UserTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new UserTable($dbAdapter);
                    return $table;
                },'RoleTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new RoleTable($dbAdapter);
                    return $table;
                },'FacilityTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new FacilityTable($dbAdapter);
                    return $table;
                },'CountryTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new CountryTable($dbAdapter);
                    return $table;
                },'FacilityTypeTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new FacilityTypeTable($dbAdapter);
                    return $table;
                },'AncSiteTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new AncSiteTable($dbAdapter);
                    return $table;
                },'SpecimenRejectionReasonTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new SpecimenRejectionReasonTable($dbAdapter);
                    return $table;
                },'DataCollectionTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new DataCollectionTable($dbAdapter);
                    return $table;
                },'DataCollectionEventLogTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new DataCollectionEventLogTable($dbAdapter);
                    return $table;
                },'TestStatusTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new TestStatusTable($dbAdapter);
                    return $table;
                },'UserCountryMapTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new UserCountryMapTable($dbAdapter);
                    return $table;
                },'GlobalConfigTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new GlobalConfigTable($dbAdapter);
                    return $table;
                },'UserClinicMapTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new UserClinicMapTable($dbAdapter);
                    return $table;
                },'AncFormTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new AncFormTable($dbAdapter);
                    return $table;
                },'ClinicDataCollectionTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ClinicDataCollectionTable($dbAdapter);
                    return $table;
                },'UserLaboratoryMapTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new UserLaboratoryMapTable($dbAdapter);
                    return $table;
                },'LoginTrackerTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new LoginTrackerTable($dbAdapter);
                    return $table;
                },'OccupationTypeTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new OccupationTypeTable($dbAdapter);
                    return $table;
                },'ClinicRiskAssessmentTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ClinicRiskAssessmentTable($dbAdapter);
                    return $table;
                },'ProvinceTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ProvinceTable($dbAdapter);
                    return $table;
                }
            ),
        );
    }
    
    public function getConfig(){
        return include __DIR__ . '/config/module.config.php';
    }

    public function getViewHelperConfig(){
        return array(
           'invokables' => array(
              'GetActiveCountries' => 'Application\View\Helper\GetActiveCountries'
            )
        );
    }
    
    public function getAutoloaderConfig(){
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    

}
