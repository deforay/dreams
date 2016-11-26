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
        $loginContainer = new Container('user');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $userService = $this->getServiceLocator()->get('UserService');
            $redirectUrl = $userService->getLogin($params);
            if($redirectUrl == 'home'){
                $dataCollectionService = $this->getServiceLocator()->get('DataCollectionService');
                $dataCollectionService->automaticDataCollectionLockAfterLogin();
            }
            return $this->redirect()->toRoute($redirectUrl);
        }
        if (isset($loginContainer->userId) && trim($loginContainer->userId)!= "") {
            return $this->redirect()->toRoute("home");
        }else{
            $countryService = $this->getServiceLocator()->get('CountryService');
            $countryList=$countryService->getActiveCountries('login',0);
            $viewModel = new ViewModel(array(
                'countries'=>$countryList
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
  
    public function logOutAction(){
        $loginContainer = new Container('user');
        $loginContainer->getManager()->getStorage()->clear('user');
        return $this->redirect()->toRoute("login");
    }
}
