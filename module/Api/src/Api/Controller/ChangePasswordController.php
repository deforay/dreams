<?php
namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class ChangePasswordController extends AbstractRestfulController {

    public function create($params) {
        $plugin = $this->HasParams();
        if($plugin->checkParams($params,array('userId'))){
        $userService = $this->getServiceLocator()->get('UserService');
        $response =$userService->changePasswordViaApi($params);
        return new JsonModel($response);
        }else{
        $response['status']='fail';
        $response['message']='Some required parameters are missing. Please try again.';
        return new JsonModel($response);
       }
    }
    
}
