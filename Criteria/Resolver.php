<?php

namespace Ctrl\Common\Criteria;

use Ctrl\Common\Tools\StringHelper;
use Doctrine\ORM\Query\Expr;
use Ctrl\Common\Criteria\InvalidCriteriaException;

class Resolver implements ResolverInterface
{
    /**
     * @var string
     */
    protected $rootAlias;

    /**
     * @param string $rootAlias
     */
    public function __construct($rootAlias)
    {
        $this->setRootAlias($rootAlias);
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
     * @param array $expressions
     * @return array
     */
    public function tokenize(array $expressions)
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
                    elseif ($wUpper === self::T_OR) {
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

    /**
     * @param array|string $tokens
     * @return array
     * @throws InvalidCriteriaException
     */
    public function createGraph($tokens)
    {
        if (is_string($tokens)) {
            $tokens = $this->tokenize(StringHelper::bracesToArray($tokens));
        }

        $logical = self::T_AND;
        $result = [];
        foreach ($tokens as $part) {
            $type = key($part);

            if ($type === self::T_AND || $type === self::T_OR) {
                if ($logical !== $type && count($result) > 1) {
                    throw new InvalidCriteriaException('can not use AND and OR in the same part of condition without braces');
                }
                $logical = $type;
            }

            if ($type === self::T_EXPR) {
                $result[] = $part;
            }
            if ($type === self::T_COMPOUND) {
                $result[] = $this->parseCompoundToken($part);;
            }
        }

        return [
            $logical => $result
        ];
    }

    protected function parseCompoundToken($token)
    {
        $parts = $token[self::T_COMPOUND];
        $sub = $this->createGraph($parts, true);
        $subKey = key($sub);
        $isSingle = count($sub[$subKey]) === 1;
        if ($isSingle) {
            if ($subKey === self::T_AND || $subKey === self::T_OR) {
                return $sub[$subKey][0];
            }
            return [self::T_EXPR => $parts];
        }
        return $sub;
    }

    /**
     * @param array $graph
     * @param array $values
     * @param int $currentKey
     * @return array
     * @throws InvalidCriteriaException
     */
    protected function parseGraph(array $graph = array(), array &$values, &$currentKey)
    {
        $type   = key($graph);
        $parsed = [];
        $joins  = [];
        if ($type === self::T_EXPR) {
            $sub = is_array($graph[$type]) ? key($graph[$type]): $graph[$type];
            $config = $this->getFieldConfig($sub, $this->getRootAlias());
            if ($config['join']) {
                $joins[] = $config['join'];
            }

            $val = null;
            if ($config['requires_value']) {
                if ($config['has_named_param']) {
                    $val = $values[$config['param_name']];
                } else {
                    if (!array_key_exists($currentKey, $values)) {
                        throw new InvalidCriteriaException(sprintf('no value found for expression: %s', $config['expression']));
                    }
                    $val = $values[$currentKey];
                    $currentKey++;
                }
            }

            $parsed[] = [$config['expression'] => $val];
        } else {
            foreach ($graph[$type] as $expr) {
                $sub = $this->parseGraph($expr, $values, $currentKey);
                array_merge($joins, $sub['joins']);
                $subType = key($sub['expressions']);
                if ($type === $subType) {
                    $parsed = array_merge($parsed, $sub['expressions'][$type]);
                } else {
                    if ($subType !== self::T_AND && $subType !== self::T_OR && $subType !== self::T_EXPR) {
                        $parsed[] = [self::T_EXPR => $sub['expressions']];
                    } else {
                        $parsed[] = $sub['expressions'];
                    }
                }
            }
        }

        $parsed = (count($parsed) === 1) ? reset($parsed): [$type => $parsed];

        return [
            'joins'         => $joins,
            'expressions'   => $parsed,
        ];
    }

    /**
     * @param array $joins
     * @return array
     */
    protected function mergeJoinPaths(array $joins)
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
        } elseif ($conditionUpper === 'IS NOT NULL') {
            $comp = 'IS NOT NULL';
        } else {
            foreach (array('LIKE', 'IN', 'NOT IN', '>=', '<=', '<', '>', '=') as $c) {
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
     * @param string $type
     * @return array
     */
    public function resolveCriteria($criteria, $type = self::T_AND)
    {
        if (!is_array($criteria)) {
            $criteria   = array($criteria);
        }

        $joins = [[]];
        $expressions = [];

        foreach ($criteria as $key => $val) {
            $hasValue = is_string($key);
            $expression = $hasValue ? $key: $val;
            $values = $hasValue ? (array)$val: array();
            $currentValKey = 0;

            if (!$hasValue && strpos($expression, ' ') === false) {
                if (strpos($expression, $this->getRootAlias() . '.') !== 0) {
                    $expression = $this->getRootAlias() . '.' . $expression;
                }
                $joins[0][] = explode('.', $expression);
            } else {
                $graph          = $this->createGraph($expression);
                $result         = $this->parseGraph($graph, $values, $currentValKey);
                $joins[]        = $result['joins'];
                $expressions    = $this->mergeExpressions($expressions, $result['expressions'], $type);
            }
        }

        $joins = call_user_func_array('array_merge', $joins);
        $joins = $this->mergeJoinPaths($joins);

        if (count($expressions) === 1) {
            $key = key($expressions[0]);
            if ($key !== self::T_EXPR) {
                $type = $key;
                $expressions = $expressions[0][$key];
            }
        }

        return array(
            'joins' => $joins,
            'expressions' => [$type => $expressions],
        );
    }

    /**
     * @param $field
     * @return array
     */
    public function resolveField($field)
    {
        $result = $this->getFieldConfig($field, $this->getRootAlias());

        return array(
            'joins' => $this->mergeJoinPaths([$result['join']]),
            'field' => $result['field'],
        );
    }

    /**
     * @param array $one
     * @param array $two
     * @param string $merge
     * @return array
     */
    protected function mergeExpressions(array $one, array $two, $merge = null)
    {
        $key = key($two);
        $values = $two[$key];

        if ($key === $merge) {
            return array_merge($one, $values);
        }

        $one[] = $two;
        return $one;
    }
}
