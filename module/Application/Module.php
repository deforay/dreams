<?php
namespace Application;

use Zend\Session\Container;
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
            $tempName=explode('Controller',$e->getRouteMatch()->getParam('controller'));
            if(substr($tempName[0], 0, -1) == 'Application'){
                $loginContainer = new Container('employee');
                //if (!isset($loginContainer->employeeId) || $loginContainer->employeeId == "") {
                //    if( ! $e->getRequest()->isXmlHttpRequest()) {
                //        $url = $e->getRouter()->assemble(array(), array('name' => 'login'));
                //        $response = $e->getResponse();
                //        $response->getHeaders()->addHeaderLine('Location', $url);
                //        $response->setStatusCode(302);
                //        $response->sendHeaders();
                //
                //        // To avoid additional processing
                //        // we can attach a listener for Event Route with a high priority
                //        $stopCallBack = function($event) use ($response) {
                //            $event->stopPropagation();
                //            return $response;
                //        };
                //        //Attach the "break" as a listener with a high priority
                //        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                //        return $response;
                //    }
                //}
                
                if ($e->getRequest()->isXmlHttpRequest()) {
                    return;
                }
            }
        }
    }
    
    public function getServiceConfig() {
        return array(
        );
    }
    
    public function getConfig(){
        return include __DIR__ . '/config/module.config.php';
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
