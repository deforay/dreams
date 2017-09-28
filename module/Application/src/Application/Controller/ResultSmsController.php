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

class ResultSmsController extends AbstractActionController{
    public function indexAction(){
        $countryId=base64_decode($this->params()->fromRoute('countryId'));
        if(trim($countryId)!= ''){
            return new ViewModel();
        }else{
            return $this->redirect()->toRoute('home');
        }
    }
  
}
