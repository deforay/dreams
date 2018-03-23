<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class UssdController extends AbstractActionController{
    public function indexAction(){
        
    }
    
    public function notEnrolledAction(){
        $request = $this->getRequest();
        if($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getUSSDNotEnrolledData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(isset($countryId) && trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $countryInfo = $countryService->getCountry($countryId);
            $ancSiteList = $ancSiteService->getActiveAncSites('ussd-not-enrolled',$countryId,$province ='',$district ='');
            return new ViewModel(array(
                'countryInfo'=>$countryInfo,
                'ancSiteList'=>$ancSiteList
            ));
        }else{
           return $this->redirect()->toRoute('home');
        }
    }
    
    public function exportUssdNotEnrolledAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $file = $dataCollectionService->exportUSSDNotEnrolledInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('file' =>$file));
            $viewModel->setTerminal(true);
            return $viewModel;
        } 
    }
  
}
