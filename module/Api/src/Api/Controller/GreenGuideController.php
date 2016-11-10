<?php
namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class GreenGuideController extends AbstractRestfulController
{
    public function getList($params){
        $params=$this->getRequest()->getQuery();
        if($params['user-id']!=''){
            $userId = $params['user-id'];
            $trafficLightService = $this->getServiceLocator()->get('TrafficLightService');
            $result = $trafficLightService->getGreenForSureInApi($userId);
            return new JsonModel($result);
        }
    }
}