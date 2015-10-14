<?php

namespace Ctrl\Common\Criteria;

interface ResolverInterface
{
    const T_EXPR        = 'expr';
    const T_AND         = 'AND';
    const T_OR          = 'OR';
    const T_COMPOUND    = 'comp';

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
     * @param $expressions
     * @return mixed
     */
    public function tokenize(array $expressions);

    /**
     * @param array|string $tokens
     * @return array
     */
    public function createGraph($tokens);

    /**
     * @param array|string $criteria
     * @param string $type self::T_AND or self::T_OR
     * @return array [ joins => [], expressions => [] ]
     */
    public function resolveCriteria($criteria, $type = self::T_AND);

    /**
     * @param $field
     * @return mixed
     */
    public function resolveField($field);
}
