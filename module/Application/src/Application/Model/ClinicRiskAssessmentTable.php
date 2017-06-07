<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;


class ClinicRiskAssessmentTable extends AbstractTableGateway {

    protected $table = 'clinic_risk_assessment';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addRiskAssessmentDetails($params){
        $loginContainer = new Container('user');
        $lastInsertedId = 0;
        if(isset($params['surveillanceId']) && trim($params['surveillanceId'])!= ''){
            $dbAdapter = $this->adapter;
	    $occupationTypeDb = new OccupationTypeTable($dbAdapter);
            $common = new CommonService();
            if(isset($params['chosenCountry']) && trim($params['chosenCountry'])!=''){
		$country = base64_decode($params['chosenCountry']);
	    }else if(isset($params['country']) && trim($params['country'])!=''){
		$country = base64_decode($params['country']);
	    }else{
                return $lastInsertedId;
            }
            $interviewDate = NULL;
            if(isset($params['interviewDate']) && trim($params['interviewDate'])!= ''){
                $interviewDate = $common->dateFormat($params['interviewDate']);
            }
            $occupation = NULL;
            if(isset($params['occupation']) && trim($params['occupation'])!= ''){
                if($params['occupation'] == 'other'){
                    $occupationTypeDb->insert(array('occupation'=>$params['occupationNew']));
                    $occupation = $occupationTypeDb->lastInsertValue;
                }else{
                   $occupation = base64_decode($params['occupation']);
                }
            }
            $ageAtFirstMarriage = NULL;
            if(isset($params['ageAtFirstMarriageInYears']) && trim($params['ageAtFirstMarriageInYears'])!= ''){
                $ageAtFirstMarriage = $params['ageAtFirstMarriageInYears'];
            }else if(isset($params['ageAtFirstMarriage']) && trim($params['ageAtFirstMarriage'])!= ''){
               $ageAtFirstMarriage = $params['ageAtFirstMarriage']; 
            }
            $noOfSexualPartners = NULL;
            if(isset($params['noOfSexualPartnersInNumbers']) && trim($params['noOfSexualPartnersInNumbers'])!= ''){
                $noOfSexualPartners = $params['noOfSexualPartnersInNumbers'];
            }else if(isset($params['noOfSexualPartners']) && trim($params['noOfSexualPartners'])!= ''){
               $noOfSexualPartners = $params['noOfSexualPartners']; 
            }
            $noOfSexualPartnersInLastSixMonths = NULL;
            if(isset($params['noOfSexualPartnersInLastSixMonthsInNumbers']) && trim($params['noOfSexualPartnersInLastSixMonthsInNumbers'])!= ''){
                $noOfSexualPartnersInLastSixMonths = $params['noOfSexualPartnersInLastSixMonthsInNumbers'];
            }else if(isset($params['noOfSexualPartnersInLastSixMonths']) && trim($params['noOfSexualPartnersInLastSixMonths'])!= ''){
               $noOfSexualPartnersInLastSixMonths = $params['noOfSexualPartnersInLastSixMonths']; 
            }
            $ageOfMainSexualPartnersInLastBirthday = NULL;
            if(isset($params['ageOfMainSexualPartnerAtLastBirthdayInYears']) && trim($params['ageOfMainSexualPartnerAtLastBirthdayInYears'])!= ''){
                $ageOfMainSexualPartnersInLastBirthday = $params['ageOfMainSexualPartnerAtLastBirthdayInYears'];
            }else if(isset($params['ageOfMainSexualPartnerAtLastBirthday']) && trim($params['ageOfMainSexualPartnerAtLastBirthday'])!= ''){
               $ageOfMainSexualPartnersInLastBirthday = $params['ageOfMainSexualPartnerAtLastBirthday']; 
            }
            $noOfDaysHadDrinkInLastSixMonths = NULL;
            if(isset($params['noOfDaysHadDrinkInLastSixMonthsInDays']) && trim($params['noOfDaysHadDrinkInLastSixMonthsInDays'])!= ''){
                $noOfDaysHadDrinkInLastSixMonths = $params['noOfDaysHadDrinkInLastSixMonthsInDays'];
            }else if(isset($params['noOfDaysHadDrinkInLastSixMonths']) && trim($params['noOfDaysHadDrinkInLastSixMonths'])!= ''){
               $noOfDaysHadDrinkInLastSixMonths = $params['noOfDaysHadDrinkInLastSixMonths']; 
            }
            $recreationalDrugs = NULL;
            if(isset($params['hadRecreationalDrugsInLastSixMonths']) && trim($params['hadRecreationalDrugsInLastSixMonths'])== 'yes'){
                $recreationalDrugs = $params['recreationalDrugs'];
            }
            $data = array(
                    'lab'=>base64_decode($params['lab']),
                    'study_id'=>$params['studyId'],
                    'interviewer_name'=>$params['interviewerName'],
                    'anc_patient_id'=>$params['ancPatientId'],
                    'interview_date'=>$interviewDate,
                    'occupation'=>$occupation,
                    'degree'=>(isset($params['degree']) && trim($params['degree'])!= '')?$params['degree']:NULL,
                    'are_married'=>(isset($params['areMarried']) && trim($params['areMarried'])!= '')?$params['areMarried']:NULL,
                    'age_at_first_marriage'=>$ageAtFirstMarriage,
                    'have_ever_been_widowed'=>(isset($params['haveEverBeenWidowed']) && trim($params['haveEverBeenWidowed'])!= '')?$params['haveEverBeenWidowed']:NULL,
                    'current_marital_status'=>(isset($params['currentMaritalStatus']) && trim($params['currentMaritalStatus'])!= '')?$params['currentMaritalStatus']:NULL,
                    'time_of_last_HIV_test'=>(isset($params['timeOfLastHIVTest']) && trim($params['timeOfLastHIVTest'])!= '')?$params['timeOfLastHIVTest']:NULL,
                    'last_HIV_test_status'=>(isset($params['lastHIVTestStatus']) && trim($params['lastHIVTestStatus'])!= '')?$params['lastHIVTestStatus']:NULL,
                    'partner_HIV_test_status'=>(isset($params['partnerHIVTestStatus']) && trim($params['partnerHIVTestStatus'])!= '')?$params['partnerHIVTestStatus']:NULL,
                    'age_at_very_first_sex'=>(isset($params['ageAtVeryFirstSex']) && trim($params['ageAtVeryFirstSex'])!= '')?$params['ageAtVeryFirstSex']:NULL,
                    'reason_for_very_first_sex'=>(isset($params['reasonForVeryFirstSex']) && trim($params['reasonForVeryFirstSex'])!= '')?$params['reasonForVeryFirstSex']:NULL,
                    'no_of_sexual_partners'=>$noOfSexualPartners,
                    'no_of_sexual_partners_in_last_six_months'=>$noOfSexualPartnersInLastSixMonths,
                    'age_of_main_sexual_partner_at_last_birthday'=>$ageOfMainSexualPartnersInLastBirthday,
                    'age_diff_of_main_sexual_partner'=>(isset($params['ageDiffOfMainSexualPartner']) && trim($params['ageDiffOfMainSexualPartner'])!= '')?$params['ageDiffOfMainSexualPartner']:NULL,
                    'is_partner_circumcised'=>(isset($params['isPartnerCircumcised']) && trim($params['isPartnerCircumcised'])!= '')?$params['isPartnerCircumcised']:NULL,
                    'last_time_of_receiving_money_for_sex'=>(isset($params['lastTimeOfReceivingMoneyForSex']) && trim($params['lastTimeOfReceivingMoneyForSex'])!= '')?$params['lastTimeOfReceivingMoneyForSex']:NULL,
                    'no_of_times_been_pregnant'=>(isset($params['noOfTimesBeenPregnant']) && trim($params['noOfTimesBeenPregnant'])!= '')?$params['noOfTimesBeenPregnant']:NULL,
                    'no_of_times_condom_used_before_pregnancy'=>(isset($params['noOfTimesCondomUsedBeforePregnancy']) && trim($params['noOfTimesCondomUsedBeforePregnancy'])!= '')?$params['noOfTimesCondomUsedBeforePregnancy']:NULL,
                    'no_of_times_condom_used_after_pregnancy'=>(isset($params['noOfTimesCondomUsedAfterPregnancy']) && trim($params['noOfTimesCondomUsedAfterPregnancy'])!= '')?$params['noOfTimesCondomUsedAfterPregnancy']:NULL,
                    'have_pain_in_lower_abdomen'=>(isset($params['havePainInLowerAbdomen']) && trim($params['havePainInLowerAbdomen'])!= '')?$params['havePainInLowerAbdomen']:NULL,
                    'have_treated_for_lower_abdomen_pain'=>(isset($params['haveTreatedForLowerAbdomenPain']) && trim($params['haveTreatedForLowerAbdomenPain'])!= '')?$params['haveTreatedForLowerAbdomenPain']:NULL,
                    'have_treated_for_syphilis'=>(isset($params['haveTreatedForSyphilis']) && trim($params['haveTreatedForSyphilis'])!= '')?$params['haveTreatedForSyphilis']:NULL,
                    'no_of_days_had_drink_in_last_six_months'=>$noOfDaysHadDrinkInLastSixMonths,
                    'do_have_more_drinks_on_one_occasion'=>(isset($params['doHaveMoreDrinksOnOneOccasion']) && trim($params['doHaveMoreDrinksOnOneOccasion'])!= '')?$params['doHaveMoreDrinksOnOneOccasion']:NULL,
                    'have_tried_recreational_drugs'=>(isset($params['haveTriedRecreationalDrugs']) && trim($params['haveTriedRecreationalDrugs'])!= '')?$params['haveTriedRecreationalDrugs']:NULL,
                    'had_recreational_drugs_in_last_six_months'=>(isset($params['hadRecreationalDrugsInLastSixMonths']) && trim($params['hadRecreationalDrugsInLastSixMonths'])!= '')?$params['hadRecreationalDrugsInLastSixMonths']:NULL,
                    'recreational_drugs'=>$recreationalDrugs,
                    'country'=>$country,
                    'added_on'=>$common->getDateTime(),
                    'added_by'=>$loginContainer->userId
                );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
      return $lastInsertedId;
    }
    
    public function fetchAllRiskAssessment($parameters){
        $loginContainer = new Container('user');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	if($parameters['countryId']== ''){
	    $aColumns = array('f.facility_name','f.facility_code','r_a.study_id','r_a.interviewer_name','r_a.anc_patient_id',"DATE_FORMAT(r_a.interview_date,'%d-%b-%Y')","DATE_FORMAT(r_a.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name','c.country_name');
	    $orderColumns = array('f.facility_name','f.facility_code','r_a.study_id','r_a.interviewer_name','r_a.anc_patient_id','r_a.interview_date','r_a.added_on','u.user_name','c.country_name');
	}else{
	    $aColumns = array('f.facility_name','f.facility_code','r_a.study_id','r_a.interviewer_name','r_a.anc_patient_id',"DATE_FORMAT(r_a.interview_date,'%d-%b-%Y')","DATE_FORMAT(r_a.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name');
	    $orderColumns = array('f.facility_name','f.facility_code','r_a.study_id','r_a.interviewer_name','r_a.anc_patient_id','r_a.interview_date','r_a.added_on','u.user_name','c.country_name');
	}

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
       $mappedLab = array();
       $uMapQuery = $sql->select()->from(array('l_map' => 'user_laboratory_map'))
                                  ->where(array('l_map.user_id'=>$loginContainer->userId));
       $uMapQueryStr = $sql->getSqlStringForSqlObject($uMapQuery);
       $uMapResult = $dbAdapter->query($uMapQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
       //Get all mapped lab
        foreach($uMapResult as $lab){
	   $mappedLab[] = $lab['laboratory_id'];
        }
        $sQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                     ->join(array('f' => 'facility'), "f.facility_id=r_a.lab",array('facility_name','facility_code'))
                     ->join(array('u' => 'user'), "u.user_id=r_a.added_by",array('user_name'))
                     ->join(array('c' => 'country'), "c.country_id=r_a.country",array('country_name'));
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('r_a.country'=>trim($parameters['countryId'])));
	}
	if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where('r_a.lab IN ("' . implode('", "', $mappedLab) . '")');
	}
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
	$tQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                      ->join(array('f' => 'facility'), "f.facility_id=r_a.lab",array('facility_name','facility_code'))
                      ->join(array('u' => 'user'), "u.user_id=r_a.added_by",array('user_name'))
                      ->join(array('c' => 'country'), "c.country_id=r_a.country",array('country_name'));
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $tQuery = $tQuery->where(array('r_a.country'=>trim($parameters['countryId'])));
	}
	if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $tQuery = $tQuery->where('r_a.lab IN ("' . implode('", "', $mappedLab) . '")');
	}
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
	    $interviewDate = '';
	    if(isset($aRow['interview_date']) && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
		$interviewDate = $common->humanDateFormat($aRow['interview_date']);
	    }
	    $addedDate = explode(" ",$aRow['added_on']);
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['study_id'];
	    $row[] = ucwords($aRow['interviewer_name']);
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $interviewDate;
	    $row[] = $common->humanDateFormat($addedDate[0])." ".$addedDate[1];
	    $row[] = ucwords($aRow['user_name']);
	    if($parameters['countryId']== ''){
	       $row[] = ucwords($aRow['country_name']);
	    }
	    if($loginContainer->hasViewOnlyAccess =='no'){
	       $row[] = '<a href="/clinic/risk-assessment/edit/' . base64_encode($aRow['assessment_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>';
	    }
	    $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchRiskAssessment($riskAssessmentId){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                                   ->join(array('f' => 'facility'), "f.facility_id=r_a.lab",array())
                                   ->where(array('r_a.assessment_id'=>$riskAssessmentId));
	$riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($riskAssessmentQuery);
      return $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function updateRiskAssessmentDetails($params){
        $loginContainer = new Container('user');
        $lastInsertedId = 0;
        if(isset($params['surveillanceId']) && trim($params['surveillanceId'])!= ''){
            $dbAdapter = $this->adapter;
	    $occupationTypeDb = new OccupationTypeTable($dbAdapter);
            $common = new CommonService();
            $lastInsertedId = base64_decode($params['riskAssessmentId']);
            $interviewDate = NULL;
            if(isset($params['interviewDate']) && trim($params['interviewDate'])!= ''){
                $interviewDate = $common->dateFormat($params['interviewDate']);
            }
            $occupation = NULL;
            if(isset($params['occupation']) && trim($params['occupation'])!= ''){
                if($params['occupation'] == 'other'){
                    $occupationTypeDb->insert(array('occupation'=>$params['occupationNew']));
                    $occupation = $occupationTypeDb->lastInsertValue;
                }else{
                   $occupation = base64_decode($params['occupation']);
                }
            }
            $ageAtFirstMarriage = NULL;
            if(isset($params['ageAtFirstMarriageInYears']) && trim($params['ageAtFirstMarriageInYears'])!= ''){
                $ageAtFirstMarriage = $params['ageAtFirstMarriageInYears'];
            }else if(isset($params['ageAtFirstMarriage']) && trim($params['ageAtFirstMarriage'])!= ''){
               $ageAtFirstMarriage = $params['ageAtFirstMarriage']; 
            }
            $noOfSexualPartners = NULL;
            if(isset($params['noOfSexualPartnersInNumbers']) && trim($params['noOfSexualPartnersInNumbers'])!= ''){
                $noOfSexualPartners = $params['noOfSexualPartnersInNumbers'];
            }else if(isset($params['noOfSexualPartners']) && trim($params['noOfSexualPartners'])!= ''){
               $noOfSexualPartners = $params['noOfSexualPartners']; 
            }
            $noOfSexualPartnersInLastSixMonths = NULL;
            if(isset($params['noOfSexualPartnersInLastSixMonthsInNumbers']) && trim($params['noOfSexualPartnersInLastSixMonthsInNumbers'])!= ''){
                $noOfSexualPartnersInLastSixMonths = $params['noOfSexualPartnersInLastSixMonthsInNumbers'];
            }else if(isset($params['noOfSexualPartnersInLastSixMonths']) && trim($params['noOfSexualPartnersInLastSixMonths'])!= ''){
               $noOfSexualPartnersInLastSixMonths = $params['noOfSexualPartnersInLastSixMonths']; 
            }
            $ageOfMainSexualPartnersInLastBirthday = NULL;
            if(isset($params['ageOfMainSexualPartnerAtLastBirthdayInYears']) && trim($params['ageOfMainSexualPartnerAtLastBirthdayInYears'])!= ''){
                $ageOfMainSexualPartnersInLastBirthday = $params['ageOfMainSexualPartnerAtLastBirthdayInYears'];
            }else if(isset($params['ageOfMainSexualPartnerAtLastBirthday']) && trim($params['ageOfMainSexualPartnerAtLastBirthday'])!= ''){
               $ageOfMainSexualPartnersInLastBirthday = $params['ageOfMainSexualPartnerAtLastBirthday']; 
            }
            $noOfDaysHadDrinkInLastSixMonths = NULL;
            if(isset($params['noOfDaysHadDrinkInLastSixMonthsInDays']) && trim($params['noOfDaysHadDrinkInLastSixMonthsInDays'])!= ''){
                $noOfDaysHadDrinkInLastSixMonths = $params['noOfDaysHadDrinkInLastSixMonthsInDays'];
            }else if(isset($params['noOfDaysHadDrinkInLastSixMonths']) && trim($params['noOfDaysHadDrinkInLastSixMonths'])!= ''){
               $noOfDaysHadDrinkInLastSixMonths = $params['noOfDaysHadDrinkInLastSixMonths']; 
            }
            $recreationalDrugs = NULL;
            if(isset($params['hadRecreationalDrugsInLastSixMonths']) && trim($params['hadRecreationalDrugsInLastSixMonths'])== 'yes'){
                $recreationalDrugs = $params['recreationalDrugs'];
            }
            $data = array(
                    'lab'=>base64_decode($params['lab']),
                    'study_id'=>$params['studyId'],
                    'interviewer_name'=>$params['interviewerName'],
                    'anc_patient_id'=>$params['ancPatientId'],
                    'interview_date'=>$interviewDate,
                    'occupation'=>$occupation,
                    'degree'=>(isset($params['degree']) && trim($params['degree'])!= '')?$params['degree']:NULL,
                    'are_married'=>(isset($params['areMarried']) && trim($params['areMarried'])!= '')?$params['areMarried']:NULL,
                    'age_at_first_marriage'=>$ageAtFirstMarriage,
                    'have_ever_been_widowed'=>(isset($params['haveEverBeenWidowed']) && trim($params['haveEverBeenWidowed'])!= '')?$params['haveEverBeenWidowed']:NULL,
                    'current_marital_status'=>(isset($params['currentMaritalStatus']) && trim($params['currentMaritalStatus'])!= '')?$params['currentMaritalStatus']:NULL,
                    'time_of_last_HIV_test'=>(isset($params['timeOfLastHIVTest']) && trim($params['timeOfLastHIVTest'])!= '')?$params['timeOfLastHIVTest']:NULL,
                    'last_HIV_test_status'=>(isset($params['lastHIVTestStatus']) && trim($params['lastHIVTestStatus'])!= '')?$params['lastHIVTestStatus']:NULL,
                    'partner_HIV_test_status'=>(isset($params['partnerHIVTestStatus']) && trim($params['partnerHIVTestStatus'])!= '')?$params['partnerHIVTestStatus']:NULL,
                    'age_at_very_first_sex'=>(isset($params['ageAtVeryFirstSex']) && trim($params['ageAtVeryFirstSex'])!= '')?$params['ageAtVeryFirstSex']:NULL,
                    'reason_for_very_first_sex'=>(isset($params['reasonForVeryFirstSex']) && trim($params['reasonForVeryFirstSex'])!= '')?$params['reasonForVeryFirstSex']:NULL,
                    'no_of_sexual_partners'=>$noOfSexualPartners,
                    'no_of_sexual_partners_in_last_six_months'=>$noOfSexualPartnersInLastSixMonths,
                    'age_of_main_sexual_partner_at_last_birthday'=>$ageOfMainSexualPartnersInLastBirthday,
                    'age_diff_of_main_sexual_partner'=>(isset($params['ageDiffOfMainSexualPartner']) && trim($params['ageDiffOfMainSexualPartner'])!= '')?$params['ageDiffOfMainSexualPartner']:NULL,
                    'is_partner_circumcised'=>(isset($params['isPartnerCircumcised']) && trim($params['isPartnerCircumcised'])!= '')?$params['isPartnerCircumcised']:NULL,
                    'last_time_of_receiving_money_for_sex'=>(isset($params['lastTimeOfReceivingMoneyForSex']) && trim($params['lastTimeOfReceivingMoneyForSex'])!= '')?$params['lastTimeOfReceivingMoneyForSex']:NULL,
                    'no_of_times_been_pregnant'=>(isset($params['noOfTimesBeenPregnant']) && trim($params['noOfTimesBeenPregnant'])!= '')?$params['noOfTimesBeenPregnant']:NULL,
                    'no_of_times_condom_used_before_pregnancy'=>(isset($params['noOfTimesCondomUsedBeforePregnancy']) && trim($params['noOfTimesCondomUsedBeforePregnancy'])!= '')?$params['noOfTimesCondomUsedBeforePregnancy']:NULL,
                    'no_of_times_condom_used_after_pregnancy'=>(isset($params['noOfTimesCondomUsedAfterPregnancy']) && trim($params['noOfTimesCondomUsedAfterPregnancy'])!= '')?$params['noOfTimesCondomUsedAfterPregnancy']:NULL,
                    'have_pain_in_lower_abdomen'=>(isset($params['havePainInLowerAbdomen']) && trim($params['havePainInLowerAbdomen'])!= '')?$params['havePainInLowerAbdomen']:NULL,
                    'have_treated_for_lower_abdomen_pain'=>(isset($params['haveTreatedForLowerAbdomenPain']) && trim($params['haveTreatedForLowerAbdomenPain'])!= '')?$params['haveTreatedForLowerAbdomenPain']:NULL,
                    'have_treated_for_syphilis'=>(isset($params['haveTreatedForSyphilis']) && trim($params['haveTreatedForSyphilis'])!= '')?$params['haveTreatedForSyphilis']:NULL,
                    'no_of_days_had_drink_in_last_six_months'=>$noOfDaysHadDrinkInLastSixMonths,
                    'do_have_more_drinks_on_one_occasion'=>(isset($params['doHaveMoreDrinksOnOneOccasion']) && trim($params['doHaveMoreDrinksOnOneOccasion'])!= '')?$params['doHaveMoreDrinksOnOneOccasion']:NULL,
                    'have_tried_recreational_drugs'=>(isset($params['haveTriedRecreationalDrugs']) && trim($params['haveTriedRecreationalDrugs'])!= '')?$params['haveTriedRecreationalDrugs']:NULL,
                    'had_recreational_drugs_in_last_six_months'=>(isset($params['hadRecreationalDrugsInLastSixMonths']) && trim($params['hadRecreationalDrugsInLastSixMonths'])!= '')?$params['hadRecreationalDrugsInLastSixMonths']:NULL,
                    'recreational_drugs'=>$recreationalDrugs,
                    'updated_on'=>$common->getDateTime(),
                    'updated_by'=>$loginContainer->userId
                );
            $this->update($data,array('assessment_id'=>$lastInsertedId));
        }
      return $lastInsertedId; 
    }
}