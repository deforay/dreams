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

class SummaryController extends AbstractActionController{
    public function indexAction(){
       $countryId = base64_decode($this->params()->fromRoute('countryId'));
       if(isset($countryId) && trim($countryId)!= ''){
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $summaries = $dataCollectionService->getSummaryDetails();
                 return new ViewModel(array(
                    'summaries'=>$summaries,
                    'countryId'=>$countryId
                 ));
       }else{
          return $this->redirect()->toRoute('home');
       }
    }
    
    public function getDataReportingWeeklyBarChartAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response = $dataCollectionService->getWeeklyDataReportingDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getBehaviourDataReportingWeeklyBarChartAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $riskAssessmentService = $this->getServiceLocator()->get('RiskAssessmentService');
            $response = $riskAssessmentService->getBehaviourDataReportingWeeklyDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}