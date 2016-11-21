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

class FacilityController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $result = $facilityService->getAllFacilites($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryList=$countryService->getActiveCountries('facility');
            return new ViewModel(array(
                'countries'=>$countryList
            ));
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $facilityService->addFacility($params);
            return $this->redirect()->toRoute('facility');
        }
        $countryService = $this->getServiceLocator()->get('CountryService');
        $facilityTypeService = $this->getServiceLocator()->get('FacilityTypeService');
        $countryList=$countryService->getActiveCountries('facility');
        $facilityTypeList=$facilityTypeService->getActiveFacilityTypes();
            return new ViewModel(array(
                'countries'=>$countryList,
                'facilityTypes'=>$facilityTypeList
            ));
    }
    
    public function editAction(){
        $facilityService = $this->getServiceLocator()->get('FacilityService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $facilityService->updateFacility($params);
            return $this->redirect()->toRoute('facility');
        }
        $countryService = $this->getServiceLocator()->get('CountryService');
        $facilityTypeService = $this->getServiceLocator()->get('FacilityTypeService');
        $facilityId=base64_decode($this->params()->fromRoute('id'));
        $result=$facilityService->getFacility($facilityId);
        $countryList=$countryService->getActiveCountries('facility');
        $facilityTypeList=$facilityTypeService->getActiveFacilityTypes();
        return new ViewModel(array(
            'countries'=>$countryList,
            'facilityTypes'=>$facilityTypeList,
            'row'=>$result
        ));
    }
}