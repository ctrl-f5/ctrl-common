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
     * @param string $expression
     * @param bool $isTokens
     * @return array
     */
    public function createGraph($expression, $isTokens = false);

    /**
     * @param array|string $criteria
     * @param string $type self::T_AND or self::T_OR
     * @return array [ joins => [], expressions => [] ]
     */
    public function resolve($criteria, $type = self::T_AND);
}
