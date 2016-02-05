<?php

namespace Ctrl\Common\EntityService;

use Ctrl\Common\Entity\EntityInterface;
use Ctrl\Common\EntityService\Finder\FinderInterface;
use Ctrl\Common\EntityService\Finder\ResultInterface;

interface ServiceInterface
{
    /**
     * @return string
     */
    public function getEntityClass();

    /**
     * @return string
     */
    public function getRootAlias();

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function assertEntityInstance($entity);

    /**
     * @return FinderInterface
     */
    public function getFinder();

    /**
     * @param array $criteria
     * @param array $orderBy
     * @return ResultInterface
     */
    public function find(array $criteria = array(), array $orderBy = array());

    /**
     * @param int $id
     * @return EntityInterface
     */
    public function findOneById($id);

    /**
     * @param EntityInterface|int $idOrEntity
     * @param bool $failOnNotFound
     * @return $this
     */
    public function remove($idOrEntity, $failOnNotFound = false);

    /**
     * @param EntityInterface $entity
     * @param bool $flush
     * @return $this
     */
    public function persist($entity, $flush = true);

    /**
     * @return $this
     */
    public function flush();
}
