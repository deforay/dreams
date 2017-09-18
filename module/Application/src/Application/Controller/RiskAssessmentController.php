<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class RiskAssessmentController extends AbstractActionController{
    public function indexAction(){
        $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
        $facilityService = $this->getServiceLocator()->get('FacilityService');
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $result = $riskAssessmentService->getAllRiskAssessment($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $countryId = '';
        $type = '';
        $date = '';
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        $type=$this->params()->fromQuery('type');
        $date=base64_decode($this->params()->fromQuery('date'));
        $facilityList=$facilityService->getActivefacilities('risk-assessment',$countryId);
        return new ViewModel(array(
            'countryId'=>$countryId,
            'facilities'=>$facilityList,
            'type'=>$type,
            'date'=>$date
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
        $facilityList=$facilityService->getActivefacilities('risk-assessment',$countryId);
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
        if(isset($countryId) && trim($countryId)!=''){
            $country = $countryId;
        }else{
            $country = $result->country;
        }
       $facilityList=$facilityService->getActivefacilities('risk-assessment',$country);
       $occupationTypeList=$riskAssessmentService->getOccupationTypes();
       return new ViewModel(array(
                'facilities'=>$facilityList,
                'occupationTypes'=>$occupationTypeList,
                'row'=>$result,
                'countryId'=>$country
            ));
    }
    
    public function viewAction(){
       $countryId=base64_decode($this->params()->fromRoute('countryId'));
       $riskAssessmentId=base64_decode($this->params()->fromRoute('id'));
       $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
       $result=$riskAssessmentService->getRiskAssessment($riskAssessmentId);
        if(isset($countryId) && trim($countryId)!=''){
            $country = $countryId;
        }else{
            $country = $result->country;
        }
       return new ViewModel(array(
                'row'=>$result,
                'countryId'=>$country
            ));
    }
    
    public function exportExcelAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $response=$riskAssessmentService->exportRiskAssessmentInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}