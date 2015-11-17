<?php

namespace Ctrl\Common\Paginator;

class DummyPaginator implements PaginatedDataInterface
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
     * @var array
     */
    protected $data = array();

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

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
     * @param int $pageCount
     * @return $this
     */
    public function setPageCount($pageCount)
    {
        $this->pageCount = $pageCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
        return $this;
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

        return $this;
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

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }
}
