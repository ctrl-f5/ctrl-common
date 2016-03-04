<?php

namespace Ctrl\Common\Criteria\Adapter;

use Ctrl\Common\Criteria\ResolverInterface;

interface ResolverAdapterInterface
{
    /**
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver);

    /**
     * @param mixed $query
     * @param array $criteria
     * @return $this
     */
    public function applyCriteria($query, array $criteria = array());

    /**
     * @param mixed $query
     * @param array $orderBy
     * @return $this
     */
    public function applyOrderBy($query, array $orderBy = array());
}
