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
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            if(isset($countryId) && trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList = $ancSiteService->getActiveAncSites('clinic-data-collection',$countryId,$province ='',$district ='');
                $ancFormFieldList = $dataCollectionService->getActiveAncFormFields();
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
            if(isset($countryId) && trim($countryId)!= ''){
                $clinicDataCollectionId = base64_decode($this->params()->fromRoute('id'));
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $result = $dataCollectionService->getClinicDataCollection($clinicDataCollectionId);
                if($result){
                    $ancSiteList = $ancSiteService->getActiveAncSites('clinic-data-collection-edit',$countryId,$province ='',$district ='');
                    $ancFormFieldList = $dataCollectionService->getActiveAncFormFields();
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
            if(isset($countryId) && trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList = $ancSiteService->getActiveAncSites('clinic-data-extraction',$countryId,$province ='',$district ='');
                $ancFormFieldList = $dataCollectionService->getActiveAncFormFields();
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
            $response = $dataCollectionService->exportClinicDataCollectionInExcel($params);
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
            $result = $dataCollectionService->getAllLabRecencyResult($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = '';
            $printSrc = '';
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            $printSrc = $this->params()->fromQuery('frmSrc');
            if(trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $facilityService = $this->getServiceLocator()->get('FacilityService');
                $ancSiteList = $ancSiteService->getActiveAncSites('anc-lab-report',$countryId,$province ='',$district ='');
                $facilityList = $facilityService->getActivefacilities('anc-lab-report',$countryId);
                return new ViewModel(array(
                    'ancSites'=>$ancSiteList,
                    'facilities'=>$facilityList,
                    'countryId'=>$countryId,
                    'printSrc'=>$printSrc
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
            $labReportResult = $dataCollectionService->getLabReportResult();
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
            $response = $dataCollectionService->checkDublicateClinicDataReport($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response'=>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function lockAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->lockClinicDataCollection($params);
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
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->unlockClinicDataCollection($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function enrollmentReportAction(){
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(isset($countryId) && trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $provinces = $countryService->getProvincesByCountry($countryId);
            $ancSiteList = $ancSiteService->getActiveAncSites('clinic-enrollment-report',$countryId,$province ='',$district ='');
            return new ViewModel(array(
                'countryId'=>$countryId,
                'provinces'=>$provinces,
                'ancSiteList'=>$ancSiteList
            ));
        }else{
            return $this->redirect()->toRoute('home');
        }
    }

    public function addReturnRecencyAction(){
        $request = $this->getRequest();
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $dataCollectionService->saveReturnOfRecencyResults($parameters);
            return $this->redirect()->toUrl($parameters['redirectUrl']);
        }
    }
    public function editReturnRecencyAction(){
        $request = $this->getRequest();
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $dataCollectionService->saveReturnOfRecencyResults($parameters);
            return $this->redirect()->toUrl($parameters['redirectUrl']);
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            if(isset($countryId) && trim($countryId)!= ''){
                $patientBarcodeId = base64_decode($this->params()->fromRoute('id'));
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList = $ancSiteService->getActiveAncSites('clinic-data-collection',$countryId,$province ='',$district ='');
                $result = $dataCollectionService->fetchSingleReturnOfRecencyResult($patientBarcodeId);
                return new ViewModel(array(
                    'ancSites'=>$ancSiteList,
                    'countryId'=>$countryId,
                    'result' => $result
                ));
            }else{
                return $this->redirect()->toRoute('home');
            }
        }
    }

    public function returnRecencyAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()) {

        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            if(isset($countryId) && trim($countryId)!= ''){
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $ancSiteList = $ancSiteService->getActiveAncSites('clinic-data-collection',$countryId,$province ='',$district ='');
                return new ViewModel(array(
                    'ancSites'=>$ancSiteList,
                    'countryId'=>$countryId
                ));
            }else{
                return $this->redirect()->toRoute('home');
            }
        }
    }    
    
    public function getEnrollmentReportDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $provinces = $countryService->getProvincesByCountry($params['country']);
            $result = $dataCollectionService->getClinicEnrollmentDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('provinces'=>$provinces,'result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function exportEnrollmentReportAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->exportEnrollmentReportInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    public function getUssdForBarcodeAction(){
        $request = $this->getRequest();
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $response = $dataCollectionService->fetchAllReturnOfRecencyResults($parameters);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}