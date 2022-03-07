<?php

namespace Drupal\app;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi_resources\Resource\EntityQueryResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

abstract class CollectionResourceBase extends EntityQueryResourceBase
{
    public function getResponse($q, Request $request)
    {
        $qCount = clone $q;
        $total = $qCount->count()->execute();

        $cacheability = new CacheableMetadata();
        $cacheability->setCacheMaxAge(0);

        $paginator = $this->getPaginatorForRequest($request);
        $paginator->applyToQuery($q, $cacheability);

        $data = $this->loadResourceObjectDataFromEntityQuery($q, $cacheability);

        $pagination_links = $paginator->getPaginationLinks($q, $cacheability);

        return $this->createJsonapiResponse($data, $request, 200, [], $pagination_links, [
            'total' => $total
        ]);
    }

    public function getRouteResourceTypes(Route $route, string $route_name): array
    {
        return $this->getResourceTypesByEntityTypeId('node');
    }
}
