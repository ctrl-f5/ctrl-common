<?php

namespace Ctrl\Common\EntityService\Finder;

use EntityService\Finder\ResultInterface;

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
     * @return ResultInterface
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
}
