<?php

namespace Ctrl\Common\Criteria\Adapter;

use Ctrl\Common\Criteria\ResolverInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class DoctrineAdapter extends AbstractAdapter
{
    /**
     * @return string
     */
    public function getRootAlias()
    {
        return $this->resolver->getRootAlias();
    }

    /**
     * @param string $rootAlias
     * @return $this
     */
    public function setRootAlias($rootAlias)
    {
        $this->resolver->setRootAlias($rootAlias);
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

        $criteria = $this->resolver->resolve($criteria);

        foreach ($criteria['joins'] as $join => $alias) {
            $queryBuilder->join($join, $alias);
        }

        $queryBuilder->where($this->createQueryExpression($queryBuilder, $criteria['expressions']));

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $graph
     * @return Expr\Andx|Expr\Orx|null
     */
    public function createQueryExpression(QueryBuilder $qb, array $graph = array())
    {
        $key        = key($graph);
        $expr       = null;

        if ($key === ResolverInterface::T_EXPR) {

            $spec       = key($graph[$key]);
            $value      = $graph[$key][$spec];
            $paramIndex = count($qb->getParameters()) + 1;
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
                        $qb->setParameter($paramIndex, $value);
                        return $qb->expr()->$func($field, '?' . $paramIndex);
                    } else if (strpos($valueSpec, ':') === 0) {
                        $qb->setParameter(substr($valueSpec, 1), $value);
                        return $qb->expr()->$func($field, $valueSpec);
                    } else if ($value === null) {
                        return $field . ' ' . $comp . ' ' . $valueSpec;
                    }
                    throw new \InvalidArgumentException('invalid field spec: ' . $spec);
            }
            return $qb->expr()->$func($field);

        } else {
            if ($key === ResolverInterface::T_AND) {
                $expr = $qb->expr()->andX();
            } else if ($key === ResolverInterface::T_OR) {
                $expr = $qb->expr()->orX();
            } else {
                throw new \InvalidArgumentException('invalid graph given');
            }

            foreach ($graph[$key] as $sub) {
                $expr->add($this->createQueryExpression($qb, $sub));
            }
            return $expr;
        }
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
}
