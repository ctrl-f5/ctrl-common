<?php

namespace Ctrl\Common\Test\EntityService\Criteria\Mock;

use Doctrine\ORM\Query\Expr;

class QueryBuilder extends \Doctrine\ORM\QueryBuilder
{
    protected $params = array();

    public function __construct()
    {

    }

    public function setParameter($name, $val, $type = null)
    {
        $this->params[$name] = $val;
    }

    public function getParameters()
    {
        return $this->params;
    }

    public function expr()
    {
        return new Expr();
    }
}
