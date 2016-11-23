<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class CountryTable extends AbstractTableGateway {

    protected $table = 'country';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addCountryDetails($params){
        $lastInsertedId = 0;
        if(isset($params['countryName']) && trim($params['countryName'])!= ''){
            $data=array('country_name'=>$params['countryName'],
                        'country_code'=>$params['countryCode'],
                        'status'=>'active'
                        );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
      return $lastInsertedId;
    }
    
    public function fetchAllCountries($parameters){
         /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */

       $aColumns = array('country_name','country_code','status');

       /*
        * Paging
        */
       $sLimit = "";
       if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
           $sOffset = $parameters['iDisplayStart'];
           $sLimit = $parameters['iDisplayLength'];
       }

       /*
        * Ordering
        */

       $sOrder = "";
       if (isset($parameters['iSortCol_0'])) {
           for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
               if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                   $sOrder .= $aColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
               }
           }
           $sOrder = substr_replace($sOrder, "", -1);
       }

       /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

       $sWhere = "";
       if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
           $searchArray = explode(" ", $parameters['sSearch']);
           $sWhereSub = "";
           foreach ($searchArray as $search) {
               if ($sWhereSub == "") {
                   $sWhereSub .= "(";
               } else {
                   $sWhereSub .= " AND (";
               }
               $colSize = count($aColumns);

               for ($i = 0; $i < $colSize; $i++) {
                   if ($i < $colSize - 1) {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                   } else {
                       $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                   }
               }
               $sWhereSub .= ")";
           }
           $sWhere .= $sWhereSub;
       }

       /* Individual column filtering */
       for ($i = 0; $i < count($aColumns); $i++) {
           if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
               if ($sWhere == "") {
                   $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               } else {
                   $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
               }
           }
       }

       /*
        * SQL queries
        * Get data to display
        */
       $dbAdapter = $this->adapter;
       $sql = new Sql($dbAdapter);
       $sQuery = $sql->select()->from('country');
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }

       if (isset($sLimit) && isset($sOffset)) {
           $sQuery->limit($sLimit);
           $sQuery->offset($sOffset);
       }

       $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
       //echo $sQueryStr;die;
       $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

       /* Data set length after filtering */
       $sQuery->reset('limit');
       $sQuery->reset('offset');
       $fQuery = $sql->getSqlStringForSqlObject($sQuery);
       $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
       $iFilteredTotal = count($aResultFilterTotal);

       /* Total data set length */
       $iTotal = $this->select()->count();
       $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
       );
       
       foreach ($rResult as $aRow) {
           $row = array();
           $row[] = ucwords($aRow['country_name']);
           $row[] = $aRow['country_code'];
           $row[] = ucwords($aRow['status']);
           $row[] = '<a href="/country/edit/' . base64_encode($aRow['country_id']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
           $output['aaData'][] = $row;
       }
       return $output;
    }
    
    public function fetchCountry($countryId){
        return $this->select(array('country_id'=>$countryId))->current();
    }
    
    public function updateCountryDetails($params){
        $countryId = 0;
        if(isset($params['countryName']) && trim($params['countryName'])!= ''){
            $countryId = base64_decode($params['countryId']);
            $data=array('country_name'=>$params['countryName'],
                        'country_code'=>$params['countryCode'],
                        'status'=>$params['countryStatus']
                        );
            $this->update($data,array('country_id'=>$countryId));
        }
      return $countryId;
    }
    
    public function fetchActiveCountries($from,$countryId){
        $loginContainer = new Container('user');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $countriesQuery = $sql->select()->from(array('c' => 'country'))
                              ->where(array('c.status'=>'active'))
                              ->order('c.country_name asc');
        if(trim($from)!= 'login'){
            if(trim($countryId)!='' && $countryId >0){
                  $countriesQuery = $countriesQuery->where(array('c.country_id'=>$countryId));
                }else{
                    if($loginContainer->roleCode!= 'CSC'){
                    $countriesQuery = $countriesQuery->where('c.country_id IN ("' . implode('", "', $loginContainer->country) . '")');
                    }
                }
        }
        $countriesQueryStr = $sql->getSqlStringForSqlObject($countriesQuery);
        return $dbAdapter->query($countriesQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}