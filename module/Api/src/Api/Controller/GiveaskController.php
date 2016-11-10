<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class GiveaskController extends AbstractRestfulController
{
     public function create($params){
          $limit='';
          $offset='';
          $str = '';
          $userId = '';
          if(isset($params['limit']) && isset($params['offset'])){
               $limit=$params['limit'];
               $offset=$params['offset'];
               if(isset($params['user'])){
               $str=$params['user'];    
               }
               if(isset($params['userId'])){
               $userId=$params['userId'];    
               }
               $giveaskService = $this->getServiceLocator()->get('GiveAskService');
               $response = $giveaskService->getApiGiveAskDetails($limit,$offset,$str,$userId);
               return new JsonModel($response);
          }else if(isset($params['type']) && $params['type']!=''){
               
               $plugin = $this->HasParams();
               if($plugin->checkParams($params,array('user-id','date','industry-id','give-ask'))){
               $giveaskService = $this->getServiceLocator()->get('GiveAskService');
               $response = $giveaskService->addGiveAskDetailsApi($params);
               return new JsonModel($response);
               }else{
               $response['status']='fail';
               $response['message']='Some required parameters are missing. Please try again.';
               return new JsonModel($response);
              }
          }
     }
     
     public function getList($params)
     {
          $params=$this->getRequest()->getQuery();
          if($params['give-ask-id']!=''){
              $giveAskId =$params['give-ask-id'];
          $giveaskService = $this->getServiceLocator()->get('GiveAskService');
          $response = $giveaskService->deleteGiveAskViaApi($giveAskId);
          return new JsonModel($response);
          }
     }
}