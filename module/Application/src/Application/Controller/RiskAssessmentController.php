<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class RiskAssessmentController extends AbstractActionController{
    public function indexAction(){
        $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $result = $riskAssessmentService->getAllRiskAssessment($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        return new ViewModel(array(
            'countryId'=>$countryId
        ));
        
    }
    
    public function addAction(){
        $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
        $request = $this->getRequest();
        if ($request->isPost()){
            $params = $request->getPost();
            $riskAssessmentService->addRiskAssessment($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        $countryService = $this->getServiceLocator()->get('CountryService');
        $facilityService = $this->getServiceLocator()->get('FacilityService');
        $countryList=$countryService->getActiveCountries('risk-assessment',0);
        $facilityList=$facilityService->getActivefacilities('data-collection',$countryId);
        $occupationTypeList=$riskAssessmentService->getOccupationTypes();
        return new ViewModel(array(
                'countries'=>$countryList,
                'facilities'=>$facilityList,
                'occupationTypes'=>$occupationTypeList,
                'countryId'=>$countryId
            ));
    }
    
    public function editAction(){
       $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
       $request = $this->getRequest();
       if ($request->isPost()){
           $params = $request->getPost();
           $riskAssessmentService->updateRiskAssessment($params);
          return $this->redirect()->toUrl($params['redirectUrl']);
       }
       $countryId=base64_decode($this->params()->fromRoute('countryId'));
       $riskAssessmentId=base64_decode($this->params()->fromRoute('id'));
       $result=$riskAssessmentService->getRiskAssessment($riskAssessmentId);
       $facilityService = $this->getServiceLocator()->get('FacilityService');
       if(!isset($countryId) || trim($countryId)==''){
            $country = $result->country;
        }else{
            $country = $countryId;
       }
       $facilityList=$facilityService->getActivefacilities('data-collection',$country);
       $occupationTypeList=$riskAssessmentService->getOccupationTypes();
       return new ViewModel(array(
                'facilities'=>$facilityList,
                'occupationTypes'=>$occupationTypeList,
                'row'=>$result,
                'countryId'=>$countryId
            ));
    }
}