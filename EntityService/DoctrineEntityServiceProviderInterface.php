<?php

namespace Ctrl\Common\EntityService;

interface DoctrineEntityServiceProviderInterface extends EntityServiceProviderInterface
{
    /**
     * @return AbstractDoctrineService
     */
    public function getEntityService();
}
