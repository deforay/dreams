<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class OdkSupervisoryAuditController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getOdkSupervisoryAuditDetails($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(isset($countryId) && trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryInfo = $countryService->getCountry($countryId);
            $provinceList = $countryService->getProvincesByCountry($countryId);
            return new ViewModel(array(
                'countryInfo'=>$countryInfo,
                'provinceList'=>$provinceList
            ));
        }else{
           return $this->redirect()->toRoute('home');
        }
    }
}