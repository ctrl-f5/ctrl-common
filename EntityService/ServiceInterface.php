<?php

namespace Ctrl\Common\EntityService;

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
     * @param object $entity
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
     * @param object|int $idOrEntity
     * @param bool $failOnNotFound
     * @return $this
     */
    public function remove($idOrEntity, $failOnNotFound = false);

    /**
     * @param object $entity
     * @param bool $flush
     * @return $this
     */
    public function persist($entity, $flush = true);

    /**
     * @return $this
     */
    public function flush();
}
