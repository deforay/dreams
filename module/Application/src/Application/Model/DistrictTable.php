<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class DistrictTable extends AbstractTableGateway {

    protected $table = 'district';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}