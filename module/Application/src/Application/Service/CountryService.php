<?php
namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;


class CountryService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function addCountry($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
           $countryDb = $this->sm->get('CountryTable');
           $result = $countryDb->addCountryDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'Country added successfully.';
           }else{
             $alertContainer->msg = 'OOPS..';
           }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }
    
    public function getAllCountries($parameters){
        $countryDb = $this->sm->get('CountryTable');
        return $countryDb->fetchAllCountries($parameters);
    }
    
    public function getCountry($countryId){
        $countryDb = $this->sm->get('CountryTable');
        return $countryDb->fetchCountry($countryId);
    }
    
    public function updateCountry($params){
        $alertContainer = new Container('alert');
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
           $countryDb = $this->sm->get('CountryTable');
           $result = $countryDb->updateCountryDetails($params);
           if($result>0){
            $adapter->commit();
               $alertContainer->msg = 'Country updated successfully.';
           }else{
             $alertContainer->msg = 'OOPS..';
           }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }
    
    public function getActiveCountries($from,$countryId){
        $countryDb = $this->sm->get('CountryTable');
        return $countryDb->fetchActiveCountries($from,$countryId);
    }
}