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

class AncSiteController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $result = $ancSiteService->getAllAncSites($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            if(isset($countryId) && trim($countryId)!= ''){
                return new ViewModel(array(
                    'countryId'=>$countryId
                ));
            }else{
                return $this->redirect()->toRoute('home');
            }
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $ancSiteService->addAncSite($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(isset($countryId) && trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $facilityTypeService = $this->getServiceLocator()->get('FacilityTypeService');
            $countryList = $countryService->getActiveCountries('anc',$countryId);
            $provinceList = $countryService->getProvincesByCountry($countryId);
            $facilityTypeList = $facilityTypeService->getActiveFacilityTypes();
                return new ViewModel(array(
                    'countries'=>$countryList,
                    'provinces'=>$provinceList,
                    'facilityTypes'=>$facilityTypeList,
                    'countryId'=>$countryId
                ));
        }else{
            return $this->redirect()->toRoute('home');
        }
    }
    
    public function editAction(){
        $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $ancSiteService->updateAncSite($params);
            return $this->redirect()->toUrl($params['redirectUrl']);
        }
        $countryId = base64_decode($this->params()->fromRoute('countryId'));
        if(isset($countryId) && trim($countryId)!= ''){
            $countryService = $this->getServiceLocator()->get('CountryService');
            $facilityTypeService = $this->getServiceLocator()->get('FacilityTypeService');
            $ancSiteId = base64_decode($this->params()->fromRoute('id'));
            $result = $ancSiteService->getAncSite($ancSiteId);
            if(isset($result->anc_site_id)){
                $countryList = $countryService->getActiveCountries('anc',$countryId);
                $provinceList = $countryService->getProvincesByCountry($countryId);
                $districtList = $countryService->getDistrictsByProvince(((int)($result->province) >0)?(int)$result->province:0);
                $facilityTypeList = $facilityTypeService->getActiveFacilityTypes();
                return new ViewModel(array(
                    'countries'=>$countryList,
                    'provinces'=>$provinceList,
                    'districts'=>$districtList,
                    'facilityTypes'=>$facilityTypeList,
                    'row'=>$result,
                    'countryId'=>$countryId
                ));
            }else{
               return $this->redirect()->toUrl('/anc-site/'.$this->params()->fromRoute('countryId'));
            }
        }else{
            return $this->redirect()->toRoute('home');
        }
    }
}