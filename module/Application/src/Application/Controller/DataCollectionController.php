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
use Zend\Session\Container;

class DataCollectionController extends AbstractActionController{
    public function indexAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $result = $dataCollectionService->getAllDataCollections($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = '';
            $type = '';
            $date = '';
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $type=$this->params()->fromRoute('type');
            $date=base64_decode($this->params()->fromRoute('date'));
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $commonService = $this->getServiceLocator()->get('CommonService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $countryList=$countryService->getActiveCountries('data-collection',0);
            $ancSiteList=$ancSiteService->getActiveAncSites('data-collection',$countryId);
            $rejectionReasonList=$commonService->getActiveRejectionReasons();
            $facilityList=$facilityService->getActivefacilities('data-collection',$countryId);
            $choosedCountryInfo=$countryService->getChoosedCountryInfo($countryId);
            $lastDataCollectionInfo = $dataCollectionService->getLastDataCollectionInfo();
            return new ViewModel(array(
                'countries'=>$countryList,
                'countryId'=>$countryId,
                'type'=>$type,
                'date'=>$date,
                'ancSites'=>$ancSiteList,
                'rejectionReasons'=>$rejectionReasonList,
                'facilities'=>$facilityList,
                'choosedCountryInfo'=>$choosedCountryInfo,
                'lastDataCollection'=>$lastDataCollectionInfo
            ));
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $dataCollectionService->addDataCollection($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
    }
    
    public function editAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService->updateDataCollection($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        $dataCollectionId=base64_decode($this->params()->fromRoute('id'));
        $result=$dataCollectionService->getDataCollection($dataCollectionId);
        $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
        $commonService = $this->getServiceLocator()->get('CommonService');
        $facilityService = $this->getServiceLocator()->get('FacilityService');
        if(!isset($countryId) || trim($countryId)==''){
            $country = $result->country;
        }else{
            $country = $countryId;
        }
        $ancSiteList=$ancSiteService->getActiveAncSites('data-collection',$country);
        $rejectionReasonList=$commonService->getActiveRejectionReasons();
        $testStatusList=$commonService->getAllTestStatus();
        $facilityList=$facilityService->getActivefacilities('data-collection',$country);
        return new ViewModel(array(
            'row'=>$result,
            'ancSites'=>$ancSiteList,
            'rejectionReasons'=>$rejectionReasonList,
            'allTestStatus'=>$testStatusList,
            'facilities'=>$facilityList,
            'countryId'=>$countryId
            
        ));
    }
    
    public function viewAction(){
        $dataCollectionId=base64_decode($this->params()->fromRoute('id'));
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $result=$dataCollectionService->getDataCollection($dataCollectionId);
        return new ViewModel(array(
            'row'=>$result,
            'countryId'=>$countryId
        ));
    }
    
    public function lockAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->lockDataCollection($params);
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
            $response=$dataCollectionService->unlockDataCollection($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function sendRequestAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->requestForUnlockDataCollection($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getCountriesLabAncAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->getCountriesLabAncDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function rot47Action(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->generateRot47String($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}