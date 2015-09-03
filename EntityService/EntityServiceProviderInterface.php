<?php

namespace Ctrl\Common\EntityService;

interface EntityServiceProviderInterface
{
    /**
     * @return ServiceInterface
     */
    public function getEntityService();
}
