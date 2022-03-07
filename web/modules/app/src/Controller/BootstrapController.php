<?php

namespace Drupal\app\Controller;

use Drupal\app\Factories\UserFactory;
use Drupal\app_organization\OrganizationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;

class BootstrapController extends BaseController
{
    public function get()
    {
        return new JsonResponse(['data' => [
            'version' => $_ENV['VERSION'],
            'country' => $_ENV['COUNTRY'],
            'bbbsc' => $_ENV['BBBSC'] == 'true',
            'userHasOrganizations' => OrganizationUtils::userHasOrganizations(UserFactory::currentUser())
        ]]);
    }
}
