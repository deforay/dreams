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
        $countryId=base64_decode($this->params()->fromRoute('id'));
        $result=$countryService->getCountry($countryId);
        return new ViewModel(array(
            'row'=>$result
        ));
    }
    
    public function getCountryProvincesAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $countryService = $this->getServiceLocator()->get('CountryService');
            $response=$countryService->getProvincesByCountry(base64_decode($params['country']));
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }  
    }
    
    public function dashboardAction(){
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        $countryService = $this->getServiceLocator()->get('CountryService');
        $countryInfo = $countryService->getCountry($countryId);
        $provinces = $countryService->getProvincesByCountry($countryId);
        return new ViewModel(array(
            'countryInfo'=>$countryInfo,
            'provinces'=>$provinces
        )); 
    }
    
    public function getDashboardDataAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->getCountryDashboardDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getDataReportingLocationsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->getDataReportingLocations($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}