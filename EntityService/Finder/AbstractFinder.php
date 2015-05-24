<?php

namespace Ctrl\Common\EntityService\Finder;

use Ctrl\Common\Criteria\ResolverInterface;
use Ctrl\Common\Tools\Doctrine\Paginator;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractFinder implements FinderInterface
{
    /**
     * @var string
     */
    protected $rootAlias;

    /**
     * @var ResolverInterface
     */
    protected $criteriaResolver;

    /**
     * @var string|null
     */
    protected $resultType = null;

    /**
     * @var int
     */
    protected $resultPage = 1;

    /**
     * @var int
     */
    protected $resultPageSize = 15;

    /**
     * @var int
     */
    protected $resultOffset = 0;

    /**
     * @var int
     */
    protected $resultLimit = null;

    /**
     * @return string
     */
    public function getRootAlias()
    {
        return $this->rootAlias;
    }

    /**
     * @param bool $reset
     * @return array
     */
    protected function getResultTypeConfig($reset = true)
    {
        $config = array(
            'type' => $this->resultType,
            'page' => $this->resultPage,
            'pageSize' => $this->resultPageSize,
            'offset' => $this->resultOffset,
            'limit' => $this->resultLimit,
        );

        if ($reset) $this->resetResultTypeConfig();

        return $config;
    }

    /**
     * @return $this
     */
    protected function resetResultTypeConfig()
    {
        $this->resultType = null;
        $this->resultPage = 1;
        $this->resultPageSize = 15;
        $this->resultOffset = 0;
        $this->resultLimit = null;

        return $this;
    }

    /**
     * @param mixed $subject
     * @return array|Paginator
     */
    abstract protected function assertFinderResult($subject);

    /**
     * @param bool $paginate
     * @param int $offset only valid if $paginate is true
     * @return $this
     */
    public function oneOrNull($paginate = false, $offset = 0)
    {
        $this->resetResultTypeConfig();

        $this->resultType = $paginate ? 'paginator': 'one_or_null';
        $this->resultOffset = $offset;
        $this->resultLimit = 1;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function firstOrNull($offset = 0)
    {
        $this->resetResultTypeConfig();

        $this->resultType = 'first_or_null';
        $this->resultOffset = $offset;
        $this->resultLimit = 1;

        return $this;
    }

    /**
     * @param int $page
     * @param int|null $pageSize
     * @return $this
     */
    public function paginate($page = 1, $pageSize = null)
    {
        $this->resetResultTypeConfig();

        $this->resultType = 'paginator';
        $this->resultPage = $page;
        if ($pageSize) $this->resultPageSize = $pageSize;

        return $this;
    }
}