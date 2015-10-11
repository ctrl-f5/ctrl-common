<?php

namespace Ctrl\Common\EntityService\Finder\Doctrine;

use Ctrl\Common\Criteria\Adapter\ResolverAdapterInterface;
use Ctrl\Common\Criteria\Adapter\DoctrineAdapter;
use Ctrl\Common\Criteria\Resolver;
use Ctrl\Common\EntityService\Finder\FinderInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Ctrl\Common\EntityService\Finder\Doctrine\Result;

class Finder implements FinderInterface
{
    /**
     * @var string
     */
    private $rootAlias;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var ResolverAdapterInterface
     */
    private $criteriaResolver;

    public function __construct(EntityRepository $repository, $rootAlias)
    {
        $this->repository = $repository;
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
     * @param EntityRepository $repository
     * @return $this
     */
    public function setEntityRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return EntityRepository
     * @throws \RuntimeException
     */
    public function getEntityRepository()
    {
        if (!$this->repository) {
            throw new \RuntimeException('no entity repository set');
        }

        return $this->repository;
    }

    /**
     * @return ResolverAdapterInterface
     */
    protected function getCriteriaResolver()
    {
        if (!$this->criteriaResolver) {
            $this->criteriaResolver = new DoctrineAdapter(
                new Resolver($this->getRootAlias())
            );
        }
        return $this->criteriaResolver;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getBaseQueryBuilder()
    {
        $rootAlias = $this->getRootAlias();
        $queryBuilder = $this->getEntityRepository()->createQueryBuilder($rootAlias);

        return $queryBuilder;
    }

    /**
     * Fetches an entity based on id,
     * fails if entity is not found
     *
     * @param int $id
     * @return object
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get($id)
    {
        $queryBuilder = $this->getBaseQueryBuilder()
            ->andWhere($this->getRootAlias() . '.id = :id')
            ->setParameter('id', $id);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * Fetches one entity based on criteria
     * criteria must result in only 1 possible entity being selected
     *
     * @param array $criteria
     * @param array $orderBy
     * @return object[]
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getBy(array $criteria = array(), array $orderBy = array())
    {
        $queryBuilder = $this->getBaseQueryBuilder();
        $this->getCriteriaResolver()
            ->applyCriteria($queryBuilder, $criteria)
            ->applyOrderBy($queryBuilder, $orderBy);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * Find all entities, filtered and ordered
     *
     * @pagination
     * @param array $criteria
     * @param array $orderBy
     * @return Result
     */
    public function find(array $criteria = array(), array $orderBy = array())
    {
        $queryBuilder = $this->getBaseQueryBuilder();
        $this->getCriteriaResolver()
            ->applyCriteria($queryBuilder, $criteria)
            ->applyOrderBy($queryBuilder, $orderBy);

        return new Result($queryBuilder);
    }
}
