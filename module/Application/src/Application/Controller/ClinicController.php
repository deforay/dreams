<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class ClinicController extends AbstractActionController{
    public function indexAction(){
        
    }
    
    public function dataCollectionAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getAllClinicDataCollections($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $ancSiteList=$ancSiteService->getActiveAncSites('clinic-data-collection',$countryId);
            $ancFormFieldList=$dataCollectionService->getActiveAncFormFields();
            return new ViewModel(array(
                'countryId'=>$countryId,
                'ansSites'=>$ancSiteList,
                'ancFormFields'=>$ancFormFieldList
            ));
        }
    }
    
    public function dataCollectionAddAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $dataCollectionService->addClinicDataCollection($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
    }
}
