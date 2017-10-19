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

class ResultEmailController extends AbstractActionController{
    public function indexAction(){
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(trim($countryId)!= ''){
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $commonService = $this->getServiceLocator()->get('CommonService');
            $facilityList = $facilityService->getActivefacilities('result-email',$countryId);
            $ancList = $ancSiteService->getActiveAncSites('result-email',$countryId);
            $testStatusList = $commonService->getAllTestStatus();
            return new ViewModel(array(
            'facilityList'=>$facilityList,
            'ancList'=>$ancList,
            'testStatus'=>$testStatusList,
            'countryId'=>$countryId
            ));
        }else{
            return $this->redirect()->toRoute('home');
        }
    }
    
    public function sendAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService = $this->getServiceLocator()->get('CommonService');
            $commonService->sendResultMail($params);
            return $this->redirect()->toUrl('/result-email/'.$params['chosenCountry']);
        }
    }
    
    public function getDataCollectionAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $dataList=$dataCollectionService->getSearchableDataCollection($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('dataList' =>$dataList));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function generatePdfAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $dataResult=$dataCollectionService->generateDataCollectionResultPdf($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('dataResult' =>$dataResult));
            $viewModel->setTerminal(true);
           return $viewModel;
        }
    }
  
}