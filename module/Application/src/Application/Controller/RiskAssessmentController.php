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
        if(trim($countryId)!= ''){
            $type=$this->params()->fromQuery('type');
            $date=$this->params()->fromQuery('date');
            $facilityList=$facilityService->getActivefacilities('risk-assessment',$countryId);
            return new ViewModel(array(
                'facilities'=>$facilityList,
                'type'=>$type,
                'date'=>$date,
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
        if(trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $countryList = $countryService->getActiveCountries('risk-assessment','');
            $facilityList = $facilityService->getActivefacilities('risk-assessment',$countryId);
            $occupationTypeList = $riskAssessmentService->getOccupationTypes();
            return new ViewModel(array(
                    'countries'=>$countryList,
                    'facilities'=>$facilityList,
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
       $countryId=base64_decode($this->params()->fromRoute('countryId'));
       if(trim($countryId)!= ''){
            $riskAssessmentId=base64_decode($this->params()->fromRoute('id'));
            $result=$riskAssessmentService->getRiskAssessment($riskAssessmentId);
            if($result){
                $facilityService = $this->getServiceLocator()->get('FacilityService');
                $facilityList=$facilityService->getActivefacilities('risk-assessment',$countryId);
                $occupationTypeList=$riskAssessmentService->getOccupationTypes();
                return new ViewModel(array(
                        'facilities'=>$facilityList,
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
       $countryId=base64_decode($this->params()->fromRoute('countryId'));
       if(trim($countryId)!= ''){
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
    
    public function generatePdfAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $dataResult=$riskAssessmentService->generateRiskAssessmentPdf($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('dataResult' =>$dataResult));
            $viewModel->setTerminal(true);
           return $viewModel;
        }
    }
}