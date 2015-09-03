<?php

namespace Ctrl\Common\EntityService;

use Ctrl\Common\EntityService\Finder\FinderInterface;

interface ServiceInterface
{
    /**
     * @return string
     */
    function getEntityClass();

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
