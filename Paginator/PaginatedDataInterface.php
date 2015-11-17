<?php

namespace Ctrl\Common\Paginator;

interface PaginatedDataInterface extends \Countable, \IteratorAggregate
{
    /**
     * @return int
     */
    public function getCurrentPage();

    /**
     * @return int
     */
    public function getPageSize();

    /**
     * @return int
     */
    public function getPageCount();

    /**
     * @param int $page
     * @return array
     */
    public function getRequestParams($page = 1);
}
