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

class StudyFilesController extends AbstractActionController{
    public function indexAction(){
        $request = $this->getRequest();
        if($request->isPost()){
            $parameters = $request->getPost();
            $commonService = $this->getServiceLocator()->get('CommonService');
            $result = $commonService->getStudyFiles($parameters);
           return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function uploadAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService = $this->getServiceLocator()->get('CommonService');
            $commonService->uploadStudyFile($params);
           return $this->redirect()->toUrl('/study-files');
        }  
    }
  
}
