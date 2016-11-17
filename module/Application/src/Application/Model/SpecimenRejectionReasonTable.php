<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class SpecimenRejectionReasonTable extends AbstractTableGateway {

    protected $table = 'specimen_rejection_reason';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function fetchActiveRejectionReasons(){
        return $this->select(array('status'=>'active'));
    }
}