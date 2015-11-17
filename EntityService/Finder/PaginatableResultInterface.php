<?php

namespace Ctrl\Common\EntityService\Finder;

use Ctrl\Common\Paginator\DoctrinePaginator;

interface PaginatableResultInterface
{
    /**
     * @param int $page
     * @param int|null $pageSize
     * @return DoctrinePaginator
     */
    public function getPaginator($page = 1, $pageSize = 15);
}
