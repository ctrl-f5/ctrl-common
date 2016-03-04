<?php

namespace Ctrl\Common\Traits;

use Symfony\Component\HttpFoundation\Request;

trait SymfonyPaginationRequestReader
{
    protected function getPaginationData(Request $request)
    {
        $pagerData = $request->query->get('pager');
        return array(
            'page' => isset($pagerData['page']) ? $pagerData['page']: 1,
            'pageSize' => isset($pagerData['pageSize']) ? $pagerData['pageSize']: 15
        );
    }
}
