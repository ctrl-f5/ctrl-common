<?php

namespace Ctrl\Common\Criteria;

interface ResolverInterface
{
    public function __construct($rootAlias);

    /**
     * @return string
     */
    public function getRootAlias();

    /**
     * @param string $rootAlias
     * @return $this
     */
    public function setRootAlias($rootAlias);

    /**
     * Apply Filter criteria
     *
     * @param mixed $subject
     * @param array $criteria
     * @return $this
     */
    public function applyCriteria($subject, array $criteria = array());

    /**
     * Apply Ordering criteria
     *
     * @param mixed $subject
     * @param array $orderBy
     * @return $this
     */
    public function applyOrderBy($subject, array $orderBy = array());

    /**
     * Unpack an array of criteria to single expressions
     *
     * @param array|string $criteria
     * @return array
     */
    public function unpack($criteria);
}