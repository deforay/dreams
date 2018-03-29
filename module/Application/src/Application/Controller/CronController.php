<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CronController extends AbstractActionController{
    
    public function indexAction(){
        
    }
    
    public function importussdAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $dataCollectionService->importUSSDData();
    }
    
    public function dataManagementAction(){
        
    }
}