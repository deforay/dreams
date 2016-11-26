<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class GlobalConfigTable extends AbstractTableGateway {

    protected $table = 'global_config';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}