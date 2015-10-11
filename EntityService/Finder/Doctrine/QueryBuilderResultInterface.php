<?php

namespace EntityService\Finder;

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
