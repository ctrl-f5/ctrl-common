<?php

namespace Ctrl\Common\EntityService\Finder\Doctrine;

use Doctrine\ORM\QueryBuilder;

interface QueryBuilderResultInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder);

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();
}
