<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class USSDImportStatusTable extends AbstractTableGateway {

    protected $table = 'ussd_import_status';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}