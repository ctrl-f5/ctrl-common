<?php

namespace Ctrl\Common\EntityService\Finder;

use Ctrl\Common\EntityService\Finder\PaginatableResultInterface;
use Ctrl\Common\EntityService\Finder\ResultInterface;

interface FinderInterface
{
    /**
     * @return string
     */
    public function getRootAlias();

    /**
     * Find all entities, filtered and ordered
     * Result is determined by the chosen result type
     *
     * @pagination
     * @param array $criteria
     * @param array $orderBy
     * @return ResultInterface|PaginatableResultInterface
     */
    public function find(array $criteria = array(), array $orderBy = array());

    /**
     * Fetches an entity based on id,
     * fails if entity is not found
     *
     * @param int $id
     * @return object
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get($id);

    /**
     * Fetches an entity based on criteria,
     * fails if entity is not found or if criteria can select multiple entities
     *
     * @param array $criteria
     * @param array $orderBy
     * @return object
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getBy(array $criteria = array(), array $orderBy = array());
}
