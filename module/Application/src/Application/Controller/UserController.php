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
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryList=$countryService->getActiveCountries('user',$countryId);
            return new ViewModel(array(
                'countries'=>$countryList,
                'countryId'=>$countryId
            ));
        }
    }
    
    public function addAction(){
        $userService = $this->getServiceLocator()->get('UserService');
        if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $result=$userService->addUser($params);
            return $this->redirect()->toUrl($params['chosenCountryId']);
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $roleService = $this->getServiceLocator()->get('RoleService');
            $countryService = $this->getServiceLocator()->get('CountryService');
            $result=$roleService->getActiveRoles($countryId);
            $countryList=$countryService->getActiveCountries('user',$countryId);
            return new ViewModel(array(
                'roleData'=>$result,
                'countries'=>$countryList,
                'countryId'=>$countryId
            ));
        }
    }
    
    public function editAction(){
        $userService=$this->getServiceLocator()->get('UserService');
         if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $userService->updateUser($params);
            return $this->redirect()->toUrl($params['chosenCountryId']);
        }else{
            $countryId=base64_decode($this->params()->fromRoute('countryId'));
            $userId=base64_decode($this->params()->fromRoute('id'));
            $result=$userService->getUser($userId);
            $userCountryList = array();
            if(isset($countryId) && trim($countryId)!=''){
                $userCountryList[] = $countryId;
            }
            if(count($result['userCountries'])>0){
                foreach($result['userCountries'] as $country){
                    $userCountryList[] = $country['country_id'];
                }
            }
            $roleService = $this->getServiceLocator()->get('RoleService');
            $countryService = $this->getServiceLocator()->get('CountryService');
            $roleResult=$roleService->getActiveRoles($countryId);
            $countryList=$countryService->getActiveCountries('user',array_unique($userCountryList));
            return new ViewModel(array(
                'row'=>$result,
                'countries'=>$countryList,
                'roleData'=>$roleResult,
                'countryId'=>$countryId
            ));
        }
    }
  
}
