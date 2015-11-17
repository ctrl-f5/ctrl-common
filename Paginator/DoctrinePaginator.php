<?php

namespace Ctrl\Common\Paginator;

use \Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrinePaginator extends Paginator implements PaginatedDataInterface
{
    /**
     * @var int
     */
    protected $pageSize = 15;

    /**
     * @var int
     */
    protected $pageCount = 1;

    /**
     * @var int
     */
    protected $currentPage = 1;

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setPageSize($size = 15)
    {
        $this->pageSize = $size;
        $this->assertPageConfig();
        return $this;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return $this
     */
    public function configure($page, $pageSize)
    {
        $this->currentPage = $page;
        $this->pageSize = $pageSize;

        $this->assertPageConfig();

        return $this;
    }

    /**
     * @param array $request
     */
    public function configureFromRequestParams($request)
    {
        $page = 1;
        $pageSize = $this->pageSize;

        if (isset($request['pager']) && is_array($request['pager'])) {
            if (isset($request['pager']['page'])) $page = $request['pager']['page'];
            if (isset($request['pager']['pageSize'])) $pageSize = $request['pager']['pageSize'];
        }

        $this->currentPage = (int)$page;
        $this->pageSize = (int)$pageSize;

        $this->assertPageConfig();
    }

    public function getRequestParams($page = 1)
    {
        return array(
            'pager' => array(
                'page' => $page,
                'pageSize' => $this->pageSize,
            )
        );
    }

    protected function assertPageConfig()
    {
        $this->pageCount = ceil($this->count() / $this->pageSize);
        if ($this->currentPage > $this->pageCount) $this->currentPage = $this->pageCount;
        if ($this->currentPage < 1) $this->currentPage = 1;

        $this->getQuery()
            ->setFirstResult(($this->currentPage * $this->pageSize) - ($this->pageSize))
            ->setMaxResults($this->pageSize);
    }
}
