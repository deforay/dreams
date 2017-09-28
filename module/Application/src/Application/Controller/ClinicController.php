<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class ClinicController extends AbstractActionController{
    public function indexAction(){
        
    }
    
    public function dataCollectionAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $dataCollectionService->getAllClinicDataCollections($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            if(trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList=$ancSiteService->getActiveAncSites('clinic-data-collection',$countryId);
                $ancFormFieldList=$dataCollectionService->getActiveAncFormFields();
                return new ViewModel(array(
                    'ancSites'=>$ancSiteList,
                    'ancFormFields'=>$ancFormFieldList,
                    'countryId'=>$countryId
                ));
            }else{
                return $this->redirect()->toRoute('home');
            }
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
    
    public function dataCollectionEditAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService->updateClinicDataCollection($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            if(trim($countryId)!= ''){
                $clinicDataCollectionId=base64_decode($this->params()->fromRoute('id'));
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $result=$dataCollectionService->getClinicDataCollection($clinicDataCollectionId);
                if($result){
                    $ancSiteList=$ancSiteService->getActiveAncSites('clinic-data-collection-edit',$countryId);
                    $ancFormFieldList=$dataCollectionService->getActiveAncFormFields();
                    return new ViewModel(array(
                        'row'=>$result,
                        'ancSites'=>$ancSiteList,
                        'ancFormFields'=>$ancFormFieldList,
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
    
    public function dataExtractionAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $dataCollectionService->getAllClinicalDataExtractions($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            if(trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList=$ancSiteService->getActiveAncSites('clinic-data-extraction',$countryId);
                $ancFormFieldList=$dataCollectionService->getActiveAncFormFields();
                return new ViewModel(array(
                        'ancSites'=>$ancSiteList,
                        'ancFormFields'=>$ancFormFieldList,
                        'countryId'=>$countryId
                    ));
            }else{
                return $this->redirect()->toRoute('home');
            }
        }
    }
    
    public function dataCollectionExportExcelAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->exportClinicDataCollectionInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function labReportAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getAllAncLabReportDatas($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            if(trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $facilityService = $this->getServiceLocator()->get('FacilityService');
                $ancSiteList=$ancSiteService->getActiveAncSites('anc-lab-report',$countryId);
                $facilityList=$facilityService->getActivefacilities('anc-lab-report',$countryId);
                return new ViewModel(array(
                    'ancSites'=>$ancSiteList,
                    'facilities'=>$facilityList,
                    'countryId'=>$countryId
                ));
            }else{
                return $this->redirect()->toRoute('home');
            }
        }
    }
    
    public function exportLabReportAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $labReportResult=$dataCollectionService->getLabReportResult();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('params'=>$params,'labReportResult' =>$labReportResult));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function checkDuplicateDataReportAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->checkDublicateClinicDataReport($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response'=>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}