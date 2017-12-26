<?php
namespace Application\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class GetManageColumns extends AbstractHelper implements ServiceLocatorAwareInterface{
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
    
    public function getServiceLocator(){  
        return $this->serviceLocator;  
    }
    
    public function __invoke(){
        $sm = $this->getServiceLocator()->getServiceLocator();
        $manageColumnsDb = $sm->get('ManageColumnsTable');
       return $manageColumnsDb->fetchUserManageColumns();
    }
}