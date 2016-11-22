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

class DataCollectionController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getAllDataCollections($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryList=$countryService->getActiveCountries('data-collection',0);
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $commonService = $this->getServiceLocator()->get('CommonService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $ancSiteList=$ancSiteService->getActiveAncSites();
            $rejectionReasonList=$commonService->getActiveRejectionReasons();
            $facilityList=$facilityService->getActivefacilities();
            return new ViewModel(array(
                'countries'=>$countryList,
                'countryId'=>$countryId,
                'ancSites'=>$ancSiteList,
                'rejectionReasons'=>$rejectionReasonList,
                'facilities'=>$facilityList
            ));
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $dataCollectionService->addDataCollection($params);
            return $this->redirect()->toUrl($params['chosenCountryId']);
        }
        $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
        $commonService = $this->getServiceLocator()->get('CommonService');
        $facilityService = $this->getServiceLocator()->get('FacilityService');
        $ancSiteList=$ancSiteService->getActiveAncSites();
        $rejectionReasonList=$commonService->getActiveRejectionReasons();
        $facilityList=$facilityService->getActivefacilities();
        return new ViewModel(array(
            'ancSites'=>$ancSiteList,
            'rejectionReasons'=>$rejectionReasonList,
            'facilities'=>$facilityList
        ));
    }
    
    public function editAction(){
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService->updateDataCollection($params);
            return $this->redirect()->toUrl($params['chosenCountryId']);
        }
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        $dataCollectionId=base64_decode($this->params()->fromRoute('id'));
        $result=$dataCollectionService->getDataCollection($dataCollectionId);
        $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
        $commonService = $this->getServiceLocator()->get('CommonService');
        $facilityService = $this->getServiceLocator()->get('FacilityService');
        $ancSiteList=$ancSiteService->getActiveAncSites();
        
        $rejectionReasonList=$commonService->getActiveRejectionReasons();
        $testStatusList=$commonService->getAllTestStatus();
        $facilityList=$facilityService->getActivefacilities();
        return new ViewModel(array(
            'row'=>$result,
            'ancSites'=>$ancSiteList,
            'rejectionReasons'=>$rejectionReasonList,
            'allTestStatus'=>$testStatusList,
            'facilities'=>$facilityList,
            'countryId'=>$countryId,
            
        ));
    }
    
    public function viewAction(){
        $dataCollectionId=base64_decode($this->params()->fromRoute('id'));
        $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
        $result=$dataCollectionService->getDataCollection($dataCollectionId);
        return new ViewModel(array(
            'row'=>$result
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
}