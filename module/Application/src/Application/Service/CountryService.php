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
             $alertContainer->msg = 'Error-Oops, something went wrong!!';
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
             $alertContainer->msg = 'Error-Oops, something went wrong!!';
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
    
    public function getChoosedCountryInfo($countryId){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $cQuery = $sql->select()->from(array('c' => 'country'))
                                ->columns(array('country_code'))
                                ->where(array('c.country_id'=>$countryId));
        $cQueryStr = $sql->getSqlStringForSqlObject($cQuery);
      return $dbAdapter->query($cQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function getProvincesByCountry($countryId){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $pQuery = $sql->select()->from(array('l_d' => 'location_details'))
                                ->where(array('l_d.parent_location'=>0,'l_d.country'=>$countryId));
        $pQueryStr = $sql->getSqlStringForSqlObject($pQuery);
      return $dbAdapter->query($pQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getDistrictsByProvince($provinceId){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $dQuery = $sql->select()->from(array('l_d' => 'location_details'))
                                ->where(array('l_d.parent_location'=>$provinceId));
        $dQueryStr = $sql->getSqlStringForSqlObject($dQuery);
      return $dbAdapter->query($dQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getDistrictsByCountry($countryId){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $dQuery = $sql->select()->from(array('l_d' => 'location_details'))
                                ->where('l_d.country = "'.$countryId.'" AND l_d.parent_location != 0');
        $dQueryStr = $sql->getSqlStringForSqlObject($dQuery);
      return $dbAdapter->query($dQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getDistrictsByProvinces($params){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $pQuery = $sql->select()->from(array('l_d' => 'location_details'));
        if(isset($params['provinces']) && trim($params['provinces'])!= ''){
            $provinceArray = explode(',',$params['provinces']);
            $provinces = array();
            for($i=0;$i<count($provinceArray);$i++){
                $provinces[] = base64_decode($provinceArray[$i]);
            }
            $pQuery = $pQuery->where('l_d.parent_location IN('.implode(',',$provinces).')');
        }else{
           $pQuery = $pQuery->where('l_d.parent_location != 0');
        }
        $pQueryStr = $sql->getSqlStringForSqlObject($pQuery);
      return $dbAdapter->query($pQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}