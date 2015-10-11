<?php

namespace Ctrl\Common\EntityService;

use Ctrl\Common\Criteria\DoctrineResolver;
use Ctrl\Common\EntityService\Finder\AbstractFinder;
use Ctrl\Common\EntityService\Finder\Doctrine\Finder;
use Ctrl\Common\EntityService\Finder\FinderInterface;
use Ctrl\Common\Tools\Doctrine\Paginator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractDoctrineService implements ServiceInterface
{
    /**
     * @var ObjectManager|EntityManager
     */
    private $doctrine;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var string
     */
    protected $rootAlias;

    /**
     * @var FinderInterface
     */
    protected $finder;

    /**
     * @return string
     */
    abstract public function getEntityClass();

    /**
     * @return string
     */
    public function getRootAlias()
    {
        if (!$this->rootAlias) {
            $arr = explode('\\', $this->getEntityClass());
            $this->rootAlias = lcfirst(end($arr));
        }

        return $this->rootAlias;
    }

    /**
     * @param ObjectManager|EntityManager $doctrine
     * @return $this
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
        return $this;
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        if (!$this->repository) {
            if (!$this->doctrine) {
                throw new \RuntimeException('doctrine not set');
            }
            $this->repository = $this->doctrine->getRepository($this->getEntityClass());
        }

        return $this->repository;
    }

    /**
     * @return FinderInterface
     */
    public function getFinder()
    {
        if (!$this->finder) {
            $this->finder = new Finder(
                $this->getEntityRepository(),
                $this->getRootAlias()
            );
        }

        return $this->finder;
    }

    /**
     * @param object $entity
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function assertEntityInstance($entity)
    {
        $class = $this->getEntityClass();
        if (!(is_object($entity) && $entity instanceof $class)) {
            throw new \InvalidArgumentException(
                sprintf('Service can only handle entities of class %s', $class)
            );
        }
    }

    /**
     * @param object|int $idOrEntity
     * @param bool $failOnNotFound
     * @return $this
     * @throws EntityNotFoundException
     */
    public function remove($idOrEntity, $failOnNotFound = false)
    {
        $entity = (is_object($idOrEntity)) ?
            $idOrEntity:
            $this->getFinder()->get($idOrEntity);

        if (!$entity && $failOnNotFound) {
            throw new EntityNotFoundException(sprintf(
                "Entity of type %s with id %s could not be found",
                $this->getEntityClass(),
                is_object($idOrEntity) ? $idOrEntity->getId(): $idOrEntity
            ));
        }

        $this->assertEntityInstance($entity);

        $this->doctrine->remove($entity);
        $this->doctrine->flush();

        return $this;
    }

    /**
     * @param object $entity
     * @param bool $flush
     * @return $this
     */
    public function persist($entity, $flush = true)
    {
        $this->assertEntityInstance($entity);

        $this->doctrine->persist($entity);
        if ($flush) $this->flush();

        return $this;
    }

    /**
     * @return $this
     */
    public function flush()
    {
        $this->doctrine->flush();

        return $this;
    }
}
