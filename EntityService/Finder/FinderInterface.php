<?php

namespace Ctrl\Common\EntityService\Finder;

use Ctrl\Common\Tools\Doctrine\Paginator;

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
     * @return array|object[]|Paginator
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
     * Fetches one entity based on criteria
     * criteria must result in only 1 possible entity being selected
     *
     * @param array $criteria
     * @param array $orderBy
     * @return object[]
     */
    public function getBy(array $criteria = array(), array $orderBy = array());

    /**
     * @param bool $paginate
     * @param int $offset only valid if $paginate is true
     * @return $this
     */
    public function oneOrNull($paginate = false, $offset = 0);

    /**
     * @param int $offset
     * @return $this
     */
    public function firstOrNull($offset = 0);

    /**
     * @param int $page
     * @param int|null $pageSize
     * @return $this
     */
    public function paginate($page = 1, $pageSize = null);
}
