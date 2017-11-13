<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class RiskAssessmentController extends AbstractActionController{
    public function indexAction(){
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
        $date = '';
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        if(trim($countryId)!= ''){
            $type=$this->params()->fromQuery('type');
            $date=$this->params()->fromQuery('date');
            $ancSiteList=$ancSiteService->getActiveAncSites('risk-assessment',$countryId);
            return new ViewModel(array(
                'ancSites'=>$ancSiteList,
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
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $countryList = $countryService->getActiveCountries('risk-assessment','');
            $ancSiteList=$ancSiteService->getActiveAncSites('risk-assessment',$countryId);
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
       $countryId=base64_decode($this->params()->fromRoute('countryId'));
       $encodedCountryId = $this->params()->fromRoute('countryId');
       if(trim($countryId)!= ''){
            $riskAssessmentId=base64_decode($this->params()->fromRoute('id'));
            $result=$riskAssessmentService->getRiskAssessment($riskAssessmentId);
            if($result){
                $preventUrl = '/clinic/risk-assessment/'.$encodedCountryId;
                if($result->status == 2){ return $this->redirect()->toUrl($preventUrl); }
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList=$ancSiteService->getActiveAncSites('risk-assessment',$countryId);
                $occupationTypeList=$riskAssessmentService->getOccupationTypes();
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
            $response = $riskAssessmentService->exportRiskAssessmentInExcel($params);
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
            $dataResult = $riskAssessmentService->generateRiskAssessmentPdf($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('dataResult' =>$dataResult));
            $viewModel->setTerminal(true);
           return $viewModel;
        }
    }
    
    public function lockAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $response=$riskAssessmentService->lockRiskAssessment($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function unlockAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $response=$riskAssessmentService->unlockRiskAssessment($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function ancAsanteResultAction(){
        $request = $this->getRequest();
        if($request->isPost()){
            $parameters = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $result = $riskAssessmentService->getANCAsanteResults($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryInfo = $countryService->getCountry($countryId);
            if($countryInfo){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList = $ancSiteService->getActiveAncSites('risk-assessment',$countryId);
                $districts = $countryService->getDistrictsByCountry($countryId);
                return new ViewModel(array(
                    'ancSites'=>$ancSiteList,
                    'districts'=>$districts,
                    'countryInfo'=>$countryInfo
                ));
            }else{
               return $this->redirect()->toRoute('home');
            }
        }
    }
    
    public function exportAncAsanteResultAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $response=$riskAssessmentService->exportAsanteResultInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}