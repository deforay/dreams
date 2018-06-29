<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class DataCollectionEventLogAdminTable extends AbstractTableGateway {

    protected $table = 'data_collection_event_log_admin';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}