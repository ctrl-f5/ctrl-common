<?php

namespace Ctrl\Common\EntityService\Finder\Doctrine;

use Ctrl\Common\Tools\Doctrine\Paginator;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Ctrl\Common\EntityService\Finder\PaginatableResultInterface;
use Ctrl\Common\EntityService\Finder\Doctrine\QueryBuilderResultInterface;
use Ctrl\Common\EntityService\Finder\ResultInterface;

class Result implements ResultInterface, PaginatableResultInterface, QueryBuilderResultInterface
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param int $offset
     * @return object
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function getOne($offset = 0)
    {
        $result = $this->queryBuilder->getQuery()->getOneOrNullResult();
        if ($result === null) {
            throw new EntityNotFoundException;
        }

        return $result;
    }

    /**
     * @param int $offset
     * @return object|null
     * @throws NonUniqueResultException
     */
    public function getOneOrNull($offset = 0)
    {
        return $this->queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $offset
     * @return object|null
     */
    public function getFirstOrNull($offset = 0)
    {
        try {
            return $this->queryBuilder->getQuery()->setMaxResults(1)->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $page
     * @param int|null $pageSize
     * @return \Iterator
     */
    public function getPage($page = 1, $pageSize = 15)
    {
        return $this->getPaginator()->getIterator();
    }

    /**
     * @param int $page
     * @param int|null $pageSize
     * @return Paginator
     */
    public function getPaginator($page = 1, $pageSize = 15)
    {
        $paginator = new Paginator($this->queryBuilder);
        $paginator->configure($page, $pageSize);

        return $paginator;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
}
