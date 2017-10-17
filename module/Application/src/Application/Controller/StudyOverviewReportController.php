<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class StudyOverviewReportController extends AbstractActionController{
    public function indexAction(){
       $request = $this->getRequest();
        if($request->isPost()){
            $parameters = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $result = $dataCollectionService->getStudyOverviewData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryInfo = $countryService->getCountry($countryId);
            if($countryInfo){
                $provinces = $countryService->getProvincesByCountry($countryId);
                return new ViewModel(array(
                    'countryInfo'=>$countryInfo,
                    'provinces'=>$provinces
                ));
            }else{
               return $this->redirect()->toRoute('home'); 
            }
        }
    }
    
    public function exportStudyOverviewAction(){
       $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
            $response=$dataCollectionService->exportStudyOverviewInExcel($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('response' =>$response));
            $viewModel->setTerminal(true);
            return $viewModel;
        } 
    }
}
