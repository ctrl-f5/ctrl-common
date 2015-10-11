<?php

namespace EntityService\Finder;

interface PaginatableResultInterface
{
    /**
     * @param int $page
     * @param int|null $pageSize
     * @return mixed
     */
    public function getPaginator($page = 1, $pageSize = null);
}
