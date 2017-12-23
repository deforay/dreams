<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class CountryController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $result = $countryService->getAllCountries($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryService->addCountry($params);
            return $this->redirect()->toRoute('country');
        }
    }
    
    public function editAction(){
        $countryService = $this->getServiceLocator()->get('CountryService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService->updateCountry($params);
            return $this->redirect()->toRoute('country');
        }
        $countryId = base64_decode($this->params()->fromRoute('id'));
        $result = $countryService->getCountry($countryId);
        if($result){
            return new ViewModel(array(
                'row'=>$result
            ));
        }else{
           return $this->redirect()->toRoute('country'); 
        }
    }
    
    public function getCountryProvincesAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $response = $countryService->getProvincesByCountry(base64_decode($params['country']));
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
           return $viewModel;
        }  
    }
    
    public function getProvinceDistrictsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $response = $countryService->getDistrictsByProvince(base64_decode($params['province']));
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
           return $viewModel;
        }  
    }
    
    public function dashboardAction(){
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        $countryService = $this->getServiceLocator()->get('CountryService');
        $countryInfo = $countryService->getCountry($countryId);
        $provinces = $countryService->getProvincesByCountry($countryId);
        if($countryInfo){
            return new ViewModel(array(
                'countryInfo'=>$countryInfo,
                'provinces'=>$provinces
            ));
        }else{
           return $this->redirect()->toRoute('home');
        }
    }
    
    public function getLabDataReportingDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->getCountryLabDataReportingDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('params'=>$params,'response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getClinicDataReportingDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->getCountryClinicDataReportingDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('params'=>$params,'response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getDataReportingLocationsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->getDataReportingLocations($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('params'=>$params,'response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function exportDashboardDataAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->exportCountryDashboardInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getMultipleProvinceDistrictsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $response = $countryService->getDistrictsByProvinces($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getDistrictsAncsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $response = $ancSiteService->getActiveAncSites('country',$params['countryId'],$params['provinces'],$params['districts']);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}