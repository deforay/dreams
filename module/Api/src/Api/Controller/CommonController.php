<?php
namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class CommonController extends AbstractRestfulController{
    
    public function create($params) {
        $plugin = $this->HasParams();
        if($plugin->checkParams($params,array('tableName'))){
        $common = $this->getServiceLocator()->get('CommonService');
        $response = $common->checkApiFieldValidations($params);
        return new JsonModel($response);
        }else{
        $response['status']='fail';
        $response['message']='Some required parameters are missing. Please try again.';
        return new JsonModel($response);
       }
    }
    
 

}