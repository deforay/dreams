<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

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
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            $type = $this->params()->fromQuery('type');
            $date = $this->params()->fromQuery('date');
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $commonService = $this->getServiceLocator()->get('CommonService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $countryList=$countryService->getActiveCountries('data-collection','');
            $ancSiteList=$ancSiteService->getActiveAncSites('data-collection',$countryId);
            $rejectionReasonList=$commonService->getActiveRejectionReasons();
            $facilityList=$facilityService->getActivefacilities('data-collection',$countryId);
            $choosedCountryInfo=$countryService->getChoosedCountryInfo($countryId);
            $latestDataCollectionInfo = $dataCollectionService->getLatestDataCollectionInfo();
            return new ViewModel(array(
                'countries'=>$countryList,
                'countryId'=>$countryId,
                'type'=>$type,
                'date'=>$date,
                'ancSites'=>$ancSiteList,
                'rejectionReasons'=>$rejectionReasonList,
                'facilities'=>$facilityList,
                'choosedCountryInfo'=>$choosedCountryInfo,
                'latestDataCollectionInfo'=>$latestDataCollectionInfo
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
        $encodedCountryId = $this->params()->fromRoute('countryId');
        $dataCollectionId=base64_decode($this->params()->fromRoute('id'));
        $result=$dataCollectionService->getDataCollection($dataCollectionId);
        if($result){
            $preventUrl = (trim($countryId)!= '')?'/data-collection/'.$encodedCountryId:'/data-collection';
            if($result->status == 2){ return $this->redirect()->toUrl($preventUrl); };
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $commonService = $this->getServiceLocator()->get('CommonService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $ancSiteList=$ancSiteService->getActiveAncSites('data-collection',$countryId);
            $rejectionReasonList=$commonService->getActiveRejectionReasons();
            $testStatusList=$commonService->getAllTestStatus();
            $facilityList=$facilityService->getActivefacilities('data-collection',$countryId);
            return new ViewModel(array(
                'row'=>$result,
                'ancSites'=>$ancSiteList,
                'rejectionReasons'=>$rejectionReasonList,
                'allTestStatus'=>$testStatusList,
                'facilities'=>$facilityList,
                'countryId'=>$countryId
                
            ));
        }else{
           return $this->redirect()->toRoute('home');
        }
    }
    
    public function viewAction(){
        $dataCollectionId = base64_decode($this->params()->fromRoute('id'));
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $result = $dataCollectionService->getDataCollection($dataCollectionId);
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
    
    public function checkPatientRecordAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->getPatientRecord($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}