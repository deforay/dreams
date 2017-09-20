<?php
namespace Application\Service;

use Zend\Session\Container;
use Exception;
use Zend\Db\Sql\Sql;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime as Mime;

class CommonService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public static function generateRandomString($length = 8, $seeds = 'alphanum') {
        // Possible seeds
        $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
        $seedings['numeric'] = '0123456789';
        $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
        $seedings['hexidec'] = '0123456789abcdef';

        // Choose seed
        if (isset($seedings[$seeds])) {
            $seeds = $seedings[$seeds];
        }

        // Seed generator
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float) $sec + ((float) $usec * 100000);
        mt_srand($seed);

        // Generate
        $str = '';
        $seeds_count = strlen($seeds);

        for ($i = 0; $length > $i; $i++) {
            $str .= $seeds{mt_rand(0, $seeds_count - 1)};
        }

        return $str;
    }

    public function checkFieldValidations($params) {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $tableName = $params['tableName'];
        $fieldName = $params['fieldName'];
        $value = trim($params['value']);
        $fnct = $params['fnct'];
        try {
            $sql = new Sql($adapter);
            if ($fnct == '' || $fnct == 'null') {
                $select = $sql->select()->from($tableName)->where(array($fieldName => $value));
                //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."'");
                $statement = $sql->prepareStatementForSqlObject($select);
                $result = $statement->execute();
                $data = count($result);
            } else {
                $table = explode("##", $fnct);
                if ($fieldName == 'password') {
                    //Password encrypted
                    $config = new \Zend\Config\Reader\Ini();
                    $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                    $password = sha1($value . $configResult["password"]["salt"]);
                    //$password = $value;
                    $select = $sql->select()->from($tableName)->where(array($fieldName=>$password,$table[0]=>$table[1]));
                    $statement = $sql->prepareStatementForSqlObject($select);
                    $result = $statement->execute();
                    $data = count($result);
                }else{
                    // first trying $table[1] without quotes. If this does not work, then in catch we try with single quotes
                    //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."' and ".$table[0]."!=".$table[1] );
                    $select = $sql->select()->from($tableName)->where(array("$fieldName='$value'", $table[0] . "!=" . "'$table[1]'"));
                    $statement = $sql->prepareStatementForSqlObject($select);
                    $result = $statement->execute();
                    $data = count($result);
                }
            }
            return $data;
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function dateFormat($date) {
        if (!isset($date) || $date == null || $date == "" || $date == "0000-00-00") {
            return "0000-00-00";
        } else {
            $dateArray = explode('/', $date);
            if (sizeof($dateArray) == 0) {
                return;
            }
            $newDate = $dateArray[2] . "-";

            $mon = $dateArray[1];

            if (strlen($mon) == 1) {
                $mon = "0" . $mon;
            }
            return $newDate .= $mon . "-" . $dateArray[0];
        }
    }

    public function humanDateFormat($date) {
        if ($date == null || $date == "" || $date == "0000-00-00") {
            return "";
        } else {
            $dateArray = explode('-', $date);
            $newDate = $dateArray[2] . "-";
            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            return $newDate .=  $monthsArray[($dateArray[1]-1)] . "-" . $dateArray[0];
        }
    }

    public function viewDateFormat($date) {
        if ($date == null || $date == "" || $date == "0000-00-00") {
            return "";
        } else {
            $dateArray = explode('-', $date);
            $newDate = $dateArray[2] . "/";

            return $newDate .= $dateArray[1] . "/" . $dateArray[0];
        }
    }
    
    public static function getDateTime($timezone = 'Asia/Calcutta') {
        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone($timezone));
        return $date->format('Y-m-d H:i:s');
    }
    
    public static function getDate($timezone = 'Asia/Calcutta') {
           $date = new \DateTime(date('Y-m-d'), new \DateTimeZone($timezone));
           return $date->format('Y-m-d');
    }
    
    public function humanMonthlyDateFormat($date) {
        if ($date == null || $date == "" || $date == "0000-00-00" || $date == "0000-00-00 00:00:00") {
            return "";
        } else {
            $dateArray = explode('-', $date);
            $newDate =  "";

            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $mon = $monthsArray[$dateArray[1]*1];

            return $newDate .= $mon . " " . $dateArray[0];
        }
    }
    
    public function getActiveRejectionReasons(){
        $specimenRejectionReasonDb = $this->sm->get('SpecimenRejectionReasonTable');
        return $specimenRejectionReasonDb->fetchActiveRejectionReasons();
    }
    
    public function getAllTestStatus(){
        $testStatusDb = $this->sm->get('TestStatusTable');
        return $testStatusDb->fetchAllTestStatus();
    }
    
    public function sendResultMail($params){
        $alertContainer = new Container('alert');
        try {
            $config = new \Zend\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            // Setup SMTP transport using LOGIN authentication
            $transport = new SmtpTransport();
            $options = new SmtpOptions(array(
                'host' => $configResult["email"]["host"],
                'port' => $configResult["email"]["config"]["port"],
                'connection_class' => $configResult["email"]["config"]["auth"],
                'connection_config' => array(
                    'username' => $configResult["email"]["config"]["username"],
                    'password' => $configResult["email"]["config"]["password"],
                    'ssl' => $configResult["email"]["config"]["ssl"]
                )
            ));
            $transport->setOptions($options);
            //get (To) email id
            $ancQuery = $sql->select()->from(array('anc' => 'anc_site'))
                            ->where(array('anc.anc_site_id'=>base64_decode($params['anc'])));
            $ancQueryStr = $sql->getSqlStringForSqlObject($ancQuery);
            $ancResult = $dbAdapter->query($ancQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if(trim($ancResult->email)!= ''){
                $fromEmail = $configResult["email"]["config"]["username"];
                $fromFullName = $configResult["email"]["config"]["username"];
                $subject = ucwords($params['subject']);
    
                $html = new MimePart(ucfirst($params['message']));
                $html->type = "text/html";
    
                $attachment = new MimePart(fopen(TEMP_UPLOAD_PATH. DIRECTORY_SEPARATOR .$params['pdfFile'],'r'));
                $attachment->type = 'application/pdf';
                $attachment->encoding    = Mime::ENCODING_BASE64;
                $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                $attachment->filename = 'LABORATORY-RECENCY-TEST-RESULT-'.date('d-M-Y-H-i-s');
                $body = new MimeMessage();
                $body->setParts(array($html,$attachment));
    
                $resultMail = new Mail\Message();
                $resultMail->setBody($body);
                $resultMail->addFrom($fromEmail, $fromFullName);
                $resultMail->addReplyTo($fromEmail, $fromFullName);
    
                $toArray = explode(",", $ancResult->email);
                foreach ($toArray as $toId) {
                    if (trim($toId) != '') {
                        $resultMail->addTo($toId);
                    }
                }
                //if (isset($params['cc']) && trim($params['cc']) != "") {
                //    $ccArray = explode(",", $params['cc']);
                //    foreach ($ccArray as $ccId) {
                //        if (trim($ccId) != '') {
                //            $resultMail->addCc($ccId);
                //        }
                //    }
                //}
                //if (isset($params['bcc']) && trim($params['bcc']) != "") {
                //    $bccArray = explode(",", $params['bcc']);
                //    foreach ($bccArray as $bccId) {
                //        if (trim($bccId) != '') {
                //            $resultMail->addBcc($bccId);
                //        }
                //    }
                //}
                $resultMail->setSubject($subject);
                $transport->send($resultMail);
                //update mail sent status
                $dataCollectionDb = $this->sm->get('DataCollectionTable');
                for($i=0;$i<count($params['dataCollection']);$i++){
                    $dataCollectionDb->update(array('result_mail_sent'=>'yes'),array('data_collection_id'=>base64_decode($params['dataCollection'][$i])));
                }
                //remove file from temporary
                $this->removeDirectory(TEMP_UPLOAD_PATH. DIRECTORY_SEPARATOR .$params['pdfFile']);
                $alertContainer->msg = 'Laboratory recency test result mailed successfully.';
              return true;
            }else{
               //remove file from temporary
                $this->removeDirectory(TEMP_UPLOAD_PATH. DIRECTORY_SEPARATOR .$params['pdfFile']);
                $alertContainer->msg = 'Invalid (To) email id';
              return false;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            error_log('Oops! Something went wrong in mailer.');
        }
    }
    
    function removeDirectory($dirname) {
        // Sanity check
        if (!file_exists($dirname)) {
            return false;
        }

        // Simple delete for a file
        if (is_file($dirname) || is_link($dirname)) {
            return unlink($dirname);
        }

        // Loop through the folder
        $dir = dir($dirname);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Recurse
            $this->removeDirectory($dirname . DIRECTORY_SEPARATOR . $entry);
        }

        // Clean up
        $dir->close();
        return rmdir($dirname);
    }
    
    public function dateRangeFormat($date) {
        if (!isset($date) || $date == null || $date == "" || $date == "0000-00-00") {
            return "";
        } else {
            $dateArray = explode('-', $date);
            if(sizeof($dateArray) == 0 ){
                return;
            }
            $newDate = $dateArray[2] . "-";

            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $mon = 1;
            $mon += array_search(ucfirst($dateArray[1]), $monthsArray);

            if (strlen($mon) == 1) {
                $mon = "0" . $mon;
            }
            return $newDate .= $mon . "-" . $dateArray[0];
        }
    }
}

?>
