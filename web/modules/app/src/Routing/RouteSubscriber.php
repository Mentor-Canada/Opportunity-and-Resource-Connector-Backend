<?php

namespace Drupal\app\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase
{
    protected function alterRoutes(RouteCollection $collection)
    {
        if ($route = $collection->get('jsonapi.node--application.collection')) {
            $route->setRequirement('_role', 'authenticated');
        }
        if ($route = $collection->get('jsonapi.user--user.collection')) {
            $route->setRequirement('_role', 'authenticated');
        }
    }
}
