<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
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
	    //patient occupation
            $occupation = NULL;
            if(isset($params['occupation']) && trim($params['occupation'])!= ''){
                if($params['occupation'] == 1111 && trim($params['occupationNew'])!= ''){
                    $occupationTypeDb->insert(array('occupation'=>$params['occupationNew'],'occupation_code'=>1111));
                    $occupation = $occupationTypeDb->lastInsertValue;
                }else{
                   $occupation = base64_decode($params['occupation']);
                }
            }
	    //patient degree
	    $hasPatientEverAttendedSchool = NULL;
	    $degree = 'not applicable';
	    if(isset($params['hasPatientEverAttendedSchool']) && trim($params['hasPatientEverAttendedSchool']) == 1){
		$hasPatientEverAttendedSchool = $params['hasPatientEverAttendedSchool'];
		$degree = (isset($params['degree']) && trim($params['degree'])!= '')?$params['degree']:NULL;
	    }else if(isset($params['hasPatientEverAttendedSchool']) && trim($params['hasPatientEverAttendedSchool'])!= ''){
		$hasPatientEverAttendedSchool = $params['hasPatientEverAttendedSchool'];
	    }
	    //marital status
	    $patientEverBeenMarried = NULL;
	    $ageAtFirstMarriage = 'not applicable';
	    $patientEverBeenWidowed = 'not applicable';
	    $currentMaritalStatus = 'not applicable';
	    if(isset($params['everBeenMarried']) && trim($params['everBeenMarried']) == 1){
		$patientEverBeenMarried = $params['everBeenMarried'];
		$patientEverBeenWidowed = (isset($params['everBeenWidowed']) && trim($params['everBeenWidowed'])!= '')?$params['everBeenWidowed']:NULL;
		$currentMaritalStatus = (isset($params['currentMaritalStatus']) && trim($params['currentMaritalStatus'])!= '')?$params['currentMaritalStatus']:NULL;
		if(isset($params['ageAtFirstMarriageInYears']) && trim($params['ageAtFirstMarriageInYears'])!= ''){
		   $ageAtFirstMarriage = '@'.$params['ageAtFirstMarriageInYears'];
		}else if(isset($params['ageAtFirstMarriage']) && trim($params['ageAtFirstMarriage'])!= ''){
		   $ageAtFirstMarriage = $params['ageAtFirstMarriage'];
		}
	    }else if(isset($params['everBeenMarried']) && trim($params['everBeenMarried'])!= ''){
		$patientEverBeenMarried = $params['everBeenMarried'];
	    }
	    //patient HIV test result
	    $hasPatientEverBeenTestedforHIV = NULL;
	    $timeofMostRecentHIVTest = 'not applicable';
	    $resultofMostRecentHIVTest = 'not applicable';
	    if(isset($params['hasPatientEverBeenTestedforHIV']) && trim($params['hasPatientEverBeenTestedforHIV']) == 1){
		$hasPatientEverBeenTestedforHIV = $params['hasPatientEverBeenTestedforHIV'];
		$timeofMostRecentHIVTest = (isset($params['timeOfLastHIVTest']) && trim($params['timeOfLastHIVTest'])!= '')?$params['timeOfLastHIVTest']:NULL;
		$resultofMostRecentHIVTest = (isset($params['lastHIVTestStatus']) && trim($params['lastHIVTestStatus'])!= '')?$params['lastHIVTestStatus']:NULL;
	    }else if(isset($params['hasPatientEverBeenTestedforHIV']) && trim($params['hasPatientEverBeenTestedforHIV'])!= ''){
		$hasPatientEverBeenTestedforHIV = $params['hasPatientEverBeenTestedforHIV'];
	    }
	    //age at very first sex
	    $ageAtVeryFirstSex = NULL;
            if(isset($params['ageAtVeryFirstSexInNumbers']) && trim($params['ageAtVeryFirstSexInNumbers'])!= ''){
               $ageAtVeryFirstSex = '@'.$params['ageAtVeryFirstSexInNumbers'];
            }else if(isset($params['ageAtVeryFirstSex']) && trim($params['ageAtVeryFirstSex'])!= ''){
               $ageAtVeryFirstSex = $params['ageAtVeryFirstSex'];
            }
	    //no.of sexual partners
	    $noOfSexualPartners = NULL;
            if(isset($params['noOfSexualPartnersInNumbers']) && trim($params['noOfSexualPartnersInNumbers'])!= ''){
                $noOfSexualPartners = '@'.$params['noOfSexualPartnersInNumbers'];
            }else if(isset($params['noOfSexualPartners']) && trim($params['noOfSexualPartners'])!= ''){
               $noOfSexualPartners = $params['noOfSexualPartners']; 
            }
	    //no.of sexual partners in last six months
	    $noOfSexualPartnersInLastSixMonths = NULL;
            if(isset($params['noOfSexualPartnersInLastSixMonthsInNumbers']) && trim($params['noOfSexualPartnersInLastSixMonthsInNumbers'])!= ''){
                $noOfSexualPartnersInLastSixMonths = '@'.$params['noOfSexualPartnersInLastSixMonthsInNumbers'];
            }else if(isset($params['noOfSexualPartnersInLastSixMonths']) && trim($params['noOfSexualPartnersInLastSixMonths'])!= ''){
               $noOfSexualPartnersInLastSixMonths = $params['noOfSexualPartnersInLastSixMonths'];
            }
	    //sexual partner's/sexually transmitted infection details
	    $partnerHIVTestStatus = NULL;
	    $ageofMainSexualPartneratLastBirthday = 'not applicable';
	    $ageDiffofMainSexualPartner = 'not applicable';
	    $isPartnerCircumcised = 'not applicable';
	    $circumcision = 'not applicable';
	    if(isset($params['partnerHIVTestStatus']) && trim($params['partnerHIVTestStatus']) == 3){
		$partnerHIVTestStatus = $params['partnerHIVTestStatus'];
	    }else if(isset($params['partnerHIVTestStatus']) && trim($params['partnerHIVTestStatus'])!= ''){
		$partnerHIVTestStatus = $params['partnerHIVTestStatus'];
		$ageofMainSexualPartneratLastBirthday = NULL;
		if(isset($params['ageOfMainSexualPartnerAtLastBirthdayInYears']) && trim($params['ageOfMainSexualPartnerAtLastBirthdayInYears'])!= ''){
		    $ageofMainSexualPartneratLastBirthday = '@'.$params['ageOfMainSexualPartnerAtLastBirthdayInYears'];
		}else if(isset($params['ageOfMainSexualPartnerAtLastBirthday']) && trim($params['ageOfMainSexualPartnerAtLastBirthday'])!= ''){
		   $ageofMainSexualPartneratLastBirthday = $params['ageOfMainSexualPartnerAtLastBirthday'];
		   $ageDiffofMainSexualPartner = (isset($params['ageDiffOfMainSexualPartner']) && trim($params['ageDiffOfMainSexualPartner'])!= '')?$params['ageDiffOfMainSexualPartner']:NULL;
		}
		$isPartnerCircumcised = NULL;
		if(isset($params['isPartnerCircumcised']) && trim($params['isPartnerCircumcised']) == 1){
		    $isPartnerCircumcised = $params['isPartnerCircumcised'];
		    $circumcision = (isset($params['circumcision']) && trim($params['circumcision'])!= '')?$params['circumcision']:NULL;
		}else if(isset($params['isPartnerCircumcised']) && trim($params['isPartnerCircumcised'])!= ''){
		   $isPartnerCircumcised = $params['isPartnerCircumcised']; 
		}
	    }
	    $hasPatinetEverReceivedGiftforSex = NULL;
	    $lastTimeOfReceivingGiftforSex = NULL;
	    $noOfTimesBeenPregnant = NULL;
	    $noOfTimesCondomUsedBeforePregnancy = NULL;
	    $noOfTimesCondomUsedAfterPregnancy = NULL;
	    if(isset($params['hasPatinetEverReceivedGiftForSex']) && trim($params['hasPatinetEverReceivedGiftForSex']) == 1){
		$hasPatinetEverReceivedGiftforSex = $params['hasPatinetEverReceivedGiftForSex'];
		$lastTimeOfReceivingGiftforSex = (isset($params['lastTimeOfReceivingGiftForSex']) && trim($params['lastTimeOfReceivingGiftForSex'])!= '')?$params['lastTimeOfReceivingGiftForSex']:NULL;
	    }else if(isset($params['hasPatinetEverReceivedGiftForSex']) && trim($params['hasPatinetEverReceivedGiftForSex'])!= ''){
		$hasPatinetEverReceivedGiftforSex = $params['hasPatinetEverReceivedGiftForSex'];
		$lastTimeOfReceivingGiftforSex = 'not applicable';
	    }
	    $noOfTimesBeenPregnant = NULL;
	    if(isset($params['noOfTimesBeenPregnantInNumbers']) && trim($params['noOfTimesBeenPregnantInNumbers'])!= ''){
		$noOfTimesBeenPregnant = '@'.$params['noOfTimesBeenPregnantInNumbers'];
	    }else if(isset($params['noOfTimesBeenPregnant']) && trim($params['noOfTimesBeenPregnant'])!= ''){
	       $noOfTimesBeenPregnant = $params['noOfTimesBeenPregnant'];
	    }
	    $noOfTimesCondomUsedBeforePregnancy = (isset($params['noOfTimesCondomUsedBeforePregnancy']) && trim($params['noOfTimesCondomUsedBeforePregnancy'])!= '')?$params['noOfTimesCondomUsedBeforePregnancy']:NULL;
	    $noOfTimesCondomUsedAfterPregnancy = (isset($params['noOfTimesCondomUsedAfterPregnancy']) && trim($params['noOfTimesCondomUsedAfterPregnancy'])!= '')?$params['noOfTimesCondomUsedAfterPregnancy']:NULL;
	    //patient disease symptoms/treatment details
	    $hasPatientHadPainInLowerAbdomen = NULL;
	    $hasPatientBeenTreatedForLowerAbdomenPain = 'not applicable';
	    if(isset($params['hasPatientHadPainInLowerAbdomen']) && trim($params['hasPatientHadPainInLowerAbdomen']) == 1){
		$hasPatientHadPainInLowerAbdomen = $params['hasPatientHadPainInLowerAbdomen'];
		$hasPatientBeenTreatedForLowerAbdomenPain = (isset($params['hasPatientBeenTreatedForLowerAbdomenPain']) && trim($params['hasPatientBeenTreatedForLowerAbdomenPain'])!= '')?$params['hasPatientBeenTreatedForLowerAbdomenPain']:NULL;
	    }else if(isset($params['hasPatientHadPainInLowerAbdomen']) && trim($params['hasPatientHadPainInLowerAbdomen'])!= ''){
		$hasPatientHadPainInLowerAbdomen = $params['hasPatientHadPainInLowerAbdomen'];
	    }
	    //patient alcohol/drug use
	    $patientHadDrinkWithAlcoholInLastSixMonths = NULL;
	    if(isset($params['patientHadDrinkWithAlcoholInLastSixMonthsInDays']) && trim($params['patientHadDrinkWithAlcoholInLastSixMonthsInDays'])!= ''){
                $patientHadDrinkWithAlcoholInLastSixMonths = '@'.$params['patientHadDrinkWithAlcoholInLastSixMonthsInDays'];
            }else if(isset($params['patientHadDrinkWithAlcoholInLastSixMonths']) && trim($params['patientHadDrinkWithAlcoholInLastSixMonths'])!= ''){
               $patientHadDrinkWithAlcoholInLastSixMonths = $params['patientHadDrinkWithAlcoholInLastSixMonths'];
            }
	    $hasPatientEverTriedRecreationalDrugs = NULL;
	    $hasPatientHadRecreationalDrugsInLastSixMonths = 'not applicable';
	    $recreationalDrugs = 'not applicable';
	    if(isset($params['hasPatientEverTriedRecreationalDrugs']) && trim($params['hasPatientEverTriedRecreationalDrugs']) == 1){
		$hasPatientEverTriedRecreationalDrugs = $params['hasPatientEverTriedRecreationalDrugs'];
		$hasPatientHadRecreationalDrugsInLastSixMonths = '';
		if(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths']) == 1){
		   $hasPatientHadRecreationalDrugsInLastSixMonths = $params['hasPatientHadRecreationalDrugsInLastSixMonths'];
		   $recreationalDrugs = (isset($params['recreationalDrugs']) && trim($params['recreationalDrugs'])!= '')?$params['recreationalDrugs']:'';
		}else if(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths'])!= ''){
		  $hasPatientHadRecreationalDrugsInLastSixMonths = $params['hasPatientHadRecreationalDrugsInLastSixMonths'];   
		}
	        $patientHadRecreationalDrugsInLastSixMonths = array('has_had_in_last_six_months'=>$hasPatientHadRecreationalDrugsInLastSixMonths,'drugs'=>$recreationalDrugs);
	    }else if(isset($params['hasPatientEverTriedRecreationalDrugs']) && trim($params['hasPatientEverTriedRecreationalDrugs'])!= ''){
		$hasPatientEverTriedRecreationalDrugs = $params['hasPatientEverTriedRecreationalDrugs'];
	    }
	    //patient abused by
	    $everAbused = NULL;
	    $whoAbused = 'not applicable';
	    $patientAbusedByInNoofTimes = 'not applicable';
	    if(isset($params['hasPatientEverBeenAbusedBySomeone']) && trim($params['hasPatientEverBeenAbusedBySomeone']) == 1){
		$everAbused = $params['hasPatientEverBeenAbusedBySomeone'];
		$whoAbused = '';
		if(isset($params['patientAbusedBy']) && count($params['patientAbusedBy']) >0){
		    $whoAbused = implode(',',$params['patientAbusedBy']);
		}else if(isset($params['patientAbusedByOther']) && trim($params['patientAbusedByOther'])!= ''){
		    $whoAbused = '@'.$params['patientAbusedByOther'];
		}
		$patientAbusedByInNoofTimes = '';
		if(isset($params['patientAbusedBySomeoneInNoofTimes']) && trim($params['patientAbusedBySomeoneInNoofTimes'])!= ''){
		    $patientAbusedByInNoofTimes = '@'.$params['patientAbusedBySomeoneInNoofTimes'];
		}else if(isset($params['patientAbusedByNoofTimes']) && trim($params['patientAbusedByNoofTimes'])!= ''){
		    $patientAbusedByInNoofTimes = $params['patientAbusedByNoofTimes'];
		}
	    }else if(isset($params['hasPatientEverBeenAbusedBySomeone']) && trim($params['hasPatientEverBeenAbusedBySomeone'])!= ''){
		$everAbused = $params['hasPatientEverBeenAbusedBySomeone'];
	    }
	    $hasPatientEverBeenAbusedBySomeone = array('ever_abused'=>$everAbused,'who_abused'=>$whoAbused,'no_of_times'=>$patientAbusedByInNoofTimes);
	    //patient hurt by someone within last year
	    $everHurted = NULL;
	    $whoHurt = 'not applicable';
	    $patientHurtByInNoofTimes = 'not applicable';
	    if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) == 1){
		$everHurted = $params['hasPatientEverBeenHurtBySomeoneWithinLastYear'];
		$whoHurt = '';
		if(isset($params['patientHurtBySomeoneWithinLastYear']) && count($params['patientHurtBySomeoneWithinLastYear']) >0){
		    $whoHurt = implode(',',$params['patientHurtBySomeoneWithinLastYear']);
		}else if(isset($params['patientHurtByOther']) && trim($params['patientHurtByOther'])!= ''){
		    $whoHurt = '@'.$params['patientHurtByOther'];
		}
		$patientHurtByInNoofTimes = '';
		if(isset($params['patientHurtBySomeoneInNoofTimes']) && trim($params['patientHurtBySomeoneInNoofTimes'])!= ''){
		    $patientHurtByInNoofTimes = '@'.$params['patientHurtBySomeoneInNoofTimes'];
		}else if(isset($params['patientHurtByNoofTimes']) && trim($params['patientHurtByNoofTimes'])!= ''){
		    $patientHurtByInNoofTimes = $params['patientHurtByNoofTimes'];
		}
		//patient hurt by someone during pregnancy
		$everHurteddp = NULL;
		$whoHurtdp = 'not applicable';
		$patientHurtByDuringPregnancyInNoofTimes = 'not applicable';
		if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) == 1){
		    $everHurteddp = $params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'];
		    $whoHurtdp = '';
		    if(isset($params['patientHurtBySomeoneDuringPregnancy']) && count($params['patientHurtBySomeoneDuringPregnancy']) >0){
			$whoHurtdp = implode(',',$params['patientHurtBySomeoneDuringPregnancy']);
		    }else if(isset($params['patientHurtByOtherDuringPregnancy']) && trim($params['patientHurtByOtherDuringPregnancy'])!= ''){
			$whoHurtdp = '@'.$params['patientHurtByOtherDuringPregnancy'];
		    }
		    $patientHurtByDuringPregnancyInNoofTimes = '';
		    if(isset($params['patientHurtBySomeoneDuringPregnancyInNoofTimes']) && trim($params['patientHurtBySomeoneDuringPregnancyInNoofTimes'])!= ''){
			$patientHurtByDuringPregnancyInNoofTimes = '@'.$params['patientHurtBySomeoneDuringPregnancyInNoofTimes'];
		    }else if(isset($params['patientHurtByDuringPregnancyNoofTimes']) && trim($params['patientHurtByDuringPregnancyNoofTimes'])!= ''){
			$patientHurtByDuringPregnancyInNoofTimes = $params['patientHurtByDuringPregnancyNoofTimes'];
		    }
		}else if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'])!= ''){
		    $everHurteddp = $params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'];
		}
		$hasPatientHurtBySomeoneDuringPregnancy = array('ever_hurt_by_during_pregnancy'=>$everHurteddp,'who_hurt'=>$whoHurtdp,'no_of_times'=>$patientHurtByDuringPregnancyInNoofTimes);
	    }else if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear'])!= ''){
		$everHurted = $params['hasPatientEverBeenHurtBySomeoneWithinLastYear'];
		$hasPatientHurtBySomeoneDuringPregnancy = array('ever_hurt_by_during_pregnancy'=>'not applicable','who_hurt'=>'not applicable','no_of_times'=>'not applicable');
	    }
	    $hasPatientHurtBySomeoneWithinLastYear = array('ever_hurt'=>$everHurted,'who_hurt'=>$whoHurt,'no_of_times'=>$patientHurtByInNoofTimes);
	    //patient forced for sex within last year
	    $everForcedforSex = NULL;
	    $whoForced = 'not applicable';
	    $patientForcedforSexWithinLastYearInNoofTimes = 'not applicable';
	    if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear']) == 1){
		$everForcedforSex = $params['hasPatientEverBeenForcedForSexWithinLastYear'];
		$whoForced = '';
		if(isset($params['patientForcedForSexWithinLastYear']) && count($params['patientForcedForSexWithinLastYear']) >0){
		    $whoForced = implode(',',$params['patientForcedForSexWithinLastYear']);
		}else if(isset($params['patientForcedForSexByOtherWithinLastYear']) && trim($params['patientForcedForSexByOtherWithinLastYear'])!= ''){
		    $whoForced = '@'.$params['patientForcedForSexByOtherWithinLastYear'];
		}
		$patientForcedforSexWithinLastYearInNoofTimes = '';
		if(isset($params['patientForcedForSexInNoofTimes']) && trim($params['patientForcedForSexInNoofTimes'])!= ''){
		    $patientForcedforSexWithinLastYearInNoofTimes = '@'.$params['patientForcedForSexInNoofTimes'];
		}else if(isset($params['patientForcedForSexNoofTimes']) && trim($params['patientForcedForSexNoofTimes'])!= ''){
		    $patientForcedforSexWithinLastYearInNoofTimes = $params['patientForcedForSexNoofTimes'];
		}
	    }else if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear'])!= ''){
		$everForcedforSex = $params['hasPatientEverBeenForcedForSexWithinLastYear'];
	    }
	    $hasPatientForcedforSexWithinLastYear = array('ever_forced_for_sex'=>$everForcedforSex,'who_forced'=>$whoForced,'no_of_times'=>$patientForcedforSexWithinLastYearInNoofTimes);
            $data = array(
                    'anc'=>base64_decode($params['ancSite']),
                    'patient_barcode_id'=>$params['patientBarcodeId'],
                    'interviewer_name'=>$params['interviewerName'],
                    'anc_patient_id'=>$params['ancPatientId'],
                    'interview_date'=>$interviewDate,
		    //'has_participant_received_dreams_services'=>(isset($params['hasParticipantReceivedDreamsServices']) && trim($params['hasParticipantReceivedDreamsServices'])!= '')?$params['hasParticipantReceivedDreamsServices']:NULL,
                    'patient_occupation'=>$occupation,
                    'has_patient_ever_attended_school'=>$hasPatientEverAttendedSchool,
		    'patient_degree'=>$degree,
		    'patient_ever_been_married'=>$patientEverBeenMarried,
		    'age_at_first_marriage'=>$ageAtFirstMarriage,
		    'patient_ever_been_widowed'=>$patientEverBeenWidowed,
		    'current_marital_status'=>$currentMaritalStatus,
		    'has_patient_ever_been_tested_for_HIV'=>$hasPatientEverBeenTestedforHIV,
		    'time_of_last_HIV_test'=>$timeofMostRecentHIVTest,
		    'last_HIV_test_status'=>$resultofMostRecentHIVTest,
		    'age_at_very_first_sex'=>$ageAtVeryFirstSex,
		    'reason_for_very_first_sex'=>(isset($params['reasonForVeryFirstSex']) && trim($params['reasonForVeryFirstSex'])!= '')?$params['reasonForVeryFirstSex']:NULL,
		    'no_of_sexual_partners'=>$noOfSexualPartners,
		    'no_of_sexual_partners_in_last_six_months'=>$noOfSexualPartnersInLastSixMonths,
		    'partner_HIV_test_status'=>$partnerHIVTestStatus,
		    'age_of_main_sexual_partner_at_last_birthday'=>$ageofMainSexualPartneratLastBirthday,
		    'age_diff_of_main_sexual_partner'=>$ageDiffofMainSexualPartner,
		    'is_partner_circumcised'=>$isPartnerCircumcised,
		    'circumcision'=>$circumcision,
		    'has_patient_ever_received_gift_for_sex'=>$hasPatinetEverReceivedGiftforSex,
		    'last_time_of_receiving_gift_for_sex'=>$lastTimeOfReceivingGiftforSex,
		    'no_of_times_been_pregnant'=>$noOfTimesBeenPregnant,
		    'no_of_times_condom_used_before_pregnancy'=>$noOfTimesCondomUsedBeforePregnancy,
		    'no_of_times_condom_used_after_pregnancy'=>$noOfTimesCondomUsedAfterPregnancy,
		    'has_patient_had_pain_in_lower_abdomen'=>$hasPatientHadPainInLowerAbdomen,
		    'has_patient_been_treated_for_lower_abdomen_pain'=>$hasPatientBeenTreatedForLowerAbdomenPain,
		    'has_patient_ever_been_treated_for_syphilis'=>(isset($params['hasPatientEverBeenTreatedForSyphilis']) && trim($params['hasPatientEverBeenTreatedForSyphilis'])!= '')?$params['hasPatientEverBeenTreatedForSyphilis']:NULL,
		    'has_patient_ever_received_vaccine_to_prevent_cervical_cancer'=>(isset($params['hasPatientEverReceivedVaccineToPreventCervicalCancer']) && trim($params['hasPatientEverReceivedVaccineToPreventCervicalCancer'])!= '')?$params['hasPatientEverReceivedVaccineToPreventCervicalCancer']:NULL,
		    'patient_had_drink_with_alcohol_in_last_six_months'=>$patientHadDrinkWithAlcoholInLastSixMonths,
		    'has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'=>(isset($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']) && trim($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion'])!= '')?$params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']:NULL,
		    'has_patient_ever_tried_recreational_drugs'=>$hasPatientEverTriedRecreationalDrugs,
		    'has_patient_had_recreational_drugs_in_last_six_months'=>(isset($patientHadRecreationalDrugsInLastSixMonths))?json_encode($patientHadRecreationalDrugsInLastSixMonths):'',
		    'has_patient_ever_been_abused_by_someone'=>json_encode($hasPatientEverBeenAbusedBySomeone),
		    'has_patient_ever_been_hurt_by_someone_within_last_year'=>json_encode($hasPatientHurtBySomeoneWithinLastYear),
		    'has_patient_ever_been_hurt_by_someone_during_pregnancy'=>json_encode($hasPatientHurtBySomeoneDuringPregnancy),
		    'has_patient_ever_been_forced_for_sex_within_last_year'=>json_encode($hasPatientForcedforSexWithinLastYear),
		    'is_patient_afraid_of_anyone'=>(isset($params['isPatientAfraidOfAnyone']) && trim($params['isPatientAfraidOfAnyone'])!= '')?$params['isPatientAfraidOfAnyone']:NULL,
                    'comment'=>$params['comment'],
		    'country'=>$country,
		    'status'=>1,
                    'added_on'=>$common->getDateTime(),
                    'added_by'=>$loginContainer->userId
                );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
	    if($lastInsertedId >0){
		$ancRapidRecencyDb = new AncRapidRecencyTable($dbAdapter);
		$controlVal = '';
		$HIVDiagnosticVal = '';
		$recencyVal = '';
		if(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])== 'done'){
		    $controlVal = (isset($params['rrrControl']) && trim($params['rrrControl'])!= '')?$params['rrrControl']:NULL;
		    $HIVDiagnosticVal = (isset($params['rrrHIVDiagnostic']) && trim($params['rrrHIVDiagnostic'])!= '')?$params['rrrHIVDiagnostic']:NULL;
		    if($HIVDiagnosticVal!= 'negative'){
		       $recencyVal = (isset($params['rrrRecency']) && trim($params['rrrRecency'])!= '')?$params['rrrRecency']:NULL;
		    }
		}
		$rrData = array(
			    'assessment_id'=>$lastInsertedId,
			    'control_line'=>$controlVal,
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
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	    $aColumns = array('anc_site_name','anc_site_code','r_a.patient_barcode_id','da_c.age','r_a.interviewer_name','r_a.anc_patient_id',"DATE_FORMAT(r_a.interview_date,'%d-%b-%Y')","DATE_FORMAT(r_a.added_on,'%d-%b-%Y')",'u.user_name','test_status_name');
	    $orderColumns = array('anc_site_name','anc_site_code','r_a.patient_barcode_id','da_c.age','r_a.interviewer_name','r_a.anc_patient_id','r_a.interview_date','r_a.added_on','u.user_name','test_status_name');
	}else{
	    $aColumns = array('anc_site_name','anc_site_code','r_a.patient_barcode_id','da_c.age','r_a.interviewer_name','r_a.anc_patient_id',"DATE_FORMAT(r_a.interview_date,'%d-%b-%Y')","DATE_FORMAT(r_a.added_on,'%d-%b-%Y')",'u.user_name','c.country_name','test_status_name');
	    $orderColumns = array('anc_site_name','anc_site_code','r_a.patient_barcode_id','da_c.age','r_a.interviewer_name','r_a.anc_patient_id','r_a.interview_date','r_a.added_on','u.user_name','c.country_name','test_status_name');
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
	  $parameters['filterDate'] = $parameters['interviewDate'];
       }
       if(isset($parameters['filterDate']) && trim($parameters['filterDate'])!= ''){
	   $filter_date = explode("to", $parameters['filterDate']);
	   if(isset($filter_date[0]) && trim($filter_date[0]) != "") {
	     $start_date = $common->dateRangeFormat(trim($filter_date[0]));
	   }if(isset($filter_date[1]) && trim($filter_date[1]) != "") {
	     $end_date = $common->dateRangeFormat(trim($filter_date[1]));
	   }
	}
	$provinces = array();
	if(isset($parameters['province']) && trim($parameters['province'])!= ''){
	    $provinceArray = explode(',',$parameters['province']);
            $provinces = array();
            for($i=0;$i<count($provinceArray);$i++){
                $provinces[] = base64_decode($provinceArray[$i]);
            }
	}
	$districts = array();
	if(isset($parameters['district']) && trim($parameters['district'])!= ''){
	    $districtArray = explode(',',$parameters['district']);
            $districts = array();
            for($i=0;$i<count($districtArray);$i++){
                $districts[] = base64_decode($districtArray[$i]);
            }
	}
	$ancs = array();
	if(isset($parameters['anc']) && trim($parameters['anc'])!= ''){
	    $ancArray = explode(',',$parameters['anc']);
            $ancs = array();
            for($i=0;$i<count($ancArray);$i++){
                $ancs[] = base64_decode($ancArray[$i]);
            }
	}
	$dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                      ->join(array('anc' => 'anc_site'), "anc.anc_site_id=r_a.anc",array('anc_site_name','anc_site_code'))
                      ->join(array('u' => 'user'), "u.user_id=r_a.added_by",array('user_name'))
                      ->join(array('c' => 'country'), "c.country_id=r_a.country",array('country_name'))
		      ->join(array('t' => 'test_status'), "t.test_status_id=r_a.status",array('test_status_name'))
		      ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation','occupation_code'))
		      ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array(),'left')
		      ->join(array('da_c' => 'data_collection'), "da_c.patient_barcode_id=r_a.patient_barcode_id",array('age'),'left');
	if(count($ancs) >0){
	   $sQuery = $sQuery->where('r_a.anc IN ("' . implode('", "', $ancs) . '")');
        }else if($loginContainer->roleCode == 'ANCSC'){
	   $sQuery = $sQuery->where(array('r_a.added_by'=>$loginContainer->userId));
        }
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $sQuery = $sQuery->where(array('r_a.country'=>trim($parameters['countryId'])));
	}else if($loginContainer->roleCode== 'CC'){
	   $sQuery = $sQuery->where('r_a.country IN ("' . implode('", "', $loginContainer->country) . '")');
	}
	if(isset($parameters['type']) && trim($parameters['type'])== 'anc-rr-recent'){
	    $sQuery = $sQuery->where(array('anc_r_r.recency_line'=>'recent'));
	}
	if(count($provinces) > 0){
	    $sQuery = $sQuery->where('anc.province IN ("' . implode('", "', $provinces) . '")');
	}
	if(count($districts) > 0){
	    $sQuery = $sQuery->where('anc.district IN ("' . implode('", "', $districts) . '")');
	}
	$data_Column = ($parameters['dateSrc'] == 'interview' || trim($parameters['interviewDate'])!= '')?'r_a.interview_date':'r_a.added_on';
	if(trim($start_date) != "" && trim($start_date)!= trim($end_date)) {
           $sQuery = $sQuery->where(array("$data_Column >='" . $start_date ."'", "$data_Column <='" . $end_date."'"));
        }else if (trim($start_date) != "") {
           $sQuery = $sQuery->where(array("$data_Column = '" . $start_date. "'"));
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
                      ->join(array('anc' => 'anc_site'), "anc.anc_site_id=r_a.anc",array('anc_site_name','anc_site_code'))
                      ->join(array('u' => 'user'), "u.user_id=r_a.added_by",array('user_name'))
                      ->join(array('c' => 'country'), "c.country_id=r_a.country",array('country_name'))
		      ->join(array('t' => 'test_status'), "t.test_status_id=r_a.status",array('test_status_name'))
		      ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation','occupation_code'))
		      ->join(array('anc_r_r'=>'anc_rapid_recency'),'anc_r_r.assessment_id=r_a.assessment_id',array(),'left')
		      ->join(array('da_c' => 'data_collection'), "da_c.patient_barcode_id=r_a.patient_barcode_id",array('age'),'left');
	if($loginContainer->roleCode == 'ANCSC'){
	   $tQuery = $tQuery->where(array('r_a.added_by'=>$loginContainer->userId));
        }
	if(isset($parameters['countryId']) && trim($parameters['countryId'])!= ''){
	   $tQuery = $tQuery->where(array('r_a.country'=>trim($parameters['countryId'])));
	}else if($loginContainer->roleCode== 'CC'){
	   $tQuery = $tQuery->where('r_a.country IN ("' . implode('", "', $loginContainer->country) . '")');
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
	    $dataView = '';
	    $dataEdit = '';
	    $dataLock = '';
	    $dataUnlock = '';
	    $pdfLink = '';
	    $userUnlockedHistory = '';
	    if($aRow['interview_date']!= null && trim($aRow['interview_date'])!= '' && $aRow['interview_date']!= '0000-00-00'){
		$interviewDate = $common->humanDateFormat($aRow['interview_date']);
	    }
	    $addedDate = explode(" ",$aRow['added_on']);
	    if($aRow['unlocked_on']!= null && trim($aRow['unlocked_on'])!= '' && $aRow['unlocked_on']!= '0000-00-00 00:00:00'){
		$unlockedDate = explode(" ",$aRow['unlocked_on']);
		$userQuery = $sql->select()->from(array('u' => 'user'))->columns(array('user_id','full_name'))->where(array('u.user_id'=>$aRow['unlocked_by']));
	        $userQueryStr = $sql->getSqlStringForSqlObject($userQuery);
	        $userResult = $dbAdapter->query($userQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
		$unlockedBy = 'System';
		if(isset($userResult->user_id)){
		    $unlockedBy = ($userResult->user_id == $loginContainer->userId)?'You':ucwords($userResult->full_name);
		}
	        $userUnlockedHistory = '<i class="zmdi zmdi-info-outline unlocKbtn" title="This row was unlocked on '.$common->humanDateFormat($unlockedDate[0])." ".$unlockedDate[1].' by '.$unlockedBy.'" style="font-size:1.3rem;"></i>';
	    }
	    //data view
	    $dataView = '<a href="/clinic/risk-assessment/view/' . base64_encode($aRow['assessment_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn blue-text custom-btn custom-btn-blue margin-bottom-1" title="View"><i class="zmdi zmdi-eye"></i> View</a>&nbsp;&nbsp';
	    //data edit
	    if($loginContainer->hasViewOnlyAccess!='yes' && $aRow['test_status_name']!= 'locked'){
		$dataEdit = '<a href="/clinic/risk-assessment/edit/' . base64_encode($aRow['assessment_id']) . '/' . base64_encode($parameters['countryId']) . '" class="waves-effect waves-light btn-small btn pink-text custom-btn custom-btn-pink margin-bottom-1" title="Edit"><i class="zmdi zmdi-edit"></i> Edit</a>&nbsp;&nbsp';
	    } if($loginContainer->hasViewOnlyAccess!='yes' && $aRow['test_status_name']== 'completed'){
		$dataLock = '<a href="javascript:void(0);" onclick="lockRiskAssessment(\''.base64_encode($aRow['assessment_id']).'\');" class="waves-effect waves-light btn-small btn green-text custom-btn custom-btn-green margin-bottom-1" title="Lock"><i class="zmdi zmdi-lock-outline"></i> Lock</a>&nbsp;&nbsp;';
	    }
	    //for csc/cc
	    if(($loginContainer->roleCode== 'CSC' || $loginContainer->roleCode== 'CC') && $loginContainer->hasViewOnlyAccess!='yes' && $aRow['test_status_name']== 'locked'){
		$dataUnlock = '<a href="javascript:void(0);" onclick="unlockRiskAssessment(\''.base64_encode($aRow['assessment_id']).'\');" class="waves-effect waves-light btn-small btn red-text custom-btn custom-btn-red margin-bottom-1" title="Unlock"><i class="zmdi zmdi-lock-open"></i> Unlock</a>&nbsp;&nbsp;';
	    }
	    $dataLockUnlock = (trim($dataLock)!= '')?$dataLock:$dataUnlock;
	    //individual assessment pdf
	    if($aRow['test_status_name']== 'locked'){
	       $pdfLink = '<a href="javascript:void(0);" onclick="printAssessmentForm(\''.base64_encode($aRow['assessment_id']).'\');" class="waves-effect waves-light btn-small btn orange-text custom-btn custom-btn-orange margin-bottom-1" title="PDF"><i class="zmdi zmdi-collection-pdf"></i> PDF</a>&nbsp;&nbsp;';
	    }
	    $row = array();
	    $row[] = ucwords($aRow['anc_site_name']);
	    $row[] = $aRow['anc_site_code'];
	    $row[] = $aRow['patient_barcode_id'];
	    $row[] = (isset($aRow['age']) && (int)$aRow['age'] > 0)?$aRow['age']:'';
	    $row[] = ucwords($aRow['interviewer_name']);
	    $row[] = $aRow['anc_patient_id'];
	    $row[] = $interviewDate;
	    $row[] = $common->humanDateFormat($addedDate[0]);
	    $row[] = ucwords($aRow['user_name']);
	    if(trim($parameters['countryId']) == ''){
	       $row[] = ucwords($aRow['country_name']);
	    }
	    $row[] = ucwords($aRow['test_status_name']);
	    $row[] = $dataEdit.$dataView.$dataLockUnlock.$pdfLink.$userUnlockedHistory;
	    $output['aaData'][] = $row;
	}
       return $output;
    }
    
    public function fetchRiskAssessment($riskAssessmentId){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $riskAssessmentQuery = $sql->select()->from(array('r_a' => 'clinic_risk_assessment'))
                                   ->join(array('anc' => 'anc_site'), "anc.anc_site_id=r_a.anc",array('anc_site_name'))
                                   ->join(array('ot' => 'occupation_type'), "ot.occupation_id=r_a.patient_occupation",array('occupationName'=>'occupation','occupation_code'))
				   ->join(array('anc_r_r' => 'anc_rapid_recency'), "anc_r_r.assessment_id=r_a.assessment_id",array('anc_rapid_recency_id','has_patient_had_rapid_recency_test','control_line','HIV_diagnostic_line','recency_line'),'left')
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
            //patient occupation
            $occupation = NULL;
            if(isset($params['occupation']) && trim($params['occupation'])!= ''){
                if($params['occupation'] == 1111 && trim($params['occupationNew'])!= ''){
                    $occupationTypeDb->insert(array('occupation'=>$params['occupationNew'],'occupation_code'=>1111));
                    $occupation = $occupationTypeDb->lastInsertValue;
                }else{
                   $occupation = base64_decode($params['occupation']);
                }
            }
	    //patient degree
	    $hasPatientEverAttendedSchool = NULL;
	    $degree = 'not applicable';
	    if(isset($params['hasPatientEverAttendedSchool']) && trim($params['hasPatientEverAttendedSchool']) == 1){
		$hasPatientEverAttendedSchool = $params['hasPatientEverAttendedSchool'];
		$degree = (isset($params['degree']) && trim($params['degree'])!= '')?$params['degree']:NULL;
	    }else if(isset($params['hasPatientEverAttendedSchool']) && trim($params['hasPatientEverAttendedSchool'])!= ''){
		$hasPatientEverAttendedSchool = $params['hasPatientEverAttendedSchool'];
	    }
	    //marital status
	    $patientEverBeenMarried = NULL;
	    $ageAtFirstMarriage = 'not applicable';
	    $patientEverBeenWidowed = 'not applicable';
	    $currentMaritalStatus = 'not applicable';
	    if(isset($params['everBeenMarried']) && trim($params['everBeenMarried']) == 1){
		$patientEverBeenMarried = $params['everBeenMarried'];
		$patientEverBeenWidowed = (isset($params['everBeenWidowed']) && trim($params['everBeenWidowed'])!= '')?$params['everBeenWidowed']:NULL;
		$currentMaritalStatus = (isset($params['currentMaritalStatus']) && trim($params['currentMaritalStatus'])!= '')?$params['currentMaritalStatus']:NULL;
		if(isset($params['ageAtFirstMarriageInYears']) && trim($params['ageAtFirstMarriageInYears'])!= ''){
		   $ageAtFirstMarriage = '@'.$params['ageAtFirstMarriageInYears'];
		}else if(isset($params['ageAtFirstMarriage']) && trim($params['ageAtFirstMarriage'])!= ''){
		   $ageAtFirstMarriage = $params['ageAtFirstMarriage'];
		}
	    }else if(isset($params['everBeenMarried']) && trim($params['everBeenMarried'])!= ''){
		$patientEverBeenMarried = $params['everBeenMarried'];
	    }
	    //patient HIV test result
	    $hasPatientEverBeenTestedforHIV = NULL;
	    $timeofMostRecentHIVTest = 'not applicable';
	    $resultofMostRecentHIVTest = 'not applicable';
	    if(isset($params['hasPatientEverBeenTestedforHIV']) && trim($params['hasPatientEverBeenTestedforHIV']) == 1){
		$hasPatientEverBeenTestedforHIV = $params['hasPatientEverBeenTestedforHIV'];
		$timeofMostRecentHIVTest = (isset($params['timeOfLastHIVTest']) && trim($params['timeOfLastHIVTest'])!= '')?$params['timeOfLastHIVTest']:NULL;
		$resultofMostRecentHIVTest = (isset($params['lastHIVTestStatus']) && trim($params['lastHIVTestStatus'])!= '')?$params['lastHIVTestStatus']:NULL;
	    }else if(isset($params['hasPatientEverBeenTestedforHIV']) && trim($params['hasPatientEverBeenTestedforHIV'])!= ''){
		$hasPatientEverBeenTestedforHIV = $params['hasPatientEverBeenTestedforHIV'];
	    }
	    //age at very first sex
	    $ageAtVeryFirstSex = NULL;
            if(isset($params['ageAtVeryFirstSexInNumbers']) && trim($params['ageAtVeryFirstSexInNumbers'])!= ''){
               $ageAtVeryFirstSex = '@'.$params['ageAtVeryFirstSexInNumbers'];
            }else if(isset($params['ageAtVeryFirstSex']) && trim($params['ageAtVeryFirstSex'])!= ''){
               $ageAtVeryFirstSex = $params['ageAtVeryFirstSex'];
            }
	    //no.of sexual partners
	    $noOfSexualPartners = NULL;
            if(isset($params['noOfSexualPartnersInNumbers']) && trim($params['noOfSexualPartnersInNumbers'])!= ''){
                $noOfSexualPartners = '@'.$params['noOfSexualPartnersInNumbers'];
            }else if(isset($params['noOfSexualPartners']) && trim($params['noOfSexualPartners'])!= ''){
               $noOfSexualPartners = $params['noOfSexualPartners']; 
            }
	    //no.of sexual partners in last six months
	    $noOfSexualPartnersInLastSixMonths = NULL;
            if(isset($params['noOfSexualPartnersInLastSixMonthsInNumbers']) && trim($params['noOfSexualPartnersInLastSixMonthsInNumbers'])!= ''){
                $noOfSexualPartnersInLastSixMonths = '@'.$params['noOfSexualPartnersInLastSixMonthsInNumbers'];
            }else if(isset($params['noOfSexualPartnersInLastSixMonths']) && trim($params['noOfSexualPartnersInLastSixMonths'])!= ''){
               $noOfSexualPartnersInLastSixMonths = $params['noOfSexualPartnersInLastSixMonths'];
            }
	    //sexual partner's/sexually transmitted infection details
	    $partnerHIVTestStatus = NULL;
	    $ageofMainSexualPartneratLastBirthday = 'not applicable';
	    $ageDiffofMainSexualPartner = 'not applicable';
	    $isPartnerCircumcised = 'not applicable';
	    $circumcision = 'not applicable';
	    if(isset($params['partnerHIVTestStatus']) && trim($params['partnerHIVTestStatus']) == 3){
		$partnerHIVTestStatus = $params['partnerHIVTestStatus'];
	    }else if(isset($params['partnerHIVTestStatus']) && trim($params['partnerHIVTestStatus'])!= ''){
		$partnerHIVTestStatus = $params['partnerHIVTestStatus'];
		$ageofMainSexualPartneratLastBirthday = NULL;
		if(isset($params['ageOfMainSexualPartnerAtLastBirthdayInYears']) && trim($params['ageOfMainSexualPartnerAtLastBirthdayInYears'])!= ''){
		    $ageofMainSexualPartneratLastBirthday = '@'.$params['ageOfMainSexualPartnerAtLastBirthdayInYears'];
		}else if(isset($params['ageOfMainSexualPartnerAtLastBirthday']) && trim($params['ageOfMainSexualPartnerAtLastBirthday'])!= ''){
		   $ageofMainSexualPartneratLastBirthday = $params['ageOfMainSexualPartnerAtLastBirthday'];
		   $ageDiffofMainSexualPartner = (isset($params['ageDiffOfMainSexualPartner']) && trim($params['ageDiffOfMainSexualPartner'])!= '')?$params['ageDiffOfMainSexualPartner']:NULL;
		}
		$isPartnerCircumcised = NULL;
		if(isset($params['isPartnerCircumcised']) && trim($params['isPartnerCircumcised']) == 1){
		    $isPartnerCircumcised = $params['isPartnerCircumcised'];
		    $circumcision = (isset($params['circumcision']) && trim($params['circumcision'])!= '')?$params['circumcision']:NULL;
		}else if(isset($params['isPartnerCircumcised']) && trim($params['isPartnerCircumcised'])!= ''){
		   $isPartnerCircumcised = $params['isPartnerCircumcised']; 
		}
	    }
	    $hasPatinetEverReceivedGiftforSex = NULL;
	    $lastTimeOfReceivingGiftforSex = NULL;
	    $noOfTimesBeenPregnant = NULL;
	    $noOfTimesCondomUsedBeforePregnancy = NULL;
	    $noOfTimesCondomUsedAfterPregnancy = NULL;
	    if(isset($params['hasPatinetEverReceivedGiftForSex']) && trim($params['hasPatinetEverReceivedGiftForSex']) == 1){
		$hasPatinetEverReceivedGiftforSex = $params['hasPatinetEverReceivedGiftForSex'];
		$lastTimeOfReceivingGiftforSex = (isset($params['lastTimeOfReceivingGiftForSex']) && trim($params['lastTimeOfReceivingGiftForSex'])!= '')?$params['lastTimeOfReceivingGiftForSex']:NULL;
	    }else if(isset($params['hasPatinetEverReceivedGiftForSex']) && trim($params['hasPatinetEverReceivedGiftForSex'])!= ''){
		$hasPatinetEverReceivedGiftforSex = $params['hasPatinetEverReceivedGiftForSex'];
		$lastTimeOfReceivingGiftforSex = 'not applicable';
	    }
	    $noOfTimesBeenPregnant = NULL;
	    if(isset($params['noOfTimesBeenPregnantInNumbers']) && trim($params['noOfTimesBeenPregnantInNumbers'])!= ''){
		$noOfTimesBeenPregnant = '@'.$params['noOfTimesBeenPregnantInNumbers'];
	    }else if(isset($params['noOfTimesBeenPregnant']) && trim($params['noOfTimesBeenPregnant'])!= ''){
	       $noOfTimesBeenPregnant = $params['noOfTimesBeenPregnant'];
	    }
	    $noOfTimesCondomUsedBeforePregnancy = (isset($params['noOfTimesCondomUsedBeforePregnancy']) && trim($params['noOfTimesCondomUsedBeforePregnancy'])!= '')?$params['noOfTimesCondomUsedBeforePregnancy']:NULL;
	    $noOfTimesCondomUsedAfterPregnancy = (isset($params['noOfTimesCondomUsedAfterPregnancy']) && trim($params['noOfTimesCondomUsedAfterPregnancy'])!= '')?$params['noOfTimesCondomUsedAfterPregnancy']:NULL;
	    //patient disease symptoms/treatment details
	    $hasPatientHadPainInLowerAbdomen = NULL;
	    $hasPatientBeenTreatedForLowerAbdomenPain = 'not applicable';
	    if(isset($params['hasPatientHadPainInLowerAbdomen']) && trim($params['hasPatientHadPainInLowerAbdomen']) == 1){
		$hasPatientHadPainInLowerAbdomen = $params['hasPatientHadPainInLowerAbdomen'];
		$hasPatientBeenTreatedForLowerAbdomenPain = (isset($params['hasPatientBeenTreatedForLowerAbdomenPain']) && trim($params['hasPatientBeenTreatedForLowerAbdomenPain'])!= '')?$params['hasPatientBeenTreatedForLowerAbdomenPain']:NULL;
	    }else if(isset($params['hasPatientHadPainInLowerAbdomen']) && trim($params['hasPatientHadPainInLowerAbdomen'])!= ''){
		$hasPatientHadPainInLowerAbdomen = $params['hasPatientHadPainInLowerAbdomen'];
	    }
	    //patient alcohol/drug use
	    $patientHadDrinkWithAlcoholInLastSixMonths = NULL;
	    if(isset($params['patientHadDrinkWithAlcoholInLastSixMonthsInDays']) && trim($params['patientHadDrinkWithAlcoholInLastSixMonthsInDays'])!= ''){
                $patientHadDrinkWithAlcoholInLastSixMonths = '@'.$params['patientHadDrinkWithAlcoholInLastSixMonthsInDays'];
            }else if(isset($params['patientHadDrinkWithAlcoholInLastSixMonths']) && trim($params['patientHadDrinkWithAlcoholInLastSixMonths'])!= ''){
               $patientHadDrinkWithAlcoholInLastSixMonths = $params['patientHadDrinkWithAlcoholInLastSixMonths'];
            }
	    $hasPatientEverTriedRecreationalDrugs = NULL;
	    $hasPatientHadRecreationalDrugsInLastSixMonths = 'not applicable';
	    $recreationalDrugs = 'not applicable';
	    if(isset($params['hasPatientEverTriedRecreationalDrugs']) && trim($params['hasPatientEverTriedRecreationalDrugs']) == 1){
		$hasPatientEverTriedRecreationalDrugs = $params['hasPatientEverTriedRecreationalDrugs'];
		$hasPatientHadRecreationalDrugsInLastSixMonths = '';
		if(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths']) == 1){
		   $hasPatientHadRecreationalDrugsInLastSixMonths = $params['hasPatientHadRecreationalDrugsInLastSixMonths'];
		   $recreationalDrugs = (isset($params['recreationalDrugs']) && trim($params['recreationalDrugs'])!= '')?$params['recreationalDrugs']:'';
		}else if(isset($params['hasPatientHadRecreationalDrugsInLastSixMonths']) && trim($params['hasPatientHadRecreationalDrugsInLastSixMonths'])!= ''){
		  $hasPatientHadRecreationalDrugsInLastSixMonths = $params['hasPatientHadRecreationalDrugsInLastSixMonths'];   
		}
	        $patientHadRecreationalDrugsInLastSixMonths = array('has_had_in_last_six_months'=>$hasPatientHadRecreationalDrugsInLastSixMonths,'drugs'=>$recreationalDrugs);
	    }else if(isset($params['hasPatientEverTriedRecreationalDrugs']) && trim($params['hasPatientEverTriedRecreationalDrugs'])!= ''){
		$hasPatientEverTriedRecreationalDrugs = $params['hasPatientEverTriedRecreationalDrugs'];
	    }
	    //patient abused by
	    $everAbused = NULL;
	    $whoAbused = 'not applicable';
	    $patientAbusedByInNoofTimes = 'not applicable';
	    if(isset($params['hasPatientEverBeenAbusedBySomeone']) && trim($params['hasPatientEverBeenAbusedBySomeone']) == 1){
		$everAbused = $params['hasPatientEverBeenAbusedBySomeone'];
		$whoAbused = '';
		if(isset($params['patientAbusedBy']) && count($params['patientAbusedBy']) >0){
		    $whoAbused = implode(',',$params['patientAbusedBy']);
		}else if(isset($params['patientAbusedByOther']) && trim($params['patientAbusedByOther'])!= ''){
		    $whoAbused = '@'.$params['patientAbusedByOther'];
		}
		$patientAbusedByInNoofTimes = '';
		if(isset($params['patientAbusedBySomeoneInNoofTimes']) && trim($params['patientAbusedBySomeoneInNoofTimes'])!= ''){
		    $patientAbusedByInNoofTimes = '@'.$params['patientAbusedBySomeoneInNoofTimes'];
		}else if(isset($params['patientAbusedByNoofTimes']) && trim($params['patientAbusedByNoofTimes'])!= ''){
		    $patientAbusedByInNoofTimes = $params['patientAbusedByNoofTimes'];
		}
	    }else if(isset($params['hasPatientEverBeenAbusedBySomeone']) && trim($params['hasPatientEverBeenAbusedBySomeone'])!= ''){
		$everAbused = $params['hasPatientEverBeenAbusedBySomeone'];
	    }
	    $hasPatientEverBeenAbusedBySomeone = array('ever_abused'=>$everAbused,'who_abused'=>$whoAbused,'no_of_times'=>$patientAbusedByInNoofTimes);
	    //patient hurt by someone within last year
	    $everHurted = NULL;
	    $whoHurt = 'not applicable';
	    $patientHurtByInNoofTimes = 'not applicable';
	    if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) == 1){
		$everHurted = $params['hasPatientEverBeenHurtBySomeoneWithinLastYear'];
		$whoHurt = '';
		if(isset($params['patientHurtBySomeoneWithinLastYear']) && count($params['patientHurtBySomeoneWithinLastYear']) >0){
		    $whoHurt = implode(',',$params['patientHurtBySomeoneWithinLastYear']);
		}else if(isset($params['patientHurtByOther']) && trim($params['patientHurtByOther'])!= ''){
		    $whoHurt = '@'.$params['patientHurtByOther'];
		}
		$patientHurtByInNoofTimes = '';
		if(isset($params['patientHurtBySomeoneInNoofTimes']) && trim($params['patientHurtBySomeoneInNoofTimes'])!= ''){
		    $patientHurtByInNoofTimes = '@'.$params['patientHurtBySomeoneInNoofTimes'];
		}else if(isset($params['patientHurtByNoofTimes']) && trim($params['patientHurtByNoofTimes'])!= ''){
		    $patientHurtByInNoofTimes = $params['patientHurtByNoofTimes'];
		}
		//patient hurt by someone during pregnancy
		$everHurteddp = NULL;
		$whoHurtdp = 'not applicable';
		$patientHurtByDuringPregnancyInNoofTimes = 'not applicable';
		if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) == 1){
		    $everHurteddp = $params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'];
		    $whoHurtdp = '';
		    if(isset($params['patientHurtBySomeoneDuringPregnancy']) && count($params['patientHurtBySomeoneDuringPregnancy']) >0){
			$whoHurtdp = implode(',',$params['patientHurtBySomeoneDuringPregnancy']);
		    }else if(isset($params['patientHurtByOtherDuringPregnancy']) && trim($params['patientHurtByOtherDuringPregnancy'])!= ''){
			$whoHurtdp = '@'.$params['patientHurtByOtherDuringPregnancy'];
		    }
		    $patientHurtByDuringPregnancyInNoofTimes = '';
		    if(isset($params['patientHurtBySomeoneDuringPregnancyInNoofTimes']) && trim($params['patientHurtBySomeoneDuringPregnancyInNoofTimes'])!= ''){
			$patientHurtByDuringPregnancyInNoofTimes = '@'.$params['patientHurtBySomeoneDuringPregnancyInNoofTimes'];
		    }else if(isset($params['patientHurtByDuringPregnancyNoofTimes']) && trim($params['patientHurtByDuringPregnancyNoofTimes'])!= ''){
			$patientHurtByDuringPregnancyInNoofTimes = $params['patientHurtByDuringPregnancyNoofTimes'];
		    }
		}else if(isset($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy']) && trim($params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'])!= ''){
		    $everHurteddp = $params['hasPatientEverBeenHurtBySomeoneDuringPregnancy'];
		}
		$hasPatientHurtBySomeoneDuringPregnancy = array('ever_hurt_by_during_pregnancy'=>$everHurteddp,'who_hurt'=>$whoHurtdp,'no_of_times'=>$patientHurtByDuringPregnancyInNoofTimes);
	    }else if(isset($params['hasPatientEverBeenHurtBySomeoneWithinLastYear']) && trim($params['hasPatientEverBeenHurtBySomeoneWithinLastYear'])!= ''){
		$everHurted = $params['hasPatientEverBeenHurtBySomeoneWithinLastYear'];
		$hasPatientHurtBySomeoneDuringPregnancy = array('ever_hurt_by_during_pregnancy'=>'not applicable','who_hurt'=>'not applicable','no_of_times'=>'not applicable');
	    }
	    $hasPatientHurtBySomeoneWithinLastYear = array('ever_hurt'=>$everHurted,'who_hurt'=>$whoHurt,'no_of_times'=>$patientHurtByInNoofTimes);
	    //patient forced for sex within last year
	    $everForcedforSex = NULL;
	    $whoForced = 'not applicable';
	    $patientForcedforSexWithinLastYearInNoofTimes = 'not applicable';
	    if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear']) == 1){
		$everForcedforSex = $params['hasPatientEverBeenForcedForSexWithinLastYear'];
		$whoForced = '';
		if(isset($params['patientForcedForSexWithinLastYear']) && count($params['patientForcedForSexWithinLastYear']) >0){
		    $whoForced = implode(',',$params['patientForcedForSexWithinLastYear']);
		}else if(isset($params['patientForcedForSexByOtherWithinLastYear']) && trim($params['patientForcedForSexByOtherWithinLastYear'])!= ''){
		    $whoForced = '@'.$params['patientForcedForSexByOtherWithinLastYear'];
		}
		$patientForcedforSexWithinLastYearInNoofTimes = '';
		if(isset($params['patientForcedForSexInNoofTimes']) && trim($params['patientForcedForSexInNoofTimes'])!= ''){
		    $patientForcedforSexWithinLastYearInNoofTimes = '@'.$params['patientForcedForSexInNoofTimes'];
		}else if(isset($params['patientForcedForSexNoofTimes']) && trim($params['patientForcedForSexNoofTimes'])!= ''){
		    $patientForcedforSexWithinLastYearInNoofTimes = $params['patientForcedForSexNoofTimes'];
		}
	    }else if(isset($params['hasPatientEverBeenForcedForSexWithinLastYear']) && trim($params['hasPatientEverBeenForcedForSexWithinLastYear'])!= ''){
		$everForcedforSex = $params['hasPatientEverBeenForcedForSexWithinLastYear'];
	    }
	    $hasPatientForcedforSexWithinLastYear = array('ever_forced_for_sex'=>$everForcedforSex,'who_forced'=>$whoForced,'no_of_times'=>$patientForcedforSexWithinLastYearInNoofTimes);
	    $status = (base64_decode($params['status']) == 2)?base64_decode($params['status']):1;
            $data = array(
                    'anc'=>base64_decode($params['ancSite']),
                    'patient_barcode_id'=>$params['patientBarcodeId'],
                    'interviewer_name'=>$params['interviewerName'],
                    'anc_patient_id'=>$params['ancPatientId'],
                    'interview_date'=>$interviewDate,
		    //'has_participant_received_dreams_services'=>(isset($params['hasParticipantReceivedDreamsServices']) && trim($params['hasParticipantReceivedDreamsServices'])!= '')?$params['hasParticipantReceivedDreamsServices']:NULL,
                    'patient_occupation'=>$occupation,
                    'has_patient_ever_attended_school'=>$hasPatientEverAttendedSchool,
		    'patient_degree'=>$degree,
		    'patient_ever_been_married'=>$patientEverBeenMarried,
		    'age_at_first_marriage'=>$ageAtFirstMarriage,
		    'patient_ever_been_widowed'=>$patientEverBeenWidowed,
		    'current_marital_status'=>$currentMaritalStatus,
		    'has_patient_ever_been_tested_for_HIV'=>$hasPatientEverBeenTestedforHIV,
		    'time_of_last_HIV_test'=>$timeofMostRecentHIVTest,
		    'last_HIV_test_status'=>$resultofMostRecentHIVTest,
		    'age_at_very_first_sex'=>$ageAtVeryFirstSex,
		    'reason_for_very_first_sex'=>(isset($params['reasonForVeryFirstSex']) && trim($params['reasonForVeryFirstSex'])!= '')?$params['reasonForVeryFirstSex']:NULL,
		    'no_of_sexual_partners'=>$noOfSexualPartners,
		    'no_of_sexual_partners_in_last_six_months'=>$noOfSexualPartnersInLastSixMonths,
		    'partner_HIV_test_status'=>$partnerHIVTestStatus,
		    'age_of_main_sexual_partner_at_last_birthday'=>$ageofMainSexualPartneratLastBirthday,
		    'age_diff_of_main_sexual_partner'=>$ageDiffofMainSexualPartner,
		    'is_partner_circumcised'=>$isPartnerCircumcised,
		    'circumcision'=>$circumcision,
		    'has_patient_ever_received_gift_for_sex'=>$hasPatinetEverReceivedGiftforSex,
		    'last_time_of_receiving_gift_for_sex'=>$lastTimeOfReceivingGiftforSex,
		    'no_of_times_been_pregnant'=>$noOfTimesBeenPregnant,
		    'no_of_times_condom_used_before_pregnancy'=>$noOfTimesCondomUsedBeforePregnancy,
		    'no_of_times_condom_used_after_pregnancy'=>$noOfTimesCondomUsedAfterPregnancy,
		    'has_patient_had_pain_in_lower_abdomen'=>$hasPatientHadPainInLowerAbdomen,
		    'has_patient_been_treated_for_lower_abdomen_pain'=>$hasPatientBeenTreatedForLowerAbdomenPain,
		    'has_patient_ever_been_treated_for_syphilis'=>(isset($params['hasPatientEverBeenTreatedForSyphilis']) && trim($params['hasPatientEverBeenTreatedForSyphilis'])!= '')?$params['hasPatientEverBeenTreatedForSyphilis']:NULL,
		    'has_patient_ever_received_vaccine_to_prevent_cervical_cancer'=>(isset($params['hasPatientEverReceivedVaccineToPreventCervicalCancer']) && trim($params['hasPatientEverReceivedVaccineToPreventCervicalCancer'])!= '')?$params['hasPatientEverReceivedVaccineToPreventCervicalCancer']:NULL,
		    'patient_had_drink_with_alcohol_in_last_six_months'=>$patientHadDrinkWithAlcoholInLastSixMonths,
		    'has_patient_often_had_4rmore_drinks_with_alcohol_on_one_occasion'=>(isset($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']) && trim($params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion'])!= '')?$params['hasPatientOftenHad4rmoreDrinksWithAlcoholOnOneOccasion']:NULL,
		    'has_patient_ever_tried_recreational_drugs'=>$hasPatientEverTriedRecreationalDrugs,
		    'has_patient_had_recreational_drugs_in_last_six_months'=>(isset($patientHadRecreationalDrugsInLastSixMonths))?json_encode($patientHadRecreationalDrugsInLastSixMonths):'',
		    'has_patient_ever_been_abused_by_someone'=>json_encode($hasPatientEverBeenAbusedBySomeone),
		    'has_patient_ever_been_hurt_by_someone_within_last_year'=>json_encode($hasPatientHurtBySomeoneWithinLastYear),
		    'has_patient_ever_been_hurt_by_someone_during_pregnancy'=>json_encode($hasPatientHurtBySomeoneDuringPregnancy),
		    'has_patient_ever_been_forced_for_sex_within_last_year'=>json_encode($hasPatientForcedforSexWithinLastYear),
		    'is_patient_afraid_of_anyone'=>(isset($params['isPatientAfraidOfAnyone']) && trim($params['isPatientAfraidOfAnyone'])!= '')?$params['isPatientAfraidOfAnyone']:NULL,
                    'comment'=>$params['comment'],
		    'status'=>$status,
                    'updated_on'=>$common->getDateTime(),
                    'updated_by'=>$loginContainer->userId
                );
            $this->update($data,array('assessment_id'=>$assessmentId));
	    //rapid recency result section
	    $ancRapidRecencyDb = new AncRapidRecencyTable($dbAdapter);
	    $controlVal = '';
	    $HIVDiagnosticVal = '';
	    $recencyVal = '';
	    if(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])== 'done'){
		$controlVal = (isset($params['rrrControl']) && trim($params['rrrControl'])!= '')?$params['rrrControl']:NULL;
		$HIVDiagnosticVal = (isset($params['rrrHIVDiagnostic']) && trim($params['rrrHIVDiagnostic'])!= '')?$params['rrrHIVDiagnostic']:NULL;
		if($HIVDiagnosticVal!= 'negative'){
		   $recencyVal = (isset($params['rrrRecency']) && trim($params['rrrRecency'])!= '')?$params['rrrRecency']:NULL;
		}
	    }
	    $rrData = array(
		        'assessment_id'=>$assessmentId,
			'has_patient_had_rapid_recency_test'=>(isset($params['hasPatientHadRapidRecencyTest']) && trim($params['hasPatientHadRapidRecencyTest'])!= '')?$params['hasPatientHadRapidRecencyTest']:NULL,
			'control_line'=>$controlVal,
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
    
    public function lockRiskAssessmentDetails($params){
        $loginContainer = new Container('user');
	$common = new CommonService();
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$data = array(
	    'status'=>2,
	    'locked_on'=>$common->getDateTime(),
	    'locked_by'=>(isset($loginContainer->userId))?$loginContainer->userId:NULL
	);
      return $this->update($data,array('assessment_id'=>base64_decode($params['assessment'])));
    }
    
    public function unlockRiskAssessmentDetails($params){
        $loginContainer = new Container('user');
	$common = new CommonService();
	$dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
	$data = array(
	    'status'=>3,
	    'unlocked_on'=>$common->getDateTime(),
	    'unlocked_by'=>(isset($loginContainer->userId))?$loginContainer->userId:NULL
	);
      return $this->update($data,array('assessment_id'=>base64_decode($params['assessment'])));
    }
    
    public function fetchBehaviourDataReportingWeeklyDetails($params){
	$common = new CommonService();
	$dbAdapter = $this->adapter;
	$sql = new Sql($dbAdapter);
	$result = array();
	if((isset($params['fromDate']) && trim($params['fromDate'])!= '') && (isset($params['toDate']) && trim($params['toDate'])!= '')){
	    $fromDateArray = explode("/",$params['fromDate']);
	    $toDateArray = explode("/",$params['toDate']);
	    $start = strtotime($fromDateArray[1].'-'.date('m', strtotime($fromDateArray[0])));
	    $end = strtotime($toDateArray[1].'-'.date('m', strtotime($toDateArray[0])));
	}else{
	    $start = strtotime(date("Y", strtotime("-1 year")).'-'.date('m', strtotime('+1 month', strtotime('-1 year'))));
	    $end = strtotime(date('Y').'-'.date('m'));
	}
	$j=0;
	$d =0;
	while($start <= $end){
	    $month = date('m', $start);$year = date('Y', $start);$monthYearFormat = date("M-Y", $start);
            $query = $sql->select()->from(array('r_a'=>'clinic_risk_assessment'))
                          ->columns(
                                  array(
                                        'total'=>new \Zend\Db\Sql\Expression("SUM(IF(r_a.status = 1 OR r_a.status = 2 OR r_a.status = 3, 1,0))"),
					'startdayofweek'=>new \Zend\Db\Sql\Expression("DATE_ADD(interview_date, INTERVAL(1-DAYOFWEEK(interview_date)) DAY)"),
					'enddayofweek'=>new \Zend\Db\Sql\Expression("DATE_ADD(interview_date, INTERVAL(7-DAYOFWEEK(interview_date)) DAY)")
                                        )
                                  )
			  ->where("Month(interview_date)='".$month."' AND Year(interview_date)='".$year."'")
			  ->group('startdayofweek');
	    $queryStr = $sql->getSqlStringForSqlObject($query);
	    $rows = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	    if(isset($rows) && count($rows) > 0){
		foreach($rows as $row){
		    if(isset($row['startdayofweek']) && $row['startdayofweek']!= null && trim($row['startdayofweek'])!= ''){
		      $result['week'][$d] = $common->humanDateFormat($row['startdayofweek']).' to '.$common->humanDateFormat($row['enddayofweek']);
		      $result['total'][$d] = $row['total'];
		      $d++;
		    }
		}
	    }
	 $start = strtotime("+1 month", $start);
         $j++;
	}
      return $result;
    }
}