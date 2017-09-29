<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class AncRapidRecencyTable extends AbstractTableGateway {

    protected $table = 'anc_rapid_recency';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}