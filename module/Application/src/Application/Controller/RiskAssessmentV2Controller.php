<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class RiskAssessmentV2Controller extends AbstractActionController{
    public function indexAction(){
        $countryService = $this->getServiceLocator()->get('CountryService');
        $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
        $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $result = $riskAssessmentService->getAllRiskAssessment($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $countryId = '';
        $type = '';
        $dateSrc = '';
        $date = '';
        $dashProvince = '';
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(trim($countryId)!= ''){
            $type = $this->params()->fromQuery('type');
            $dateSrc = $this->params()->fromQuery('dSrc');
            $date = $this->params()->fromQuery('date');
            $dashProvince = base64_decode($this->params()->fromQuery('province'));
            $provinces = $countryService->getProvincesByCountry($countryId);
            $districts = $countryService->getDistrictsByProvinces($params = array());
            $ancSiteList = $ancSiteService->getActiveAncSites('risk-assessment',$countryId,$province ='',$district ='');
            return new ViewModel(array(
                'provinces'=>$provinces,
                'districts'=>$districts,
                'ancSites'=>$ancSiteList,
                'type'=>$type,
                'dateSrc'=>$dateSrc,
                'date'=>$date,
                'dashProvince'=>$dashProvince,
                'countryId'=>$countryId
            ));
        }else{
           return $this->redirect()->toRoute('home');
        }
    }
    
    public function addAction(){
        $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
        $request = $this->getRequest();
        if ($request->isPost()){
            $params = $request->getPost();
            $riskAssessmentService->addRiskAssessment($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(isset($countryId) && trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $countryList = $countryService->getActiveCountries('risk-assessment','');
            $ancSiteList = $ancSiteService->getActiveAncSites('risk-assessment',$countryId,$province ='',$district ='');
            $occupationTypeList = $riskAssessmentService->getOccupationTypes();
            return new ViewModel(array(
                    'countries'=>$countryList,
                    'ancSites'=>$ancSiteList,
                    'occupationTypes'=>$occupationTypeList,
                    'countryId'=>$countryId
                ));
        }else{
           return $this->redirect()->toRoute('home'); 
        }
    }
    
    public function editAction(){
       $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
       $request = $this->getRequest();
       if ($request->isPost()){
           $params = $request->getPost();
           $riskAssessmentService->updateRiskAssessment($params);
          return $this->redirect()->toUrl($params['redirectUrl']);
       }
       $countryId = base64_decode($this->params()->fromRoute('countryId'));
       $encodedCountryId = $this->params()->fromRoute('countryId');
       if(isset($countryId) && trim($countryId)!= ''){
            $riskAssessmentId = base64_decode($this->params()->fromRoute('id'));
            $result = $riskAssessmentService->getRiskAssessment($riskAssessmentId);
            if($result){
                $preventUrl = '/clinic/risk-assessment/v2/'.$encodedCountryId;
                if($result->status == 2){ return $this->redirect()->toUrl($preventUrl); }
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList = $ancSiteService->getActiveAncSites('risk-assessment',$countryId,$province ='',$district ='');
                $occupationTypeList = $riskAssessmentService->getOccupationTypes();
                return new ViewModel(array(
                        'ancSites'=>$ancSiteList,
                        'occupationTypes'=>$occupationTypeList,
                        'row'=>$result,
                        'countryId'=>$countryId
                     ));
            }else{
               return $this->redirect()->toRoute('home'); 
            }
       }else{
          return $this->redirect()->toRoute('home');
       } 
    }
    
    public function viewAction(){
       $countryId = base64_decode($this->params()->fromRoute('countryId'));
       if(isset($countryId) && trim($countryId)!= ''){
        $riskAssessmentId = base64_decode($this->params()->fromRoute('id'));
        $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
        $result = $riskAssessmentService->getRiskAssessment($riskAssessmentId);
        if($result){
         return new ViewModel(array(
                'row'=>$result,
                'countryId'=>$countryId
              ));
        }else{
         return $this->redirect()->toRoute('home');
        }
       }else{
        return $this->redirect()->toRoute('home');
       }
    }
}