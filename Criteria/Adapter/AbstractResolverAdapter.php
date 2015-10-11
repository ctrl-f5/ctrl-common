<?php

namespace Ctrl\Common\Criteria\Adapter;

use Ctrl\Common\Criteria\ResolverInterface;
use Doctrine\ORM\Query\Expr;

abstract class AbstractResolverAdapter implements ResolverAdapterInterface
{
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }
}
