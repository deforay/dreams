<?php
namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class IndustryController extends AbstractRestfulController
{
    public function getList(){
        $params=$this->getRequest()->getQuery();
            $industryService = $this->getServiceLocator()->get('IndustryService');
            $result = $industryService->getIndustryInApi();
            return new JsonModel($result);
    }
}