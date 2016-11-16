<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;


class AncSiteService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function addAncSite($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $ancSiteDb = $this->sm->get('AncSiteTable');
            $result = $ancSiteDb->addAncSiteDetails($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->msg = 'ANC site added successfully.';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
    
    public function getAllAncSites($parameters){
        $ancSiteDb = $this->sm->get('AncSiteTable');
        return $ancSiteDb->fetchAllAncSites($parameters);
    }
    
    public function getAncSite($ancSiteId){
        $ancSiteDb = $this->sm->get('AncSiteTable');
        return $ancSiteDb->fetchAncSite($ancSiteId);
    }
    
    public function updateAncSite($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $ancSiteDb = $this->sm->get('AncSiteTable');
            $result = $ancSiteDb->updateAncSiteDetails($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->msg = 'ANC site updated successfully.';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}