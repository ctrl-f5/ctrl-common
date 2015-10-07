<?php

namespace Ctrl\Common\Criteria;

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
        $criteria = $this->unpack($criteria);

        foreach ($criteria['joins']         as $join => $alias)     $queryBuilder->join($join, $alias);
        foreach ($criteria['conditions']    as $where)              $queryBuilder->andWhere($where);
        foreach ($criteria['parameters']    as $key => $value)      $queryBuilder->setParameter($key, $value);

        return $this;
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

            $config = $this->getFieldConfig($field, false);
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

    public function createGraph($expression)
    {
        $result = array();
        $expression = trim($expression);
        $len = strlen($expression);

        // search open
        $open = strpos($expression, '(');
        // no open? add offset > last
        if ($open === false) {
            $result[] = $expression;
            return $result;
        }

        // open > offset? add offset => open
        if ($open > 0) {
            $result[] = trim(substr($expression, 0, $open));
        }

        // search close
        $count = 0;
        $close = $open;
        for ($i = $open; $i < $len; $i++) {
            if ($expression[$i] === '(') {
                $count++;
            }
            if ($expression[$i] === ')') {
                $count--;
                if ($count === 0) {
                    $close = $i;
                    break;
                }
            }
        }

        // add open to close
        $subExpr = $this->createGraph(substr($expression, $open + 1, $close - $open - 1));
        if (count($subExpr) > 1) {
            $subExpr = array($subExpr);
        }
        $result = array_merge($result, $subExpr);

        // close is last? ready
        if ($close === $len - 1) {
            return $result;
        }

        // no next open? add close => last
        $result = array_merge($result, $this->createGraph(substr($expression, $close + 1)));

        return $result;
    }

    public function tokenize($expressions)
    {
        $tokens = array();

        foreach ($expressions as $expr) {
            if (is_array($expr)) {
                $tokens[][self::T_COMPOUND] = $this->tokenize($expr);
            }

            $expr = trim(strtolower($expr));
            if (1 === preg_match('/( and )|( and$)|(^and )|(^and$)|( or )|( or$)|(^or )|(^or$)/', $expr)) {
                $words = explode(' ', $expr);
                $subExpr = [];

                foreach ($words as $w) {
                    if ($w === 'and' || $w === 'or') {
                        if (count($subExpr)) {
                            $tokens[][self::T_EXPR] = implode(' ', $subExpr);
                            $subExpr = [];
                        }
                        if ($w === 'and') {
                            $tokens[][self::T_AND] = 'and';
                        }
                        else if ($w === 'or') {
                            $tokens[][self::T_OR] = 'or';
                        }
                    } else {
                        $subExpr[] = $w;
                    }
                }
                if (count($subExpr)) {
                    $tokens[][self::T_EXPR] = implode(' ', $subExpr);
                }

            } else {
                $tokens[][self::T_EXPR] = $expr;
            }
        }

        return $tokens;
    }

    /**
     * @param array|string $criteria
     * @return array
     */
    public function unpack($criteria)
    {
        if (!is_array($criteria)) {
            $criteria = array($criteria);
        }

        $joins = array();
        $conditions = array();
        $parameters = array();
        $paramCount = 1;
        $paramAddedCount = 1;

        foreach ($criteria as $key => $val) {
            $hasValue = is_string($key);
            $expression = $hasValue ? $key: $val;
            $values = (array)$val;

            if (!$hasValue && strpos($expression, ' ') === false && strpos($expression, '=') === false) {
                $joins[] = $expression;
            } else {
                $graph = $this->createGraph($expression);
                $conditions[] = $this->parseGraph($graph, $values);
            }

        }

        $joins = $this->mergeJoinPaths($joins);

        return array(
            'joins' => $joins,
            'conditions' => $conditions,
            'parameters' => $parameters,
        );
    }

    protected function parseGraph(array $graph = array(), array &$values)
    {
        $expr = [];
        foreach ($graph as $sub) {
            if (is_array($sub)) {
                $expr[] = '(' . $this->parseGraph($sub, $values) . ')';
            } else {
                $tokens = $this->tokenize(array($sub));
                foreach ($tokens as $token) {
                    foreach ($token as $type => $value) {
                        if ($type === self::T_AND || $type === self::T_OR) {
                            $expr[] = $value;
                        } else {
                            $config = $this->getFieldConfig($value, $this->getRootAlias());
                            $field = $config['field'];

                            $expr[] = $field;
                        }
                    }
                }
            }
        }
        //var_dump($expr);
        return $expr;
    }

    /**
     * @param array|string $criteria
     * @return array
     */
    public function unpack_old($criteria)
    {
        $joins = array();
        $conditions = array();
        $parameters = array();
        $paramCount = 1;
        $paramAddedCount = 1;

        foreach ((array)$criteria as $key => $val) {
            $hasValue = is_string($key);
            $fieldConfig = $hasValue ? $key: $val;
            $val = (array)$val;

            $expressions = $this->unpackFieldExpression($fieldConfig, $this->getRootAlias());
            foreach ($expressions as $exr) {
                $config = $this->getFieldConfig($exr, $hasValue, $paramCount);
                $path = $config['path'];

                // add conditions and parameters
                if ($config['is_property']) {
                    $conditions[] = $config['field'];
                    if ($config['requires_value']) {
                        if ($config['has_named_param']) {
                            $parameters[$config['param_name']] = count($val) === 1 ? array_shift($val): $val[$config['param_name']];
                        } else {
                            $parameters[$paramAddedCount] = array_shift($val);
                            $paramAddedCount++;
                        }
                    }
                    array_pop($path);
                }

                // add joins
                $joins[] = $path;
            }
        }

        $joins = $this->mergeJoinPaths($joins);

        return array(
            'joins' => $joins,
            'conditions' => $conditions,
            'parameters' => $parameters,
        );
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

    protected function unpackFieldExpression($expr, $rootAlias)
    {
        $result = array();
        $expr = str_replace(
            array(' AND ', ' OR '),
            array(' and ', ' or '),
            trim(trim($expr), '()')
        );

        $parts = explode(' and ', $expr);
        if (count($parts) > 1) {
            foreach ($parts as $part) {
                $fields = $this->unpackFieldExpression(trim($part), $rootAlias);
                $result[] = $fields[0];
            }
            return $result;
        }

        for ($i = 0; $i < count($parts); $i++) {
            if (strpos($parts[$i], $rootAlias . '.') !== 0) $parts[$i] = $rootAlias . '.' . $parts[$i];
        }

        return $parts;
    }

    /**
     * @param string $expr
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
        $parent = count($path) > 2 ? $path[count($path) - 2]: $path[0];
        $alias = end($path);

        $hasNamedParam = strpos($expr, ' :') !== false;
        $paramName = $hasNamedParam ? trim(end($parts), ':'): null;

        $comp = '=';
        $requiresValue = false;
        $valueSpec = null;

        if ($condition === 'is null') {
            $comp = 'IS NULL';
        } else if ($condition === 'is not null') {
            $comp = 'IS NOT NULL';
        } else {
            foreach (array('IN', '>=', '<=', '<', '>', '=') as $c) {
                if (strpos($condition, $c) === 0) {
                    $comp = $c;
                    $requiresValue = true;
                    $valueSpec = trim(substr($condition, strlen($c)));
                    break;
                }
            }
        }
        if ($valueSpec !== null && strpos($valueSpec, ':') !== 0 && strpos($valueSpec, '?') !== 0) {
            $valueSpec = ' ' . $valueSpec;
        } else {
            $valueSpec = '';
        }

        return array(
            'comparison' => $comp,
            'alias' => $alias,
            'parent' => $parent,
            'path' => $path,
            'requires_value' => $requiresValue,
            'has_named_param' => $hasNamedParam,
            'param_name' => $paramName,
            'field' => $parent . '.' . $alias . ' ' . $comp . $valueSpec,
        );
    }

    /**
     * @param string $expr
     * @param bool $hasValue
     * @param int &$paramCount
     * @return array
     */
    protected function getFieldConfig_old($expr, $hasValue, &$paramCount = 0)
    {
        $parts = explode(' ', trim($expr, " ()"));
        $fieldPart = array_shift($parts);
        $path = explode('.', $fieldPart);
        $parent = count($path) > 2 ? $path[count($path) - 2]: $path[0];
        $alias = end($path);
        $isProp = $hasValue || count($parts) > 1;

        $hasNamedParam = strpos($expr, ' :') !== false;
        $paramName = $hasNamedParam ? trim(end($parts), ':'): null;

        $comp = '=';
        $condition = '';
        $requiresValue = true;
        if ($isProp) {
            if (count($parts)) {
                $condition = implode(' ', $parts);
                $requiresValue = $hasNamedParam;
            } else {
                $condition = $comp . ' ?' . $paramCount;
                $paramCount++;
            }
        } else {
            $requiresValue = false;
        }

        return array(
            'is_property' => $isProp,
            'comparison' => $comp,
            'alias' => $alias,
            'parent' => $parent,
            'path' => $path,
            'requires_value' => $requiresValue,
            'has_named_param' => $hasNamedParam,
            'param_name' => $paramName,
            'field' => $parent . '.' . $alias . ' ' . $condition,
        );
    }
}
