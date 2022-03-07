<?php

namespace Drupal\rest_password\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RestPasswordRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RestPasswordRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('rest.lost_password_resource.POST')) {
      $requirements = $route->getRequirements();
      if (!empty($requirements['_csrf_request_header_token'])) {
        unset($requirements['_csrf_request_header_token']);
        unset($requirements['_permission']);
        $options = $route->getOptions();
        unset($options['_auth']);
        $route->setOptions($options);
        $route->setRequirements($requirements);
      }
    }

    if ($route = $collection->get('rest.lost_password_reset.POST')) {
      $requirements = $route->getRequirements();
      if (!empty($requirements['_csrf_request_header_token'])) {
        unset($requirements['_csrf_request_header_token']);
        unset($requirements['_permission']);
        $options = $route->getOptions();
        unset($options['_auth']);
        $route->setOptions($options);
        $route->setRequirements($requirements);
      }
    }

    // Need to alter user auth a bit.
    if ($route = $collection->get('user.login.http')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\rest_password\Controller\UserAuthenticationTempPassController::login';
      $route->setDefaults($defaults);
    }
  }

}
