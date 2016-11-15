<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;


class FacilityTable extends AbstractTableGateway {

    protected $table = 'facility';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}