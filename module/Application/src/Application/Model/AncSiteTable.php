<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class AncSiteTable extends AbstractTableGateway {

    protected $table = 'anc_site';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addAncSiteDetails($params){
        $lastInsertedId = 0;
	if(isset($params['ancSiteName']) && trim($params['ancSiteName'])!= ''){
	    $data = array(
		'anc_site_name' => $params['ancSiteName'],
		'anc_site_code' => $params['ancSiteCode'],
		'anc_site_type' => base64_decode($params['ancSiteType']),
		'email' => $params['email'],
		'contact_person' => $params['contactPerson'],
		'phone_number' => $params['mobile'],
		'country' => base64_decode($params['country']),
		'address' => $params['address'],
		'latitude' => $params['latitude'],
		'longitude' => $params['longitude'],
		'status' => 'active'
	    );
	    $this->insert($data);
	    $lastInsertedId = $this->lastInsertValue;
	}
	return $lastInsertedId;
    }
    
    public function fetchAllAncSites($parameters){
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $aColumns = array('anc.anc_site_name','anc.anc_site_code','f_typ.facility_type_name','anc.email','anc.contact_person','anc.phone_number','c.country_name','anc.status');
        $orderColumns = array('anc.anc_site_name','f_typ.facility_type_name','anc.email','anc.contact_person','anc.phone_number','c.country_name','anc.status');

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
                   $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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
       $sQuery = $sql->select()->from(array('anc' => 'anc_site'))
		     ->join(array('f_typ' => 'facility_type'), "f_typ.facility_type_id=anc.anc_site_type",array('facility_type_name'))
		     ->join(array('c' => 'country'), "c.country_id=anc.country",array('country_name'));
	
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
	$tQuery = $sql->select()->from(array('anc' => 'anc_site'))
		      ->join(array('f_typ' => 'facility_type'), "f_typ.facility_type_id=anc.anc_site_type",array('facility_type_name'))
		      ->join(array('c' => 'country'), "c.country_id=anc.country",array('country_name'));

	$tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
	$tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
	$iTotal = count($tResult);
	$output = array(
		   "sEcho" => intval($parameters['sEcho']),
		   "iTotalRecords" => $iTotal,
		   "iTotalDisplayRecords" => $iFilteredTotal,
		   "aaData" => array()
	);
	foreach ($rResult as $aRow) {
            $row = array();
            $row[] = ucwords($aRow['anc_site_name'])." - ".$aRow['anc_site_code'];
            $row[] = ucwords($aRow['facility_type_name']);
            $row[] = $aRow['email'];
            $row[] = ucwords($aRow['contact_person']);
            $row[] = $aRow['phone_number'];
            $row[] = ucwords($aRow['country_name']);
            $row[] = ucwords($aRow['status']);
            $row[] = '<a href="/anc-site/edit/' . base64_encode($aRow['anc_site_id']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
            $output['aaData'][] = $row;
	}
	return $output;
    }
    
    public function fetchAncSite($ancSiteId){
        return $this->select(array('anc_site_id'=>$ancSiteId))->current();
    }
    
    public function updateAncSiteDetails($params){
        $ancSiteId = 0;
	if(isset($params['ancSiteName']) && trim($params['ancSiteName'])!= ''){
            $ancSiteId = base64_decode($params['ancSiteId']);
	    $data = array(
		'anc_site_name' => $params['ancSiteName'],
		'anc_site_code' => $params['ancSiteCode'],
		'anc_site_type' => base64_decode($params['ancSiteType']),
		'email' => $params['email'],
		'contact_person' => $params['contactPerson'],
		'phone_number' => $params['mobile'],
		'country' => base64_decode($params['country']),
		'address' => $params['address'],
		'latitude' => $params['latitude'],
		'longitude' => $params['longitude'],
		'status' => $params['ancSiteStatus']
	    );
	    $this->update($data,array('anc_site_id'=>$ancSiteId));
	}
	return $ancSiteId;
    }
}