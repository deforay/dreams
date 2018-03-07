<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class USSDSurveyTable extends AbstractTableGateway {

    protected $table = 'ussd_survey';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}