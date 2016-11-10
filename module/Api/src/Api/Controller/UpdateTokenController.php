<?php
namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class UpdateTokenController extends AbstractRestfulController{

    public function create($postParams) {
        $plugin = $this->HasParams();
        if($plugin->checkParams($postParams,array('userId','deviceId'))){
            $userService = $this->getServiceLocator()->get('UserService');
            $response =$userService->updateDeviceToken($postParams);
            return new JsonModel($response);
        }else{
            $response['status']='fail';
            $response['message']='Some required parameters are missing. Please try again.';
            return new JsonModel($response); 
        }
    }

}

