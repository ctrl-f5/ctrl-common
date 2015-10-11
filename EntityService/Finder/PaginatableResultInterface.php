<?php

namespace Ctrl\Common\EntityService\Finder;

use Ctrl\Common\Tools\Doctrine\Paginator;

interface PaginatableResultInterface
{
    /**
     * @param int $page
     * @param int|null $pageSize
     * @return Paginator
     */
    public function getPaginator($page = 1, $pageSize = 15);
}
