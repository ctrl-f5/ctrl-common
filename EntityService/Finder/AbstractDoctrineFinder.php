<?php

namespace Ctrl\Common\EntityService\Finder;

use Ctrl\Common\Criteria\DoctrineResolver;
use Ctrl\Common\Tools\Doctrine\Paginator;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class AbstractDoctrineFinder extends AbstractFinder
{
    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var DoctrineResolver
     */
    protected $criteriaResolver;

    public function __construct(EntityRepository $repository, $rootAlias)
    {
        $this->repository = $repository;
        $this->rootAlias = $rootAlias;
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
     */
    public function getEntityRepository()
    {
        if (!$this->repository) {
            throw new \RuntimeException('no entity repository set');
        }

        return $this->repository;
    }

    /**
     * @return DoctrineResolver
     */
    protected function getCriteriaResolver()
    {
        if (!$this->criteriaResolver) {
            $this->criteriaResolver = new DoctrineResolver($this->getRootAlias());
        }
        return $this->criteriaResolver;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return array|Paginator
     */
    protected function assertFinderResult($queryBuilder)
    {
        $config = $this->getResultTypeConfig();

        switch ($config['type']) {
            case 'one_or_null':
                return $queryBuilder->getQuery()->getOneOrNullResult();
            case 'first_or_null':
                return $queryBuilder->getQuery()->setMaxResults(1)->getOneOrNullResult();
            case 'paginator':
                return $this->getPaginator($queryBuilder, $config['page'], $config['pageSize']);
            default:
                return $queryBuilder->getQuery()->getResult();
        }
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
            ->andWhere($this->getRootAlias() . ".id = :id")
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
     * @return object[]|Paginator
     */
    public function find(array $criteria = array(), array $orderBy = array())
    {
        $queryBuilder = $this->getBaseQueryBuilder();
        $this->getCriteriaResolver()
            ->applyCriteria($queryBuilder, $criteria)
            ->applyOrderBy($queryBuilder, $orderBy);

        return $this->assertFinderResult($queryBuilder);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return Paginator
     */
    protected function getPaginator(QueryBuilder $queryBuilder, $page = 1, $pageSize = 15, array $orderBy = array())
    {
        $this->criteriaResolver->applyOrderBy($queryBuilder, $orderBy);

        $paginator = new Paginator($queryBuilder);
        $paginator->configure($page, $pageSize);

        return $paginator;
    }
}