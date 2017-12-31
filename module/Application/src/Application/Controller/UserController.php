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

class UserController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $userService = $this->getServiceLocator()->get('UserService');
            $result = $userService->getAllUsers($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            return new ViewModel(array(
                'countryId'=>$countryId
            ));
        }
    }
    
    public function addAction(){
        $userService = $this->getServiceLocator()->get('UserService');
        if($this->getRequest()->isPost()){
            $params = $this->getRequest()->getPost();
            $userService->addUser($params);
           return $this->redirect()->toUrl($params['redirectUrl']);
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            $roleService = $this->getServiceLocator()->get('RoleService');
            $countryService = $this->getServiceLocator()->get('CountryService');
            $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
            $facilityService = $this->getServiceLocator()->get('FacilityService');
            $result = $roleService->getActiveRoles($countryId);
            $countryList = $countryService->getActiveCountries('user',$countryId);
            $allCountryList = $countryService->getActiveCountries('login',$countryId);
            $ancSiteList = $ancSiteService->getActiveAncSites('user',$countryId,$province ='',$district ='');
            $facilityList = $facilityService->getActivefacilities('user',$countryId);
            return new ViewModel(array(
                'roleData'=>$result,
                'countries'=>$countryList,
                'allCountries'=>$allCountryList,
                'ancSites'=>$ancSiteList,
                'facilities'=>$facilityList,
                'countryId'=>$countryId
            ));
        }
    }
    
    public function editAction(){
        $userService = $this->getServiceLocator()->get('UserService');
         if($this->getRequest()->isPost()){
            $params = $this->getRequest()->getPost();
            $userService->updateUser($params);
           return $this->redirect()->toUrl($params['redirectUrl']);
        }else{
            $countryId = base64_decode($this->params()->fromRoute('countryId'));
            $userId = base64_decode($this->params()->fromRoute('id'));
            $result = $userService->getUser($userId);
            if(isset($result->user_id)){
                $roleService = $this->getServiceLocator()->get('RoleService');
                $countryService = $this->getServiceLocator()->get('CountryService');
                $ancSiteService = $this->getServiceLocator()->get('AncSiteService');
                $facilityService = $this->getServiceLocator()->get('FacilityService');
                $roleResult = $roleService->getActiveRoles($countryId);
                $countryList = $countryService->getActiveCountries('user',$countryId);
                $allCountryList = $countryService->getActiveCountries('login',$countryId);
                $ancSiteList = $ancSiteService->getActiveAncSites('user',$countryId,$province ='',$district ='');
                $facilityList = $facilityService->getActivefacilities('user',$countryId);
                return new ViewModel(array(
                    'row'=>$result,
                    'countries'=>$countryList,
                    'allCountries'=>$allCountryList,
                    'ancSites'=>$ancSiteList,
                    'facilities'=>$facilityList,
                    'roleData'=>$roleResult,
                    'countryId'=>$countryId
                ));
            }else{
                return $this->redirect()->toUrl('/user/'.$this->params()->fromRoute('countryId'));
            }
        }
    }
    
    public function changePasswordAction(){
        $request = $this->getRequest();
        if($request->isPost()){
            $params = $request->getPost();
            $userService = $this->getServiceLocator()->get('UserService');
            $userService->changeAccountPassword($params);
            return $this->redirect()->toRoute('home');
        }
    }
    
    public function checkPasswordAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $userService = $this->getServiceLocator()->get('UserService');
            $row = $userService->checkAccountPassword($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('row' =>$row));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
  
}
