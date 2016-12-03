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

class DataExtractionController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getAllDataExtractions($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $countryList=$countryService->getActiveCountries('data-extraction',$countryId);
            $ancSiteList=$ancSiteService->getActiveAncSites('data-extraction',$countryId);
            $facilityList=$facilityService->getActivefacilities('data-extraction',$countryId);
            return new ViewModel(array(
                'countries'=>$countryList,
                'ancSites'=>$ancSiteList,
                'facilities'=>$facilityList,
                'countryId'=>$countryId
            ));
        }
    }
    
    public function exportExcelAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->exportDataCollectionInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function printLabLogbookAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getAllLabLogbook($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $ancSiteList=$ancSiteService->getActiveAncSites('lab-logbook',$countryId);
            $facilityList=$facilityService->getActivefacilities('lab-logbook',$countryId);
            return new ViewModel(array(
                'countryId'=>$countryId,
                'ancSites'=>$ancSiteList,
                'facilities'=>$facilityList
            ));
        }
    }
    
    public function generateLogbookPdfAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $logbookResult=$dataCollectionService->getLogbookResult($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('logbookResult' =>$logbookResult));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}