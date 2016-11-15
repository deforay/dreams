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

class RoleController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $roleService = $this->getServiceLocator()->get('RoleService');
            $result = $roleService->getAllRoles($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $roleService = $this->getServiceLocator()->get('RoleService');
            $roleService->addRole($params);
            return $this->redirect()->toRoute('role');
        }
    }
    
     public function editAction(){
        $roleService = $this->getServiceLocator()->get('RoleService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $roleService = $this->getServiceLocator()->get('RoleService');
            $roleService->updateRole($params);
            return $this->redirect()->toRoute('role');
        }
        $roleId=base64_decode($this->params()->fromRoute('id'));
        $result=$roleService->getRole($roleId);
        return new ViewModel(array(
            'row'=>$result
        ));
    }
  
}
