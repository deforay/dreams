<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class LoginController extends AbstractRestfulController
{
     public function create($params) {
        $plugin = $this->HasParams();
        if($plugin->checkParams($params,array('email','password'))){
          $userService = $this->getServiceLocator()->get('UserService');
          $response =$userService->userLoginViaAPI($params);
        return new JsonModel($response);
        }else{
          $response['status']='fail';
          $response['message']='Some required parameters are missing. Please try again.';
        return new JsonModel($response);
       }
     }
}