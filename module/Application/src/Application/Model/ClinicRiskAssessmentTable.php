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
        if(isset($params['patientBarcodeId']) && trim($params['patientBarcodeId'])!= ''){
	    $common = new CommonService();
            $dbAdapter = $this->adapter;
	    $occupationTypeDb = new OccupationTypeTable($dbAdapter);
            if(isset($params['chosenCountry']) && trim($params['chosenCountry'])!=''){
		$country = base64_decode($params['chosenCountry']);
	    }else if(isset($params['country']) && trim($params['country'])!=''){
		$country = base64_decode($params['country']);
	    }else{
                return false;
            }
            $interviewDate = NULL;
            if(isset($params['interviewDate']) && trim($params['interviewDate'])!= ''){
                $interviewDate = $common->dateFormat($params['interviewDate']);
            }
            $occupation = NULL;
            if(isset($params['occupation']) && trim($params['occupation'])!= ''){
                if($params['occupation'] == 'other'){
		    if(trim($params['occupationNew'])!= ''){
                      $occupationTypeDb->insert(array('occupation'=>$params['occupationNew']));
                      $occupation = $occupationTypeDb->lastInsertValue;
		    }
                }else{
                   $occupation = base64_decode($params['occupation']);
                }
            }
	    if(isset($params['everBeenMarried']) && trim($params['everBeenMarried']) == 'no'){
	      $params['ageAtFirstMarriageInYears'] = '';
	      $params['ageAtFirstMarriage'] = 'not applicable';
	      $params['everBeenWidowed'] = 'not applicable';
	      $params['currentMaritalStatus'] = 'not applicable';
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
            $noOfDaysInLastSixMonths = NULL;
            if(isset($params['hasPatientHadDrinkWithAlcoholInLastSixMonthsInDays']) && trim($params['hasPatientHadDrinkWithAlcoholInLastSixMonthsInDays'])!= ''){
                $noOfDaysInLastSixMonths = $params['hasPatientHadDrinkWithAlcoholInLastSixMonthsInDays'];
            }else if(isset($params['hasPatientHadDrinkWithAlcoholInLastSixMonths']) && trim($params['hasPatientHadDrinkWithAlcoholInLastSixMonths'])!= ''){
               $noOfDaysInLastSixMonths = $params['hasPatientHadDrinkWithAlcoholInLastSixMonths']; 
            }
            $recreationalDrugs = NULL;
            if(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths'])== 'yes'){
                $recreationalDrugs = $params['recreationalDrugs'];
            }
	    $patientHurtBy = NULL;
	    if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear'])!= ''){
		if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear'])== 'yes'){
		    $patientHurtBy = array(
					    'has_patient_hurt_by'=>$params['hasPatientEverBeenHurtBySomeoneWithinLastYear'],
					    'patient_hurt_by'=>(isset($params['patientHurtBySomeoneWithinLastYear']) && trim($params['patientHurtBySomeoneWithinLastYear'])!= '')?$params['patientHurtBySomeoneWithinLastYear']:'',
					    'no_of_times'=>$params['patientHurtBySomeoneWithinLastYearInNoofTimes']
					);
		}else{
		    $patientHurtBy = array('has_patient_hurt_by'=>$params['hasPatientEverBeenHurtBySomeoneWithinLastYear'],'patient_hurt_by'=>'','no_of_times'=>'');
		}
	    }
	    $patientHurtBySomeoneDuringPregnancy = NULL;
	    if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'])!= ''){
		if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'])== 'yes'){
		    $patientHurtBySomeoneDuringPregnancy = array(
					    'has_patient_hurt_by_someone_during_pregnancy'=>$params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'],
					    'patient_hurt_by_someone_during_pregnancy'=>(isset($params['patientHurtBySomeoneDuringPregnancy']) && trim($params['patientHurtBySomeoneDuringPregnancy'])!= '')?$params['patientHurtBySomeoneDuringPregnancy']:''
					);
		}else{
		    $patientHurtBySomeoneDuringPregnancy = array('has_patient_hurt_by_someone_during_pregnancy'=>$params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'],'patient_hurt_by_someone_during_pregnancy'=>'');
		}
	    }
	    $patientForcedForSex = NULL;
	    if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear'])!= ''){
		if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear'])== 'yes'){
		    $patientForcedForSex = array(
					    'has_patient_forced_for_sex'=>$params['hasPatientEverBeenForcedForSexWithinLastYear'],
					    'patient_forced_by'=>(isset($params['patientForcedForSexWithinLastYear']) && trim($params['patientForcedForSexWithinLastYear'])!= '')?$params['patientForcedForSexWithinLastYear']:'',
					    'no_of_times'=>$params['patientForcedForSexWithinLastYearInNoofTimes']
					);
		}else{
		    $patientForcedForSex = array('has_patient_forced_for_sex'=>$params['hasPatientEverBeenForcedForSexWithinLastYear'],'patient_forced_by'=>'','no_of_times'=>'');
		}
	    }
            $data = array(
                    'lab'=>base64_decode($params['lab']),
                    'patient_barcode_id'=>$params['patientBarcodeId'],
                    'interviewer_name'=>$params['interviewerName'],
                    'anc_patient_id'=>$params['ancPatientId'],
                    'interview_date'=>$interviewDate,
		    'has_participant_received_dreams_services'=>(isset($params['hasParticipantReceivedDreamsServices']) && trim($params['hasParticipantReceivedDreamsServices'])!= '')?$params['hasParticipantReceivedDreamsServices']:NULL,
                    'patient_occupation'=>$occupation,
                    'patient_degree'=>(isset($params['degree']) && trim($params['degree'])!= '')?$params['degree']:NULL,
                    'patient_ever_been_married'=>(isset($params['everBeenMarried']) && trim($params['everBeenMarried'])!= '')?$params['everBeenMarried']:NULL,
                    'age_at_first_marriage'=>$ageAtFirstMarriage,
                    'patient_ever_been_widowed'=>(isset($params['everBeenWidowed']) && trim($params['everBeenWidowed'])!= '')?$params['everBeenWidowed']:NULL,
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
                    'last_time_of_receiving_gift_for_sex'=>(isset($params['lastTimeOfReceivingGiftForSex']) && trim($params['lastTimeOfReceivingGiftForSex'])!= '')?$params['lastTimeOfReceivingGiftForSex']:NULL,
                    'no_of_times_been_pregnant'=>(isset($params['noOfTimesBeenPregnant']) && trim($params['noOfTimesBeenPregnant'])!= '')?$params['noOfTimesBeenPregnant']:NULL,
                    'no_of_times_condom_used_before_pregnancy'=>(isset($params['noOfTimesCondomUsedBeforePregnancy']) && trim($params['noOfTimesCondomUsedBeforePregnancy'])!= '')?$params['noOfTimesCondomUsedBeforePregnancy']:NULL,
                    'no_of_times_condom_used_after_pregnancy'=>(isset($params['noOfTimesCondomUsedAfterPregnancy']) && trim($params['noOfTimesCondomUsedAfterPregnancy'])!= '')?$params['noOfTimesCondomUsedAfterPregnancy']:NULL,
                    'has_patient_had_pain_in_lower_abdomen'=>(isset($params['hasPatientHadPainInLowerAbdomen']) && trim($params['hasPatientHadPainInLowerAbdomen'])!= '')?$params['hasPatientHadPainInLowerAbdomen']:NULL,
                    'has_patient_been_treated_for_lower_abdomen_pain'=>(isset($params['hasPatientBeenTreatedForLowerAbdomenPain']) && trim($params['hasPatientBeenTreatedForLowerAbdomenPain'])!= '')?$params['hasPatientBeenTreatedForLowerAbdomenPain']:NULL,
                    'has_patient_ever_been_treated_for_syphilis'=>(isset($params['hasPatientEverBeenTreatedForSyphilis']) && trim($params['hasPatientEverBeenTreatedForSyphilis'])!= '')?$params['hasPatientEverBeenTreatedForSyphilis']:NULL,
		    'has_patient_ever_received_vaccine_to_prevent_HPV'=>(isset($params['hasPatientEverReceivedVaccineToPreventHPV']) && trim($params['hasPatientEverReceivedVaccineToPreventHPV'])!= '')?$params['hasPatientEverReceivedVaccineToPreventHPV']:NULL,
                    'has_patient_had_drink_with_alcohol_in_last_six_months'=>$noOfDaysInLastSixMonths,
                    'has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'=>(isset($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']) && trim($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion'])!= '')?$params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']:NULL,
                    'has_patient_ever_tried_recreational_drugs'=>(isset($params['hasPatientEverTriedRecreationalDrugs']) && trim($params['hasPatientEverTriedRecreationalDrugs'])!= '')?$params['hasPatientEverTriedRecreationalDrugs']:NULL,
                    'has_patient_had_recreational_drugs_in_last_six_months'=>(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths'])!= '')?$params['hasPatientHadRecreationalDrugsInLastSixMonths']:NULL,
                    'recreational_drugs'=>$recreationalDrugs,
		    'has_patient_ever_been_abused_by_someone'=>(isset($params['hasPatientEverBeenAbusedBySomeone']) && trim($params['hasPatientEverBeenAbusedBySomeone'])!= '')?$params['hasPatientEverBeenAbusedBySomeone']:NULL,
		    'has_patient_ever_been_hurt_by_someone_within_last_year'=>($patientHurtBy !=NULL)?json_encode($patientHurtBy):'',
		    'has_patient_ever_been_hurt_by_someone_during_pregnancy'=>($patientHurtBySomeoneDuringPregnancy !=NULL)?json_encode($patientHurtBySomeoneDuringPregnancy):'',
		    'has_patient_ever_been_forced_for_sex_within_last_year'=>($patientForcedForSex !=NULL)?json_encode($patientForcedForSex):'',
		    'is_patient_afraid_of_anyone'=>(isset($params['isPatientAfraidOfAnyone']) && trim($params['isPatientAfraidOfAnyone'])!= '')?$params['isPatientAfraidOfAnyone']:NULL,
                    'comment'=>$params['comment'],
		    'country'=>$country,
                    'added_on'=>$common->getDateTime(),
                    'added_by'=>$loginContainer->userId
                );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
	    if($lastInsertedId >0){
		$ancRapidRecencyDb = new AncRapidRecencyTable($dbAdapter);
		$HIVDiagnosticVal = '';
		$recencyVal = '';
		if(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])== 'done'){
		    $HIVDiagnosticVal = (isset($params['rrrHIVDiagnostic']) && trim($params['rrrHIVDiagnostic'])!= '')?$params['rrrHIVDiagnostic']:NULL;
		    if($HIVDiagnosticVal!= 'negative'){
		       $recencyVal = (isset($params['rrrRecency']) && trim($params['rrrRecency'])!= '')?$params['rrrRecency']:NULL;
		    }
		}
		$rrData = array(
			    'assessment_id'=>$lastInsertedId,
			    'has_patient_had_rapid_recency_test'=>(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])!= '')?$params['hasPatientHadRapidRecencyTest']:NULL,
			    'HIV_diagnostic_line'=>$HIVDiagnosticVal,
			    'recency_line'=>$recencyVal
			);
	        $ancRapidRecencyDb->insert($rrData);
	    }
        }
      return $lastInsertedId;
    }
    
    public function fetchAllRiskAssessment($parameters){
        $loginContainer = new Container('user');
	$queryContainer = new Container('query');
        $common = new CommonService();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	if($parameters['countryId']== ''){
	    $aColumns = array('f.facility_name','f.facility_code','r_a.patient_barcode_id','r_a.interviewer_name','r_a.anc_patient_id',"DATE_FORMAT(r_a.interview_date,'%d-%b-%Y')","DATE_FORMAT(r_a.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name','c.country_name');
	    $orderColumns = array('f.facility_name','f.facility_code','r_a.patient_barcode_id','r_a.interviewer_name','r_a.anc_patient_id','r_a.interview_date','r_a.added_on','u.user_name','c.country_name');
	}else{
	    $aColumns = array('f.facility_name','f.facility_code','r_a.patient_barcode_id','r_a.interviewer_name','r_a.anc_patient_id',"DATE_FORMAT(r_a.interview_date,'%d-%b-%Y')","DATE_FORMAT(r_a.added_on,'%d-%b-%Y %H:%i:%s')",'u.user_name');
	    $orderColumns = array('f.facility_name','f.facility_code','r_a.patient_barcode_id','r_a.interviewer_name','r_a.anc_patient_id','r_a.interview_date','r_a.added_on','u.user_name');
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
       $start_date = '';
       $end_date = '';
       if(isset($parameters['interviewDate']) && trim($parameters['interviewDate'])!= ''){
	   $interview_date = explode("to", $parameters['interviewDate']);
	   if(isset($interview_date[0]) && trim($interview_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($interview_date[0]));
	   }if(isset($interview_date[1]) && trim($interview_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($interview_date[1]));
	   }
	}
	$labs = array();
	if(isset($parameters['lab']) && trim($parameters['lab'])!= ''){
	    $labs = explode(',',$parameters['lab']);
	}
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
                      ->join(array('da_c' => 'data_collection'), "da_c.patient_barcode_id=r_a.patient_barcode_id",array())
                      ->join(array('f' => 'facility'), "f.facility_id=r_a.lab",array('facility_name','facility_code'))
		      ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation'),'left')
                      ->join(array('u' => 'user'), "u.user_id=r_a.added_by",array('user_name'))
                      ->join(array('c' => 'country'), "c.country_id=r_a.country",array('country_name'));
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('da_c.country'=>trim($parameters['countryId']),'r_a.country'=>trim($parameters['countryId'])));
	}if(isset($parameters['date']) && trim($parameters['date'])!= ''){
	   $splitReportingMonthYear = explode("/",$parameters['date']);
	   $sQuery = $sQuery->where('MONTH(da_c.added_on) ="'.date('m', strtotime($splitReportingMonthYear[0])).'" AND YEAR(da_c.added_on) ="'.$splitReportingMonthYear[1].'"');
	}if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("r_a.interview_date >='" . $start_date ."'", "r_a.interview_date <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
            $sQuery = $sQuery->where(array("r_a.interview_date = '" . $start_date. "'"));
        }if(count($labs) >0){
	    $sQuery = $sQuery->where('r_a.lab IN ("' . implode('", "', $labs) . '")');
	}else if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
	    $sQuery = $sQuery->where('r_a.lab IN ("' . implode('", "', $mappedLab) . '")');
	}
       if (isset($sWhere) && $sWhere != "") {
           $sQuery->where($sWhere);
       }

       if (isset($sOrder) && $sOrder != "") {
           $sQuery->order($sOrder);
       }
       $queryContainer->riskAssessmentQuery = $sQuery;
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
                      ->join(array('da_c' => 'data_collection'), "da_c.patient_barcode_id=r_a.patient_barcode_id",array())
                      ->join(array('f' => 'facility'), "f.facility_id=r_a.lab",array('facility_name','facility_code'))
		      ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation'),'left')
                      ->join(array('u' => 'user'), "u.user_id=r_a.added_by",array('user_name'))
                      ->join(array('c' => 'country'), "c.country_id=r_a.country",array('country_name'));
        if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $tQuery = $tQuery->where(array('da_c.country'=>trim($parameters['countryId']),'r_a.country'=>trim($parameters['countryId'])));
	}if($loginContainer->roleCode== 'LS' || $loginContainer->roleCode== 'LDEO'){
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
	    $interviewDate = '';
	    $edit = '';
	    if(isset($aRow['interview_date']) && $aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
		$interviewDate = $common->humanDateFormat($aRow['interview_date']);
	    }
	    $addedDate = explode(" ",$aRow['added_on']);
	    if($loginContainer->hasViewOnlyAccess!='yes'){
		$edit = '<a href="/clinic/risk-assessment/edit/' . base64_encode($aRow['assessment_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-10" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp';
	    }
	    $view = '<a href="/clinic/risk-assessment/view/' . base64_encode($aRow['assessment_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-10" title="View"><i class="zmdi zmdi-eye"></i> View</a>&nbsp;&nbsp';
	    $pdf = '<a href="javascript:void(0);" onclick="printAssessmentForm(\''.base64_encode($aRow['assessment_id']).'\');" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-10" title="PDF"><i class="zmdi zmdi-collection-pdf"></i> PDF</a>';
	    $row = array();
	    $row[] = ucwords($aRow['facility_name']);
	    $row[] = $aRow['facility_code'];
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = ucwords($aRow['interviewer_name']);
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $interviewDate;
	    $row[] = $common->humanDateFormat($addedDate[0])." ".$addedDate[1];
	    $row[] = ucwords($aRow['user_name']);
	    if(trim($parameters['countryId']) == ''){
	       $row[] = ucwords($aRow['country_name']);
	    }
	    $row[] = $edit.$view.$pdf;
	    $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchRiskAssessment($riskAssessmentId){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                                   ->join(array('f' => 'facility'), "f.facility_id=r_a.lab",array('facility_name'))
                                   ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation'),'left')
				   ->join(array('anc_r_r' => 'anc_rapid_recency'), "anc_r_r.assessment_id=r_a.assessment_id",array('anc_rapid_recency_id','has_patient_had_rapid_recency_test','HIV_diagnostic_line','recency_line'),'left')
                                   ->where(array('r_a.assessment_id'=>$riskAssessmentId));
	$riskAssessmentQueryStr = $sql->getSqlStringForSqlObject($riskAssessmentQuery);
      return $dbAdapter->query($riskAssessmentQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function updateRiskAssessmentDetails($params){
        $loginContainer = new Container('user');
        $assessmentId = 0;
        if(isset($params['patientBarcodeId']) && trim($params['patientBarcodeId'])!= ''){
	    $assessmentId = base64_decode($params['riskAssessmentId']);
	    $common = new CommonService();
            $dbAdapter = $this->adapter;
	    $occupationTypeDb = new OccupationTypeTable($dbAdapter);
            $interviewDate = NULL;
            if(isset($params['interviewDate']) && trim($params['interviewDate'])!= ''){
                $interviewDate = $common->dateFormat($params['interviewDate']);
            }
            $occupation = NULL;
            if(isset($params['occupation']) && trim($params['occupation'])!= ''){
                if($params['occupation'] == 'other'){
		    if(trim($params['occupationNew'])!= ''){
                       $occupationTypeDb->insert(array('occupation'=>$params['occupationNew']));
                       $occupation = $occupationTypeDb->lastInsertValue;
		    }
                }else{
                   $occupation = base64_decode($params['occupation']);
                }
            }
	    if(isset($params['everBeenMarried']) && trim($params['everBeenMarried']) == 'no'){
	      $params['ageAtFirstMarriageInYears'] = '';
	      $params['ageAtFirstMarriage'] = 'not applicable';
	      $params['everBeenWidowed'] = 'not applicable';
	      $params['currentMaritalStatus'] = 'not applicable';
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
            $noOfDaysInLastSixMonths = NULL;
            if(isset($params['hasPatientHadDrinkWithAlcoholInLastSixMonthsInDays']) && trim($params['hasPatientHadDrinkWithAlcoholInLastSixMonthsInDays'])!= ''){
                $noOfDaysInLastSixMonths = $params['hasPatientHadDrinkWithAlcoholInLastSixMonthsInDays'];
            }else if(isset($params['hasPatientHadDrinkWithAlcoholInLastSixMonths']) && trim($params['hasPatientHadDrinkWithAlcoholInLastSixMonths'])!= ''){
               $noOfDaysInLastSixMonths = $params['hasPatientHadDrinkWithAlcoholInLastSixMonths']; 
            }
            $recreationalDrugs = NULL;
            if(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths'])== 'yes'){
                $recreationalDrugs = $params['recreationalDrugs'];
            }
	    $patientHurtBy = NULL;
	    if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear'])!= ''){
		if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear'])== 'yes'){
		    $patientHurtBy = array(
					    'has_patient_hurt_by'=>$params['hasPatientEverBeenHurtBySomeoneWithinLastYear'],
					    'patient_hurt_by'=>(isset($params['patientHurtBySomeoneWithinLastYear']) && trim($params['patientHurtBySomeoneWithinLastYear'])!= '')?$params['patientHurtBySomeoneWithinLastYear']:'',
					    'no_of_times'=>$params['patientHurtBySomeoneWithinLastYearInNoofTimes']
					);
		}else{
		    $patientHurtBy = array('has_patient_hurt_by'=>$params['hasPatientEverBeenHurtBySomeoneWithinLastYear'],'patient_hurt_by'=>'','no_of_times'=>'');
		}
	    }
	    $patientHurtBySomeoneDuringPregnancy = NULL;
	    if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'])!= ''){
		if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'])== 'yes'){
		    $patientHurtBySomeoneDuringPregnancy = array(
					    'has_patient_hurt_by_someone_during_pregnancy'=>$params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'],
					    'patient_hurt_by_someone_during_pregnancy'=>(isset($params['patientHurtBySomeoneDuringPregnancy']) && trim($params['patientHurtBySomeoneDuringPregnancy'])!= '')?$params['patientHurtBySomeoneDuringPregnancy']:''
					);
		}else{
		    $patientHurtBySomeoneDuringPregnancy = array('has_patient_hurt_by_someone_during_pregnancy'=>$params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'],'patient_hurt_by_someone_during_pregnancy'=>'');
		}
	    }
	    $patientForcedForSex = NULL;
	    if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear'])!= ''){
		if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear'])== 'yes'){
		    $patientForcedForSex = array(
					    'has_patient_forced_for_sex'=>$params['hasPatientEverBeenForcedForSexWithinLastYear'],
					    'patient_forced_by'=>(isset($params['patientForcedForSexWithinLastYear']) && trim($params['patientForcedForSexWithinLastYear'])!= '')?$params['patientForcedForSexWithinLastYear']:'',
					    'no_of_times'=>$params['patientForcedForSexWithinLastYearInNoofTimes']
					);
		}else{
		    $patientForcedForSex = array('has_patient_forced_for_sex'=>$params['hasPatientEverBeenForcedForSexWithinLastYear'],'patient_forced_by'=>'','no_of_times'=>'');
		}
	    }
            $data = array(
                    'lab'=>base64_decode($params['lab']),
                    'patient_barcode_id'=>$params['patientBarcodeId'],
                    'interviewer_name'=>$params['interviewerName'],
                    'anc_patient_id'=>$params['ancPatientId'],
                    'interview_date'=>$interviewDate,
		    'has_participant_received_dreams_services'=>(isset($params['hasParticipantReceivedDreamsServices']) && trim($params['hasParticipantReceivedDreamsServices'])!= '')?$params['hasParticipantReceivedDreamsServices']:NULL,
                    'patient_occupation'=>$occupation,
                    'patient_degree'=>(isset($params['degree']) && trim($params['degree'])!= '')?$params['degree']:NULL,
                    'patient_ever_been_married'=>(isset($params['everBeenMarried']) && trim($params['everBeenMarried'])!= '')?$params['everBeenMarried']:NULL,
                    'age_at_first_marriage'=>$ageAtFirstMarriage,
                    'patient_ever_been_widowed'=>(isset($params['everBeenWidowed']) && trim($params['everBeenWidowed'])!= '')?$params['everBeenWidowed']:NULL,
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
                    'last_time_of_receiving_gift_for_sex'=>(isset($params['lastTimeOfReceivingGiftForSex']) && trim($params['lastTimeOfReceivingGiftForSex'])!= '')?$params['lastTimeOfReceivingGiftForSex']:NULL,
                    'no_of_times_been_pregnant'=>(isset($params['noOfTimesBeenPregnant']) && trim($params['noOfTimesBeenPregnant'])!= '')?$params['noOfTimesBeenPregnant']:NULL,
                    'no_of_times_condom_used_before_pregnancy'=>(isset($params['noOfTimesCondomUsedBeforePregnancy']) && trim($params['noOfTimesCondomUsedBeforePregnancy'])!= '')?$params['noOfTimesCondomUsedBeforePregnancy']:NULL,
                    'no_of_times_condom_used_after_pregnancy'=>(isset($params['noOfTimesCondomUsedAfterPregnancy']) && trim($params['noOfTimesCondomUsedAfterPregnancy'])!= '')?$params['noOfTimesCondomUsedAfterPregnancy']:NULL,
                    'has_patient_had_pain_in_lower_abdomen'=>(isset($params['hasPatientHadPainInLowerAbdomen']) && trim($params['hasPatientHadPainInLowerAbdomen'])!= '')?$params['hasPatientHadPainInLowerAbdomen']:NULL,
                    'has_patient_been_treated_for_lower_abdomen_pain'=>(isset($params['hasPatientBeenTreatedForLowerAbdomenPain']) && trim($params['hasPatientBeenTreatedForLowerAbdomenPain'])!= '')?$params['hasPatientBeenTreatedForLowerAbdomenPain']:NULL,
                    'has_patient_ever_been_treated_for_syphilis'=>(isset($params['hasPatientEverBeenTreatedForSyphilis']) && trim($params['hasPatientEverBeenTreatedForSyphilis'])!= '')?$params['hasPatientEverBeenTreatedForSyphilis']:NULL,
		    'has_patient_ever_received_vaccine_to_prevent_HPV'=>(isset($params['hasPatientEverReceivedVaccineToPreventHPV']) && trim($params['hasPatientEverReceivedVaccineToPreventHPV'])!= '')?$params['hasPatientEverReceivedVaccineToPreventHPV']:NULL,
                    'has_patient_had_drink_with_alcohol_in_last_six_months'=>$noOfDaysInLastSixMonths,
                    'has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'=>(isset($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']) && trim($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion'])!= '')?$params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']:NULL,
                    'has_patient_ever_tried_recreational_drugs'=>(isset($params['hasPatientEverTriedRecreationalDrugs']) && trim($params['hasPatientEverTriedRecreationalDrugs'])!= '')?$params['hasPatientEverTriedRecreationalDrugs']:NULL,
                    'has_patient_had_recreational_drugs_in_last_six_months'=>(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths'])!= '')?$params['hasPatientHadRecreationalDrugsInLastSixMonths']:NULL,
                    'recreational_drugs'=>$recreationalDrugs,
		    'has_patient_ever_been_abused_by_someone'=>(isset($params['hasPatientEverBeenAbusedBySomeone']) && trim($params['hasPatientEverBeenAbusedBySomeone'])!= '')?$params['hasPatientEverBeenAbusedBySomeone']:NULL,
		    'has_patient_ever_been_hurt_by_someone_within_last_year'=>($patientHurtBy !=NULL)?json_encode($patientHurtBy):'',
		    'has_patient_ever_been_hurt_by_someone_during_pregnancy'=>($patientHurtBySomeoneDuringPregnancy !=NULL)?json_encode($patientHurtBySomeoneDuringPregnancy):'',
		    'has_patient_ever_been_forced_for_sex_within_last_year'=>($patientForcedForSex !=NULL)?json_encode($patientForcedForSex):'',
		    'is_patient_afraid_of_anyone'=>(isset($params['isPatientAfraidOfAnyone']) && trim($params['isPatientAfraidOfAnyone'])!= '')?$params['isPatientAfraidOfAnyone']:NULL,
		    'comment'=>$params['comment'],
                    'updated_on'=>$common->getDateTime(),
                    'updated_by'=>$loginContainer->userId
                );
            $this->update($data,array('assessment_id'=>$assessmentId));
	    //rapid recency result section
	    $ancRapidRecencyDb = new AncRapidRecencyTable($dbAdapter);
	    $HIVDiagnosticVal = '';
	    $recencyVal = '';
	    if(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])== 'done'){
		$HIVDiagnosticVal = (isset($params['rrrHIVDiagnostic']) && trim($params['rrrHIVDiagnostic'])!= '')?$params['rrrHIVDiagnostic']:NULL;
		if($HIVDiagnosticVal!= 'negative'){
		   $recencyVal = (isset($params['rrrRecency']) && trim($params['rrrRecency'])!= '')?$params['rrrRecency']:NULL;
		}
	    }
	    $rrData = array(
		        'assessment_id'=>$assessmentId,
			'has_patient_had_rapid_recency_test'=>(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])!= '')?$params['hasPatientHadRapidRecencyTest']:NULL,
			'HIV_diagnostic_line'=>$HIVDiagnosticVal,
			'recency_line'=>$recencyVal
		    );
	    if(isset($params['ancRapidRecencyId']) && trim($params['ancRapidRecencyId'])!= ''){
		$ancRapidRecencyDb->update($rrData,array('assessment_id'=>$assessmentId));
	    }else{
	        $ancRapidRecencyDb->insert($rrData);
	    }
        }
      return $assessmentId;
    }
}