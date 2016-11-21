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

class EmployeeController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $employeeService = $this->getServiceLocator()->get('EmployeeService');
            $result = $employeeService->getAllEmployees($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryList=$countryService->getActiveCountries('employee');
            return new ViewModel(array(
                'countries'=>$countryList
            ));
        }
    }
    
    public function addAction(){
        $employeeService=$this->getServiceLocator()->get('EmployeeService');
        if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $result=$employeeService->addEmployee($params);
            return $this->_redirect()->toRoute('employee');
        }else{
            $roleService = $this->getServiceLocator()->get('RoleService');
            $countryService = $this->getServiceLocator()->get('CountryService');
            $result=$roleService->getActiveRoles();
            $countryList=$countryService->getActiveCountries('employee');
            return new ViewModel(array(
            'roleData'=>$result,
            'countries'=>$countryList
            ));
        }
    }
    
    public function editAction(){
        $employeeService=$this->getServiceLocator()->get('EmployeeService');
         if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $employeeService->updateEmployee($params);
            return $this->redirect()->toRoute('employee');
        }else{
            $employeeId=base64_decode($this->params()->fromRoute('id'));
            $result=$employeeService->getEmployee($employeeId);
            $roleService = $this->getServiceLocator()->get('RoleService');
            $countryService = $this->getServiceLocator()->get('CountryService');
            $roleResult=$roleService->getActiveRoles();
            $countryList=$countryService->getActiveCountries('employee');
            return new ViewModel(array(
                'empResult'=>$result,
                'countries'=>$countryList,
                'roleData'=>$roleResult
            ));
        }
    }
  
}
