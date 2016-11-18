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

class LoginController extends AbstractActionController{
    public function indexAction(){
        $loginContainer = new Container('employee');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $employeeService = $this->getServiceLocator()->get('EmployeeService');
            $redirectUrl = $employeeService->getLogin($params);
            return $this->redirect()->toRoute($redirectUrl);
        }
        if (isset($loginContainer->employeeId) && trim($loginContainer->employeeId)!= "") {
            return $this->redirect()->toRoute("home");
        }else{
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryList=$countryService->getActiveCountries('login');
            $viewModel = new ViewModel(array(
                'countries'=>$countryList
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
  
    public function logOutAction(){
        $loginContainer = new Container('employee');
        $loginContainer->getManager()->getStorage()->clear('employee');
        return $this->redirect()->toRoute("login");
    }
}
