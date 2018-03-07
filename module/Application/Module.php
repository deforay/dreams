<?php
namespace Application;

use Zend\Session\Container;

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
use Application\Model\AncRapidRecencyTable;
use Application\Model\LocationDetailsTable;
use Application\Model\StudyFilesTable;
use Application\Model\ManageColumnsTable;
use Application\Model\USSDSurveyTable;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\View\Model\ViewModel;

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
            $tempName = explode('Controller',$e->getRouteMatch()->getParam('controller'));
            if(substr($tempName[0], 0, -1) == 'Application'){
                $loginContainer = new Container('user');
                if (!isset($loginContainer->userId)) {
                    if(! $e->getRequest()->isXmlHttpRequest()) {
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
                }else{
                    if((substr($tempName[1], 1) == 'Index' || substr($tempName[1], 1) == 'Country' || substr($tempName[1], 1) == 'User' || substr($tempName[1], 1) == 'Facility' || substr($tempName[1], 1) == 'AncSite' || substr($tempName[1], 1) == 'StudyOverviewReport' || substr($tempName[1], 1)== 'RiskAssessment' || substr($tempName[1], 1) == 'RiskAssessmentV2' || substr($tempName[1], 1) == 'Summary') && $e->getRouteMatch()->getParam('action')!= 'change-password' && ($loginContainer->roleCode == 'LS' || $loginContainer->roleCode == 'LDEO')){
                        if ($e->getRequest()->isXmlHttpRequest()) {
                            return;
                        }
                        $response = $e->getResponse();
			$response->getHeaders()->addHeaderLine('Location', '/data-collection/'.base64_encode($loginContainer->country[0]));
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
                    }else if(substr($tempName[1], 1)!= 'Clinic' && substr($tempName[1], 1)!= 'RiskAssessment' && substr($tempName[1], 1)!= 'RiskAssessmentV2' && substr($tempName[1], 1)!= 'StudyFiles' && $e->getRouteMatch()->getParam('action')!= 'change-password' && $loginContainer->roleCode == 'ANCSC'){
                        if ($e->getRequest()->isXmlHttpRequest()) {
                            return;
                        }
                        $response = $e->getResponse();
			$response->getHeaders()->addHeaderLine('Location', '/clinic/data-collection/'.base64_encode($loginContainer->country[0]));
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
                    }else if($loginContainer->hasViewOnlyAccess == 'yes' && ($loginContainer->roleCode == 'LS' || $loginContainer->roleCode == 'LDEO')){
                        if(substr($tempName[1], 1)== 'DataCollection' && $e->getRouteMatch()->getParam('action') == 'edit'){
                            $response = $e->getResponse();
                            $response->getHeaders()->addHeaderLine('Location', '/data-collection/'.base64_encode($loginContainer->country[0]));
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
                    }else if($loginContainer->hasViewOnlyAccess == 'yes' && $loginContainer->roleCode == 'ANCSC'){
                        if((substr($tempName[1], 1)== 'RiskAssessment' || substr($tempName[1], 1)!= 'RiskAssessmentV2') && ($e->getRouteMatch()->getParam('action') == 'add' || $e->getRouteMatch()->getParam('action') == 'edit')){
                            $response = $e->getResponse();
                            $response->getHeaders()->addHeaderLine('Location', '/clinic/risk-assessment/'.base64_encode($loginContainer->country[0]));
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
                        }else if(substr($tempName[1], 1)== 'Clinic' && $e->getRouteMatch()->getParam('action') == 'data-collection-edit'){
                            $response = $e->getResponse();
                            $response->getHeaders()->addHeaderLine('Location', '/clinic/data-collection/'.base64_encode($loginContainer->country[0]));
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
                },'AncRapidRecencyTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new AncRapidRecencyTable($dbAdapter);
                    return $table;
                },'LocationDetailsTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new LocationDetailsTable($dbAdapter);
                    return $table;
                },'StudyFilesTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new StudyFilesTable($dbAdapter);
                    return $table;
                },'ManageColumnsTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new ManageColumnsTable($dbAdapter);
                    return $table;
                },'USSDSurveyTable' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new USSDSurveyTable($dbAdapter);
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
              'GetActiveCountries' => 'Application\View\Helper\GetActiveCountries',
	      'GetManageColumns' => 'Application\View\Helper\GetManageColumns'
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