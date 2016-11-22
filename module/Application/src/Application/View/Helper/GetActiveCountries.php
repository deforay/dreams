<?php
namespace Application\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class GetActiveCountries extends AbstractHelper implements ServiceLocatorAwareInterface{
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
    
    public function getServiceLocator(){  
        return $this->serviceLocator;  
    }
    
    public function __invoke($from){
        $sm = $this->getServiceLocator()->getServiceLocator();
        $countryDb = $sm->get('CountryTable');
        return $countryDb->fetchActiveCountries($from,'');
    }
}