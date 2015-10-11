<?php

namespace Ctrl\Common\EntityService;

interface ServiceProviderInterface
{
    /**
     * @return ServiceInterface
     */
    public function getEntityService();
}
