<?php

namespace Ctrl\Common\Criteria;

use Ctrl\Common\Tools\ArrayHelper;
use Ctrl\Common\Tools\StringHelper;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class DoctrineResolver implements ResolverInterface
{
    /**
     * @var string
     */
    protected $rootAlias;

    public function __construct($rootAlias)
    {
        $this->rootAlias = $rootAlias;
    }

    /**
     * @return string
     */
    public function getRootAlias()
    {
        return $this->rootAlias;
    }

    /**
     * @param string $rootAlias
     * @return $this
     */
    public function setRootAlias($rootAlias)
    {
        $this->rootAlias = $rootAlias;
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $criteria
     * @return $this
     */
    public function applyCriteria($queryBuilder, array $criteria = array())
    {
        if (!count($criteria)) {
            return $this;
        }

        $criteria = $this->resolve($criteria);

        foreach ($criteria['joins'] as $join => $alias) {
            $queryBuilder->join($join, $alias);
        }

        $queryBuilder->where($this->createQueryExpression($queryBuilder, $criteria['expressions']));

        return $this;
    }

    public function createQueryExpression(QueryBuilder $qb, array $graph = array())
    {
        $expr       = null;
        $logical    = null;
        if (array_key_exists(self::T_AND, $graph)) {
            $logical = self::T_AND;
            $expr = $qb->expr()->andX();
        } else if (array_key_exists(self::T_OR, $graph)) {
            $logical = self::T_OR;
            $expr = $qb->expr()->orX();
        } else {
            throw new \InvalidArgumentException('invalid graph given');
        }

        $graph          = $graph[$logical];
        $paramIndex     = count($qb->getParameters()) + 1;
        foreach ($graph as $spec => $value) {
            if ($spec !== self::T_AND && $spec !== self::T_OR) {
                list($field, $equation) = explode(' ', $spec, 2);
                switch ($equation) {
                    case 'IS NULL':
                        $func = 'isNull';
                        break;
                    case 'IS NOT NULL':
                        $func = 'isNotNull';
                        break;
                    default:
                        list($comp, $valueSpec) = explode(' ', $equation, 2);
                        switch ($comp) {
                            case '=':
                                $func = 'eq';
                                break;
                            case '<>':
                                $func = 'neq';
                                break;
                            case '<':
                                $func = 'lt';
                                break;
                            case '>':
                                $func = 'gt';
                                break;
                            case '<=':
                                $func = 'lte';
                                break;
                            case '>=':
                                $func = 'gte';
                                break;
                            case 'IN':
                                $func = 'in';
                                break;
                            case 'NOT IN':
                                $func = 'notIn';
                                break;
                            case 'LIKE':
                                $func = 'like';
                                break;
                            case 'NOT LIKE':
                                $func = 'notLike';
                                break;
                            default:
                                throw new \InvalidArgumentException(sprintf('unknown comparison: %s', $comp));
                        }
                        if ($valueSpec === '?') {
                            $expr->add($qb->expr()->$func($field, '?' . $paramIndex));
                            $qb->setParameter($paramIndex, $value);
                        } else if (strpos($valueSpec, ':') === 0) {
                            $expr->add($qb->expr()->$func($field, $valueSpec));
                            $qb->setParameter(substr($valueSpec, 1), $value);
                        }
                        $paramIndex++;
                        continue 2;
                }
                $expr->add($qb->expr()->$func($field));
            } else {
                $expr->add($this->createQueryExpression($qb, [$spec => $value]));
            }
        }

        return $expr;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $orderBy
     * @return $this
     */
    public function applyOrderBy($queryBuilder, array $orderBy = array())
    {
        $joins = array();
        $orderConfig = array();
        foreach ($orderBy as $key => $val) {
            $root = $this->getRootAlias();
            $field = is_string($key) ? $key: $val;
            $order = is_string($key) ? $val: 'ASC';
            if (strpos($field, $root . '.') !== 0) $field = $root . '.' . $field;

            $config = $this->getFieldConfig($field, $this->getRootAlias());
            $path = $config['path'];
            array_pop($path);
            $joins[] = $path;
            $orderConfig[$config['field']] =  $order;
        }

        $joins = $this->mergeJoinPaths($joins);

        foreach ($joins         as $join => $alias)         $queryBuilder->join($join, $alias);
        foreach ($orderConfig   as $sortField => $order)    $queryBuilder->addOrderBy($sortField, $order);

        return $this;
    }

    public function tokenize($expressions)
    {
        $tokens = array();

        foreach ($expressions as $expr) {
            if (is_array($expr)) {
                $tokens[][self::T_COMPOUND] = $this->tokenize($expr);
                continue;
            }

            $expr = trim($expr);
            $words = explode(' ', $expr);
            $subExpr = [];

            foreach ($words as $w) {
                $wUpper = strtoupper($w);
                if ($wUpper === self::T_AND || $wUpper === self::T_OR) {
                    if (count($subExpr)) {
                        $tokens[][self::T_EXPR] = implode(' ', $subExpr);
                        $subExpr = [];
                    }
                    if ($wUpper === self::T_AND) {
                        $tokens[][self::T_AND] = self::T_AND;
                    }
                    else if ($wUpper === self::T_OR) {
                        $tokens[][self::T_OR] = self::T_OR;
                    }
                } else {
                    $subExpr[] = $w;
                }
            }
            if (count($subExpr)) {
                $tokens[][self::T_EXPR] = implode(' ', $subExpr);
            }
        }

        return $tokens;
    }

    public function createGraph($expression, $isTokens = false)
    {
        if ($isTokens) {
            $parts = $expression;
        } else {
            $parts = $this->tokenize(StringHelper::bracesToArray($expression));
        }

        $logical = self::T_AND;
        $result = [];
        $usedLogical = null;
        foreach ($parts as $part) {
            foreach ($part as $type => $val) {
                if ($type === self::T_EXPR) {
                    $result[] = $val;
                } else if ($type === self::T_COMPOUND) {
                    $result[] = $this->createGraph($val, true);
                }
                if ($type === self::T_OR) {
                    if ($usedLogical === self::T_AND) {
                        throw new \Exception('can not use AND and OR in the same part of condition without braces');
                    }
                    $logical = self::T_OR;
                    $usedLogical = self::T_OR;
                }
                if ($type === self::T_AND) {
                    if ($usedLogical === self::T_OR) {
                        throw new \Exception('can not use AND and OR in the same part of condition without braces');
                    }
                    $usedLogical = self::T_AND;
                }
            }
        }

        return [
            $logical => $result
        ];
    }

    protected function parseGraph(array $graph = array(), array &$values, &$currentKey)
    {
        $expressions = null;
        $logical = null;
        if (array_key_exists(self::T_AND, $graph)) {
            $logical = self::T_AND;
        } else if (array_key_exists(self::T_OR, $graph)) {
            $logical = self::T_OR;
        } else {
            throw new \InvalidArgumentException('invalid graph given');
        }

        $expressions    = $graph[$logical];
        $parsed         = [];
        $joins          = [];
        foreach ($expressions as $key => $expr) {
            if (is_array($expr)) {
                $subset         = $this->parseGraph($expr, $values, $currentKey);
                $parsed[]       = $subset['expressions'];
                $joins          = array_merge($subset['joins']);
            } else {
                $config = $this->getFieldConfig($expr, $this->getRootAlias());
                if ($config['join']) {
                    $joins[] = $config['join'];
                }
                $val = null;
                if ($config['requires_value']) {
                    if ($config['has_named_param']) {
                        $val = $values[$config['param_name']];
                    } else {
                        if (!array_key_exists($currentKey, $values)) {
                            throw new \InvalidArgumentException(sprintf('no value found for expression: %s', $config['expression']));
                        }
                        $val = $values[$currentKey];
                        $currentKey++;
                    }
                }
                $parsed[$config['expression']] = $val;
            }
        }

        return [
            'joins'         => $joins,
            'expressions'   => [$logical => $parsed],
        ];
    }

    protected function mergeJoinPaths($joins)
    {
        $merged = array();
        foreach ($joins as $path) {
            if (empty($path)) return $merged;

            $previous = $path[0];
            for ($i = 1; $i < count($path); $i++) {
                $current = $path[$i];
                $merged[$previous . '.' . $current] = $current;
                $previous = $current;
            }
        }

        return $merged;
    }

    /**
     * @param string $expr
     * @param string $root
     * @return array
     */
    protected function getFieldConfig($expr, $root)
    {
        if (strpos($expr, ' ') === false) {
            $field = $expr;
            $condition = '=';
        } else {
            $exprParts = explode(' ', $expr, 2);
            $field = $exprParts[0];
            $condition = $exprParts[1];
        }

        if (strpos($field, $root.'.') !== 0) {
            $field = $root . '.' . $field;
        }

        $path = explode('.', $field);
        $pathLen = count($path);
        $alias = end($path);
        $parent = $path[0];
        $join = null;

        if ($pathLen > 2) {
            $parent = $path[$pathLen - 2];
            $join = array_slice($path, 0, $pathLen - 1);
        }

        $comp = '=';
        $requiresValue = false;
        $valueSpec = '';
        $hasNamedParam = false;
        $paramName = null;
        $conditionUpper = strtoupper($condition);

        if ($conditionUpper === 'IS NULL') {
            $comp = 'IS NULL';
        } else if ($conditionUpper === 'IS NOT NULL') {
            $comp = 'IS NOT NULL';
        } else {
            foreach (array('IN', 'NOT IN', '>=', '<=', '<', '>', '=') as $c) {
                if (strpos($conditionUpper, $c) === 0) {
                    $comp = $c;
                    $valueSpec = trim(substr($condition, strlen($c)));
                    $requiresValue = true;
                    break;
                }
            }
        }
        if (strpos($valueSpec, ':') === 0) {
            $hasNamedParam = true;
            $paramName = trim($valueSpec, ':');
        }
        if ($valueSpec !== '') {
            $valueSpec = ' ' . $valueSpec;
            $requiresValue = $hasNamedParam || strpos($valueSpec, '?') !== false;
        } else {
            if ($requiresValue) {
                $valueSpec = ' ?';
            }
        }

        return array(
            'comparison' => $comp,
            'alias' => $alias,
            'parent' => $parent,
            'path' => $path,
            'join' => $join,
            'requires_value' => $requiresValue,
            'has_named_param' => $hasNamedParam,
            'param_name' => $paramName,
            'expression' => $parent . '.' . $alias . ' ' . $comp . $valueSpec,
            'field' => $parent . '.' . $alias,
        );
    }

    /**
     * @param array|string $criteria
     * @return array
     */
    public function resolve($criteria)
    {
        $single = false;
        if (!is_array($criteria)) {
            $criteria = array($criteria);
            $single = true;
        }

        $joins = array();
        $expressions = array();

        foreach ($criteria as $key => $val) {
            $hasValue = is_string($key);
            $expression = $hasValue ? $key: $val;
            $values = $hasValue ? (array)$val: array();
            $currentValKey = 0;

            if (!$hasValue && strpos($expression, ' ') === false) {
                if (strpos($expression, $this->getRootAlias() . '.') !== 0) {
                    $expression = $this->getRootAlias() . '.' . $expression;
                }
                $joins[] = explode('.', $expression);
            } else {
                $graph = $this->createGraph($expression);
                $result = $this->parseGraph($graph, $values, $currentValKey);
                $joins = array_merge($result['joins'], $joins);
                $expressions[] = $result['expressions'];
            }

        }

        $joins = $this->mergeJoinPaths($joins);
        if ($single) {
            $expressions = $expressions[0];
        } else {
            $expressions = count($expressions) ? call_user_func_array('array_merge_recursive', $expressions): [];
            if (array_key_exists(self::T_AND, $expressions) && array_key_exists(self::T_OR, $expressions) && !array_key_exists(self::T_OR, $expressions[self::T_AND])) {
                $expressions[self::T_AND][self::T_OR] = $expressions[self::T_OR];
                unset($expressions[self::T_OR]);
            }
        }

        return array(
            'joins' => $joins,
            'expressions' => $expressions,
        );
    }
}
