<?php

namespace EntityService\Finder;

interface ResultInterface 
{
    /**
     * @param int $offset
     * @return object
     */
    public function getOne($offset = 0);

    /**
     * @param int $offset
     * @return object|null
     */
    public function getOneOrNull($offset = 0);

    /**
     * @param int $offset
     * @return object|null
     */
    public function getFirstOrNull($offset = 0);

    /**
     * @param int $page
     * @param int|null $pageSize
     * @return array|\Iterator
     */
    public function getPage($page = 1, $pageSize = null);
}
