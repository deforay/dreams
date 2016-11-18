<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class DataCollectionEventLogTable extends AbstractTableGateway {

    protected $table = 'data_collection_event_log';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}