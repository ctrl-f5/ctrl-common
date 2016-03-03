<?php

namespace Ctrl\Common\Criteria\Adapter;

use Ctrl\Common\Criteria\InvalidCriteriaException;
use Ctrl\Common\Criteria\ResolverInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class DoctrineAdapter extends AbstractResolverAdapter
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

        $criteria = $this->resolver->resolveCriteria($criteria);

        foreach ($criteria['joins'] as $join => $alias) {
            $queryBuilder->leftJoin($join, $alias);
        }

        $queryBuilder->where($this->createQueryExpression($queryBuilder, $criteria['expressions']));

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $graph
     * @return Expr\Andx|Expr\Orx|null
     * @throws InvalidCriteriaException
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
                            throw new InvalidCriteriaException(sprintf('unknown comparison: %s', $comp));
                    }
                    if ($valueSpec === '?') {
                        $qb->setParameter($paramIndex, $value);
                        return $qb->expr()->$func($field, '?' . $paramIndex);
                    } elseif (strpos($valueSpec, ':') === 0) {
                        $qb->setParameter(substr($valueSpec, 1), $value);
                        return $qb->expr()->$func($field, $valueSpec);
                    } elseif ($value === null) {
                        return $field . ' ' . $comp . ' ' . $valueSpec;
                    }
                    throw new InvalidCriteriaException('invalid field spec: ' . $spec);
            }
            return $qb->expr()->$func($field);

        } else {
            if ($key === ResolverInterface::T_AND) {
                $expr = $qb->expr()->andX();
            } elseif ($key === ResolverInterface::T_OR) {
                $expr = $qb->expr()->orX();
            } else {
                throw new InvalidCriteriaException('invalid graph given');
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
        foreach ($orderBy as $field => $dir) {
            if (is_int($field)) {
                $field = $dir;
                $dir = 'ASC';
            }
            $result = $this->resolver->resolveField($field);
            foreach ($result['joins'] as $join => $alias) {
                $queryBuilder->join($join, $alias);
            }
            $queryBuilder->addOrderBy($result['field'], $dir);
        }

        return $this;
    }
}
